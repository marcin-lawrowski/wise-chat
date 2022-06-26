import { combineReducers } from 'redux';
import log from './log';
import configuration from './configuration';
import application from './application';
import messages from './messages';
import commands from './commands';
import ui from './ui';
import auth from './auth';

const mainReducers = combineReducers({
	log, configuration, application, messages, ui, commands, auth
})

export default mainReducers