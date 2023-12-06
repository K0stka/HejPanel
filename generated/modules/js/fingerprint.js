GET_FINGERPRINT().then((fp) => {
	API_MANAGER.schedule(
		new ApiTask(
			USER_API,
			"post",
			{
				type: "fingerprint",
				fingerprint: fp,
			},
			new ApiCallback(() => {}),
		),
	);
});
