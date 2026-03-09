import React from "react";
import { connect } from "react-redux";
import {Scrollbar} from "react-scrollbars-custom";
import { focusChannel, openChannel, alertError, stopIgnoringChannel, confirm } from "actions/ui";
import { markRecentChatRead } from "actions/application";
import { sendUserCommand, clearUserCommand } from "actions/commands";
import Time from "ui/common/channel/messages/message/Time";
import Decorator from "ui/common/channel/messages/message/Decorator";
import PropTypes from "prop-types";

class RecentArea extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			tab: 'messages'
		}

		this.handleClick = this.handleClick.bind(this);
		this.handleAvatarError = this.handleAvatarError.bind(this);
	}

	handleAvatarError(e) {
		e.target.src = this.props.configuration.baseDir + '/gfx/icons/user.png';
	}

	componentDidMount() {
		this.props.clearUserCommand('recent');
	}

	componentDidUpdate(prevProps) {
		const commandFailure = this.props.command !== prevProps.command && this.props.command && this.props.command.success === false;

		if (commandFailure) {
			this.props.alertError(this.props.command.error);
			this.props.clearUserCommand('recent');
		}
	}

	handleClick(recentChat) {
		if (this.props.ignoredChannels.includes(recentChat.channel.id)) {
			this.props.confirm(this.props.i18n.ignoredInfo, function () {
				this.props.stopIgnoringChannel(recentChat.channel.id);
				this.openRecentChat(recentChat);
			}.bind(this));
		} else {
			this.openRecentChat(recentChat);
		}
	}

	openRecentChat(recentChat) {
		this.props.openChannel(recentChat.channel.id);
		this.props.focusChannel(recentChat.channel.id);

		if (!recentChat.read) {
			this.props.markRecentChatRead(recentChat.channel.id);
			this.props.sendUserCommand('recent', 'markChannelAsRead', {channel: recentChat.channel.id});
		}

		if (this.props.onClick) {
			this.props.onClick(recentChat);
		}
	}

	renderEntry(recentChat, index, array) {
		return <div key={ recentChat.channel.id } className={ 'wcRecent ' + (recentChat.read ? 'wcRead' : 'wcUnread' ) + (index === 0 ? ' wcFirst' : '') + (index + 1 === array.length ? ' wcLast' : '') } onClick={ e => this.handleClick(recentChat) }>
			{ this.props.configuration.interface.recent.status &&
				<React.Fragment>
					{ recentChat.channel.online === true
						? (<span className="wcStatus wcOnline" />)
						: (<span className="wcStatus wcOffline" />)
					}
				</React.Fragment>
			}

			{recentChat.channel.avatar &&
				<img src={recentChat.channel.avatar} onError={ this.handleAvatarError } className="wcFunctional wcRecentChatAvatar" alt={recentChat.channel.name} />
			}

			<div className="wcRight">
				<div className="wcHead">
					<span className="wcName">{ recentChat.channel.name }</span>
					<Time timeUTC={ recentChat.timeUTC } />
				</div>
				{ this.props.configuration.interface.recent.excerpts &&
					<span className="wcText"><Decorator>{ recentChat.text }</Decorator></span>
				}
			</div>
		</div>
	}

	switchTab(e, tab) {
		e.preventDefault();
		this.setState({tab: tab});
	}

	render() {
		if (!this.props.configuration.interface.recent.enabled) {
			return null;
		}

		const feedTotal = this.props.userFeed.length;
		const unseenFeedTotal = this.props.userFeed.filter( userFeedEntry => !userFeedEntry.seen ).length;
		const unreadChats = this.props.recentChats.filter( recentChat => !recentChat.read );
		const chatArchive = this.props.recentChats.filter( recentChat => recentChat.read );

		return(
			<div className="wcRecentChats">
				{ feedTotal > 0 &&
					<div className="wcPrimaryTabs">
						<li className="wcPrimaryTabItem">
							<a className={ `wcPrimaryTabLink ${ this.state.tab === 'messages' ? 'wcPrimaryTabItemActive' : '' }` } onClick={ e => this.switchTab(e, 'messages') } href="#">{ unreadChats.length > 0 && <span className="wcRedCounter">{ unreadChats.length }</span> } { this.props.i18n.recentChats }</a>
						</li>
						<li className="wcPrimaryTabItem">
							<a className={ `wcPrimaryTabLink ${ this.state.tab === 'feed' ? 'wcPrimaryTabItemActive' : '' }` } onClick={ e => this.switchTab(e, 'feed') } href="#">{ unseenFeedTotal > 0 && <span className="wcRedCounter">{ unseenFeedTotal }</span> } { this.props.i18n.notifications }</a>
						</li>
					</div>
				}

				<div className={ 'wcRecentContainer' + (this.state.tab !== 'messages' ? ' wcInvisible' : '') }>
					{ this.props.recentChats.length > 0 ? (
						<Scrollbar scrollTop={ 0 }>
							{ unreadChats.length > 0 && <div className="wcHeader wcUnreadMessages">{ this.props.i18n.unreadMessages } </div> }
							{ unreadChats.map( (recentChat, index, array) => this.renderEntry(recentChat, index, array) ) }
							{ chatArchive.length > 0 && <div className="wcHeader wcUnreadMessages">{ this.props.i18n.messagesArchive } </div> }
							{ chatArchive.map( (recentChat, index, array) => this.renderEntry(recentChat, index, array) ) }
						</Scrollbar>
					):(
						<span className="wcNoRecent">{ this.props.i18n.noRecentChats }</span>
					)}
				</div>
			</div>
		);
	}
}

RecentArea.propTypes = {
	onClick: PropTypes.func
};

export default connect(
	(state) => ({
		i18n: state.application.i18n,
		configuration: state.configuration,
		channels: state.application.channels,
		recentChats: state.application.recentChats,
		userFeed: state.application.userFeed,
		command: state.commands.sent.recent,
		ignoredChannels: state.ui.ignoredChannels
	}),
	{ focusChannel, openChannel, alertError, sendUserCommand, clearUserCommand, markRecentChatRead, stopIgnoringChannel, confirm }
)(RecentArea);