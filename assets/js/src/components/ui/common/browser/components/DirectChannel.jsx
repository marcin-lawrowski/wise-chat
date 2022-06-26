import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { confirm } from "actions/ui";

class DirectChannel extends React.Component {

	constructor(props) {
		super(props);

		this.handleChannelClick = this.handleChannelClick.bind(this);
		this.handleAvatarError = this.handleAvatarError.bind(this);
	}

	handleAvatarError(e) {
		e.target.src = this.props.configuration.baseDir + '/gfx/icons/user.png';
	}

	handleChannelClick(e) {
		if (this.props.channel.url) {
			return;
		}
		e.preventDefault();
	}

	render() {
		return(
			<a
				ref={ this.props.forwardedRef }
				href={ this.props.channel.url ? this.props.channel.url : '#'}
				onClick={ e => this.handleChannelClick(e) }
				target='_blank'
				rel='noopener noreferrer nofollow'
				className={ "wcChannelTrigger" + (this.props.highlighted ? ' wcAnimation wcAnimationFlash' : '') }
				onMouseEnter={ this.props.onMouseEnter }
				onMouseLeave={ this.props.onMouseLeave }
				onFocus={ this.props.onFocus }
				onBlur={ this.props.onBlur }
			>
				{this.props.configuration.interface.browser.status &&
					<React.Fragment>
						{this.props.channel.online === true && <span className="wcStatus wcOnline"/>}
						{this.props.channel.online === false && <span className="wcStatus wcOffline"/>}
					</React.Fragment>
				}
				{this.props.channel.avatar && <img src={ this.props.channel.avatar } onError={ this.handleAvatarError } className="wcFunctional wcAvatar" alt={this.props.channel.name}/> }
				<span className="wcDetails">
						<span
							className="wcName"
							style={ { color: this.props.channel.textColor ? this.props.channel.textColor : undefined } }
						>
							{this.props.channel.name}
						</span>
					{this.props.channel.countryFlagSrc && <img src={ this.props.channel.countryFlagSrc } className="wcFunctional wcCountryFlag" alt={this.props.channel.countryCode}/> }
					{this.props.channel.city && <span className="wcCity">{this.props.channel.city}</span> }
					{this.props.channel.countryCode && <span className="wcCountry">{this.props.channel.countryCode}</span> }
				</span>
			</a>
		)
	}

}

DirectChannel.propTypes = {
	channel: PropTypes.object.isRequired,
	highlighted: PropTypes.bool
};

const ConnectedDirectChannel = connect(
  (state) => ({
		configuration: state.configuration,
		i18n: state.application.i18n,
		ignoredChannels: state.ui.ignoredChannels,
	}),
	{ confirm }
)(DirectChannel);

export default React.forwardRef(({ open, ...props }, ref) => (
	<ConnectedDirectChannel {...props} forwardedRef={ref} />
));