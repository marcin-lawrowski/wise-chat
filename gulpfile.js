var gulp         = require('gulp');
var autoprefixer = require('gulp-autoprefixer');
var babel        = require('gulp-babel');
var concat       = require('gulp-concat');
var eslint       = require('gulp-eslint');
var uglify		 = require('gulp-uglify-es').default;
var rename		 = require('gulp-rename');
var notify       = require('gulp-notify');
var plumber      = require('gulp-plumber');
var sass         = require('gulp-sass');
var sourcemaps   = require('gulp-sourcemaps');
var cleanCSS	 = require('gulp-clean-css');
var buffer 		 = require('vinyl-buffer');
var browserify   = require('browserify');
var exorcist     = require('exorcist');
var babelify 	 = require('babelify');
var source		 = require('vinyl-source-stream');
var watchify     = require("watchify");
var livereload   = require('gulp-livereload');

var onError = function(err) {
	notify.onError({
		title:    "Error",
		message:  "<%= error %>",
	})(err);
	this.emit('end');
};

var plumberOptions = {
	errorHandler: onError,
};

const vendors = [
	'jquery', 'bootstrap', 'react', 'react-dom', 'prop-types', 'react-redux', 'react-google-charts', 'react-router', 'react-router-dom',
	'react-select', 'react-bootstrap', 'react-datepicker', 'redux', 'redux-thunk', 'ckeditor4-react', 'qs', 'moment'
];

var jsFiles = {
	vendor: [

	],
	main: 'assets/js/src/index.jsx',
	source: [
		'assets/js/src/**/*.js',
		'assets/js/src/**/*.jsx'
	]
};

var cssFiles = {
	vendor: [
		'node_modules/simplebar/dist/simplebar.min.css',
		'node_modules/reactjs-popup/dist/index.css'
	],
	source: [
		'assets/css/src/**/*.scss'
	]
};

// lint JS/JSX files:
gulp.task('eslint', function() {
	return gulp.src(jsFiles.source)
		.pipe(eslint({
			baseConfig: {
				"parserOptions": {
					"ecmaVersion": 2018,
					"sourceType": "module",
					"ecmaFeatures": {
						"jsx": true,
						"modules": true
					}
				}
			}
		}))
		.pipe(eslint.format())
		.pipe(eslint.failAfterError());
});

gulp.task("build-sources-dev", function() {
	var args = watchify.args;
	args.extensions = ['.js', '.jsx'];

	var bundler = watchify(browserify({
		entries: jsFiles.main,
		paths: ['./node_modules', './assets/js/src/redux', './assets/js/src/components', './assets/js/src'],
		extensions: ['.jsx', '.js'],
		debug: true,
		detectGlobals: true,
		cache: {}, packageCache: {}
	}), args);

	bundler.transform(babelify.configure({
		presets : ["@babel/preset-env", "@babel/preset-react"],
		plugins : ["@babel/plugin-transform-runtime"]
	}));

	function logInfo(text) {
		console.log('[' + new Date().toISOString().match(/(\d{2}:){2}\d{2}/)[0] + '] ' + text);
	}

	let bundle = function() {
		var start = Date.now();
		logInfo('Rebundling ...');

		return bundler.bundle()
			.on("error", err => {
				console.log(err.message);
			})
			.on('end', function() {
				logInfo('Finished rebundling in ' + (Date.now() - start) + 'ms.');
			})
			.pipe(exorcist('assets/js/wise-chat.js.map'))
			.pipe(source('wise-chat.js'))
			.pipe(buffer())
			.pipe(gulp.dest('assets/js' ))
			.pipe(livereload());
	}

	bundler.on("update", bundle);

	return bundle();
})

// gather all source files, combine them into single file and minified file
// TODO: include source map together with the minified file
gulp.task('build-sources-prod', function() {
	process.env.NODE_ENV = 'production';

	return browserify({
			entries: jsFiles.main,
			paths: ['./node_modules', './assets/js/src/redux', './assets/js/src/components', './assets/js/src'],
			extensions: ['.jsx', '.js'],
			debug: true,
			detectGlobals: true,
			cache: {}, packageCache: {}
		})
		.transform(babelify.configure({
			presets : ["@babel/preset-env", "@babel/preset-react"],
			plugins : ["@babel/plugin-transform-runtime"]
		}))
		.bundle()
		.on('error', err => {
			console.log(err.message);
		})
		.pipe(exorcist('assets/js/wise-chat.js.map'))
		.pipe(source('wise-chat.js'))
		.pipe(buffer())
		.pipe(gulp.dest('assets/js' ))
		.pipe(uglify())
		.pipe(rename( { suffix: '.min' }))
		.pipe(gulp.dest( 'assets/js' ));
});

// compile Sass to CSS
gulp.task('sass', function() {
	var autoprefixerOptions = {
		overrideBrowserslist: ['last 2 versions'],
	};

	var sassOptions = {
		includePaths: [

		]
	};

	return gulp.src(cssFiles.source)
		.pipe(plumber(plumberOptions))
		.pipe(sourcemaps.init())
		.pipe(sass(sassOptions))
		.pipe(autoprefixer(autoprefixerOptions))
		.pipe(concat('wise-chat.css'))
		.pipe(gulp.dest('assets/css'))
		.pipe(cleanCSS({ compatibility: 'ie8' }))
		.pipe(rename({ suffix: '.min' }))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest('assets/css'));
});

// gather all vendor CSS files and combine them into single library file:
gulp.task('concat-vendors-css', function() {
	return gulp.src(cssFiles.vendor)
		.pipe(concat('wise-chat-libs.min.css'))
		.pipe(gulp.dest('assets/css'));
});

gulp.task('sass-watchify', function() {
	gulp.watch('assets/css/src/**/*.scss', gulp.series('sass'));
});

gulp.task('build-dev', gulp.series('eslint', gulp.parallel('sass', 'concat-vendors-css'), 'build-sources-dev', 'sass-watchify'));
gulp.task('build-prod', gulp.series('eslint', gulp.parallel('sass', 'concat-vendors-css'), 'build-sources-prod'));

// set the default task:
gulp.task('default', gulp.series('build-dev'));