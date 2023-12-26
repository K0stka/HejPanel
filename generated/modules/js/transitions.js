async function getPageContent(url) {
	return new Promise((resolve) => {
		$.ajax({
			type: "post",
			url: url,
			cache: "no-cache",
			success: function (result, textStatus, request) {
				const title = decodeURI(request.getResponseHeader("title")).replaceAll("+", " ");
				resolve([title, result]);
			},
			error: function (result) {
				API_MANAGER.errorHandlers.network_error.call(url, [], result);
				resolve([document.title, document.body.innerHTML]);
			},
		});
	});
}

const navigate = async (url, target_history_level = 0, top = 0, left = 0, direction = 0, fade = false, fetchSamePage = false, isBackNavigation = false) => {
	const starting_history_level = HISTORY_TREE.length - 1;
	const main = document.querySelector("main");
	main.classList = main.classList.filter((e) => e != "toRight" && e != "toLeft" && e != "toIn" && e != "toOut" && e != "toFade" && e != "fromRight" && e != "fromLeft" && e != "fromIn" && e != "fromOut" && e != "fromFade");

	// SAME PAGE NAVIGATION
	if (url == window.location.href && !fade && !fetchSamePage) {
		main.scrollTo({ top: 0, left: 0, behavior: "smooth" });
		return;
	}

	// HISTORY TREE EDGE CASE HANDLING

	if (starting_history_level > target_history_level) {
		while (HISTORY_TREE.length - 1 > target_history_level) HISTORY_TREE.pop();
	}

	if (starting_history_level + 1 < target_history_level) {
		console.error("HISTORY_TREE depth delta > 1\nGoing from " + starting_history_level + " to " + target_history_level);
		while (HISTORY_TREE.length < target_history_level) HISTORY_TREE.push({ url: url, top: 0, left: 0 });
	}

	// HISTORY TREE UPDATE

	HISTORY_TREE[target_history_level] = { url: url, top: 0, left: 0 };

	// RETURN PAGE STATUS UPDATE

	if (target_history_level - starting_history_level > 0) {
		HISTORY_TREE[target_history_level - 1].url = window.location.href;
		HISTORY_TREE[target_history_level - 1].top = main.scrollTop;
		HISTORY_TREE[target_history_level - 1].left = main.scrollLeft;
	}

	// LEAVING ANIMATION

	if (target_history_level - starting_history_level == 0 && (direction == -1 || direction == 1) && !fade) {
		if (direction == -1) {
			main.classList.add("toRight");
		} else if (direction == 1) {
			main.classList.add("toLeft");
		}
	} else if (target_history_level - starting_history_level == -1 && !fade) {
		main.classList.add("toOut");
	} else if (target_history_level - starting_history_level == 1 && !fade) {
		main.classList.add("toIn");
	} else {
		main.classList.add("toFade");
	}

	// FETCH (And make sure that CSS animations are finished)

	const {
		0: [title, content],
	} = await Promise.all([
		getPageContent(url),
		new Promise((resolve) => {
			setTimeout(resolve, 150);
		}),
	]);

	if (isBackNavigation) {
		history.pushState(0, title, url);
	} else {
		history.replaceState(0, title, url);
	}
	document.title = title;

	// ENTERING ANIMATION

	document.body.innerHTML = content;

	const newMain = document.querySelector("main");

	if (newMain) {
		if ((direction == -1 || direction == 1) && !fade) {
			if (direction == -1) {
				newMain.classList.add("fromLeft");
			} else if (direction == 1) {
				newMain.classList.add("fromRight");
			}
		} else if (target_history_level - starting_history_level == -1 && !fade) {
			newMain.classList.add("fromOut");
		} else if (target_history_level - starting_history_level == 1 && !fade) {
			newMain.classList.add("fromIn");
		} else {
			newMain.classList.add("fromFade");
		}

		// SCROLL RESTORATION

		newMain.scrollTo({ top: top, left: left });
	}

	onReady();
};

const fadeTo = (url, target_history_level = null) => {
	if (!target_history_level) target_history_level = HISTORY_TREE.length - 1;
	navigate(url, target_history_level, 0, 0, 0, true, true);
};

let HISTORY_TREE = [{ url: window.location.href, top: 0, left: 0 }];

if (window.history && history.pushState) {
	window.addEventListener(
		"load",
		function () {
			history.replaceState(-1, document.title, ""); // back state
			history.pushState(0, document.title, ""); // main state

			this.addEventListener(
				"popstate",
				function (event, state) {
					if ((state = event.state) && state == -1) {
						if (HISTORY_TREE.length > 1) {
							navigate(HISTORY_TREE[HISTORY_TREE.length - 2].url, HISTORY_TREE.length - 2, HISTORY_TREE[HISTORY_TREE.length - 2].top, HISTORY_TREE[HISTORY_TREE.length - 2].left, 0, false, true, true);
						} else {
							navigate(HISTORY_TREE[HISTORY_TREE.length - 1].url, HISTORY_TREE.length - 1, HISTORY_TREE[HISTORY_TREE.length - 1].top, HISTORY_TREE[HISTORY_TREE.length - 1].left, 0, false, true, true);
						}
					}
				},
				false,
			);
		},
		false,
	);
}
