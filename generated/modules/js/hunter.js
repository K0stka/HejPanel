let hit = false;

GET_FINGERPRINT().then((fp) => {
	if (!fp.mobile) return;

	if (!fp.brands.some((b) => b.brand == "Brave")) return;

	hit = true;

	USER_API.nonBlockingPost(
		{
			h: fp,
		},
		null,
		null,
	);

	document.addEventListener("onReady", () => {
		if (!whitelisted) {
			GET_FINGERPRINT().then((fp) => {
				if (!fp.mobile) {
					hit = false;
					return;
				}

				if (!fp.brands.some((b) => b.brand == "Brave")) {
					hit = false;
					return;
				}

				hit = true;
			});
		}
	});
});
