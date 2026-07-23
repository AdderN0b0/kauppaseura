<?php
/**
 * Shared public-site configuration.
 *
 * The same non-secret JSON file is consumed by the theme, static exporter,
 * validator, and publication script. This keeps canonical and schema URLs in
 * one place without putting Local paths or credentials in version control.
 *
 * @package Lakeuden_Kauppaseura
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the public, non-secret site configuration.
 *
 * @return array{productionUrl:string,timezone:string}
 */
function lakeuden_kauppaseura_site_configuration() {
	static $configuration = null;

	if ( is_array( $configuration ) ) {
		return $configuration;
	}

	$configuration = array(
		'productionUrl' => home_url( '/' ),
		'timezone'      => 'Europe/Helsinki',
	);
	$config_file   = dirname( __DIR__, 4 ) . '/tools/site-config.json';

	if ( is_readable( $config_file ) ) {
		$decoded = json_decode( (string) file_get_contents( $config_file ), true );
		if ( is_array( $decoded ) ) {
			$configuration = array_merge( $configuration, $decoded );
		}
	}

	return $configuration;
}

/**
 * Return the centrally configured production base URL.
 *
 * @return string
 */
function lakeuden_kauppaseura_production_base_url() {
	$configuration = lakeuden_kauppaseura_site_configuration();
	$configured    = (string) ( $configuration['productionUrl'] ?? '' );
	$validated     = esc_url_raw( $configured, array( 'http', 'https' ) );

	if ( ! $validated ) {
		$validated = home_url( '/' );
	}

	return trailingslashit( $validated );
}

/**
 * Join a public path to the configured production URL.
 *
 * @param string $path Root-relative public path.
 * @return string
 */
function lakeuden_kauppaseura_production_url( $path = '' ) {
	return lakeuden_kauppaseura_production_base_url() . ltrim( (string) $path, '/' );
}
