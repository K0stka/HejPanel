// Config
const CAROUSEL_TICKS_PER_SECOND = 50;
const CAROUSEL_SPEED = 10; // S per revolution
const FETCH_EVERY_N_SECONDS = 30; // Must be greater than CAROUSEL_SPEED

// DOM
const panelTime = document.querySelector("#panel-time");
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

let panels = PANEL_DETAILS_PRELOAD.map((p) => ({ id: p.id, url: p.url, element: document.querySelector("#panel-" + p.id) }));

let carousel_paused = false;
let carouselTick = 0;

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

	panelCounter.innerHTML = panelPointer + 1 + "/" + panels.length;

	panels.forEach((e) => {
		e.element.classList.add("panel-hidden");
		e.element.classList.remove("animate-in");
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
	PANEL_API.nonBlockingGet({ j: null }, null, API_MANAGER.error_handlers.warn).then(updateJidelna);
};

const updateJidelna = (jidelna) => {
	if (jidelna.result && jidelna.result == "error") {
		panelJidelna.innerHTML = "<b>Nemohli jsme načíst data z jídelny 😞</b>";
	} else {
		panelJidelna.innerHTML = '<div class="panel-food-row"><b>Polévka:</b> ' + jidelna.X1 + '</div><div class="panel-food-row"><b>Oběd 1:</b> ' + jidelna.O1 + '</div><div class="panel-food-row"><b>Oběd 2:</b> ' + jidelna.O2 + '</div><div class="panel-food-row"><b>Oběd 3:</b> ' + jidelna.O3 + '</div><div class="panel-food-row"><b>Svačina:</b> ' + jidelna.SV + "</div>";
	}
};

function updateRadialGraph() {
	if (panels.length < 2) radialGraph.style.opacity = 0;
	else radialGraph.style.opacity = 1;
	radialGraph.style.setProperty("--value", (carouselTick / CAROUSEL_SPEED) * 2 + "%");
	requestAnimationFrame(updateRadialGraph);
}

// On load
cyclePanels();
updateJidelna(JIDELNA_PRELOAD);

// Timers
panelTime.innerHTML = new Date().toLocaleTimeString();
setInterval(() => {
	panelTime.innerHTML = new Date().toLocaleTimeString();
}, 1000);

requestAnimationFrame(updateRadialGraph);

// Carousel
setInterval(() => {
	if (document.hidden || carousel_paused) return;

	carouselTick++;

	if (carouselTick / CAROUSEL_TICKS_PER_SECOND >= CAROUSEL_SPEED) {
		updatePanels();
		cyclePanels();
		carouselTick = 0;
	}
}, 1000 / CAROUSEL_TICKS_PER_SECOND);

// Hydrator
setInterval(() => {
	if (document.hidden || carousel_paused) return;

	PANEL_API.nonBlockingGet({ i: panelIds }, null, null).then((result) => {
		console.log("Caching panels: " + result.a.map((panel) => panel.i).join(", "), "\nForgetting panels: " + result.r.join(", "));

		panelIds.push(...result.a.map((panel) => panel.i));
		panelIds = panelIds.filter((id) => !result.r.includes(id));

		panelsToAdd.push(...result.a);
		panelIdsToRemove.push(...result.r);
	});
}, FETCH_EVERY_N_SECONDS * 1000);

// Jidelna
setInterval(() => {
	fetchJidelna();
}, 3600000);

// Touch-pause & double-tap
let doubleTap = 0; // 0 = no tap, 1 = 1st tap down, 2 = 1st tap up, (3) = 2nd tap down -> triggering
let doubleTapCooldown = false;
addEventListener("touchstart", () => {
	carousel_paused = true;

	if (doubleTap == 0) {
		doubleTap = 1;
		setTimeout(function () {
			doubleTap = 0;
		}, 500);
		return false;
	}

	if (doubleTap == 1) {
		doubleTap = 0;
		return;
	}
	if (doubleTapCooldown) return;

	doubleTapCooldown = true;
	updatePanels();
	cyclePanels();
	carouselTick = 0;
	setTimeout(function () {
		doubleTapCooldown = false;
	}, 1000); // Panel animate-in animation length
});
addEventListener("touchend", () => {
	carousel_paused = false;

	if (doubleTap == 1) doubleTap = 2;
});
