let selectedImage = true;

const set_type_image = document.querySelector("#set-type-image");
const set_type_text = document.querySelector("#set-type-text");
const additional_settings = document.querySelector("#additional-settings");

const fileInput = document.createElement("input");
fileInput.type = "file";
fileInput.id = "file";

const input_label = document.createElement("div");
input_label.classList.add("input-label");

const label = document.createElement("label");
label.htmlFor = "text";
label.innerHTML = "Text, který se má zobrazit:";

const textInput = document.createElement("input");
textInput.id = "text";
textInput.addEventListener("change", (event) => {
	if (textInput.value != "") {
		textInput.setAttribute("not-empty", "");
	} else {
		textInput.removeAttribute("not-empty");
	}
});

input_label.appendChild(label);
input_label.appendChild(textInput);

const controlsToImage = () => {
	set_type_image.classList.remove("non-active");
	set_type_text.classList.add("non-active");
	set_type_image.classList.add("active");
	set_type_text.classList.remove("active");

	selectedImage = true;

	additional_settings.innerHTML = "";
	additional_settings.appendChild(fileInput);
};
const controlsToText = () => {
	set_type_image.classList.add("non-active");
	set_type_text.classList.remove("non-active");
	set_type_image.classList.remove("active");
	set_type_text.classList.add("active");

	selectedImage = false;

	additional_settings.innerHTML = "";
	additional_settings.appendChild(input_label);
};

set_type_image.addEventListener("click", controlsToImage);
set_type_text.addEventListener("click", controlsToText);

controlsToImage();
