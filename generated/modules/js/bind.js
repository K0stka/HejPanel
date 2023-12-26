function emitEvent(eventId) {
	if (API_MANAGER.apiBusy) {
		API_MANAGER.errorHandlers.notice.call("Tried to send a request but the API is already busy", { e: eventId }, window.location.href);
		return;
	}

	API_MANAGER.busy();
	$.ajax({
		type: "post",
		url: window.location.href,
		cache: "no-cache",
		data: {
			e: eventId,
		},
		success: function (result) {
			eval(result);
			API_MANAGER.free();
		},
		error: function (result) {
			API_MANAGER.errorHandlers.network_error.call(window.location.href, { e: eventId }, result);
			API_MANAGER.free(true);
		},
	});
}
