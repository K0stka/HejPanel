const panelTime = document.querySelector("#panel-time");

if (panelTime) {
	panelTime.innerHTML = new Date().toLocaleTimeString();
	setInterval(() => {
		panelTime.innerHTML = new Date().toLocaleTimeString();
	}, 1000);
}

const panelContainer = document.querySelector("#panel-container");
const panelCounter = document.querySelector("#panel-counter");

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
			panel.innerHTML = '<img src="$prefix/contentAPI/' + panelContent + '">';
			break;
		default:
			panel.classList.add("panel-text");
			panel.innerHTML = "Invalid panel type: " + panelType;
	}
	return panel;
};

const updatePanels = async (newPanelIds) => {
	if (areSameSet(panelIds, newPanelIds)) return;

	console.log("%cUpdating panels...", "color: gray");

	const panelIdsToAdd = newPanelIds.filter((id) => !panelIds.includes(id));
	if (panelIdsToAdd.length > 0) {
		const newPanels = (await PANEL_API.get({ t: "b", ids: panelIdsToAdd }, new ApiCallback(() => {}))).data;
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
					return false;
				}
				return true;
			});
			if (removedPanelPointer <= panelPointer) {
				panelPointer--;
				console.log("Panel pointer slip");
			}

			panels = panels.filter((p) => p.id != id);

			let panel = document.getElementById("panel-" + id);
			panel.classList.add("panel-fade-out");
			panel.addEventListener("animationend", () => {
				panel.remove();
			});
		});
	}
};

const cyclePanels = () => {
	if (panels.length <= 1) return;

	panelPointer = (panelPointer + 1) % panels.length;

	panelCounter.innerHTML = panelPointer + 1 + "/" + panels.length;

	panels.forEach((e) => e.element.classList.remove("animate-in"));

	panels[panelPointer].element.classList.add("animate-in");
};

let panelPointer = 0;
let panelIds = ["loading"];
let panels = [
	{
		id: "loading",
		element: document.querySelector("#panel-loading"),
	},
];

if (panelContainer) {
	PANEL_API.get({ t: "c" }, false).then((newPanelIds) => updatePanels(newPanelIds).then(cyclePanels));

	setInterval(() => {
		PANEL_API.get({ t: "c" }, false).then((newPanelIds) => updatePanels(newPanelIds).then(cyclePanels));
	}, 10000);
}
