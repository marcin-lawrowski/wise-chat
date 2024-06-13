import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { focusChannel, openChannel, stopIgnoringChannel, confirm, notify } from "actions/ui";
import { Scrollbar } from "react-scrollbars-custom";
import DirectChannel from "./components/DirectChannel";
import Counter from "ui/common/counter/Counter";

class FullChannelsMode extends React.Component {

	get MAX_PUBLIC_CHANNELS() {
		return 3;
	}

	constructor(props) {
		super(props);

		this.state = {
			highlighted: [],
			searchPhrase: ''
		}

		this.handleChannelClick = this.handleChannelClick.bind(this);
		this.handleSearchClear = this.handleSearchClear.bind(this);
		this.clearHighlighted = this.clearHighlighted.bind(this);
	}

	componentDidUpdate(prevProps) {
		const directChannelsChanged = this.props.directChannels !== prevProps.directChannels;

		if (directChannelsChanged) {
			const prevIds = prevProps.directChannels.filter( channel => channel.id && channel.online ).map( channel => channel.id );
			const currentIds = this.props.directChannels.filter( channel => channel.id && channel.online ).map( channel => channel.id );

			const newChannels = this.props.directChannels.filter( channel => !prevIds.includes(channel.id) && channel.online ).map( channel => channel.id );
			if (newChannels.length > 0) {
				this.props.notify('userJoined');
				if (this.props.configuration.notifications.userJoined.browserHighlight) {
					this.setState({highlighted: newChannels}, this.clearHighlighted);
				}
			}

			const absentChannels = prevProps.directChannels.filter( channel => !currentIds.includes(channel.id) && channel.online ).map( channel => channel.id );
			if (absentChannels.length > 0) {
				this.props.notify('userLeft');
				if (this.props.configuration.notifications.userLeft.browserHighlight) {
					this.setState({highlighted: absentChannels}, this.clearHighlighted);
				}
			}
		}
	}

	clearHighlighted() {
		setTimeout(function() { this.setState({ highlighted: [] }); }.bind(this), 5000)
	}

	handleChannelClick(channel) {
		if (this.props.ignoredChannels.includes(channel.id)) {
			this.props.confirm(this.props.i18n.ignoredInfo, function() {
				this.props.stopIgnoringChannel(channel.id);
				this.props.openChannel(channel.id);
				this.props.focusChannel(channel.id);
			}.bind(this));
		} else if (!channel.locked) {
			this.props.openChannel(channel.id);
			this.props.focusChannel(channel.id);
		}
	}

	handleSearchClear(e) {
		e.preventDefault();

		this.setState({ searchPhrase: '' });
	}

	getPublicChannelClasses(channel) {
		const classes = ["wcChannelTrigger"];
		if (channel.id === this.props.focusedChannel) {
			classes.push('wcFocusedChannel');
		}
		if (channel.protected === true && channel.authorized === false) {
			classes.push('wcLockedChannel');
		}
		if (channel.protected === true && channel.authorized === true) {
			classes.push('wcUnLockedChannel');
		}

		return classes.join(' ');
	}

	render() {
		const showPublicChannels = this.props.publicChannels.length > 1 && this.props.configuration.interface.chat.publicEnabled;

		return(
			<React.Fragment>
				{ showPublicChannels &&
					<div className="wcChannels wcPublicChannels">
						<span className="wcLabel">{ this.props.i18nBase.channels }</span>
						<div className="wcList" style={ { height: this.props.publicChannels.length <= this.MAX_PUBLIC_CHANNELS ? 'auto' : undefined } }>
							<Scrollbar native={ this.props.publicChannels.length <= this.MAX_PUBLIC_CHANNELS } noScrollX={ true }>
								{ this.props.publicChannels.map( channel =>
									<a
										key={ channel.id }
										href="#"
										onClick={ e => { e.preventDefault(); this.handleChannelClick(channel); } }
										className={ this.getPublicChannelClasses(channel) }
									>
										<span className="wcDetails">
											<span
												className="wcName"
												style={ { color: channel.textColor ? channel.textColor : undefined } }
											>
												{channel.name}
											</span>
										</span>
									</a>
								) }
							</Scrollbar>
						</div>
					</div>
				}
				{ this.props.directChannels.length > 0 &&
					<React.Fragment>
						<div className="wcChannels wcDirectChannels">
							{ this.props.publicChannels.length > 1 &&
								<span className="wcLabel">{ this.props.i18nBase.users }</span>
							}
							<div className="wcList">
								<Scrollbar noScrollX={ true }>
									{ this.props.directChannels.filter( channel => !this.state.searchPhrase || channel.name.match(new RegExp(this.state.searchPhrase, 'i'))).map( (channel, index) =>
										<React.Fragment key={ channel.id}>
											<DirectChannel channel={ channel } highlighted={ this.state.highlighted.includes(channel.id) } />
										</React.Fragment>
									) }
								</Scrollbar>
							</div>
						</div>
						<Counter />
						{ this.props.configuration.interface.browser.searchSubChannels &&
							<div className="wcFooter">
								<div className="wcSearch">
									<input
										type="text"
										placeholder={ this.props.i18n.subChannelsSearchHint }
										value={ this.state.searchPhrase }
										onChange={ (e) => this.setState({ searchPhrase: e.target.value })}
									/>
									{ this.state.searchPhrase &&
										<a href="#" className="wcClear wcFunctional" onClick={ this.handleSearchClear } />
									}
								</div>
							</div>
						}
					</React.Fragment>
				}
			</React.Fragment>
		)
	}

}

FullChannelsMode.defaultProps = {
	infoWindowPosition: "left center"
};

FullChannelsMode.propTypes = {
	configuration: PropTypes.object.isRequired,
	publicChannels: PropTypes.array.isRequired,
	ignoredChannels: PropTypes.array.isRequired,
	directChannels: PropTypes.array.isRequired,
	keepInside: PropTypes.string,
	infoWindowPosition: PropTypes.string.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		publicChannels: state.application.publicChannels,
		directChannels: state.application.directChannels,
		i18nBase: state.configuration.i18n,
		i18n: state.application.i18n,
		focusedChannel: state.ui.focusedChannel,
		ignoredChannels: state.ui.ignoredChannels
	}),
	{ focusChannel, openChannel, stopIgnoringChannel, confirm, notify }
)(FullChannelsMode);