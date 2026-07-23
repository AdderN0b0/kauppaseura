<?php
/**
 * Load the site-owned Instagram feed plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lks_instagram_feed_plugin = WP_PLUGIN_DIR . '/lks-instagram-feed/lks-instagram-feed.php';

if ( file_exists( $lks_instagram_feed_plugin ) ) {
	require_once $lks_instagram_feed_plugin;
}
