// IMPORTANT: SERVICEWORKER DISABLED IN DEV

// FALLBACK VALUES
let base_url = "";
let cacheName = "cache";
let cacheId = "";
let appShellFiles = [];

const appStaticFiles = ["/assets/pwa/index.php", "/assets/icons/icon.png", "/assets/icons/icon-192x192.png"]; // Cached on install in the format: prefix + i
const appDynamicFiles = ["/css/reset-fonts-transitions-dialog-index-phone.css", "/js/ajax-index-api-transitions-bind.js", "/assets/manifest.json"]; // Cached on install in the format: prefix + i ?v = cacheId

let allowCache = []; // Cached on first request in the format: prefix + i

const allowDirectories = ["/assets/", "/css/", "/external/", "/js/"]; // Cached on first request if url contains i

self.addEventListener("install", (e) => {
	base_url = new URL(location).searchParams.get("base_url");
	cacheId = new URL(location).searchParams.get("cacheId");
	cacheName = cacheName + cacheId;

	console.log("%c[SW " + cacheId + "] Installing service worker version " + cacheId, "color: orange");

	if (base_url.includes("localhost") || base_url.includes("192.168.137.1")) {
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
	if (base_url.includes("localhost") || base_url.includes("192.168.137.1")) return;

	if (e.request.method === "GET") {
		if (allowCache.includes(e.request.url) || allowDirectories.some((dir) => e.request.url.includes(dir)) || (!navigator.onLine && !base_url.includes("localhost"))) {
			e.respondWith(
				(async () => {
					const r = await caches.match(e.request.url); // CHECK CACHE AND RETURN IF FOUND
					if (r) {
						console.log("%c[SW " + cacheId + "] Returning from cache: " + e.request.url, "color: grey");
						return r;
					}
					if ((allowCache.includes(e.request.url) || allowDirectories.some((dir) => e.request.url.includes(dir))) && (navigator.onLine || base_url.includes("localhost"))) {
						// ADD TO CACHE IF SPECIFIED
						console.log("%c[SW " + cacheId + "] Caching new asset: " + e.request.url, "color: lime");
						const response = await fetch(e.request, { cache: "no-cache" });
						const cache = await caches.open(cacheName);
						cache.put(e.request.url, response.clone());
						return response;
					}
					if (!navigator.onLine) {
						// RETURN OFFLINE PAGE
						console.log("[SW " + cacheId + "] Returning offline page - " + e.request.url);
						return caches.match(base_url + "/assets/pwa/index.php");
					}
				})(),
			);
		}
	}
});

self.addEventListener("activate", (e) => {
	console.log("%c[SW " + cacheId + "] Activating service worker version " + cacheId, "color:pink");
	if (base_url.includes("localhost") || base_url.includes("192.168.137.1")) {
		self.clients.claim();
		return;
	}

	e.waitUntil(
		caches.keys().then((keyList) => {
			Promise.all(
				keyList.map((key) => {
					if (key == cacheName) return;
					console.log("%c[SW " + cacheId + "] Removing cache: " + key, "color:pink");
					return caches.delete(key);
				}),
			).then(() => {
				self.clients.claim();
				console.log("%c[SW " + cacheId + "] Activation completed", "color:pink");
			});
		}),
	);
});

self.addEventListener("push", (e) => {
	const notification = e.data.json();
	console.log(notification);

	// window.fadeTo(notification.url, 0);
	// window.createModal(notification.title, notification.body);

	e.waitUntil(
		self.registration.showNotification(notification.title, {
			body: notification.body,
			icon: base_url + "/assets/icons/icon-192x192.png",
			data: {
				notifURL: notification.url,
			},
		}),
	);
});

self.addEventListener("notificationclick", (e) => {
	e.waitUntil(clients.openWindow(e.notification.data.notifURL));
});
