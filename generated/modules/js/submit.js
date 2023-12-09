let selectedImage = true;

const submitForm = document.querySelector("#submit-form");

const set_type_image = document.querySelector("#set-type-image");
const set_type_text = document.querySelector("#set-type-text");
const additional_settings = document.querySelector("#additional-settings");

const input_label_file = document.createElement("div");
input_label_file.classList.add("input-label");

const fileInput = document.createElement("input");
fileInput.type = "file";
fileInput.id = "file";
fileInput.addEventListener("input", (event) => {
	fileInput.classList.remove("error");
});
fileInput.setAttribute("required", "");

const input_label_text = document.createElement("div");
input_label_text.classList.add("input-label");

const label = document.createElement("label");
label.htmlFor = "text";
label.innerHTML = "Text, který se má zobrazit:";

const textInput = document.createElement("textarea");
textInput.id = "text";
textInput.addEventListener("input", (event) => {
	textInput.classList.remove("error");

	if (textInput.value != "") {
		textInput.setAttribute("not-empty", "");
	} else {
		textInput.removeAttribute("not-empty");
	}

	textPanel.innerHTML = textInput.value.replaceAll("\n", "<br>");
});
textInput.setAttribute("required", "");

const panelContainer = document.querySelector("#panel-container");

const textPanel = document.createElement("div");
textPanel.classList.add("panel");
textPanel.classList.add("panel-text");

const imagePanel = document.createElement("div");
imagePanel.classList.add("panel");
imagePanel.classList.add("panel-image");

const submitBtn = document.querySelector("#submit");

input_label_file.appendChild(fileInput);

input_label_text.appendChild(label);
input_label_text.appendChild(textInput);

const controlsToImage = () => {
	set_type_image.classList.remove("non-active");
	set_type_text.classList.add("non-active");
	set_type_image.classList.add("active");
	set_type_text.classList.remove("active");

	selectedImage = true;

	additional_settings.innerHTML = "";
	additional_settings.appendChild(input_label_file);

	panelContainer.innerHTML = "";
	panelContainer.appendChild(imagePanel);
};
const controlsToText = () => {
	set_type_image.classList.add("non-active");
	set_type_text.classList.remove("non-active");
	set_type_image.classList.remove("active");
	set_type_text.classList.add("active");

	selectedImage = false;

	additional_settings.innerHTML = "";
	additional_settings.appendChild(input_label_text);

	panelContainer.innerHTML = "";
	textPanel.innerHTML = textInput.value.replaceAll("\n", "<br>");
	panelContainer.appendChild(textPanel);
};

set_type_image.addEventListener("click", controlsToImage);
set_type_text.addEventListener("click", controlsToText);

controlsToImage();

if (submitBtn) {
	submitBtn.addEventListener("click", (event) => {
		if (VALIDATE_FORM(submitForm, "error")) {
			GET_FINGERPRINT().then((fp) => {
				if (selectedImage) {
					let fileName = "";
					API_MANAGER.schedule(
						new ApiTask(
							CONTENT_API,
							"uploadFiles",
							{
								type: "upload",
								fingerprint: fp,
							},
							fileInput,
							"file",
							new ApiCallback(() => {}),
							new ApiCallback((result) => {
								fileName = result;
							}),
						),
						new ApiTask(
							PANEL_API,
							"post",
							{
								type: "addPanel",
								show_from: document.querySelector("#show-from").value,
								show_till: document.querySelector("#show-till").value,
								fingerprint: fp,
								panel_type: "image",
								content: fileName,
								note: document.querySelector("#note").value,
							},
							new ApiCallback(() => {
								createModal("Panel byl úspěšně odeslán", "Jakmile bude ověřen a nastane jeho čas, zobrazí se na hejpanelu");
							}),
						),
					);
				} else {
					PANEL_API.post(
						{
							type: "addPanel",
							show_from: document.querySelector("#show-from").value,
							show_till: document.querySelector("#show-till").value,
							fingerprint: fp,
							panel_type: "text",
							content: textInput.value,
							note: document.querySelector("#note").value,
						},
						new ApiCallback(() => {
							createModal("Panel byl úspěšně odeslán", "Jakmile bude ověřen a nastane jeho čas, zobrazí se na hejpanelu");
						}),
					);
				}
			});
		}
	});
}
