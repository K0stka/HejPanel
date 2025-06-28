const registerForm = document.querySelector("#login-form");

const name = document.querySelector("#name");
const nickname = document.querySelector("#nickname");
const password = document.querySelector("#password");
const code = document.querySelector("#code");

const registerBtn = document.querySelector("#submit");

if (registerBtn) {
	registerBtn.addEventListener("click", (e) => {
		if (VALIDATE_FORM(registerForm, "error")) {
			USER_API.post(
				{
					type: "register",
					name: name.value,
					nickname: nickname.value,
					password: password.value,
					code: code.value,
				},
				new ApiCallback(() => {
					fadeTo(base_url + "/", 0);
				}),
			);
		}
	});
}
