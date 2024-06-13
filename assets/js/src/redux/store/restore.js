import { restoreChannels } from "actions/ui";

/**
 * Restores the store from the local storage.
 * @param store
 */
export default function restoreStore(store) {
	store.dispatch(restoreChannels());
}