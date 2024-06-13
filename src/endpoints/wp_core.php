<?php

function requireIfExists($file) {
    if (file_exists(ABSPATH.WPINC.'/'.$file)) {
        require_once(ABSPATH.WPINC.'/'.$file);
    }
}

$config = json_decode(file_get_contents(dirname(__FILE__).'/engines.json'), true);

// core of WordPress:
if (is_array($config) && isset($config['abspath']) && $config['abspath'] && file_exists($config['abspath'].'wp-load.php')) {
	require_once($config['abspath'].'wp-load.php');
} else {
	require_once(dirname(__DIR__) . '/../../../../wp-load.php');
}

require_once(ABSPATH.WPINC.'/default-filters.php');

// translations originally loaded via: wp-config.php -> wp-settings.php (wp_not_installed()) -> load.php (is_blog_installed()) -> functions.php
require_once(ABSPATH.WPINC.'/l10n.php');
require_once(ABSPATH.WPINC.'/class-wp-textdomain-registry.php');
require_once(ABSPATH.WPINC.'/class-wp-locale.php');
require_once(ABSPATH.WPINC.'/class-wp-locale-switcher.php');
wp_load_translations_early();

if (file_exists(ABSPATH.WPINC.'/class-wp-session-tokens.php')) {
    requireIfExists('class-wp-session-tokens.php');
    requireIfExists('class-wp-user-meta-session-tokens.php');
} else {
    requireIfExists('session.php');
}

// features enabled:
requireIfExists('blocks.php');
require_once(ABSPATH.WPINC.'/formatting.php');
require_once(ABSPATH.WPINC.'/query.php');
require_once(ABSPATH.WPINC.'/comment.php');
require_once(ABSPATH.WPINC.'/meta.php');
requireIfExists('class-wp-meta-query.php');
require_once(ABSPATH.WPINC.'/post.php');
requireIfExists('class-wp-post.php');
require_once(ABSPATH.WPINC.'/shortcodes.php');
requireIfExists('block-template-utils.php');
require_once(ABSPATH.WPINC.'/media.php');
require_once(ABSPATH.WPINC.'/user.php');
require_once(ABSPATH.WPINC.'/taxonomy.php');
requireIfExists('class-wp-tax-query.php');
require_once(ABSPATH.WPINC.'/link-template.php');
require_once(ABSPATH.WPINC.'/rewrite.php');
require_once(ABSPATH.WPINC.'/author-template.php');
requireIfExists('class-wp-rewrite.php');
requireIfExists('class-wp-query.php');
requireIfExists('rest-api.php');
require_once(ABSPATH.WPINC.'/rewrite.php');
require_once(ABSPATH.WPINC.'/kses.php');
require_once(ABSPATH.WPINC.'/revision.php');
require_once(ABSPATH.WPINC.'/capabilities.php');
requireIfExists('class-wp-roles.php');
requireIfExists('class-wp-role.php');
require_once(ABSPATH.WPINC.'/pluggable.php');
require_once(ABSPATH.WPINC.'/pluggable-deprecated.php');
require_once(ABSPATH.WPINC.'/ms-functions.php');
requireIfExists('block-template-utils.php');

requireIfExists('class-wp-user.php');
requireIfExists('class-wp-user-query.php');

$GLOBALS['wp_rewrite'] = new WP_Rewrite();
$GLOBALS['wp_query'] = new WP_Query();

// NOTICE: hack for warning in plugin_basename() function:
$wp_plugin_paths = array();

wp_plugin_directory_constants();
wp_cookie_constants();