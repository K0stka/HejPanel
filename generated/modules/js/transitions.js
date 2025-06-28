// CONFIGURATION

const CSS_ANIMATION_HALF_LENGTH_MS = 150;

const SINGLE_PAGE = true;

const TAKE_OVER_HISTORY = false && window.history && history.pushState && !SINGLE_PAGE;

const SAME_LEVEL_DIRECTION = (STARTING_POSITION, TARGET_POSITION) => {
	return "toRight"; // toRight, toLeft, toIn, toOut, toFade
};

let NAVIGATING = false;

// UTIL
const SAME_LEVEL_DIRECTION_END = (STARTING_POSITION, TARGET_POSITION) => {
	const reverseMap = {
		toRight: "fromLeft",
		toLeft: "fromRight",
		toIn: "fromIn",
		toOut: "fromOut",
		toFade: "fromFade",
	};

	return reverseMap[SAME_LEVEL_DIRECTION(STARTING_POSITION, TARGET_POSITION)];
};

const GET_PAGE_MAP_POSITION = (url = window.location.href, top = null, left = null) => {
	const map = url
		.replace(base_url, "")
		.replace(/^\/|\/$/g, "")
		.split("/");

	return {
		sameOrigin: url.includes(base_url),
		url: url,
		levels: map,
		depth: map.length,
		top: top ?? document.querySelector("main")?.scrollTop ?? 0,
		left: left ?? document.querySelector("main")?.scrollLeft ?? 0,
	};
};

const HANDLE_HISTORY_JUMP = (STARTING_POSITION, TARGET_POSITION) => {
	if (STARTING_POSITION.depth == TARGET_POSITION.depth) {
		console.log("REPLACING STATE");
		history.replaceState(0, title, fetchedUrl);
	} else if (STARTING_POSITION.depth > TARGET_POSITION.depth) {
		console.log("POPPING " + STARTING_POSITION.depth - TARGET_POSITION.depth + " STATE(S)");
		for (i = 0; i < STARTING_POSITION.depth - TARGET_POSITION.depth; i++) history.popState();
	} else if (STARTING_POSITION.depth < TARGET_POSITION.depth) {
		console.log("PUSHING " + TARGET_POSITION.depth - STARTING_POSITION.depth + " STATE(S)");
		history.pushState(0, title, fetchedUrl);
	}
};

const getPageContent = async (url) => {
	return new Promise((resolve) => {
		$.ajax({
			type: "post",
			url: url,
			cache: "no-cache",
			success: function (result, textStatus, request) {
				const title = decodeURI(request.getResponseHeader("title")).replaceAll("+", " ");
				const body = document.createElement("body");
				body.innerHTML = result;
				resolve([url, title, body]);
			},
			error: function (result) {
				API_MANAGER.errorHandlers.network_error.call(result, [], url);
				resolve([window.location.href, document.title, document.body]);
			},
		});
	});
};

// MAIN FUNCTIONS

const navigate = (url) => {
	// SAME PAGE NAVIGATION
	if (url == window.location.href) {
		document.querySelector("main")?.scrollTo({ top: 0, left: 0, behavior: "smooth" });
		return;
	}

	NAVIGATE_TO_POSITION(GET_PAGE_MAP_POSITION(url, 0, 0));
};

const fadeTo = (url) => {
	NAVIGATE_TO_POSITION(GET_PAGE_MAP_POSITION(url, 0, 0), true);
};

const NAVIGATE_TO_POSITION = async (TARGET_POSITION, forceFade = false) => {
	if (NAVIGATING) return;

	if (!TARGET_POSITION.sameOrigin) {
		window.location.href = TARGET_POSITION.url;
		return;
	}

	NAVIGATING = true;

	const STARTING_POSITION = GET_PAGE_MAP_POSITION();

	const OLD_MAIN = document.querySelector("main");
	if (OLD_MAIN) OLD_MAIN.classList = OLD_MAIN.classList.filter((e) => !["toRight", "toLeft", "toIn", "toOut", "toFade", "fromRight", "fromLeft", "fromIn", "fromOut", "fromFade"].includes(e));

	console.log("%c[NAVIGATOR]\n%cStarting depth: " + STARTING_POSITION.depth + "\nTarget depth: " + TARGET_POSITION.depth + "\nStarting url: " + STARTING_POSITION.url + "\nTarget url: " + TARGET_POSITION.url, "background: lightblue;color: black;", "font-weight: bold;");

	// LEAVING ANIMATION

	onFinished();

	if (OLD_MAIN)
		if (STARTING_POSITION.depth == TARGET_POSITION.depth && !forceFade) {
			OLD_MAIN.classList.add(SAME_LEVEL_DIRECTION(STARTING_POSITION, TARGET_POSITION));
		} else if (STARTING_POSITION.depth > TARGET_POSITION.depth && !forceFade) {
			OLD_MAIN.classList.add("toOut");
		} else if (STARTING_POSITION.depth < TARGET_POSITION.depth && !forceFade) {
			OLD_MAIN.classList.add("toIn");
		} else {
			OLD_MAIN.classList.add("toFade");
		}

	// FETCH (And make sure that CSS animations are finished)

	const {
		0: [fetchedUrl, title, fetchedBody],
	} = await Promise.all([
		getPageContent(TARGET_POSITION.url),
		new Promise((resolve) => {
			setTimeout(resolve, CSS_ANIMATION_HALF_LENGTH_MS);
		}),
	]);

	// HANDLE HISTORY

	if (SINGLE_PAGE && fetchedUrl == TARGET_POSITION.url) {
		history.replaceState(0, title, fetchedUrl);
	} else if (TAKE_OVER_HISTORY && fetchedUrl == TARGET_POSITION.url) {
		console.trace("NOT IMPLEMENTED");
		history.replaceState(0, title, fetchedUrl);
	} else if (fetchedUrl == TARGET_POSITION.url) {
		history.pushState(0, title, fetchedUrl);
	}

	document.title = title;

	// ENTERING ANIMATION

	const dialogBackup = fetchedBody.querySelector("dialog");

	if (dialogBackup) dialogBackup.remove();

	document.body = fetchedBody;

	if (dialogBackup) createModal(dialogBackup.querySelector(".dialog-header").innerHTML, dialogBackup.querySelector(".dialog-content").innerHTML);

	const NEW_MAIN = document.querySelector("main");

	if (NEW_MAIN) {
		if (STARTING_POSITION.depth == TARGET_POSITION.depth && !forceFade) {
			NEW_MAIN.classList.add(SAME_LEVEL_DIRECTION_END(STARTING_POSITION, TARGET_POSITION));
		} else if (STARTING_POSITION.depth > TARGET_POSITION.depth && !forceFade) {
			NEW_MAIN.classList.add("fromOut");
		} else if (STARTING_POSITION.depth < TARGET_POSITION.depth && !forceFade) {
			NEW_MAIN.classList.add("fromIn");
		} else {
			NEW_MAIN.classList.add("fromFade");
		}

		// SCROLL RESTORATION

		NEW_MAIN.scrollTo({ top: TARGET_POSITION.top, left: TARGET_POSITION.left });
	}

	NAVIGATING = false;
	onReady();
};

// HISTORY HANDLING

if (TAKE_OVER_HISTORY) {
	console.trace("NOT IMPLEMENTED");

	window.addEventListener(
		"load",
		function () {
			history.replaceState(-1, "BACK", window.location); // back state
			history.pushState(0, "NORMAL", window.location); // main state

			this.addEventListener(
				"popstate",
				function (event, state) {
					if (event.state == -1) {
						console.log(event);
						history.pushState(0, "NORMAL", window.location);
					}
				},
				false,
			);
		},
		false,
	);
}
