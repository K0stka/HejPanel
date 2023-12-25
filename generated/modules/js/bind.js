function emitEvent(eventId) {
	$.ajax({
		type: "post",
		url: window.location.href,
		cache: "no-cache",
		data: {
			e: eventId,
		},
		success: function (result) {
			eval(result);
		},
		error: function (result) {
			API_MANAGER.errorHandlers.network_error.call(url, [], result);
		},
	});
}
