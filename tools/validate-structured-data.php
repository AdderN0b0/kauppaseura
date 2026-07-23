<?php
/**
 * Validate structured-data fixtures and representative generated pages.
 *
 * This script never writes to WordPress. Complete, incomplete, cancelled,
 * postponed, and rescheduled event states are supplied as in-memory meta
 * overrides to the same builder used by the public theme.
 *
 * Usage: php tools/validate-structured-data.php [generated-directory]
 */

declare(strict_types=1);

$workspace = dirname( __DIR__ );
$wp_load   = $workspace . '/wp-load.php';
$build     = str_replace( '\\', '/', $argv[1] ?? $workspace . '/deliverables/lakeuden-kauppaseura-build' );
$errors    = array();

if ( ! is_file( $wp_load ) ) {
	fwrite( STDERR, "WordPress was not found at the repository root.\n" );
	exit( 1 );
}

require_once $wp_load;

$events = get_posts(
	array(
		'post_type'      => 'lks_event',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

if ( ! $events ) {
	fwrite( STDERR, "At least one published event is required for non-persistent schema fixtures.\n" );
	exit( 1 );
}

$event_id = (int) $events[0]->ID;
$future   = '2099-07-15';
$base_fixture = array(
	'_lks_event_date'                  => $future,
	'_lks_event_start_time'            => '18:00',
	'_lks_event_end_date'              => $future,
	'_lks_event_end_time'              => '20:00',
	'_lks_event_place'                 => 'Testipaikka',
	'_lks_event_city'                  => 'Seinäjoki',
	'_lks_event_attendance_mode'       => 'offline',
	'_lks_event_registration_url'      => 'https://example.com/ilmoittautuminen',
	'_lks_event_registration_deadline' => '2099-07-10',
	'_lks_event_price'                 => 'Maksuton',
	'_lks_event_cancelled'             => '',
	'_lks_event_status'                => '',
	'_lks_event_registration'          => '',
);

$complete = lakeuden_kauppaseura_event_schema( $event_id, $base_fixture );
fixture_expect( 'Event' === ( $complete['@type'] ?? '' ), $errors, 'complete event has Event type' );
foreach ( array( 'name', 'description', 'startDate', 'endDate', 'eventStatus', 'eventAttendanceMode', 'location', 'organizer', 'url', 'offers' ) as $property ) {
	fixture_expect( ! empty( $complete[ $property ] ), $errors, "complete event contains {$property}" );
}
fixture_expect( 0 === ( $complete['offers']['price'] ?? null ), $errors, 'free complete event has numeric zero price' );
fixture_expect( 'EUR' === ( $complete['offers']['priceCurrency'] ?? '' ), $errors, 'complete event uses EUR' );
fixture_expect( (bool) preg_match( '/T18:00:00\+0[23]:00$/', (string) ( $complete['startDate'] ?? '' ) ), $errors, 'complete event startDate uses Europe/Helsinki offset' );

$incomplete_fixture = array_merge(
	$base_fixture,
	array(
		'_lks_event_start_time'            => '',
		'_lks_event_end_date'              => '',
		'_lks_event_end_time'              => '',
		'_lks_event_place'                 => '[VAHVISTETAAN]',
		'_lks_event_city'                  => '',
		'_lks_event_attendance_mode'       => '',
		'_lks_event_registration_url'      => '',
		'_lks_event_registration_deadline' => '',
		'_lks_event_price'                 => '[VAHVISTETAAN]',
	)
);
$incomplete = lakeuden_kauppaseura_event_schema( $event_id, $incomplete_fixture );
fixture_expect( $future === ( $incomplete['startDate'] ?? '' ), $errors, 'incomplete event keeps a confirmed date without inventing a time' );
foreach ( array( 'endDate', 'eventAttendanceMode', 'location', 'offers' ) as $property ) {
	fixture_expect( ! isset( $incomplete[ $property ] ), $errors, "incomplete event omits unknown {$property}" );
}
fixture_expect( ! str_contains( wp_json_encode( $incomplete, JSON_UNESCAPED_UNICODE ), '[VAHVISTETAAN]' ), $errors, 'incomplete event omits placeholders' );

$cancelled_fixture = array_merge( $base_fixture, array( '_lks_event_cancelled' => '1' ) );
$cancelled         = lakeuden_kauppaseura_event_schema( $event_id, $cancelled_fixture );
fixture_expect( 'https://schema.org/EventCancelled' === ( $cancelled['eventStatus'] ?? '' ), $errors, 'cancelled checkbox maps to EventCancelled' );
fixture_expect( ! isset( $cancelled['offers'] ), $errors, 'cancelled event omits offers' );

$postponed_fixture = array_merge( $base_fixture, array( '_lks_event_status' => 'postponed' ) );
$postponed         = lakeuden_kauppaseura_event_schema( $event_id, $postponed_fixture );
fixture_expect( 'https://schema.org/EventPostponed' === ( $postponed['eventStatus'] ?? '' ), $errors, 'postponed state maps to EventPostponed' );

$rescheduled_fixture = array_merge(
	$base_fixture,
	array(
		'_lks_event_status'         => 'rescheduled',
		'_lks_event_previous_start' => '2099-07-08T18:00:00+03:00',
	)
);
$rescheduled = lakeuden_kauppaseura_event_schema( $event_id, $rescheduled_fixture );
fixture_expect( 'https://schema.org/EventRescheduled' === ( $rescheduled['eventStatus'] ?? '' ), $errors, 'rescheduled state maps to EventRescheduled' );
fixture_expect( '2099-07-08T18:00:00+03:00' === ( $rescheduled['previousStartDate'] ?? '' ), $errors, 'rescheduled event preserves confirmed previousStartDate' );

if ( ! is_dir( $build ) ) {
	$errors[] = "Generated build does not exist: {$build}";
} else {
	$representatives = array(
		'homepage'       => array( 'file' => $build . '/index.html', 'types' => array( 'Organization', 'WebSite', 'WebPage', 'BreadcrumbList' ) ),
		'membership'     => array( 'file' => $build . '/jaseneksi/index.html', 'types' => array( 'Organization', 'WebSite', 'WebPage', 'BreadcrumbList', 'FAQPage' ) ),
		'events archive' => array( 'file' => $build . '/tapahtumat/index.html', 'types' => array( 'Organization', 'WebSite', 'WebPage', 'BreadcrumbList' ) ),
	);

	foreach ( $representatives as $label => $representative ) {
		$document = static_schema_document( $representative['file'], $errors, $label );
		if ( ! $document ) {
			continue;
		}
		foreach ( $representative['types'] as $type ) {
			fixture_expect( static_schema_has_type( $document['schema'], $type ), $errors, "{$label} contains {$type}" );
		}
		if ( 'events archive' === $label ) {
			fixture_expect( ! static_schema_has_type( $document['schema'], 'Event' ), $errors, 'events archive does not misrepresent itself as one Event' );
		}
	}

	$all_html = glob( $build . '/*/index.html' ) ?: array();
	$blog_document = first_schema_document_with_type( $all_html, 'BlogPosting', $errors );
	fixture_expect( null !== $blog_document, $errors, 'representative blog article contains BlogPosting' );
	$event_html = glob( $build . '/tapahtuma/*/index.html' ) ?: array();
	$event_document = first_schema_document_with_type( $event_html, 'Event', $errors );
	fixture_expect( null !== $event_document, $errors, 'representative event page contains Event' );
}

if ( $errors ) {
	echo count( $errors ) . " structured-data validation error(s):\n- " . implode( "\n- ", $errors ) . "\n";
	exit( 1 );
}

echo "Structured-data validation passed: homepage, membership, blog article, events archive, event page, complete event, incomplete event, cancelled event, postponed event, rescheduled event, canonical URLs and Open Graph URLs.\n";

/**
 * Record one fixture assertion.
 *
 * @param array<int,string> $errors Error collection.
 */
function fixture_expect( bool $condition, array &$errors, string $message ): void {
	if ( ! $condition ) {
		$errors[] = $message;
	}
}

/**
 * Read one generated page and decode its centralized JSON-LD graph.
 *
 * @param array<int,string> $errors Error collection.
 * @return array{schema:array<string,mixed>,canonical:string,og_url:string}|null
 */
function static_schema_document( string $file, array &$errors, string $label ): ?array {
	if ( ! is_file( $file ) ) {
		$errors[] = "{$label} file is missing: {$file}";
		return null;
	}

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( (string) file_get_contents( $file ), LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET );
	libxml_clear_errors();
	$xpath    = new DOMXPath( $dom );
	$scripts  = $xpath->query( '//script[@type="application/ld+json"]' );
	$canonical = $xpath->query( '//link[translate(@rel,"CANONICAL","canonical")="canonical"]' )->item( 0 );
	$og_url    = $xpath->query( '//meta[@property="og:url"]' )->item( 0 );

	if ( 1 !== $scripts->length ) {
		$errors[] = "{$label} must contain one JSON-LD graph";
		return null;
	}

	$schema = json_decode( $scripts->item( 0 )->textContent, true );
	if ( ! is_array( $schema ) ) {
		$errors[] = "{$label} contains invalid JSON-LD";
		return null;
	}

	$canonical_url = $canonical ? $canonical->getAttribute( 'href' ) : '';
	$og_value      = $og_url ? $og_url->getAttribute( 'content' ) : '';
	fixture_expect( '' !== $canonical_url, $errors, "{$label} contains canonical URL" );
	fixture_expect( $canonical_url === $og_value, $errors, "{$label} Open Graph URL equals canonical URL" );
	fixture_expect( ! str_contains( wp_json_encode( $schema ), 'lakeuden-kauppaseura.local' ), $errors, "{$label} schema omits Local URLs" );

	return array(
		'schema'    => $schema,
		'canonical' => $canonical_url,
		'og_url'    => $og_value,
	);
}

/**
 * Recursively check a decoded graph for one @type.
 *
 * @param mixed $schema Decoded JSON-LD value.
 */
function static_schema_has_type( $schema, string $type ): bool {
	if ( ! is_array( $schema ) ) {
		return false;
	}

	$types = $schema['@type'] ?? array();
	$types = is_array( $types ) ? $types : array( $types );
	if ( in_array( $type, $types, true ) ) {
		return true;
	}

	foreach ( $schema as $value ) {
		if ( static_schema_has_type( $value, $type ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Find the first generated page containing a requested schema type.
 *
 * @param array<int,string> $files  Candidate HTML files.
 * @param array<int,string> $errors Error collection.
 * @return array{schema:array<string,mixed>,canonical:string,og_url:string}|null
 */
function first_schema_document_with_type( array $files, string $type, array &$errors ): ?array {
	foreach ( $files as $file ) {
		$local_errors = array();
		$document     = static_schema_document( $file, $local_errors, basename( dirname( $file ) ) );
		if ( $document && static_schema_has_type( $document['schema'], $type ) ) {
			foreach ( $local_errors as $error ) {
				$errors[] = $error;
			}
			return $document;
		}
	}

	return null;
}
