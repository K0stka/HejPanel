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
	switch (panelType) {
		case "text":
			return '<div class="panel panel-text panel-hidden" id="panel-' + panelId + '">' + panelContent + "</div>";
		case "image":
			return '<div class="panel panel-image panel-hidden" id="panel-' + panelId + '"><img src="$prefix/contentAPI/' + panelContent + '"></div>';
		default:
			return '<div class="panel panel-text panel-hidden" id="panel-' + panelId + '">Invalid panel type: ' + panelType + "</div>";
	}
};

const updatePanels = async (panelIds) => {
	console.log("%cUpdating panels...", "color: gray");

	if (panels == panelIds) return;

	const panelIdsToAdd = panelIds.filter((id) => !panels.includes(id));
	if (panelIdsToAdd.length > 0) {
		const newPanels = (await PANEL_API.get({ t: "b", ids: panelIdsToAdd }, new ApiCallback(() => {}))).data;
		newPanels.forEach((panel) => {
			console.log("%cAdding panel " + panel.id, "color: lime");
			panels.push(panel.id);
			panelContainer.innerHTML += renderPanel(panel.id, panel.type, panel.content);
		});
	}

	const panelIdsToRemove = panels.filter((id) => !panelIds.includes(id));
	panels = panels.filter((id) => !panelIdsToRemove.includes(id));
	if (panelIdsToRemove.length > 0)
		panelIdsToRemove.forEach((e) => {
			console.log("%cRemoving panel " + e, "color: red");
			try {
				document.getElementById("panel-" + e).remove();
			} catch (e) {}
		});
};

const cyclePanels = () => {
	if (panels.length == 0) return;

	panelPointer = (panelPointer + 1) % panels.length;
	panelCounter.innerHTML = panelPointer + 1 + "/" + panels.length;
	panelContainer.children.forEach((e) => e.classList.remove("animate-in"));
	panelContainer.children[panelPointer].classList.add("animate-in");
};

let panels = ["loading"];
let panelPointer = 0;

if (panelContainer) {
	PANEL_API.get({ t: "c" }, false).then((panelIds) =>
		updatePanels(panelIds).then(() => {
			cyclePanels();
		}),
	);

	setInterval(() => {
		PANEL_API.get({ t: "c" }, false).then((panelIds) => updatePanels(panelIds).then(cyclePanels));
	}, 10000);
}
