const CAROUSEL_SPEED = 10; // S per revolution

const panelTime = document.querySelector("#panel-time");
const panelJidelna = document.querySelector("#panel-jidelna");

let carousel_paused = false;

if (panelTime) {
	panelTime.innerHTML = new Date().toLocaleTimeString();
	setInterval(() => {
		panelTime.innerHTML = new Date().toLocaleTimeString();
	}, 1000);
}

const panelContainer = document.querySelector("#panel-container");
const panelCounter = document.querySelector("#panel-counter");
const radialGraph = document.querySelector("#panel-radial-graph");

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

const updatePanels = async (newPanelIds) => {
	if (typeof newPanelIds != "object") return;

	if (areSameSet(panelIds, newPanelIds)) return;

	console.log("%cUpdating panels...", "color: gray");

	const panelIdsToAdd = newPanelIds.filter((id) => !panelIds.includes(id));
	if (panelIdsToAdd.length > 0) {
		await PANEL_API.get({ i: panelIdsToAdd }, null, null).then((newPanels) => {
			newPanels.forEach((panel) => {
				console.log("%cAdding panel " + panel.id, "color: lime");

				panelIds.push(panel.id);

				const newPanel = renderPanel(panel.id, panel.type, panel.content);

				panelContainer.appendChild(newPanel);

				panels.push({
					id: panel.id,
					element: newPanel,
				});
			});
		});
	}

	const panelIdsToRemove = panelIds.filter((id) => !newPanelIds.includes(id));
	if (panelIdsToRemove.length > 0) {
		panelIds = panelIds.filter((id) => !panelIdsToRemove.includes(id));

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

const fetchJidelna = async () => {
	PANEL_API.get({ j: null }, null, null).then(updateJidelna);
};

const updateJidelna = (jidelna) => {
	if (jidelna.result && jidelna.result == "error") {
		panelJidelna.innerHTML = "<b>Nemohli jsme načíst data z jídelny 😞</b>";
	} else {
		panelJidelna.innerHTML = '<div class="panel-food-row"><b>Polévka:</b> ' + jidelna.X1 + '</div><div class="panel-food-row"><b>Oběd 1:</b> ' + jidelna.O1 + '</div><div class="panel-food-row"><b>Oběd 2:</b> ' + jidelna.O2 + '</div><div class="panel-food-row"><b>Oběd 3:</b> ' + jidelna.O3 + '</div><div class="panel-food-row"><b>Svačina:</b> ' + jidelna.SV + "</div>";
	}
};

// Initial values
let panelPointer = -1;
let panelIds = PANELS_PRELOAD;
let panels = PANELS_PRELOAD.map((id) => ({ id: id, element: document.querySelector("#panel-" + id) }));
cyclePanels();
updateJidelna(JIDELNA_PRELOAD);

// Carousel
let carouselTick = 0;
setInterval(() => {
	if (document.hidden || carousel_paused) return;

	carouselTick++;
	if (panels.length < 2) radialGraph.style.opacity = 0;
	else radialGraph.style.opacity = 1;
	radialGraph.style.setProperty("--value", (carouselTick / CAROUSEL_SPEED) * 2 + "%");

	if (carouselTick / 50 >= CAROUSEL_SPEED) {
		PANEL_API.get({ t: "c" }, null, null).then((newPanelIds) => updatePanels(newPanelIds).then(cyclePanels));
		carouselTick = 0;
	}
}, 20);

// Jidelna
setInterval(() => {
	fetchJidelna();
}, 3600000);
