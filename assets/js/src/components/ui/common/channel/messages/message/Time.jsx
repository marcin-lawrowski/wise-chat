import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import moment from "moment";

class Time extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			stopRender: false,
			date: undefined,
			time: undefined
		}

		this.formatElapsedDateAndTime = this.formatElapsedDateAndTime.bind(this);
		this.formatFullDateAndTime = this.formatFullDateAndTime.bind(this);
	}

	shouldComponentUpdate(nextProps, nextState, nextContext) {
		return !this.state.stopRender && this.props.serverTimeUTC !== null;
	}

	componentDidMount() {
		this.prepareDateAndTime();
	}

	componentDidUpdate(prevProps, prevState, snapshot) {
		const timeChanged = this.props.serverTimeUTC !== prevProps.serverTimeUTC && this.props.serverTimeUTC !== null;

		if (timeChanged && !this.state.stopRender) {
			this.prepareDateAndTime();
		}
	}

	prepareDateAndTime() {
		const mode = this.props.configuration.interface.message.timeMode;
		if (mode === 'hidden') {
			this.setState({ stopRender: true });
			return null;
		}

		const date = moment.utc(this.props.timeUTC, moment.ISO_8601);
		const nowDate = moment.utc(this.props.serverTimeUTC, moment.ISO_8601);

		if (mode === 'elapsed') {
			this.formatElapsedDateAndTime(date, nowDate);
		} else {
			this.formatFullDateAndTime(date, nowDate);
		}
	}

	formatFullDateAndTime(date, nowDate) {
		const timeFormat = this.props.configuration.interface.message.timeFormat;
		const dateFormat = this.props.configuration.interface.message.dateFormat;
		const localDate = date.local();
		const localNowDate = nowDate.local();

		let timeFormatted = timeFormat && timeFormat.length > 0
			? localDate.format(timeFormat)
			: localDate.format('LT');

		if (localDate.isSame(localNowDate, 'day')) {
			this.setState({ stopRender: true, time: timeFormatted });
		} else {
			let dateFormatted = dateFormat && dateFormat.length > 0
				? localDate.format(dateFormat)
				: localDate.format('L');
			this.setState({ stopRender: true, date: dateFormatted, time: timeFormatted });
		}
	}

	formatElapsedDateAndTime(date, nowDate) {
		const localDate = date.local();
		const localNowDate = nowDate.local();
		const ms = localNowDate.diff(localDate);
		const duration = moment.duration(ms);
		let diffSeconds = duration.asSeconds();
		const yesterdayDate = moment(localNowDate).subtract(1, 'days');
		const timeFormat = this.props.configuration.interface.message.timeFormat;
		const dateFormat = this.props.configuration.interface.message.dateFormat;

		let timeFormatted = timeFormat && timeFormat.length > 0
			? localDate.format(timeFormat)
			: localDate.format('LT');

		if (diffSeconds < 60) {
			if (diffSeconds <= 0) {
				diffSeconds = 1;
			}
			this.setState({ time: diffSeconds + ' ' + this.props.i18n.secAgo });
		} else if (diffSeconds < 60 * 60) {
			this.setState({ time: parseInt(diffSeconds / 60) + ' ' + this.props.i18n.minAgo });
		} else if (localDate.isSame(localNowDate, 'day')) {
			this.setState({ stopRender: true, time: timeFormatted });
		} else if (localDate.isSame(yesterdayDate, 'day')) {
			this.setState({ stopRender: true, time: this.props.i18n.yesterday + ' ' + timeFormatted });
		} else {
			let dateFormatted = dateFormat && dateFormat.length > 0
				? localDate.format(dateFormat)
				: localDate.format('L');
			this.setState({ stopRender: true, date: dateFormatted, time: timeFormatted });
		}
	}

	render() {
		return(
			<span className="wcTime">
				{this.state.date &&
					<span className="wcMessageTimeDate">{this.state.date}</span>
				}
				{this.state.time &&
					<span className="wcMessageTimeHour">{this.state.time}</span>
				}
			</span>
		)
	}

}

Time.propTypes = {
	configuration: PropTypes.object.isRequired,
	timeUTC: PropTypes.string.isRequired
};

export default connect(
	state => ({
		configuration: state.configuration,
		i18n: state.configuration.i18n,
		serverTimeUTC: state.application.heartbeat.nowTime
	})
)(Time);