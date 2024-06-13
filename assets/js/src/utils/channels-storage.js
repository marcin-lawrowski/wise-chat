/**
 * Channels storage utility.
 * It uses Local Storage to store currently opened and focused channels.
 */
export default class ChannelsStorage {

	constructor(instanceId) {
		if (!instanceId) {
			instanceId = 'c';
		}
		this.instanceId = instanceId;
		this.LOCAL_STORAGE_KEY = "WiseChatChannelsStorage";
	}

	/**
	 * Determines if the store was used before.
	 * @returns {boolean}
	 */
	isEmpty() {
		const data = this.getData();

		return data[this.instanceId] === undefined;
	}

	/**
	 * Convert channel IDs.
	 *
	 * @param {String} fromChannelId
	 * @param {String} toChannelId
	 */
	mapChannel(fromChannelId, toChannelId) {
		if (this.isFocused(fromChannelId)) {
			this.markFocused(toChannelId);
		}

		if (this.isOpen(fromChannelId)) {
			this.clear(fromChannelId);
			this.markOpen(toChannelId);
		}
	}

	/**
	 * Marks the channel as hidden.
	 *
	 * @param {String} channelId
	 */
	markHidden(channelId) {
		const data = this.getInstanceData();

		if (!data.hidden.includes(channelId)) {
			data.hidden.push(channelId);
		}

		this.saveInstanceData(data);
	}

	/**
	 * Check if the channel is hidden.
	 *
	 * @param {String} channelId
	 * @return {Boolean}
	 */
	isHidden(channelId) {
		const data = this.getInstanceData();

		return data.hidden.includes(channelId);
	}

	/**
	 * Clears hidden of the channel.
	 *
	 * @param {String} channelId
	 */
	unmarkHidden(channelId) {
		const data = this.getInstanceData();

		this.saveInstanceData({
			...data,
			hidden: data.hidden.filter( channelIdCandidate => channelIdCandidate !== channelId )
		});
	}

	/**
	 * Marks the channel as ignored.
	 *
	 * @param {String} channelId
	 */
	markIgnored(channelId) {
		const data = this.getInstanceData();

		this.saveInstanceData({
			...data,
			ignored: [...(data.ignored ? data.ignored : []), channelId]
		});
	}

	/**
	 * Marks the channel as focused.
	 *
	 * @param {String} channelId
	 */
	markFocused(channelId) {
		this.saveInstanceData({
			...this.getInstanceData(),
			focused: channelId
		});
	}

	/**
	 * Returns the focused channel.
	 *
	 * @return {String}
	 */
	getFocused() {
		const data = this.getInstanceData();

		return data.focused ? data.focused : null;
	}

	/**
	 * Marks the channel as open.
	 *
	 * @param {String} channelId
	 */
	markOpen(channelId) {
		const data = this.getInstanceData();

		this.saveInstanceData({
			...data,
			open: [...(data.open ? data.open : []), channelId]
		});
	}

	/**
	 * Removes the channel from all lists.
	 *
	 * @param {String} channelId
	 */
	clear(channelId) {
		const data = this.getInstanceData();

		// do not clear the ignored:
		this.saveInstanceData({
			...data,
			focused: data.focused === channelId ? undefined : data.focused,
			open: data.open.filter( channelIdCandidate => channelIdCandidate !== channelId ),
			hidden: data.hidden.filter( channelIdCandidate => channelIdCandidate !== channelId )
		});
	}

	/**
	 * Check if the channel is on the list.
	 *
	 * @param {String} channelId
	 * @return {Boolean}
	 */
	isOpen(channelId) {
		const data = this.getInstanceData();

		return data.open.includes(channelId);
	}

	/**
	 * Check if the channel is focused.
	 *
	 * @param {String} channelId
	 * @return {Boolean}
	 */
	isFocused(channelId) {
		const data = this.getInstanceData();

		return data.focused === channelId;
	}

	/**
	 * Check if the channel is on the ignored list.
	 *
	 * @param {String} channelId
	 * @return {Boolean}
	 */
	isIgnored(channelId) {
		return this.getInstanceData().ignored.includes(channelId);
	}

	/**
	 * Removes the channel from the ignored lists.
	 *
	 * @param {String} channelId
	 */
	clearIgnored(channelId) {
		const data = this.getInstanceData();

		this.saveInstanceData({
			...data,
			ignored: data.ignored.filter( channelIdCandidate => channelIdCandidate !== channelId )
		});
	}

	/**
	 * Returns opened channels.
	 *
	 * @return {Array}
	 */
	getOpenedChannels() {
		return this.getInstanceData().open;
	}

	/**
	 * Returns ignored channels.
	 *
	 * @return {Array}
	 */
	getIgnoredChannels() {
		return this.getInstanceData().ignored;
	}

	/**
	 * Returns hidden channels.
	 *
	 * @return {Array}
	 */
	getHiddenChannels() {
		return this.getInstanceData().hidden;
	}

	getInstanceData() {
		const data = this.getData();

		return data[this.instanceId] ? data[this.instanceId] : {
			open: [],
			focused: undefined,
			hidden: [],
			ignored: []
		};
	}

	saveInstanceData(instanceData) {
		const data = {
			...this.getData(), [this.instanceId]: instanceData
		};

		this.saveData(data);
	}

	getData() {
		let data = {};
		if (typeof(Storage) !== "undefined") {
			const encodedData = window.localStorage.getItem(this.LOCAL_STORAGE_KEY);
			if (encodedData !== null) {
				try {
					data = JSON.parse(encodedData);
				} catch (e) { }
			}
		}

		return data;
	}

	saveData(data) {
		if (typeof(Storage) !== "undefined") {
			window.localStorage.setItem(this.LOCAL_STORAGE_KEY, JSON.stringify(data));
		}
	}
}