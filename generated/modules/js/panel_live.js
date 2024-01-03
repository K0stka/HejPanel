// Config
const CAROUSEL_TICKS_PER_SECOND = 50;
const CAROUSEL_SPEED = 10; // S per revolution
const FETCH_EVERY_N_SECONDS = 30; // Must be greater than CAROUSEL_SPEED

// DOM
const panelTime = document.querySelector("#panel-time");
const panelJidelna = document.querySelector("#panel-jidelna");

const panelContainer = document.querySelector("#panel-container");
const panelCounter = document.querySelector("#panel-counter");
const radialGraph = document.querySelector("#panel-radial-graph");

// State
let panelPointer = -1;
let panelIds = PANELS_PRELOAD;

let panelsToAdd = [];
let panelIdsToRemove = [];

let panels = PANELS_PRELOAD.map((id) => ({ id: id, element: document.querySelector("#panel-" + id) }));

let carousel_paused = false;
let carouselTick = 0;

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

	panels[panelPointer].element.classList.add("animate-in");
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
