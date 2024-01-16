// Config
const CAROUSEL_TICKS_PER_SECOND = 50;
const FETCH_EVERY_N_SECONDS = 30; // Must be greater than CAROUSEL_SPEED
const CAROUSEL_SPEED = 15; // S per revolution

const FETCH_JIDELNA_EVERY_N_MINUTES = 30;

// Timetable
const timetable = [
	{
		type: "1. hodina",
		from_milTime: 800,
		from_time: "8:00",
		to_milTime: 845,
		to_time: "8:45",
	},
	{
		type: "Přestávka",
		from_milTime: 845,
		from_time: "8:45",
		to_milTime: 855,
		to_time: "8:55",
	},
	{
		type: "2. hodina",
		from_milTime: 855,
		from_time: "8:55",
		to_milTime: 940,
		to_time: "9:40",
	},
	{
		type: "Přestávka",
		from_milTime: 940,
		from_time: "9:40",
		to_milTime: 1000,
		to_time: "10:00",
	},
	{
		type: "3. hodina",
		from_milTime: 1000,
		from_time: "10:00",
		to_milTime: 1045,
		to_time: "10:45",
	},
	{
		type: "Přestávka",
		from_milTime: 1045,
		from_time: "10:45",
		to_milTime: 1055,
		to_time: "10:55",
	},
	{
		type: "4. hodina",
		from_milTime: 1055,
		from_time: "10:55",
		to_milTime: 1140,
		to_time: "11:40",
	},
	{
		type: "Přestávka",
		from_milTime: 1140,
		from_time: "11:40",
		to_milTime: 1150,
		to_time: "11:50",
	},
	{
		type: "5. hodina",
		from_milTime: 1150,
		from_time: "11:50",
		to_milTime: 1235,
		to_time: "12:35",
	},
	{
		type: "Přestávka",
		from_milTime: 1235,
		from_time: "12:35",
		to_milTime: 1245,
		to_time: "12:45",
	},
	{
		type: "6. hodina",
		from_milTime: 1245,
		from_time: "12:45",
		to_milTime: 1330,
		to_time: "13:30",
	},
	{
		type: "Přestávka",
		from_milTime: 1330,
		from_time: "13:30",
		to_milTime: 1400,
		to_time: "14:00",
	},
	{
		type: "7. hodina",
		from_milTime: 1400,
		from_time: "14:00",
		to_milTime: 1445,
		to_time: "14:45",
	},
	{
		type: "Přestávka",
		from_milTime: 1445,
		from_time: "14:45",
		to_milTime: 1540,
		to_time: "15:40",
	},
	{
		type: "8. hodina",
		from_milTime: 1455,
		from_time: "14:55",
		to_milTime: 1540,
		to_time: "15:40",
	},
];

// DOM
const panelInfo = document.querySelector("#panel-info");
const panelLogoBtn = document.querySelector("#panel-logo-button");
const panelTime = document.querySelector("#panel-time");
const panelTimetable = document.querySelector("#panel-timetable");
const panelJidelna = document.querySelector("#panel-jidelna");
const panelQR = document.querySelector("#panel-qr");
const panelCTA = document.querySelector("#panel-cta");

const panelContainer = document.querySelector("#panel-container");
const panelCounter = document.querySelector("#panel-counter");
const radialGraph = document.querySelector("#panel-radial-graph");

// State
let panelPointer = -1;
let panelIds = PANELS_PRELOAD;

let panelsToAdd = [];
let panelIdsToRemove = [];
let panelsApplied = true;

let panels = PANEL_DETAILS_PRELOAD.map((p) => ({ id: p.id, url: p.url, element: document.querySelector("#panel-" + p.id) }));

let carousel_paused = false;
let carouselTick = 0;

let panelInfoVisible = false;

// Force reload
let forceReload = FORCE_RELOAD_PRELOAD;

const qrcode = new QRCode(panelQR, {
	text: base_url + "/",
	width: 128,
	height: 128,
	colorDark: "#000000",
	colorLight: "#ffffff",
	correctLevel: QRCode.CorrectLevel.H,
});
qrcode.clear();

const renderPanel = (panelId, panelType, panelContent) => {
	const panel = document.createElement("div");
	panel.classList.add("panel");
	panel.classList.add("panel-hidden");
	panel.id = "panel-" + panelId;

	switch (panelType) {
		case "text":
			panel.classList.add("panel-text");
			panel.innerHTML = panelContent;
			break;
		case "image":
			panel.classList.add("panel-image");
			panel.innerHTML = '<img src="' + base_url + "/api/content/" + panelContent + '" class="backdrop"><img src="' + base_url + "/api/content/" + panelContent + '">';
			break;
		default:
			panel.classList.add("panel-text");
			panel.innerHTML = "Invalid panel type: " + panelType;
	}
	return panel;
};

const updatePanels = () => {
	panelsApplied = true;

	if (panelsToAdd.length == 0 && panelIdsToRemove == 0) return;

	console.log("%cUpdating panels...", "color: gray");

	if (panelsToAdd.length > 0) {
		panelsToAdd.forEach((panel) => {
			console.log("%cAdding panel " + panel.i, "color: lime");

			const newPanel = renderPanel(panel.i, panel.t, panel.c);

			panelContainer.appendChild(newPanel);

			panels.push({
				id: panel.i,
				url: panel.u,
				element: newPanel,
			});
		});
	}

	if (panelIdsToRemove.length > 0) {
		panelIdsToRemove.forEach((id) => {
			console.log("%cRemoving panel " + id, "color: red");

			removedPanelPointer = 0;
			panels.every((panel, i) => {
				if (panel.id == id) {
					removedPanelPointer = i;

					if (panel.element.classList.contains("animate-in")) {
						panel.element.classList.remove("animate-in");
						panel.element.addEventListener("animationend", () => {
							panel.element.remove();
						});
					} else {
						panel.element.remove();
					}
					return false;
				}
				return true;
			});
			if (removedPanelPointer <= panelPointer) {
				panelPointer--;
				console.log("%cPanel pointer slip", "color: orange;");
			}

			panels = panels.filter((p) => p.id != id);
		});
	}

	panelsToAdd = [];
	panelIdsToRemove = [];
};

const cyclePanels = () => {
	if (panels.length == 0) return;

	panelPointer = (panelPointer + 1) % panels.length;

	handlePanelRotation();
};

const cyclePanelsBackwards = () => {
	if (panels.length == 0) return;

	panelPointer = panelPointer - 1;
	if (panelPointer < 0) panelPointer = panels.length - 1;

	handlePanelRotation(true);
};

const handlePanelRotation = (reverse = false) => {
	panelCounter.innerHTML = panelPointer + 1 + "/" + panels.length;

	const visibleIds = [panelPointer, reverse ? (panelPointer + 1) % panels.length : panelPointer - 1 < 0 ? panels.length - 1 : panelPointer - 1];

	panels.forEach((e, i) => {
		e.element.classList.add("panel-hidden");
		e.element.classList.remove("animate-in");

		if (reverse) {
			e.element.classList.add("reverse");
			e.element.classList.remove("normal");
		} else {
			e.element.classList.add("normal");
			e.element.classList.remove("reverse");
		}

		if (visibleIds.includes(i)) e.element.classList.remove("transparent");
		else e.element.classList.add("transparent");
	});

	const panel = panels[panelPointer];

	if (panel.url) {
		panelQR.classList.add("visible");
		qrcode.clear();
		qrcode.makeCode(panel.url);

		panelCTA.classList.add("visible");
		panelCTA.href = panel.url;
	} else {
		panelQR.classList.remove("visible");
		panelCTA.classList.remove("visible");
	}

	panel.element.classList.add("animate-in");
};

const fetchJidelna = () => {
	PANEL_API.nonBlockingGet({ j: null }, null, API_MANAGER.errorHandlers.notice).then(updateJidelna);
};

const updateJidelna = (jidelna) => {
	if (jidelna.result && jidelna.result == "error") {
		panelJidelna.innerHTML = "<b>Nemohli jsme načíst data z jídelny 😞</b>";
	} else {
		panelJidelna.innerHTML = '<div class="panel-food-row"><b>Polévka:</b> ' + jidelna.X1 + '</div><div class="panel-food-row"><b>Oběd 1:</b> ' + jidelna.O1 + '</div><div class="panel-food-row"><b>Oběd 2:</b> ' + jidelna.O2 + '</div><div class="panel-food-row"><b>Oběd 3:</b> ' + jidelna.O3 + '</div><div class="panel-food-row"><b>Svačina:</b> ' + jidelna.SV + "</div>";
	}
};

let updateRadialGraph = () => {
	if (panels.length < 2) radialGraph.style.opacity = 0;
	else radialGraph.style.opacity = 1;
	radialGraph.style.setProperty("--value", (carouselTick / CAROUSEL_SPEED) * 2 + "%");
	requestAnimationFrame(updateRadialGraph);
};
requestAnimationFrame(updateRadialGraph);

// On load
cyclePanels();
updateJidelna(JIDELNA_PRELOAD);

// Clock & timetable
panelTime.innerHTML = new Date().toLocaleTimeString();
const clockInterval = setInterval(() => {
	const now = new Date();
	const milTime = now.getHours() * 100 + now.getMinutes();

	panelTime.innerHTML = now.toLocaleTimeString();
	panelTimetable.innerHTML = "";
	timetable.forEach((event) => {
		if ((event.from_milTime <= milTime && event.to_milTime > milTime) || true) {
			panelTimetable.innerHTML = event.type + "<br>(" + event.from_time + " - " + event.to_time + ")";
			return;
		}
	});
}, 1000);

// Carousel
const carouselInterval = setInterval(() => {
	if (document.hidden || carousel_paused) return;

	carouselTick++;

	if (carouselTick / CAROUSEL_TICKS_PER_SECOND >= CAROUSEL_SPEED) {
		updatePanels();
		cyclePanels();
		carouselTick = 0;
	}
}, 1000 / CAROUSEL_TICKS_PER_SECOND);

// Panel hydrator
const hydratorInterval = setInterval(() => {
	if (document.hidden || carousel_paused || !panelsApplied) return;

	panelsApplied = false;
	PANEL_API.nonBlockingGet({ i: panelIds }, null, null).then((result) => {
		if (result.f != forceReload) window.location.reload();

		if (result.a.length == 0 && result.r.length == 0) return;

		console.log("Caching panels: " + result.a.map((panel) => panel.i).join(", "), "\nForgetting panels: " + result.r.join(", "));

		panelIds.push(...result.a.map((panel) => panel.i));
		panelIds = panelIds.filter((id) => !result.r.includes(id));

		panelsToAdd.push(...result.a);
		panelIdsToRemove.push(...result.r);
	});
}, FETCH_EVERY_N_SECONDS * 1000);

// Jidelna
const jidelnaInterval = setInterval(() => {
	fetchJidelna();
}, FETCH_JIDELNA_EVERY_N_MINUTES * 60000);

// Phone panelInfo toggle
panelLogoBtn.addEventListener("click", () => {
	if (panelInfoVisible) {
		panelInfo.classList.remove("visible");
		panelInfoVisible = false;
		carousel_paused = false;
	} else {
		panelInfo.classList.add("visible");
		panelInfoVisible = true;
		carousel_paused = true;
	}
});

// Util function for gesture handlers
const handleCarouselSkip = (reverse = false) => {
	if (skipCooldown) return;

	updatePanels();

	if (reverse) cyclePanelsBackwards();
	else cyclePanels();

	carouselTick = 0;
	skipCooldown = true;

	setTimeout(() => {
		skipCooldown = false;
	}, 750);
};

// Touch-pause & hold-pause & easy panelInfo dismiss
let skipCooldown = false;
let moved = false;
let holding = false;

const touchStart = (event) => {
	if (event.target.closest("dialog")) return;
	moved = false;
	holding = false;

	if (event.target == panelLogoBtn || event.target == panelCTA) return;

	setTimeout(() => {
		holding = true;
	}, 200);

	carousel_paused = true;
};

const touchMove = () => {
	moved = true;
};

const touchEnd = (event) => {
	if (event.target.closest("dialog")) return;

	if (moved && panelInfoVisible) return;

	carousel_paused = false;

	if (moved) return;

	if (event.target != panelLogoBtn && panelInfoVisible) {
		panelInfo.classList.remove("visible");
		panelInfoVisible = false;
		return;
	}

	if (event.target == panelLogoBtn || event.target == panelCTA) return;

	if (!holding) {
		handleCarouselSkip();
	}
};

addEventListener("touchstart", touchStart);
addEventListener("touchmove", touchMove);
addEventListener("touchend", touchEnd);

// Swipe gestures
let xDown = null;
let yDown = null;

const gestureTouchStart = (event) => {
	if (event.target.closest("dialog")) return;

	const firstTouch = event.touches[0];
	xDown = firstTouch.clientX;
	yDown = firstTouch.clientY;
};

const gestureTouchMove = (event) => {
	if (!xDown || !yDown) {
		return;
	}

	const xUp = event.touches[0].clientX;
	const yUp = event.touches[0].clientY;

	const xDiff = xDown - xUp;
	const yDiff = yDown - yUp;

	// Has to travel at least 100px
	if (xDiff * xDiff + yDiff * yDiff < 10000) return;

	if (Math.abs(xDiff) > Math.abs(yDiff)) {
		if (xDiff > 0) {
			if (!panelInfoVisible) {
				handleCarouselSkip();
			}
		} else {
			if (!panelInfoVisible) {
				handleCarouselSkip(true);
			}
		}
	} else {
		if (yDiff > 0) {
			if (!panelInfoVisible) {
				createModal("Nastavení", '<div class="button-group"><button onclick="fadeTo(\'' + base_url + "/submit')\">Přidat panel</button><button onclick=\"fadeTo('" + base_url + "/login')\">Přihlásit se</button></div>");
			}
		} else {
			if (!panelInfoVisible) {
				panelInfo.classList.add("visible");
				panelInfoVisible = true;
				carousel_paused = true;
			}
		}
	}

	xDown = null;
	yDown = null;
};

addEventListener("touchstart", gestureTouchStart);
addEventListener("touchmove", gestureTouchMove);

window.addEventListener(
	"onFinished",
	() => {
		updateRadialGraph = () => {};

		clearInterval(clockInterval);
		clearInterval(carouselInterval);
		clearInterval(hydratorInterval);
		clearInterval(jidelnaInterval);

		removeEventListener("touchstart", touchStart);
		removeEventListener("touchmove", touchMove);
		removeEventListener("touchend", touchEnd);
		removeEventListener("touchstart", gestureTouchStart);
		removeEventListener("touchmove", gestureTouchMove);
	},
	{ once: true },
);
