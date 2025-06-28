// IMPORTANT: SERVICEWORKER DISABLED IN DEV

// FALLBACK VALUES
const channel = new BroadcastChannel("notifications");

let IS_DISABLED = false;
let hidden = false;
let base_url = "";
let cacheName = "cache";
let cacheId = "";
let appShellFiles = [];

const appStaticFiles = ["/assets/icons/icon.png", "/assets/icons/icon512_maskable.png", "/assets/icons/icon512_rounded.png", "/assets/misc/Nunito-VariableFont_wght.ttf"]; // Cached on install in the format: prefix + i
const appDynamicFiles = ["/assets/pwa/index.php", "/css/reset-fonts-transitions-dialog-index-phone.css", "/js/ajax-util-index-api-transitions-bind-hunter.js", "/assets/manifest.json"]; // Cached on install in the format: prefix + i ?v = cacheId

let allowCache = []; // Cached on first request in the format: prefix + i

const allowDirectories = ["/assets/", "/css/", "/js/"]; // Cached on first request if url contains i

self.addEventListener("install", (e) => {
	const url = new URL(location);
	base_url = url.searchParams.get("base_url");
	cacheId = url.searchParams.get("cacheId");
	cacheName = cacheName + cacheId;

	IS_DISABLED = url.searchParams.get("enabled") != "true";

	console.log("%c[SW " + cacheId + "] Installing service worker version%c " + cacheId, "background: orange;color: black", "font-weight: bold");

	if (IS_DISABLED) {
		self.skipWaiting();
		return;
	}

	appShellFiles = appStaticFiles.map((i) => base_url + i).concat(appDynamicFiles.map((i) => base_url + i + "?v=" + cacheId));

	allowCache = allowCache.map((i) => base_url + i).concat(appShellFiles);

	e.waitUntil(
		caches.open(cacheName).then((cache) => {
			cache
				.addAll(appShellFiles)
				.then(() => {
					console.log("%c[SW " + cacheId + "] Cached shell files:" + appShellFiles.map((e) => "\n" + e), "color: lime");
					self.skipWaiting();
					console.log("%c[SW " + cacheId + "] Installation complete, waiting...", "color: lime");
				})
				.catch((e) => {
					console.log("%c[SW " + cacheId + "] Error, when trying to cache shell files:" + appShellFiles.map((e) => "\n" + e) + " -> " + e, "color: red");
				});
		}),
	);
});

self.addEventListener("fetch", (e) => {
	if (IS_DISABLED) return;

	if (e.request.method === "GET") {
		if (!navigator.onLine || allowCache.includes(e.request.url) || allowDirectories.some((dir) => e.request.url.includes(dir))) {
			e.respondWith(
				(async () => {
					const r = await caches.match(e.request.url); // CHECK CACHE AND RETURN IF FOUND
					if (r) {
						console.log("%c[SW " + cacheId + "] Returning from cache: " + e.request.url, "color: grey");
						return r;
					}
					if ((allowCache.includes(e.request.url) || allowDirectories.some((dir) => e.request.url.includes(dir))) && navigator.onLine) {
						// ADD TO CACHE IF SPECIFIED
						console.log("%c[SW " + cacheId + "] Caching new asset: " + e.request.url, "color: lime");
						const response = await fetch(e.request, { cache: "no-cache" });
						const cache = await caches.open(cacheName);
						cache.put(e.request.url, response.clone());
						return response;
					}
					if (!navigator.onLine && e.request.url == base_url + "/assets/manifest.json?v=" + cacheId) {
						// RETURN DEFAULT MANIFEST
						console.log("%c[SW " + cacheId + "] Returning default fallback manifest", "color: red");
						return new Response("{}");
					}
					if (!navigator.onLine) {
						// RETURN OFFLINE PAGE
						console.log("%c[SW " + cacheId + "] Returning offline page - " + e.request.url, "color: grey");
						const r = await caches.match(base_url + "/assets/pwa/index.php?v=" + cacheId);
						if (r) return r;
						return new Response("You are offline", { status: 404, statusText: "Offline" });
					}
				})(),
			);
		}
	}
});

self.addEventListener("activate", (e) => {
	console.log("%c[SW " + cacheId + "] Activating service worker version%c " + cacheId, "background: orange;color: black", "font-weight: bold");
	if (IS_DISABLED) {
		self.clients.claim();
		return;
	}

	e.waitUntil(
		caches.keys().then((keyList) => {
			Promise.all(
				keyList.map((key) => {
					if (key == cacheName) return;
					console.log("%c[SW " + cacheId + "] Removing cache:%c " + key, "color:red", "font-weight: bold");
					return caches.delete(key);
				}),
			).then(() => {
				self.clients.claim();
				console.log("%c[SW " + cacheId + "] Activation completed", "background: orange;color: black");
			});
		}),
	);
});

self.addEventListener("push", (e) => {
	const notification = e.data.json();

	if (hidden) {
		console.log("%c[SW " + cacheId + "] Push received & created NOTIFICATION:%c " + notification.title + " - " + notification.body, "background: orange;color: black", "font-weight: bold");

		e.waitUntil(
			self.registration.showNotification(notification.title, {
				body: notification.body,
				icon: base_url + "/assets/icons/icon-192x192.png",
				data: {
					notifURL: notification.url,
				},
			}),
		);
	} else {
		console.log("%c[SW " + cacheId + "] Push received & created MODAL:%c " + notification.title + " - " + notification.body, "background: orange;color: black", "font-weight: bold");

		channel.postMessage({ title: notification.title, body: notification.body, url: notification.url });
	}
});

self.addEventListener("notificationclick", (e) => {
	e.waitUntil(clients.openWindow(e.notification.data.notifURL));
});

self.addEventListener("message", (event) => {
	hidden = event.data.hidden;
});
