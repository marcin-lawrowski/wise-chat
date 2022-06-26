<?php

function requireIfExists($file) {
    if (file_exists(ABSPATH.WPINC.'/'.$file)) {
        require_once(ABSPATH.WPINC.'/'.$file);
    }
}

// core of WordPress:
require_once(dirname(__DIR__).'/../../../../wp-load.php');

require_once(ABSPATH.WPINC.'/default-filters.php');
require_once(ABSPATH.WPINC.'/l10n.php');

if (file_exists(ABSPATH.WPINC.'/class-wp-session-tokens.php')) {
    requireIfExists('class-wp-session-tokens.php');
    requireIfExists('class-wp-user-meta-session-tokens.php');
} else {
    requireIfExists('session.php');
}

// features enabled:
require_once(ABSPATH.WPINC.'/formatting.php');
require_once(ABSPATH.WPINC.'/query.php');
require_once(ABSPATH.WPINC.'/comment.php');
require_once(ABSPATH.WPINC.'/meta.php');
requireIfExists('class-wp-meta-query.php');
require_once(ABSPATH.WPINC.'/post.php');
requireIfExists('class-wp-post.php');
require_once(ABSPATH.WPINC.'/shortcodes.php');
require_once(ABSPATH.WPINC.'/media.php');
require_once(ABSPATH.WPINC.'/user.php');
require_once(ABSPATH.WPINC.'/taxonomy.php');
requireIfExists('class-wp-tax-query.php');
require_once(ABSPATH.WPINC.'/link-template.php');
require_once(ABSPATH.WPINC.'/rewrite.php');
require_once(ABSPATH.WPINC.'/author-template.php');
requireIfExists('class-wp-rewrite.php');
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

requireIfExists('class-wp-user.php');
requireIfExists('class-wp-user-query.php');

$GLOBALS['wp_rewrite'] = new WP_Rewrite();

// NOTICE: hack for warning in plugin_basename() function:
$wp_plugin_paths = array();

wp_plugin_directory_constants();
wp_cookie_constants();