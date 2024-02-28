/**
 * @typedef {Object} DepartureCacheEntry
 * @property {string} DepartureCacheEntry.id The id of the departure cache entry in the format SCHEDULED-NUMBER-DESTINATION
 * @property {Departure} DepartureCacheEntry.departure The departure object
 * @property {Date} DepartureCacheEntry.departing Date object storing the time the connection is departing at
 */
/**
 * @typedef {Object} Departure
 * @property {string} Departure.destination Where is the connection headed
 * @property {string} Departure.number Number of the line
 * @property {string} Departure.scheduled Time in the HH:MM format when the connection is scheduled to depart
 * @property {boolean} Departure.blinking Whether the connection is bound to depart in the next 5 minutes
 * @property {number} Departure.delay Delay of the connection in minutes & 0 if unknown
 */

/** @type {{ladova: Object.<string, DepartureCacheEntry>, natrati:Object.<string, DepartureCacheEntry>}} Simple in-memory cache storage */
let departures = {
	ladova: {},
	natrati: {},
};

/**
 * Safely transforms an unknown object into a DepartureCacheEntry.
 *
 * @param {Object} object Object that *should* be in the Departure format
 * @returns {?DepartureCacheEntry} The transformed DepartureCacheEntry object.
 */
const departureCacheEntryFactory = (object) => {
	const departureCacheEntry = {};
	departureCacheEntry.departure = {};
	departureCacheEntry.departure.destination = object.destination ?? "Budova C";
	departureCacheEntry.departure.number = object.number ?? "??";
	departureCacheEntry.departure.scheduled = object.scheduled ?? "00:00";
	departureCacheEntry.departure.blinking = object.blinking ?? false;
	departureCacheEntry.departure.delay = object.delay ?? 0;
	departureCacheEntry.id = `${departureCacheEntry.departure.scheduled}-${departureCacheEntry.departure.number}-${departureCacheEntry.departure.destination}`;
	try {
		const [hours, minutes] = departureCacheEntry.departure.scheduled.split(":").map((e) => parseInt(e));
		const now = new Date();
		departureCacheEntry.departing = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hours, minutes + departureCacheEntry.departure.delay, 0, 0);
		return departureCacheEntry;
	} catch (e) {
		return null;
	}
};

const fetchDepartures = () => {
	fetch("https://hejpanel-departures.102.nedomovi.net/")
		.then((response) => response.json())
		.then((data) => {
			data.ladova?.forEach((departure) => {
				const departureCacheEntry = departureCacheEntryFactory(departure);
				if (departures.ladova[departureCacheEntry.id]) {
					departures.ladova[departureCacheEntry.id].departing = departureCacheEntry.departing;
					departures.ladova[departureCacheEntry.id].departure.delay = departureCacheEntry.departure.delay;
					departures.ladova[departureCacheEntry.id].departure.blinking = departureCacheEntry.departure.blinking;
				} else {
					departureCacheEntry.departure.destination = departureCacheEntry.departure.destination.replaceAll(/,,? ?/g, ", ").replaceAll(/\. ?/g, ". ");
					departures.ladova[departureCacheEntry.id] = departureCacheEntry;
				}
			});
			departures.ladova = objectSort(departures.ladova, (a, b) => a.departing.getTime() - b.departing.getTime());
			data.natrati?.forEach((departure) => {
				const departureCacheEntry = departureCacheEntryFactory(departure);
				if (departures.natrati[departureCacheEntry.id]) {
					departures.natrati[departureCacheEntry.id].departing = departureCacheEntry.departing;
					departures.natrati[departureCacheEntry.id].departure.delay = departureCacheEntry.departure.delay;
					departures.natrati[departureCacheEntry.id].departure.blinking = departureCacheEntry.departure.blinking;
				} else {
					departureCacheEntry.departure.destination = departureCacheEntry.departure.destination.replaceAll(/,,? ?/g, ", ").replaceAll(/\. ?/g, ". ");
					departures.natrati[departureCacheEntry.id] = departureCacheEntry;
				}
			});
			departures.natrati = objectSort(departures.natrati, (a, b) => a.departing.getTime() - b.departing.getTime());
			updateDepartures();
		});
};

const updateDepartures = () => {
	const now = new Date();
	now.setSeconds(0);
	const nowTime = now.getTime();

	let display = 3;
	objectForEach(departures.ladova, (key, /** @type {DepartureCacheEntry} */ d) => {
		if (d.departing.getTime() < nowTime) {
			delete departures.ladova[d.id];
		} else if (display > 0) {
			hydrateDepartureRow(panelDeparturesElements.ladova[3 - display], d);
			display--;
		}
	});

	if (display > 0) for (i = display; i > 0; i--) resetDepartureRow(panelDeparturesElements.ladova[3 - i]);

	display = 3;
	objectForEach(departures.natrati, (key, /** @type {DepartureCacheEntry} */ d) => {
		if (d.departing.getTime() < nowTime) {
			delete departures.natrati[d.id];
		} else if (display > 0) {
			hydrateDepartureRow(panelDeparturesElements.natrati[3 - display], d);
			display--;
		}
	});

	if (display > 0) for (i = display; i > 0; i--) resetDepartureRow(panelDeparturesElements.natrati[3 - i]);
};

/**
 * Fill the departure info container with the info about the specified departure
 *
 * @param {HTMLDivElement} container Container to fill with the info
 * @param {DepartureCacheEntry} departureCacheEntry The departure info
 */
const hydrateDepartureRow = (container, departureCacheEntry) => {
	container.children[0].childNodes[0].textContent = departureCacheEntry.departure.scheduled;
	container.children[0].querySelector("span").innerText = departureCacheEntry.departure.delay != 0 ? "+" + departureCacheEntry.departure.delay + "" : "";
	container.children[1].innerHTML = `${
		departureCacheEntry.departure.number.length < 3 ? `<img src="${base_url}/assets/images/dpmo.webp">` : departureCacheEntry.departure.number.length < 4 ? "🚌" : `<img src="${base_url}/assets/images/cd.webp">`
	} ${departureCacheEntry.departure.number}`;
	container.children[2].innerText = departureCacheEntry.departure.destination;
};

/**
 * Empty out a departure info container
 *
 * @param {HTMLDivElement} element Container to empty
 */
const resetDepartureRow = (element) => {
	element.children[0].childNodes[0].textContent = "";
	element.children[0].querySelector("span").innerText = "";
	element.children[1].innerText = "";
	element.children[2].innerText = "";
};
