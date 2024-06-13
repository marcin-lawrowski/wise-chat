import { requestChannelOpening } from "actions/ui";

/**
 * Installs external event listeners that dispatch actions on the store.
 * @param store
 */
export default function install(store) {
	installChatButton(store);
}

function installChatButton(store) {
	jQuery(".wise-chat-send-message").on('click', function(e) {
		e.preventDefault();
		store.dispatch(requestChannelOpening(jQuery(this).data('user-id')));
	});
}