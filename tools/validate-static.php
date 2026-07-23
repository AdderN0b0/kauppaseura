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
		}
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
			expect( str_starts_with( $canonical_url, 'https://addern0b0.github.io/kauppaseura/' ), $errors, $relative, 'canonical must use the production base' );
			$canonical_urls[] = $canonical_url;
		}
	}

	foreach ( $xpath->query( '//script[@type="application/ld+json"]' ) as $script ) {
		++$json_count;
		json_decode( $script->textContent, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$errors[] = "{$relative}: invalid JSON-LD (" . json_last_error_msg() . ')';
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
	}
	expect( $high_priority <= 1, $errors, $relative, 'contains more than one high-priority image' );

	foreach ( array( 'href', 'src', 'poster' ) as $attribute ) {
		foreach ( $xpath->query( "//*[@{$attribute}]" ) as $node ) {
			$reference = html_entity_decode( trim( $node->getAttribute( $attribute ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( '' === $reference || str_starts_with( $reference, '#' ) || preg_match( '#^(?:https?:|mailto:|tel:|data:|blob:|javascript:)#i', $reference ) ) {
				continue;
			}
			++$references;
			$path = parse_url( $reference, PHP_URL_PATH ) ?: '';
			if ( str_starts_with( $path, '/kauppaseura/' ) ) {
				$target = $root . '/' . substr( $path, strlen( '/kauppaseura/' ) );
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

echo 'Validated ' . count( $files ) . " HTML files, {$references} local references, {$image_count} images, {$json_count} JSON-LD blocks, {$unresolved_membership} unresolved membership facts, {$unresolved_launch_copy} unresolved launch-copy fields, {$unresolved_board_members} temporary board members, {$unresolved_testimonials} temporary testimonials and {$unresolved_output_placeholders} unpublished placeholders.\n";
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
