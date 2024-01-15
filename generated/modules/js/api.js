class ApiCallback {
	callback;

	constructor(callback) {
		this.callback = callback;
	}

	call(result, data, address) {
		if (result === null || data === null || address === null) {
			console.trace("MISSING PARAMETERS ON API CALLBACK CALL");
			console.log("Result: ", result, "\nData: ", data, "\nAddress:", address);
		}
		this.callback(result, data, address);
	}
}

class ApiTask {
	method;
	parameters;

	constructor(api, method, ...parameters) {
		this.api = api;

		this.method = method;

		this.parameters = parameters;
	}

	call() {
		this.api[this.method](...this.parameters);
	}
}

class ApiConnector {
	apiName;
	apiManager;
	constructor(apiName, apiManager) {
		this.apiName = apiName;
		this.apiManager = apiManager;
		// this.address = base_url.replace("://", "://api.") + "/" + apiName + "/"; CORS error :I
		// this.address = base_url + "/php/api/" + apiName + ".php"; UGLY URL :l
		this.address = base_url + "/api/" + apiName + "/";
	}

	async get(data, callback = this.apiManager.defaultResponseHandler, userErrorCallback = this.apiManager.errorHandlers.user_error) {
		return await this.fetch("get", data, false, callback, userErrorCallback);
	}

	async post(data, callback = this.apiManager.defaultResponseHandler, userErrorCallback = this.apiManager.errorHandlers.user_error) {
		return await this.fetch("post", data, false, callback, userErrorCallback);
	}

	async nonBlockingGet(data, callback = this.apiManager.defaultResponseHandler, userErrorCallback = this.apiManager.errorHandlers.user_error) {
		return await this.fetch("get", data, true, callback, userErrorCallback);
	}

	async nonBlockingPost(data, callback = this.apiManager.defaultResponseHandler, userErrorCallback = this.apiManager.errorHandlers.user_error) {
		return await this.fetch("post", data, true, callback, userErrorCallback);
	}

	async fetch(method, data, isNonBlocking = false, callback = null, userErrorCallback = null) {
		const API_MANAGER = this.apiManager;
		const address = this.address;

		if (API_MANAGER.apiBusy) {
			API_MANAGER.errorHandlers.notice.call("Tried to send a request but the API is already busy", data, this.address);
			return;
		}

		if (!isNonBlocking) API_MANAGER.busy();

		let request = {
			type: method,
			url: address,
			data: data,
		};

		if (method == "post") request.cache = "no-cache";

		return new Promise((resolve, reject) => {
			request.success = (result) => {
				if (hasJsonStructure(result)) {
					if (!isNonBlocking) API_MANAGER.free();
					callback?.call(result, data, address);
					resolve(result);
				} else {
					if (!isNonBlocking) API_MANAGER.free(true);
					if (!isNonBlocking) API_MANAGER.errorHandlers.php_error.call(result, data, address);
					reject(result);
				}
			};
			request.error = (result) => {
				if (!isNonBlocking) API_MANAGER.free(true);

				if (result.status == 0) {
					if (!isNonBlocking) API_MANAGER.errorHandlers.network_error.call(result, data, address);
					reject(result);
				} else if (result.responseJSON) {
					userErrorCallback?.call(result.responseJSON, data, address);
					reject(result.responseJSON);
				} else {
					if (!isNonBlocking) API_MANAGER.errorHandlers.php_error.call(result.responseText, data, address);
					reject(result.responseText);
				}
			};

			$.ajax(request);
		});
	}

	async uploadFiles(
		requestData,
		fileInputElement,
		fileInputName,
		progressCallback = this.apiManager.errorHandlers.notice,
		successCallback = this.apiManager.defaultResponseHandler,
		errorCallback = new ApiCallback((result, data, address) => {
			this.apiManager.errorHandlers.user_error.call(
				{
					message: result.files
						.map((e, i) => {
							switch (e.status) {
								case "success":
									return "<b>Soubor " + (i + 1) + ":</b> " + "Soubor byl úspěšně nahrán";
								case "user_error":
									return "<b>Soubor " + (i + 1) + ":</b> " + e.response.message ?? e.response ?? "Nebyla obtržena žádná chybová hláška";
								case "network_error":
									return "<b>Soubor " + (i + 1) + ":</b> " + "Chyba sítě";
								case "php_error":
									return "<b>Soubor " + (i + 1) + ":</b> " + "Chyba serveru";
							}
						})
						.join("<br><br>"),
				},
				data,
				address,
			);
		}),
		emptyInputCallback = () => {},
	) {
		if (this.apiManager.apiBusy) {
			this.apiManager.errorHandlers.notice.call("Tried to send a request but the API is already busy", "[...]", this.address);
			return;
		}

		if (fileInputElement.files.length == 0) {
			emptyInputCallback(fileInputElement);
			return;
		}

		const API_MANAGER = this.apiManager;
		const ADDRESS = this.address;

		API_MANAGER.busy();

		API_MANAGER.fileUploadProgress = {
			uploaded: 0,
			total: 0,
			status: "pending",
			filesCount: fileInputElement.files.length,
			files: fileInputElement.files.map(() => ({
				uploaded: 0,
				total: 0,
				status: "pending",
				response: "",
			})),
		};

		return await Promise.all(
			fileInputElement.files.map(async (file, i) => {
				let formdata = new FormData();

				objectForEach(requestData, (key, value) => formdata.append(key, JSON.stringify(value)));

				formdata.append(fileInputName, file);

				formdata.append("fileIndex", i + 1);
				formdata.append("fileCount", fileInputElement.files.length);

				var request = new XMLHttpRequest();

				return new Promise((resolve) => {
					request.onload = () => {
						let json;
						try {
							json = JSON.parse(request.responseText);
						} catch (e) {
							API_MANAGER.fileUploadProgress.files[i].status = "php_error";
							API_MANAGER.fileUploadProgress.files[i].response = {};

							let sent = {};
							objectForEach(requestData, (key, value) => (sent[key] = value));

							sent[fileInputName] = "binary";

							sent["fileIndex"] = i + 1;
							sent["fileCount"] = fileInputElement.files.length;

							API_MANAGER.errorHandlers.php_error.call(request.responseText, sent, this.address);

							resolve(false);
						}

						if (json && json.result && json.result == "success") {
							API_MANAGER.fileUploadProgress.files[i].status = "success";
							API_MANAGER.fileUploadProgress.files[i].response = json ?? {};

							resolve(true);
						} else if (json && json.result && json.result == "error") {
							API_MANAGER.fileUploadProgress.files[i].status = "user_error";
							API_MANAGER.fileUploadProgress.files[i].response = json ?? {};

							resolve(false);
						} else {
							API_MANAGER.fileUploadProgress.files[i].status = "php_error";
							API_MANAGER.fileUploadProgress.files[i].response = json ?? {};

							let sent = {};
							objectForEach(requestData, (key, value) => (sent[key] = value));

							sent[fileInputName] = "binary";

							sent["fileIndex"] = i + 1;
							sent["fileCount"] = fileInputElement.files.length;

							API_MANAGER.errorHandlers.php_error.call(request.responseText, sent, this.address);

							resolve(false);
						}
					};

					request.upload.addEventListener("progress", function (e) {
						API_MANAGER.fileUploadProgress.uploaded += e.loaded - API_MANAGER.fileUploadProgress.files[i].uploaded;
						API_MANAGER.fileUploadProgress.files[i].uploaded = e.loaded;
						API_MANAGER.fileUploadProgress.total += e.total - API_MANAGER.fileUploadProgress.files[i].total;
						API_MANAGER.fileUploadProgress.files[i].total = e.total;

						progressCallback.call(API_MANAGER.fileUploadProgress, formdata, ADDRESS);
					});

					request.upload.addEventListener("error", function (e) {
						API_MANAGER.fileUploadProgress.files[i].status = "network_error";
						API_MANAGER.fileUploadProgress.files[i].response = e ?? {};

						let sent = {};
						objectForEach(requestData, (key, value) => (sent[key] = value));

						sent[fileInputName] = "binary";

						sent["fileIndex"] = i + 1;
						sent["fileCount"] = fileInputElement.files.length;

						API_MANAGER.errorHandlers.network_error.call(e, sent, ADDRESS);

						resolve(false);
					});

					request.open("post", ADDRESS);
					request.send(formdata);
				});
			}),
		).then((results) => {
			const successful = results.every((result) => result);

			API_MANAGER.free(!successful);

			API_MANAGER.fileUploadProgress.status = successful ? "success" : "error";

			if (successful) successCallback.call(API_MANAGER.fileUploadProgress, "[...]", this.address);
			else errorCallback.call(API_MANAGER.fileUploadProgress, "[...]", this.address);

			const fileUploadProgress = API_MANAGER.fileUploadProgress;
			API_MANAGER.fileUploadProgress = {};

			return fileUploadProgress;
		});
	}

	fetchContinuously(data, callback = this.apiManager.defaultResponseHandler, lastCallback = this.apiManager.defaultResponseHandler, networkErrorHandler = this.apiManager.errorHandlers.network_error) {
		if (this.apiManager.apiBusy) {
			this.apiManager.errorHandlers.notice.call("Tried to send a request but the API is already busy", data, this.address);
			return;
		}

		this.apiManager.busy();

		let es;
		const API_MANAGER = this.apiManager;
		const address = this.address;

		var get = [];

		for (var key in data) {
			if (data.hasOwnProperty(key)) {
				get.push(key + "=" + encodeURIComponent(data[key]));
			}
		}

		get.join("&");

		es = new EventSource(address + "?" + get);

		es.addEventListener("message", function (e) {
			var result = JSON.parse(e.data);
			if (e.lastEventId == "CLOSE") {
				lastCallback.call({ messsage: result.message, progress: result.progress }, data, address);
				es.close();
				API_MANAGER.free();
			} else {
				callback.call({ messsage: result.message, progress: result.progress }, data, address);
			}
		});

		es.addEventListener("error", function (e) {
			networkErrorHandler.call(e, data, address);
			es.close();
			API_MANAGER.free(true);
		});
	}
}

class ApiManager {
	apiBusy = false;
	errorHandlers;
	defaultResponseHandler;
	requests = [];
	fileUploadProgress = {};

	constructor(defaultResponseHandler, errorHandlers = {}) {
		if (!errorHandlers.user_error || !errorHandlers.server_error || !errorHandlers.network_error || !errorHandlers.notice || !errorHandlers.php_error) console.error("Not all error handlers were set!\nError handlers:", errorHandlers);
		this.errorHandlers = errorHandlers;
		this.defaultResponseHandler = defaultResponseHandler;
	}

	schedule(...requests) {
		this.requests.push(...requests);

		this.nextTask();
	}

	scheduleTask(api, method, ...parameters) {
		this.requests.push(new ApiTask(api, method, ...parameters));

		this.nextTask();
	}

	nextTask() {
		if (this.requests.length == 0 || this.apiBusy == true) return;

		this.requests.shift().call();
	}

	busy() {
		this.apiBusy = true;
	}

	free(wasError = false) {
		this.apiBusy = false;
		if (!wasError) this.nextTask();
		else if (this.requests.length > 0) {
			this.errorHandlers.notice.call("Stopping the execution of this queue because an error has occured.\nDropping " + this.requests.length + " task(s):\n" + this.requests.map((e) => "Address:\n" + e.api.address + "\nMethod: " + e.method + "\nParameters: " + JSON.stringify(e.parameters) + "\n"), "[...]", "...");
			this.requests = [];
		}
	}
}

const VALIDATE = (value, type) => {
	return REGEXES[type] && REGEXES[type].test(value) == true;
};

const VALIDATE_FORM = (element, addClass = null) => {
	let r = true;
	element.querySelectorAll("input[data-type]").forEach((input) => {
		if (addClass) {
			input.classList.remove(addClass);
		}

		if (!REGEXES[input.getAttribute("data-type")]) {
			console.error("No regex found for data-type: " + input.getAttribute("data-type"));
			r = false;
		}

		if (!REGEXES[input.getAttribute("data-type")].test(input.value)) {
			if (addClass) setTimeout(() => input.classList.add(addClass), 0);
			r = false;
		}
	});
	element.querySelectorAll("input[required], textarea[required]").forEach((input) => {
		if (addClass) {
			input.classList.remove(addClass);
		}

		if (input.value == "" || (input.type == "file" && input.files.length == 0)) {
			if (addClass) setTimeout(() => input.classList.add(addClass), 0);
			r = false;
		}
	});

	return r;
};

const GET_FINGERPRINT = async () => {
	return navigator.userAgentData.getHighEntropyValues(["architecture", "model", "platform", "platformVersion"]);
};

const API_MANAGER = new ApiManager(new ApiCallback((result, data, address) => createModal("Požadavek byl úspěšně vykonnán", "")), {
	user_error: new ApiCallback((result, data, address) => createModal("Neplatný požadavek", result.message)),
	server_error: new ApiCallback((result, data, address) => {
		createModal("Nespecifikovaná hláška " + result.status, "Prosím kontaktuj správce se sledem událostí, které k tomuto vedli a těmito informacemi:<br><br>ADDRESS:<br>" + address.toString() + "<br><br>" + "SENT:<br>" + JSON.stringify(data) + "<br><br>" + "RECIEVED:<br>" + result.responseJSON?.message ?? "Nebyla obtržena žádná chybová hláška");
		console.error("Fetch request failed with error code: " + (result.status ?? "???") + "\nAPI address: " + address + "\n", data, "\n     |\n    \\|/\n", result.responseJSON ?? result.responseText ?? result);
	}),
	network_error: new ApiCallback((result, data, address) => {
		createModal("Požadavek selhal", "Nemohli jsme kontaktovat server. Prosím zkontroluj své připojení k internetu a zkus to znovu.");
		console.error("Fetch request failed due to a network error\nAPI address: " + address + "\n", data, "\n     |\n    \\|/\n", result);
	}),
	notice: new ApiCallback((result, data, address) => {
		console.warn("Notice:\nAPI address: " + address + "\n", data, "\n     |\n    \\|/\n", result);
	}),
	php_error: new ApiCallback((result, data, address) => {
		createModal("Chyba serveru", "Došlo k nespecifikované chybě serveru.<br><br>Prosím kontaktuj správce se sledem událostí, které k tomuto vedli a těmito informacemi:<br><br>ADDRESS:<br>" + address.toString() + "<br><br>" + "SENT:<br>" + JSON.stringify(data) + "<br><br>RECIEVED:<br>...");
		console.error("PHP ERROR:\nAPI address: " + address + "\n", data, "\n     |\n    \\|/\n", result);
	}),
});

window.onbeforeunload = () => {
	if (API_MANAGER.apiBusy == true) {
		return false;
	}
};

const USER_API = new ApiConnector("user", API_MANAGER);
const CONTENT_API = new ApiConnector("content", API_MANAGER);
const PANEL_API = new ApiConnector("panel", API_MANAGER);
