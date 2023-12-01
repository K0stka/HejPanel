// Quality of life changes to vanilla JS
const objectFilter = (object, callback) => {
	return Object.fromEntries(Object.entries(object).filter(([key, val]) => callback(val, key)));
};

const objectForEach = (object, callback) => {
	Object.entries(object).forEach(([key, val]) => callback(key, val));
};

NodeList.prototype.forEach = Array.prototype.forEach;
FileList.prototype.map = Array.prototype.map;

// https://www.cssscript.com/accessible-modal-dialog-animations/
const createModal = (header, text) => {
	let noAnim = false;
	document.querySelectorAll("dialog").forEach((e) => {
		e.remove();
		noAnim = true;
	});

	const dialog = document.createElement("dialog");
	dialog.innerHTML = '<div class="dialog-close" onclick="closeModal(this.parentNode)">×</div><div class="dialog-header">' + header + '</div><div class="dialog-content">' + text + "</div>";

	dialog.addEventListener("click", (event) => {
		if (event.target === dialog) {
			closeModal(dialog);
		}
	});

	if (noAnim) dialog.classList.add("noAnim");

	document.body.appendChild(dialog);

	dialog.showModal();
};

const closeModal = (e = null) => {
	if (!e) e = document.querySelector("dialog");
	if (!e) return;

	e.classList.add("is-hidden");

	e.addEventListener("animationend", () => e.remove());
};

const focusLast = (input) => {
	input.focus();
	const val = input.value;
	input.value = "";
	input.value = val;
};

const onReady = () => {
	document.querySelectorAll("form, input").forEach((e) => {
		e.setAttribute("autocomplete", "off");
	});

	try {
		document.querySelector("#back").addEventListener("click", (e) => {
			window.history.go(-1);
			return false;
		});
	} catch (e) {}

	document.querySelectorAll("a").forEach((e) => {
		e.onclick = (event) => {
			// Prevent empty redirects
			event.preventDefault();
		};
	});

	document.querySelectorAll("a[data-hierarchy]").forEach((e) => {
		e.onclick = (event) => {
			navigate(e.href, e.getAttribute("data-hierarchy"), 0, 0, e.getAttribute("data-direction") ?? 0);
			event.preventDefault();
		};
	});

	document.querySelectorAll("resource").forEach(async (e) => {
		const type = e.getAttribute("data-type");
		const version = e.getAttribute("data-version");
		const contents = await (await fetch(base_url + "/" + type + "/" + e.getAttribute("data-modules") + "." + type + "?v=" + version)).text();
		if (type == "js") {
			eval(contents);
		} else {
			const style = document.createElement("style");
			style.innerHTML = contents;
			document.head.appendChild(style);
		}
		e.remove();
	});

	document.querySelectorAll("phpValuesHydrate").forEach((e) => {
		objectForEach(JSON.parse(e.innerHTML), (i, v) => {
			window[i] = v;
		});
		e.remove();
	});

	document.querySelectorAll("button").forEach((e) => {
		e.setAttribute("type", "button");
	});

	document.querySelectorAll("form").forEach((e) => {
		const inputs = e.querySelectorAll("input");

		const lastInput = inputs[inputs.length - 1];

		const submitBtn = e.querySelector("button");

		inputs.forEach((input, index) => {
			if (index == input.length - 1) return;
			input.onkeydown = (event) => {
				if (event.key === "Enter") focusLast(inputs[index + 1]);
			};
		});

		lastInput.onkeydown = (event) => {
			if (event.key === "Enter")
				if (submitBtn) {
					submitBtn.click();
				} else {
					e.submit();
				}
		};
	});
};

document.addEventListener("DOMContentLoaded", onReady);
