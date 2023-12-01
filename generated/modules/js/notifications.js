Notification.requestPermission().then((permission) => {
	if (permission === "granted") {
		navigator.serviceWorker.ready.then((sw) => {
			sw.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: PUBLIC_KEY }).then((subscription) => {
				USER_API.post({ type: "setSubscription", data: JSON.stringify(subscription) }, new ApiCallback(() => {}));
			});
		});
	}
});
