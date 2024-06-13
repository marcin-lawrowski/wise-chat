import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Channel from "ui/common/channel/Channel";
import { focusChannel } from "actions/ui";
import $ from "jquery";
import CloseButton from "ui/common/channel/components/CloseButton";

class TabbedController extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			tooManyTabs: false
		}

		this.tabsElement = React.createRef();
		this.handleTabClick = this.handleTabClick.bind(this);
		this.handleTabsChange = this.handleTabsChange.bind(this);
		this.handleResize = this.handleResize.bind(this);
		this.handleNavigateLeft = this.handleNavigateLeft.bind(this);
		this.handleNavigateRight = this.handleNavigateRight.bind(this);
	}

	componentDidMount() {
		window.addEventListener('resize', this.handleResize);
		this.handleTabsChange();
	}

	componentWillUnmount() {
		window.removeEventListener('resize', this.handleResize);
	}

	componentDidUpdate(prevProps) {
		const openedChannelsChange = this.props.openedChannels !== prevProps.openedChannels;

		if (openedChannelsChange) {
			this.handleTabsChange();
		}
	}

	handleResize() {
		this.handleTabsChange();
	}

	handleTabClick(e, channelId) {
		e.preventDefault();

		this.props.focusChannel(channelId);
	}

	handleTabsChange() {
		if (!this.tabsElement.current) {
			return;
		}
		if (this.isOverflown(this.tabsElement.current)) {
			if (!this.state.tooManyTabs) {
				this.setState({tooManyTabs: true});
			}
		} else {
			if (this.state.tooManyTabs) {
				this.setState({tooManyTabs: false});
			}
		}
	}

	handleNavigateLeft(e) {
		e.preventDefault();

		const element = $(this.tabsElement.current);

		if (element.scrollLeft() > 0) {
			element.scrollLeft(element.scrollLeft() - 20);
		}
	}

	handleNavigateRight(e) {
		e.preventDefault();

		const element = $(this.tabsElement.current);

		element.scrollLeft(element.scrollLeft() + 20);
	}

	isOverflown(element) {
		return element.scrollHeight > element.clientHeight || element.scrollWidth > element.clientWidth;
	}

	isTabNavigatorVisible() {
		const isSingleChannelMode = this.props.publicChannels.length === 1;

		if (!this.props.configuration.interface.browser.enabled) {
			return false;
		}

		if (isSingleChannelMode) {
			if (this.props.configuration.interface.chat.publicEnabled) {
				return this.getOpenedChannels().length > 1;
			}
		}

		return true;
	}

	getOpenedChannels() {
		return this.props.openedChannels
			.filter( channelId => this.props.channels.find( channel => channel.id === channelId && (this.props.configuration.interface.chat.publicEnabled || channel.type === 'direct')) )
			.map( channelId => this.props.channels.find( channel => channel.id === channelId) );
	}

	render() {
		const openedChannels = this.getOpenedChannels();

		return(
			<React.Fragment>
				{ this.isTabNavigatorVisible() &&
					<div className={ `wcTabsContainer ${this.state.tooManyTabs ? 'wcTabsTooMany' : ''}` }>
						<div className="wcTabs" ref={ this.tabsElement }>
							{ openedChannels.map( channel =>
								<div key={ channel.id } className={ "wcTab" + (channel.id === this.props.focusedChannel ? '  wcCurrent' : '') } onClick={ e => this.handleTabClick(e, channel.id) }>
									<span className="wcName">{ channel.name } { this.props.channelsUi[channel.id] && this.props.channelsUi[channel.id].unread > 0 ? <span className="wcUnread">*</span> : '' }</span>
									{ (this.props.publicChannels.length > 1 || channel.type === 'direct') && <CloseButton channel={channel} focusOnClose={ true } /> }
								</div>
							)}
						</div>
						<div className="wcTabsNav">
							<a href="#" onClick={ e => this.handleNavigateLeft(e) } className="wcLeft wcFunctional" />
							<a href="#" onClick={ e => this.handleNavigateRight(e) } className="wcRight wcFunctional" />
						</div>
					</div>
				}
				{ openedChannels.map( channel =>
					<div key={ channel.id } className={ "wcTabContent" + (channel.id !== this.props.focusedChannel ? ' wcInvisible' : '') }>
						<Channel channel={ channel } />
					</div>
				)}
				{ this.props.channels.length > 0 && !openedChannels.find( channel => channel.id === this.props.focusedChannel) &&
					<div className="wcTabContent wcTabContentEmpty">
						<span className="wcEmptyChannel">{ this.props.i18nBase.noChannels }</span>
					</div>
				}
			</React.Fragment>
		)
	}

}

TabbedController.propTypes = {
	configuration: PropTypes.object.isRequired,
	publicChannels: PropTypes.array.isRequired,
	channels: PropTypes.array.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		i18nBase: state.configuration.i18n,
		publicChannels: state.application.publicChannels,
		channels: state.application.channels,
		focusedChannel: state.ui.focusedChannel,
		openedChannels: state.ui.openedChannels,
		channelsUi: state.ui.channels
	}),
	{ focusChannel }
)(TabbedController);