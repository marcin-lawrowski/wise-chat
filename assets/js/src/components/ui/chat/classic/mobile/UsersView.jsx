import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import {Scrollbar} from "react-scrollbars-custom";
import DirectChannel from "components/ui/common/browser/components/DirectChannel";

class UsersView extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			searchPhrase: ''
		}

		this.handleSearchClear = this.handleSearchClear.bind(this);
	}

	handleSearchClear(e) {
		e.preventDefault();

		this.setState({ searchPhrase: '' });
	}

	render() {

		return(
			<div className="wcBrowser">
				<div className="wcChannels wcDirectChannels">
					<Scrollbar>
						{ this.props.directChannels.filter( channel => !this.state.searchPhrase || channel.name.match(new RegExp(this.state.searchPhrase, 'i'))).map( (channel, index) =>
							<DirectChannel key={ channel.id } channel={ channel } />
						) }
					</Scrollbar>
				</div>
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
			</div>
		)

	}

}

UsersView.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		directChannels: state.application.directChannels,
		i18n: state.application.i18n,
		focusedChannel: state.ui.focusedChannel
	})
)(UsersView);