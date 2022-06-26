import $ from "jquery";

/**
 * Adds support for "progress" and "progressUpload" events. Required for progress bars.
 */
export function installXhrProgressEvent() {
	const originalXhr = $.ajaxSettings.xhr;

	$.ajaxSetup({
		xhr: function() {
			const req = originalXhr.call($.ajaxSettings), that = this;

			if (req) {
				if (typeof req.addEventListener == "function" && that.progress !== undefined) {
					req.addEventListener("progress", function(evt) {
						that.progress(evt);
					}, false);
				}
				if (typeof req.upload == "object" && that.progressUpload !== undefined) {
					req.upload.addEventListener("progress", function(evt) {
						that.progressUpload(evt);
					}, false);
				}
			}
			return req;
		}
	});
}