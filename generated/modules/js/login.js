const loginForm = document.querySelector("#login-form");

const nickname = document.querySelector("#nickname");
const password = document.querySelector("#password");

const loginBtn = document.querySelector("#submit");

if (loginBtn) {
	loginBtn.addEventListener("click", (e) => {
		if (VALIDATE_FORM(loginForm, "error")) {
			USER_API.post(
				{
					type: "login",
					nickname: nickname.value,
					password: password.value,
				},
				new ApiCallback(() => {
					fadeTo(base_url + "/", 0);
				}),
			);
		}
	});
}
