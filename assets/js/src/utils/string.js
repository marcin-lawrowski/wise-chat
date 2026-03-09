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


export function sprintf() {
  // arguments
  var args = Array.prototype.slice.call(arguments)
    // parameters for string
  , n = args.slice(1, -1)
    // string
  , text = args[0] ?? ''
    // check for `Number`
  , _res = isNaN(parseInt(args[args.length - 1]))
             ? args[args.length - 1]
               // alternatively, if string passed
               // as last argument to `sprintf`,
               // `eval(args[args.length - 1])`
             : Number(args[args.length - 1])
    // array of replacement values
  , arr = n.concat(_res)
    // `res`: `text`
  , res = text;
  // loop `arr` items
  for (var i = 0; i < arr.length; i++) {
    // replace formatted characters within `res` with `arr` at index `i`
    res = res.replace(/%d|%s/, arr[i])
  }
  // return string `res`
  return res
};