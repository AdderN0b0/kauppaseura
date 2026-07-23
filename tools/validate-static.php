<?php
/**
 * Validate the generated Lakeuden Kauppaseura static site.
 *
 * Usage: php tools/validate-static.php [generated-directory]
 */

declare(strict_types=1);

$workspace = dirname( __DIR__ );
$root      = str_replace( '\\', '/', $argv[1] ?? $workspace . '/deliverables/lakeuden-kauppaseura-build' );
$allowed   = str_replace( '\\', '/', $workspace . '/deliverables/' );
$config_file = $workspace . '/tools/site-config.json';
$configuration = is_file( $config_file ) ? json_decode( (string) file_get_contents( $config_file ), true ) : null;

if ( ! is_array( $configuration ) || ! filter_var( $configuration['productionUrl'] ?? '', FILTER_VALIDATE_URL ) ) {
	fwrite( STDERR, "tools/site-config.json must contain a valid productionUrl.\n" );
	exit( 1 );
}

$production_base = rtrim( (string) $configuration['productionUrl'], '/' ) . '/';
$production_path = '/' . trim( (string) ( parse_url( $production_base, PHP_URL_PATH ) ?: '' ), '/' );
$production_path = '/' === $production_path ? '/' : $production_path . '/';

if ( ! str_starts_with( $root . '/', $allowed ) || ! is_dir( $root ) ) {
	fwrite( STDERR, "Validation target must be an existing directory below deliverables.\n" );
	exit( 1 );
}

$files = array();
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $iterator as $item ) {
	if ( $item->isFile() && 'html' === strtolower( $item->getExtension() ) ) {
		$files[] = str_replace( '\\', '/', $item->getPathname() );
	}
}
sort( $files );

$errors      = array();
$image_count = 0;
$json_count  = 0;
$references  = 0;
$canonical_urls = array();
$unresolved_membership = 0;
$unresolved_launch_copy = 0;
$unresolved_board_members = 0;
$unresolved_testimonials = 0;
$unresolved_output_placeholders = 0;

foreach ( $files as $file ) {
	$relative = ltrim( substr( $file, strlen( $root ) ), '/' );
	$html     = (string) file_get_contents( $file );
	$dom      = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET );
	libxml_clear_errors();
	$xpath = new DOMXPath( $dom );

	expect( $xpath->query( '/html[@lang="fi"]' )->length === 1, $errors, $relative, 'missing lang="fi"' );
	expect( $xpath->query( '//h1' )->length === 1, $errors, $relative, 'must contain exactly one H1' );
	expect( $xpath->query( '//*[@id="main"]' )->length === 1, $errors, $relative, 'must contain exactly one #main' );
	expect( $xpath->query( '//main | //*[@role="main"]' )->length === 1, $errors, $relative, 'must contain exactly one main landmark' );
	expect( $xpath->query( '//footer' )->length === 1, $errors, $relative, 'must contain exactly one footer landmark' );
	expect( $xpath->query( '//a[contains(concat(" ", normalize-space(@class), " "), " lks-skip-link ") and @href="#main"]' )->length === 1, $errors, $relative, 'must contain one skip link' );

	$h1 = $xpath->query( '//h1' )->item( 0 );
	a11y_expect( ! $h1 || '' !== normalized_text( $h1 ), $errors, $relative, 'H1 must have a meaningful accessible name' );

	$previous_heading_level = 0;
	foreach ( $xpath->query( '//h1 | //h2 | //h3 | //h4 | //h5 | //h6' ) as $heading ) {
		$heading_level = (int) substr( $heading->nodeName, 1 );
		a11y_expect( '' !== normalized_text( $heading ), $errors, $relative, strtoupper( $heading->nodeName ) . ' must not be empty' );
		if ( $previous_heading_level > 0 ) {
			a11y_expect( $heading_level <= $previous_heading_level + 1, $errors, $relative, "heading level skips from H{$previous_heading_level} to H{$heading_level}" );
		}
		$previous_heading_level = $heading_level;
	}

	foreach ( $xpath->query( '//a[@href] | //button' ) as $control ) {
		if ( element_or_ancestor_has_attribute( $control, 'aria-hidden', 'true' ) ) {
			continue;
		}
		a11y_expect( '' !== accessible_name( $control ), $errors, $relative, $control->nodeName . ' must have an accessible name' );
	}

	$labels = array();
	foreach ( $xpath->query( '//label[@for]' ) as $label ) {
		$labels[ $label->getAttribute( 'for' ) ] = true;
	}
	foreach ( $xpath->query( '//input[not(translate(@type,"HIDDEN","hidden")="hidden") and not(translate(@type,"SUBMIT","submit")="submit") and not(translate(@type,"BUTTON","button")="button") and not(translate(@type,"RESET","reset")="reset")] | //textarea | //select' ) as $control ) {
		$id         = $control->getAttribute( 'id' );
		$labelled   = ( $id && isset( $labels[ $id ] ) ) || $control->hasAttribute( 'aria-label' ) || $control->hasAttribute( 'aria-labelledby' );
		$parent     = $control->parentNode;
		while ( ! $labelled && $parent instanceof DOMElement ) {
			$labelled = 'label' === $parent->nodeName;
			$parent   = $parent->parentNode;
		}
		a11y_expect( $labelled, $errors, $relative, $control->nodeName . ( $id ? "#{$id}" : '' ) . ' must have an explicit label' );
	}
	foreach ( $xpath->query( '//fieldset' ) as $fieldset ) {
		a11y_expect( $xpath->query( './legend[normalize-space()]', $fieldset )->length === 1, $errors, $relative, 'fieldset must have one non-empty legend' );
	}

	if ( preg_match_all( '/\[[^\]]*(?:VAHVISTETAAN|ESIMERKKI|LISÄTÄÄN|ENNEN JULKAISUA)[^\]]*\]/u', $html, $placeholder_matches ) ) {
		$unresolved_output_placeholders += count( $placeholder_matches[0] );
		$errors[] = "{$relative}: contains unpublished placeholder content";
	}

	foreach ( $xpath->query( '//*[@data-lks-membership-fact and @data-lks-launch-required="true"]' ) as $membership_fact ) {
		$key   = $membership_fact->getAttribute( 'data-lks-membership-fact' );
		$value = trim( (string) preg_replace( '/\s+/u', ' ', $membership_fact->textContent ) );
		if ( '' === $value || str_contains( $value, '[VAHVISTETAAN]' ) ) {
			++$unresolved_membership;
			$errors[] = "{$relative}: unresolved launch-required membership fact {$key}";
		}
	}

	foreach ( $xpath->query( '//*[@data-lks-launch-copy and @data-lks-launch-required="true"]' ) as $launch_copy ) {
		$key   = $launch_copy->getAttribute( 'data-lks-launch-copy' );
		$value = trim( (string) preg_replace( '/\s+/u', ' ', $launch_copy->textContent ) );
		if ( '' === $value || str_contains( $value, '[VAHVISTETAAN]' ) ) {
			++$unresolved_launch_copy;
			$errors[] = "{$relative}: unresolved launch-required copy {$key}";
		}
	}

	foreach ( $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " lks-board-member-card ") and @data-lks-person-placeholder="true"]' ) as $board_member ) {
		++$unresolved_board_members;
		$errors[] = "{$relative}: board member still contains temporary content";
	}

	foreach ( $xpath->query( '//*[@data-lks-testimonial-placeholder="true"]' ) as $testimonial ) {
		++$unresolved_testimonials;
		$errors[] = "{$relative}: member testimonial still contains temporary content";
	}

	if ( 'meista/index.html' === $relative ) {
		expect( 1 === $xpath->query( '//section[contains(concat(" ", normalize-space(@class), " "), " lks-about-board ")]' )->length, $errors, $relative, 'must contain one board section' );
		expect( 8 === $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " lks-board-member-card ")]' )->length, $errors, $relative, 'must contain exactly eight board-member cards' );
	}

	if ( 'jaseneksi/index.html' === $relative ) {
		$fallbacks = $xpath->query( '//*[@data-lks-static-membership-form]' );
		expect( 3 === $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " lks-member-testimonial-card ")]' )->length, $errors, $relative, 'must contain exactly three member-testimonial cards' );
		expect( 1 === $fallbacks->length, $errors, $relative, 'must contain exactly one static membership-form fallback' );
		expect( 0 === $xpath->query( '//*[@data-lks-live-membership-form] | //form' )->length, $errors, $relative, 'must not contain a live form in static output' );
		if ( 1 === $fallbacks->length ) {
			$fallback = $fallbacks->item( 0 );
			expect( ! $fallback->hasAttribute( 'hidden' ), $errors, $relative, 'static membership-form fallback must be visible' );
			expect( $xpath->query( './/a[@href]', $fallback )->length >= 1, $errors, $relative, 'static membership-form fallback must provide a contact link' );
			expect( $xpath->query( './/a[contains(@href,"tietosuoja")]', $fallback )->length === 1, $errors, $relative, 'static membership fallback must link to the privacy notice' );
		}
	}

	if ( 'tietosuoja/index.html' === $relative ) {
		$privacy_text = normalized_text( $xpath->query( '//*[@id="main"]' )->item( 0 ) );
		foreach (
			array(
				'Lakeuden Kauppaseura ry',
				'Jäsenyyskiinnostus',
				'Tapahtumiin ilmoittautuminen',
				'Tapahtumailmoitusten tilaus',
				'Vapaaehtoinen toiminta- ja tapahtumaviestintä',
				'Tietosuojavaltuutetun toimistolle',
				'GitHub Pages',
				'WPForms Lite',
			) as $required_privacy_text
		) {
			expect( str_contains( $privacy_text, $required_privacy_text ), $errors, $relative, "privacy notice must document {$required_privacy_text}" );
		}
		expect( $xpath->query( '//*[@data-lks-legal-review="required"]' )->length >= 2, $errors, $relative, 'must retain visible legal-review markers' );
	}

	$ids = array();
	foreach ( $xpath->query( '//*[@id]' ) as $node ) {
		$id = $node->getAttribute( 'id' );
		if ( isset( $ids[ $id ] ) ) {
			$errors[] = "{$relative}: duplicate id #{$id}";
		}
		$ids[ $id ] = true;
	}

	$noindex = $xpath->query( '//meta[translate(@name,"ROBOTS","robots")="robots" and contains(translate(@content,"NOINDEX","noindex"),"noindex")]' )->length > 0;
	if ( ! $noindex ) {
		$required = array(
			'description'         => '//meta[translate(@name,"DESCRIPTION","description")="description"]',
			'canonical'           => '//link[translate(@rel,"CANONICAL","canonical")="canonical"]',
			'og:title'            => '//meta[@property="og:title"]',
			'og:description'      => '//meta[@property="og:description"]',
			'og:url'              => '//meta[@property="og:url"]',
			'og:image'            => '//meta[@property="og:image"]',
			'twitter:card'        => '//meta[@name="twitter:card"]',
			'twitter:title'       => '//meta[@name="twitter:title"]',
			'twitter:description' => '//meta[@name="twitter:description"]',
			'twitter:image'       => '//meta[@name="twitter:image"]',
		);
		foreach ( $required as $label => $query ) {
			expect( $xpath->query( $query )->length === 1, $errors, $relative, "must contain exactly one {$label}" );
		}
		$canonical = $xpath->query( '//link[translate(@rel,"CANONICAL","canonical")="canonical"]' )->item( 0 );
		if ( $canonical ) {
			$canonical_url = $canonical->getAttribute( 'href' );
			expect( str_starts_with( $canonical_url, $production_base ), $errors, $relative, 'canonical must use the configured production base' );
			$canonical_urls[] = $canonical_url;
			$og_url = $xpath->query( '//meta[@property="og:url"]' )->item( 0 );
			expect( ! $og_url || $og_url->getAttribute( 'content' ) === $canonical_url, $errors, $relative, 'Open Graph URL must equal the canonical URL' );
		}
	}

	$json_scripts = $xpath->query( '//script[@type="application/ld+json"]' );
	if ( ! $noindex ) {
		expect( 1 === $json_scripts->length, $errors, $relative, 'must contain exactly one centralized JSON-LD graph' );
	}

	foreach ( $json_scripts as $script ) {
		++$json_count;
		$schema = json_decode( $script->textContent, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$errors[] = "{$relative}: invalid JSON-LD (" . json_last_error_msg() . ')';
			continue;
		}

		$schema_text = $script->textContent;
		expect( ! preg_match( '/\[[^\]]*(?:VAHVISTETAAN|LISÄTÄÄN|ENNEN JULKAISUA)[^\]]*\]/iu', $schema_text ), $errors, $relative, 'JSON-LD must not contain unresolved placeholders' );
		expect( ! str_contains( $schema_text, 'lakeuden-kauppaseura.local' ), $errors, $relative, 'JSON-LD must not contain Local development URLs' );
		if ( ! str_contains( $production_base, 'github.io' ) ) {
			expect( ! str_contains( $schema_text, 'github.io' ), $errors, $relative, 'JSON-LD must not retain a GitHub Pages URL after domain migration' );
		}

		if ( ! $noindex && is_array( $schema ) ) {
			foreach ( array( 'Organization', 'WebSite', 'WebPage', 'BreadcrumbList' ) as $required_type ) {
				expect( schema_has_type( $schema, $required_type ), $errors, $relative, "JSON-LD graph must contain {$required_type}" );
			}
			expect( 'https://schema.org' === ( $schema['@context'] ?? '' ), $errors, $relative, 'JSON-LD graph must use the Schema.org context' );
		}

		if ( 'jaseneksi/index.html' === $relative ) {
			expect( schema_has_type( $schema, 'FAQPage' ), $errors, $relative, 'membership page JSON-LD must preserve FAQPage data' );
		}

		if ( str_starts_with( $relative, 'tapahtuma/' ) ) {
			$event = schema_node_by_type( $schema, 'Event' );
			expect( is_array( $event ), $errors, $relative, 'event page JSON-LD must contain Event' );
			if ( is_array( $event ) ) {
				foreach ( array( 'name', 'description', 'startDate', 'eventStatus', 'organizer', 'url' ) as $property ) {
					expect( ! empty( $event[ $property ] ), $errors, $relative, "Event must contain {$property}" );
				}
				expect(
					in_array(
						$event['eventStatus'] ?? '',
						array(
							'https://schema.org/EventScheduled',
							'https://schema.org/EventCancelled',
							'https://schema.org/EventPostponed',
							'https://schema.org/EventRescheduled',
						),
						true
					),
					$errors,
					$relative,
					'Event status must use a supported Schema.org URL'
				);
				if ( isset( $event['location'] ) ) {
					expect( ! empty( $event['eventAttendanceMode'] ), $errors, $relative, 'Event with a location must contain eventAttendanceMode' );
				}
				if ( isset( $event['offers'] ) ) {
					expect( ! empty( $event['offers']['url'] ), $errors, $relative, 'Event Offer must contain a registration URL' );
				}
				if ( 'https://schema.org/EventCancelled' === ( $event['eventStatus'] ?? '' ) ) {
					expect( ! isset( $event['offers'] ), $errors, $relative, 'cancelled Event must not contain offers' );
				}
			}
		}

		$article = schema_node_by_type( $schema, 'BlogPosting' );
		if ( is_array( $article ) ) {
			foreach ( array( 'headline', 'datePublished', 'dateModified', 'author', 'image', 'description', 'publisher' ) as $property ) {
				expect( ! empty( $article[ $property ] ), $errors, $relative, "BlogPosting must contain {$property}" );
			}
		}
	}

	$high_priority = 0;
	foreach ( $xpath->query( '//img' ) as $image ) {
		++$image_count;
		foreach ( array( 'alt', 'width', 'height' ) as $attribute ) {
			expect( $image->hasAttribute( $attribute ), $errors, $relative, "image missing {$attribute}" );
		}
		if ( 'high' === strtolower( $image->getAttribute( 'fetchpriority' ) ) ) {
			++$high_priority;
		}

		if ( $image->hasAttribute( 'alt' ) ) {
			$alt = trim( $image->getAttribute( 'alt' ) );
			if ( '' === $alt ) {
				$is_decorative = element_or_ancestor_has_attribute( $image, 'aria-hidden', 'true' )
					|| element_or_ancestor_has_attribute( $image, 'role', 'presentation' )
					|| element_or_ancestor_has_attribute( $image, 'role', 'none' );
				a11y_expect( $is_decorative, $errors, $relative, 'empty image alt is only allowed for explicitly decorative images' );
			} else {
				$src_path = (string) parse_url( $image->getAttribute( 'src' ), PHP_URL_PATH );
				$stem     = pathinfo( rawurldecode( $src_path ), PATHINFO_FILENAME );
				a11y_expect( ! preg_match( '/\.(?:avif|gif|jpe?g|png|svg|webp)$/i', $alt ), $errors, $relative, "image alt must not be a filename ({$alt})" );
				a11y_expect( '' === $stem || normalized_token( $alt ) !== normalized_token( $stem ), $errors, $relative, "image alt must not repeat its filename ({$alt})" );
			}
		}
	}
	expect( $high_priority <= 1, $errors, $relative, 'contains more than one high-priority image' );

	foreach ( $xpath->query( '//time' ) as $time ) {
		$datetime = trim( $time->getAttribute( 'datetime' ) );
		a11y_expect( '' !== $datetime, $errors, $relative, 'time element must include datetime' );
		a11y_expect( '' !== normalized_text( $time ), $errors, $relative, 'time element must contain readable text' );
	}

	foreach ( $xpath->query( '//*[@target="_blank"]' ) as $external ) {
		$rel = preg_split( '/\s+/', strtolower( trim( $external->getAttribute( 'rel' ) ) ) ) ?: array();
		a11y_expect( in_array( 'noopener', $rel, true ), $errors, $relative, 'target="_blank" link must use rel="noopener"' );
	}

	foreach ( array( 'href', 'src', 'poster' ) as $attribute ) {
		foreach ( $xpath->query( "//*[@{$attribute}]" ) as $node ) {
			$reference = html_entity_decode( trim( $node->getAttribute( $attribute ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( '' === $reference || str_starts_with( $reference, '#' ) || preg_match( '#^(?:https?:|mailto:|tel:|data:|blob:|javascript:)#i', $reference ) ) {
				continue;
			}
			++$references;
			$path = parse_url( $reference, PHP_URL_PATH ) ?: '';
			if ( str_starts_with( $path, $production_path ) ) {
				$target = $root . '/' . substr( $path, strlen( $production_path ) );
			} else {
				$target = dirname( $file ) . '/' . rawurldecode( $path );
			}
			$target = str_replace( '\\', '/', $target );
			if ( str_ends_with( $path, '/' ) ) {
				$target .= 'index.html';
			}
			if ( ! file_exists( $target ) ) {
				$errors[] = "{$relative}: missing local {$attribute} target {$reference}";
			}
		}
	}

	foreach ( $xpath->query( '//*[@srcset]' ) as $node ) {
		foreach ( explode( ',', $node->getAttribute( 'srcset' ) ) as $candidate ) {
			$reference = preg_split( '/\s+/', trim( $candidate ) )[0] ?? '';
			if ( '' === $reference || preg_match( '#^(?:https?:|data:)#i', $reference ) ) {
				continue;
			}
			++$references;
			$target = str_replace( '\\', '/', dirname( $file ) . '/' . rawurldecode( parse_url( $reference, PHP_URL_PATH ) ?: '' ) );
			if ( ! file_exists( $target ) ) {
				$errors[] = "{$relative}: missing local srcset target {$reference}";
			}
		}
	}
}

$sitemap_file = $root . '/sitemap.xml';
expect( is_file( $sitemap_file ), $errors, 'sitemap.xml', 'is missing' );
if ( is_file( $sitemap_file ) ) {
	$sitemap = simplexml_load_file( $sitemap_file );
	expect( false !== $sitemap, $errors, 'sitemap.xml', 'is invalid XML' );
	if ( false !== $sitemap ) {
		$sitemap_urls = array();
		foreach ( $sitemap->url as $entry ) {
			$sitemap_urls[] = trim( (string) $entry->loc );
		}
		$sitemap_urls   = array_values( array_unique( $sitemap_urls ) );
		$canonical_urls = array_values( array_unique( $canonical_urls ) );
		sort( $sitemap_urls );
		sort( $canonical_urls );
		expect( $sitemap_urls === $canonical_urls, $errors, 'sitemap.xml', 'URLs must exactly match canonical URLs from indexable HTML' );
	}
}

foreach ( array( 'robots.txt', '404.html', 'favicon.ico', 'favicon-32x32.png', 'apple-touch-icon.png', '.nojekyll' ) as $required_file ) {
	expect( file_exists( $root . '/' . $required_file ), $errors, $required_file, 'is missing' );
}

$accessibility_errors = count( array_filter( $errors, static fn( $error ) => str_contains( $error, '[accessibility]' ) ) );
echo 'Validated ' . count( $files ) . " HTML files, {$references} local references, {$image_count} images, {$json_count} JSON-LD blocks, {$accessibility_errors} accessibility errors, {$unresolved_membership} unresolved membership facts, {$unresolved_launch_copy} unresolved launch-copy fields, {$unresolved_board_members} temporary board members, {$unresolved_testimonials} temporary testimonials and {$unresolved_output_placeholders} unpublished placeholders.\n";
if ( $errors ) {
	echo count( $errors ) . " error(s):\n- " . implode( "\n- ", $errors ) . "\n";
	exit( 1 );
}

echo "Validation passed with zero errors.\n";

/**
 * Record a failed assertion.
 *
 * @param array<int,string> $errors Error collection.
 */
function expect( bool $condition, array &$errors, string $file, string $message ): void {
	if ( ! $condition ) {
		$errors[] = "{$file}: {$message}";
	}
}

/**
 * Record an accessibility-specific failed assertion.
 *
 * @param array<int,string> $errors Error collection.
 */
function a11y_expect( bool $condition, array &$errors, string $file, string $message ): void {
	if ( ! $condition ) {
		$errors[] = "{$file}: [accessibility] {$message}";
	}
}

/**
 * Normalize visible text for empty-name checks.
 */
function normalized_text( DOMNode $node ): string {
	return trim( (string) preg_replace( '/\s+/u', ' ', $node->textContent ) );
}

/**
 * Return a conservative accessible name for static validation.
 */
function accessible_name( DOMElement $element ): string {
	foreach ( array( 'aria-label', 'title' ) as $attribute ) {
		if ( $element->hasAttribute( $attribute ) && '' !== trim( $element->getAttribute( $attribute ) ) ) {
			return trim( $element->getAttribute( $attribute ) );
		}
	}

	$text = normalized_text( $element );
	if ( '' !== $text ) {
		return $text;
	}

	foreach ( $element->getElementsByTagName( 'img' ) as $image ) {
		if ( '' !== trim( $image->getAttribute( 'alt' ) ) ) {
			return trim( $image->getAttribute( 'alt' ) );
		}
	}

	return '';
}

/**
 * Check an element and its ancestors for one exact attribute value.
 */
function element_or_ancestor_has_attribute( DOMNode $node, string $attribute, string $value ): bool {
	$current = $node;
	while ( $current instanceof DOMElement ) {
		if ( strtolower( trim( $current->getAttribute( $attribute ) ) ) === strtolower( $value ) ) {
			return true;
		}
		$current = $current->parentNode;
	}

	return false;
}

/**
 * Determine whether a decoded JSON-LD graph contains one schema type.
 *
 * @param mixed $schema Decoded JSON-LD value.
 */
function schema_has_type( $schema, string $type ): bool {
	return null !== schema_node_by_type( $schema, $type );
}

/**
 * Return the first decoded JSON-LD node with the requested type.
 *
 * @param mixed $schema Decoded JSON-LD value.
 * @return array<string,mixed>|null
 */
function schema_node_by_type( $schema, string $type ): ?array {
	if ( ! is_array( $schema ) ) {
		return null;
	}

	$types = $schema['@type'] ?? array();
	$types = is_array( $types ) ? $types : array( $types );
	if ( in_array( $type, $types, true ) ) {
		return $schema;
	}

	foreach ( $schema as $value ) {
		$match = schema_node_by_type( $value, $type );
		if ( null !== $match ) {
			return $match;
		}
	}

	return null;
}

/**
 * Normalize text and filenames for an obvious filename-as-alt comparison.
 */
function normalized_token( string $value ): string {
	$value = trim( $value );
	if ( function_exists( 'mb_strtolower' ) ) {
		$value = mb_strtolower( $value, 'UTF-8' );
	} else {
		$value = strtolower( strtr( $value, array(
			'Å' => 'å',
			'Ä' => 'ä',
			'Ö' => 'ö',
			'Š' => 'š',
			'Ž' => 'ž',
		) ) );
	}

	return (string) preg_replace( '/[^\p{L}\p{N}]+/u', '', $value );
}
