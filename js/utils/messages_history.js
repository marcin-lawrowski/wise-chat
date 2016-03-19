/**
 * Wise Chat messages history utility. 
 * It uses Local Storage to store and retrieve historical chat messages typed by the current user.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 * @link http://kaine.pl/projects/wp-plugins/wise-chat
 */
function WiseChatMessagesHistory() {
	var LOCAL_STORAGE_KEY_MESSAGES_KEY = "WiseChatMessagesStack";
	var pointer = -1;
	var size = 50;
	
	this.getPointer = function() {
		return pointer;
	};
	
	this.resetPointer = function() {
		pointer = -1;
	};
	
	this.getCurrentSize = function() {
		var stack = getStack();
		
		if (stack !== null) {
			return stack.length;
		}
		
		return 0;
	};
	
	this.addMessage = function(message) {
		var stack = getStack();
		if (stack == null || typeof(stack) === "undefined") {
			stack = new Array();
		}
		if (stack.length >= size) {
			stack.shift();
		}
		stack.push(message);
		
		setStack(stack);
	};
	
	this.getPreviousMessage = function() {
		var stack = getStack();
		
		if (stack != null) {
			if (pointer < size - 1 && pointer < this.getCurrentSize() - 1) {
				pointer++;
			}
			
			var message = stack[(stack.length - 1) - pointer];
			
			return message;
		}
		
		return null;
	};
	
	this.getNextMessage = function() {
		var stack = getStack();
		
		if (stack != null) {
			if (pointer > 0) {
				pointer--;
			}
			
			var message = stack[(stack.length - 1) - pointer];
			
			return message;
		}
		
		return null;
	};
	
	function getStack() {
		if (typeof(Storage) !== "undefined") {
			var stack = window.localStorage.getItem(LOCAL_STORAGE_KEY_MESSAGES_KEY);
		
			if (stack != null) {
				return JSON.parse(stack);
			}
		}
		
		return null;
	};
	
	function setStack(stack) {
		if (typeof(Storage) !== "undefined") {
			window.localStorage.setItem(LOCAL_STORAGE_KEY_MESSAGES_KEY, JSON.stringify(stack));
		}
	};
};