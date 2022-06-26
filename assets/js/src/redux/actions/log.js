import moment from 'moment';

export function log(level, content, details) {
	return {
		type: 'log.append',
		payload: {
			level: level,
			content: content,
			details: details,
			timestamp: moment().format('YYYY-MM-DD hh:mm:ss')
		}
	}
}

export function logInfo(content, details) {
	return log('info', content, details);
}

export function logError(content, details) {
	return log('error', content, details);
}

export function logDebug(level, content, details) {
	return log('debug', content, details);
}