import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { setMobileTopTab, setMobileTitle, focusChannel, openChannel } from "actions/ui";
import Logger from "ui/common/logger/Logger";
import ChatsView from "./ChatsView";
import ChannelsView from "./ChannelsView";
import UsersView from "./UsersView";
import RecentView from "./RecentView";
import { capitalizeFirstLetter } from "utils/string";
import ChannelView from "./ChannelView";
import CustomizeView from "./CustomizeView";
import Toasts from "ui/common/toasts/Toasts";

class MobileChat extends React.Component {

	constructor(props) {
		super(props);

		this.handleTabClick = this.handleTabClick.bind(this);

		this.showFocusedChannel();

		if (!this.props.configuration.interface.chat.publicEnabled && this.isUsersTabVisible()) {
			this.props.setMobileTopTab('users');
		}
	}

	handleTabClick(tab) {
		this.props.setMobileTopTab(tab.slug)
		this.props.setMobileTitle(tab.name);
		this.props.focusChannel(undefined);
	}

	componentDidUpdate(prevProps) {
		const focusedChannelChanged = this.props.focusedChannel !== prevProps.focusedChannel && this.props.focusedChannel;
		if (focusedChannelChanged) {
			this.showFocusedChannel();
		}
	}

	showFocusedChannel() {
		const channel = this.props.channels.find( channelCandidate => channelCandidate.id === this.props.focusedChannel );
		if (channel) {
			this.props.setMobileTopTab('channel');
			this.props.setMobileTitle(channel.name);
		}
	}

	isUsersTabVisible() {
		return this.props.configuration.interface.browser.enabled;
	}

	render() {
		const topTabs = [
			{ slug: 'chats', name: this.props.i18nBase.chats, component: <ChatsView />, header: this.props.configuration.interface.chat.mobile.tabs.chats },
			{ slug: 'channels', name: this.props.i18nBase.channels, component: <ChannelsView />, header: this.props.configuration.interface.chat.publicEnabled },
			{ slug: 'users', name: this.props.i18nBase.users, component: <UsersView />, header: this.isUsersTabVisible() },
			{ slug: 'channel', name: this.props.i18nBase.channel, component: <ChannelView />, header: false }
		];

		if (this.props.configuration.interface.recent.enabled) {
			topTabs.push({ slug: 'recent', name: this.props.i18nBase.recent, component: <RecentView />, header: true, redCounter: this.props.recentChats.filter( recentChat => recentChat.read === false).length });
		}
		if (this.props.user && this.props.user.settings.allowCustomize) {
			topTabs.push({ slug: 'customize', name: '', component: <CustomizeView visible={ this.props.topTab === 'customize' } />, header: true },);
		}
		return(
			<React.Fragment>
				{ this.props.titleVisible && (this.props.titleOverride || this.props.configuration.interface.chat.title) &&
					<div className="wcTitle">
						{ [this.props.configuration.interface.chat.title, this.props.titleOverride].filter( title => title && title.length ).join(' - ') }
					</div>
				}
				<div className={ "wcTabs" + (topTabs.filter( tab => tab.header ).length > 3 ? ' wcTabsCompact' : '') }>
					{ topTabs.filter( tab => tab.header ).map( (tab, index) =>
						<div
							key={ index }
							className={ "wcTab wcTab" + capitalizeFirstLetter(tab.slug) + (this.props.topTab === tab.slug ? ' wcCurrent' : '') }
							onClick={ e => this.handleTabClick(tab) }
						>
							<span className="wcName">{ tab.name }</span>
							{ tab.redCounter > 0 && <span className="wcRedCounter">{ tab.redCounter }</span> }
						</div>
					)}
				</div>
				{ topTabs.map( (tab, index) =>
					<div key={ index } className={ "wcTabContent wcTabContent" + capitalizeFirstLetter(tab.slug) + (this.props.topTab !== tab.slug ? ' wcInvisible' : '') }>
						{ tab.component }
					</div>
				)}
				{this.props.configuration.debug &&
					<Logger/>
				}
				<Toasts />
			</React.Fragment>
		)
	}

}

MobileChat.propTypes = {
	configuration: PropTypes.object.isRequired,
	titleVisible: PropTypes.bool.isRequired,
};

MobileChat.defaultProps = {
	titleVisible: true
};

export default connect(
	(state) => ({
		i18nBase: state.configuration.i18n,
		configuration: state.configuration,
		topTab: state.ui.mobile.topTab,
		titleOverride: state.ui.mobile.title,
		channels: state.application.channels,
		focusedChannel: state.ui.focusedChannel,
		openedChannels: state.ui.openedChannels,
		user: state.application.user,
		recentChats: state.application.recentChats,
		publicChannels: state.application.publicChannels
	}),
	{ setMobileTopTab, setMobileTitle, focusChannel, openChannel }
)(MobileChat);