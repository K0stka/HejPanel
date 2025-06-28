const auto_scale = () => {
	document.querySelectorAll(".object-fit-fill").forEach((e) => {
		const width = e.clientWidth;
		const height = e.clientHeight;

		const parentWidth = e.parentElement.clientWidth;
		const parentHeight = e.parentElement.clientHeight;

		e.style.transform = "scale(" + parentWidth / width + "," + parentHeight / height + ") ";
	});
};

window.addEventListener("resize", auto_scale);
window.addEventListener("onReady", auto_scale);
