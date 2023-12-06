const logoutBtn = document.querySelector("#logout");

if (logoutBtn) {
	logoutBtn.addEventListener("click", () => {
		USER_API.post(
			{
				type: "logout",
			},
			new ApiCallback(() => {
				fadeTo(base_url + "/login");
			}),
		);
	});
}
