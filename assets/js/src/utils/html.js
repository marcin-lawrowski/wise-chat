import $ from "jquery";

export function getAncestorBackgroundColor(element) {
	function isTransparent(bgcolor){
		return (bgcolor === "transparent" || bgcolor.substring(0,4) === "rgba");
	}

	let bgColor = element.css('background-color');
	if (isTransparent(bgColor)) {
		element.parents().each(function() {
			if (!isTransparent($(this).css('background-color'))){
				bgColor = $(this).css('background-color');
				return false;
			}
		});
	}

	return bgColor;
}