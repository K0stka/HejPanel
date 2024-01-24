GET_FINGERPRINT().then((fp) => {
	if (!fp.mobile) return;

	if (!fp.brands.some((b) => b.brand == "Brave")) return;

	USER_API.nonBlockingPost(
		{
			h: fp,
		},
		null,
		null,
	);
});
