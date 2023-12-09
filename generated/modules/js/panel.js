const auto_scale = () => {
	document.querySelectorAll(".auto-scale").forEach((e) => {
		const width = e.clientWidth;

		const parentWidth = e.parentElement.clientWidth;
		const parentHeight = e.parentElement.clientHeight;
		const targetWidth = e.getAttribute("data-target-width");

		// e.style.height = (width * parentHeight) / parentWidth + "px";

		e.style.transform = "scale(" + (parentWidth * targetWidth) / 100 / width + ")";

		const boundingRect = e.getBoundingClientRect();
		const parentBoundingRect = e.parentElement.getBoundingClientRect();

		let eTop = e.style.top.replace("px", "");
		eTop = parseFloat(eTop == "" ? "0" : eTop);

		let eLeft = e.style.left.replace("px", "");
		eLeft = parseFloat(eLeft == 0 ? "0" : eLeft);

		e.style.top = (boundingRect.top - eTop - parentBoundingRect.top) * -1 + "px";
		e.style.left = (boundingRect.left - eLeft - parentBoundingRect.left) * -1 + "px";
	});
};

window.addEventListener("resize", auto_scale);
auto_scale();
