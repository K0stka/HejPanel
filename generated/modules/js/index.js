// https://www.cssscript.com/accessible-modal-dialog-animations/
const createModal = (header, text, disableClose = false) => {
	let noAnim = false;
	document.querySelectorAll("dialog").forEach((e) => {
		e.remove();
		noAnim = true;
	});

	const dialog = document.createElement("dialog");
	dialog.innerHTML = (disableClose ? "" : '<div class="dialog-close" onclick="closeModal(this.parentNode)">×</div>') + '<div class="dialog-header">' + header + '</div><div class="dialog-content">' + text + "</div>";

	if (!disableClose) {
		dialog.addEventListener("click", (event) => {
			if (event.target === dialog) {
				closeModal(dialog);
			}
		});
	} else {
		dialog.addEventListener("cancel", (event) => {
			event.preventDefault();
		});
	}

	if (noAnim) dialog.classList.add("noAnim");

	document.body.appendChild(dialog);

	dialog.showModal();

	return dialog;
};

const createPersistentModal = (header, text) => {
	const dialog = document.querySelector("dialog[open]") ?? createModal(header, text, true);

	dialog.querySelector(".dialog-header").innerHTML = header;
	dialog.querySelector(".dialog-content").innerHTML = text;
};

const closeModal = (e = null) => {
	if (!e) e = document.querySelector("dialog");
	if (!e) return;

	e.classList.add("is-hidden");

	let event = new CustomEvent("close", { detail: {} });

	e.addEventListener("animationend", () => {
		if (event)
			// Was triggering twice for some reason?
			e.dispatchEvent(event);
		event = null;
		e.remove();
	});
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

	document.querySelectorAll("input").forEach((e) => {
		e.addEventListener("input", (event) => {
			e.classList.remove("error");

			if (e.value != "") {
				e.setAttribute("not-empty", "");
			} else {
				e.removeAttribute("not-empty");
			}
		});
	});

	document.querySelectorAll(".auto-color").forEach((e) => {
		const color = JSON.parse(e.computedStyleMap().get("background-color").toString().replace("rgb(", "[").replace("rgba(", "[").replace(")", "]"));

		const c = [color[0] / 255, color[1] / 255, [2] / 255].map((col) => {
			if (col <= 0.03928) {
				return col / 12.92;
			}
			return Math.pow((col + 0.055) / 1.055, 2.4);
		});

		var L = 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];

		e.style.color = L > 0.179 ? "var(--text)" : "var(--background)";
	});

	document.querySelectorAll("a").forEach((e) => {
		e.onclick = (event) => {
			navigate(e.href);
			event.preventDefault();
		};
	});

	document.querySelectorAll("*[safe-href]").forEach((e) => {
		e.onclick = (event) => {
			createModal("Pozor, klikáte na potencionálně nebezpečný odkaz", "Odkaz, na který Vás přesměrujeme nepochází od autora stránky, pokračujete na vlastní nebezpečí.<br>URL: <b>" + e.getAttribute("safe-href") + "</b><br><br><button onclick=\"fadeTo('" + e.getAttribute("safe-href") + "');\">Pokračovat</button>");
			event.preventDefault();
		};
	});

	document.querySelectorAll("resource").forEach(async (e) => {
		const type = e.getAttribute("data-type");
		const version = e.getAttribute("data-version");

		const url = base_url + "/" + type + "/" + e.getAttribute("data-modules") + "." + type + "?v=" + version;

		const contents = await (await fetch(url)).text();

		if (type == "js") {
			try {
				eval(contents);
			} catch (e) {
				API_MANAGER.errorHandlers.server_error.call({ status: "JS ERROR", responseText: contents } ?? "", "[...]", url);
				console.trace(e);
			}
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
		const inputs = e.querySelectorAll("input, textarea");

		const lastInput = inputs[inputs.length - 1];

		const btns = e.querySelectorAll("button");
		const submitBtn = btns[btns.length - 1];

		inputs.forEach((input, index) => {
			if (index == inputs.length - 1) return;
			input.addEventListener("keydown", (event) => {
				if (event.key === "Enter" && (input.tagName == "INPUT" || event.ctrlKey)) focusLast(inputs[index + 1]);
			});
		});

		lastInput.addEventListener("keydown", (event) => {
			if (event.key === "Enter" && (lastInput.tagName == "INPUT" || event.ctrlKey))
				if (submitBtn) {
					submitBtn.click();
				} else {
					e.submit();
				}
		});
	});

	document.querySelectorAll("*[bind]").forEach((e) => {
		e.addEventListener("click", function () {
			emitEvent(e.getAttribute("bind"));
		});
	});

	let event = new CustomEvent("onReady", { detail: {} });
	window.dispatchEvent(event);
};

const onFinished = () => {
	let event = new CustomEvent("onFinished", { detail: {} });
	window.dispatchEvent(event);
};

window.addEventListener("onReady", () => {
	document.querySelector(".hamburger")?.addEventListener("click", function () {
		const nav = document.querySelector("nav");

		if (!nav) return;

		if (nav.classList.contains("visible")) nav.classList.remove("visible");
		else nav.classList.add("visible");
	});

	document.querySelector(".logo")?.addEventListener("click", () => fadeTo(base_url + "/"));
});
document.addEventListener("DOMContentLoaded", onReady);
