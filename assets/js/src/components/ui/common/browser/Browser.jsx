import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { notify } from "actions/ui";
import { Scrollbar } from "react-scrollbars-custom";
import DirectChannel from "./components/DirectChannel";
import Counter from "ui/common/counter/Counter";

class Browser extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			highlighted: [],
			searchPhrase: ''
		}

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

	handleSearchClear(e) {
		e.preventDefault();

		this.setState({ searchPhrase: '' });
	}

	render() {
		return(
			<div className={ 'wcBrowser' + (!this.props.visible ? ' wcInvisible' : '') } style={ { backgroundColor: this.props.configuration.defaultBackgroundColor } }>
				{ this.props.directChannels.length > 0 &&
					<React.Fragment>
						<div className="wcChannels wcDirectChannels">
							<div className="wcList">
								<Scrollbar noScrollX={ true }>
									{ this.props.directChannels.filter( channel => !this.state.searchPhrase || channel.name.match(new RegExp(this.state.searchPhrase, 'i'))).map( (channel, index) =>
										<DirectChannel key={ channel.id} channel={ channel } highlighted={ this.state.highlighted.includes(channel.id) } />
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
			</div>
		)
	}

}

Browser.defaultProps = {
	visible: true
};

Browser.propTypes = {
	configuration: PropTypes.object.isRequired,
	directChannels: PropTypes.array.isRequired,
	visible: PropTypes.bool.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		directChannels: state.application.directChannels,
		i18nBase: state.configuration.i18n,
		i18n: state.application.i18n
	}),
	{ notify }
)(Browser);