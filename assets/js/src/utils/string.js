var $ = require("jquery");

export function uniqueId() {
	return Math.random().toString(36).substr(2, 9);
}

export function capitalizeFirstLetter(string) {
	return string.charAt(0).toUpperCase() + string.slice(1);
}

export function htmlDecode(value) {
  return $("<textarea/>").html(value).text();
}

function htmlEncode(value) {
  return $('<textarea/>').text(value).html();
}