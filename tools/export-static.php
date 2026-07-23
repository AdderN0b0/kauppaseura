<?php
/**
 * Build a self-contained, file:// friendly copy of the local Lakeuden
 * Kauppaseura site. WordPress runtime scripts are removed while the small
 * dependency-free accessibility script and JSON-LD metadata are retained.
 *
 * Usage:
 * php tools/export-static.php [output-directory]
 */

declare(strict_types=1);

$source_origin = 'http://lakeuden-kauppaseura.local';
$workspace     = dirname( __DIR__ );
$output        = $argv[1] ?? $workspace . '/deliverables/lakeuden-kauppaseura-build';
$output        = str_replace( '\\', '/', $output );
$allowed_root  = str_replace( '\\', '/', $workspace . '/deliverables/' );

if ( ! str_starts_with( $output . '/', $allowed_root ) ) {
	fwrite( STDERR, "Refusing to write outside the workspace deliverables directory.\n" );
	exit( 1 );
}

/**
 * Delete an existing generated directory after its location has been checked.
 */
function remove_generated_directory( string $directory ): void {
	if ( ! is_dir( $directory ) ) {
		return;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $item ) {
		if ( $item->isDir() ) {
			rmdir( $item->getPathname() );
		} else {
			unlink( $item->getPathname() );
		}
	}

	rmdir( $directory );
}

remove_generated_directory( $output );
mkdir( $output, 0777, true );

/**
 * Fetch a URL and return body plus response content type.
 *
 * @return array{body:string,content_type:string,status:int}
 */
function fetch_url_with_curl_exe( string $url ): array {
	$body_file    = tempnam( sys_get_temp_dir(), 'lks-body-' );
	$headers_file = tempnam( sys_get_temp_dir(), 'lks-head-' );

	if ( false === $body_file || false === $headers_file ) {
		throw new RuntimeException( 'Could not create temporary files for curl.exe.' );
	}

	$command = array(
		'curl.exe',
		'--location',
		'--fail',
		'--silent',
		'--show-error',
		'--max-redirs',
		'5',
		'--connect-timeout',
		'15',
		'--max-time',
		'60',
		'--user-agent',
		'Lakeuden Kauppaseura offline exporter',
		'--header',
		'Accept: */*',
		'--dump-header',
		$headers_file,
		'--output',
		$body_file,
		$url,
	);

	$process = proc_open(
		$command,
		array(
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		),
		$pipes
	);

	if ( ! is_resource( $process ) ) {
		@unlink( $body_file );
		@unlink( $headers_file );
		throw new RuntimeException( 'Could not start curl.exe.' );
	}

	$output = stream_get_contents( $pipes[1] );
	$error  = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );

	$exit_code = proc_close( $process );
	$body      = file_get_contents( $body_file );
	$headers   = file( $headers_file, FILE_IGNORE_NEW_LINES ) ?: array();
	@unlink( $body_file );
	@unlink( $headers_file );

	$status       = 0;
	$content_type = '';

	foreach ( $headers as $header ) {
		if ( preg_match( '#^HTTP/\S+\s+(\d+)#i', $header, $status_match ) ) {
			$status = (int) $status_match[1];
		}
		if ( preg_match( '#^Content-Type:\s*(.+)$#i', $header, $type_match ) ) {
			$content_type = strtolower( trim( explode( ';', $type_match[1] )[0] ?? '' ) );
		}
	}

	if ( 0 !== $exit_code || false === $body || $status < 200 || $status >= 400 ) {
		throw new RuntimeException( "Could not fetch {$url} with curl.exe (HTTP {$status}) {$error}{$output}" );
	}

	return array(
		'body'         => (string) $body,
		'content_type' => $content_type,
		'status'       => $status,
	);
}

function fetch_url( string $url ): array {
	if ( ! function_exists( 'curl_init' ) ) {
		$scheme = strtolower( parse_url( $url, PHP_URL_SCHEME ) ?: '' );
		if ( 'https' === $scheme && ! in_array( 'https', stream_get_wrappers(), true ) ) {
			return fetch_url_with_curl_exe( $url );
		}

		$headers = array(
			'Accept: */*',
			'User-Agent: Lakeuden Kauppaseura offline exporter',
		);
		$context = stream_context_create(
			array(
				'http' => array(
					'follow_location' => 1,
					'ignore_errors'    => true,
					'max_redirects'    => 5,
					'timeout'          => 60,
					'header'           => implode( "\r\n", $headers ),
				),
			)
		);

		$body = file_get_contents( $url, false, $context );
		$response_headers = $http_response_header ?? array();
		$status = 0;
		$content_type = '';

		foreach ( $response_headers as $header ) {
			if ( preg_match( '#^HTTP/\S+\s+(\d+)#i', $header, $status_match ) ) {
				$status = (int) $status_match[1];
			}
			if ( preg_match( '#^Content-Type:\s*(.+)$#i', $header, $type_match ) ) {
				$content_type = strtolower( trim( explode( ';', $type_match[1] )[0] ?? '' ) );
			}
		}

		if ( false === $body || $status < 200 || $status >= 400 ) {
			throw new RuntimeException( "Could not fetch {$url} (HTTP {$status})" );
		}

		return array(
			'body'         => (string) $body,
			'content_type' => $content_type,
			'status'       => $status,
		);
	}

	$handle = curl_init( $url );
	curl_setopt_array(
		$handle,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS      => 5,
			CURLOPT_CONNECTTIMEOUT => 15,
			CURLOPT_TIMEOUT        => 60,
			CURLOPT_USERAGENT      => 'Lakeuden Kauppaseura offline exporter',
			CURLOPT_HTTPHEADER     => array( 'Accept: */*' ),
		)
	);

	$body         = curl_exec( $handle );
	$status       = (int) curl_getinfo( $handle, CURLINFO_RESPONSE_CODE );
	$content_type = (string) curl_getinfo( $handle, CURLINFO_CONTENT_TYPE );
	$error        = curl_error( $handle );
	curl_close( $handle );

	if ( false === $body || $status < 200 || $status >= 400 ) {
		throw new RuntimeException( "Could not fetch {$url} (HTTP {$status}) {$error}" );
	}

	return array(
		'body'         => (string) $body,
		'content_type' => strtolower( trim( explode( ';', $content_type )[0] ?? '' ) ),
		'status'       => $status,
	);
}

/**
 * Normalize dot segments in a URL path.
 */
function normalize_path( string $path ): string {
	$segments = explode( '/', $path );
	$output   = array();

	foreach ( $segments as $segment ) {
		if ( '' === $segment || '.' === $segment ) {
			continue;
		}
		if ( '..' === $segment ) {
			array_pop( $output );
			continue;
		}
		$output[] = $segment;
	}

	return '/' . implode( '/', $output );
}

/**
 * Resolve a URL reference against an absolute base URL.
 */
function resolve_url( string $base, string $reference ): string {
	$reference = html_entity_decode( trim( $reference ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	if ( '' === $reference || preg_match( '#^(?:data:|blob:|mailto:|tel:|javascript:)#i', $reference ) ) {
		return $reference;
	}

	if ( str_starts_with( $reference, '#' ) ) {
		$base_parts = parse_url( $base );
		return ( $base_parts['scheme'] ?? 'http' ) . '://' . ( $base_parts['host'] ?? '' ) . ( $base_parts['path'] ?? '/' ) . $reference;
	}

	if ( preg_match( '#^https?://#i', $reference ) ) {
		return $reference;
	}

	$base_parts = parse_url( $base );
	$scheme     = $base_parts['scheme'] ?? 'http';
	$host       = $base_parts['host'] ?? '';
	$port       = isset( $base_parts['port'] ) ? ':' . $base_parts['port'] : '';

	if ( str_starts_with( $reference, '//' ) ) {
		return $scheme . ':' . $reference;
	}

	$fragment = '';
	if ( str_contains( $reference, '#' ) ) {
		list( $reference, $fragment_value ) = explode( '#', $reference, 2 );
		$fragment = '#' . $fragment_value;
	}

	$query = '';
	if ( str_contains( $reference, '?' ) ) {
		list( $reference, $query_value ) = explode( '?', $reference, 2 );
		$query = '?' . $query_value;
	}

	if ( '' === $reference ) {
		$path = $base_parts['path'] ?? '/';
	} elseif ( str_starts_with( $reference, '/' ) ) {
		$path = normalize_path( $reference );
	} else {
		$base_path = $base_parts['path'] ?? '/';
		$directory = str_ends_with( $base_path, '/' ) ? $base_path : dirname( $base_path ) . '/';
		$path      = normalize_path( $directory . $reference );
	}

	return $scheme . '://' . $host . $port . $path . $query . $fragment;
}

/**
 * Convert an internal URL into its stable lookup form.
 */
function normalized_page_url( string $url, string $origin ): string {
	$parts = parse_url( $url );
	$path  = $parts['path'] ?? '/';
	$path  = '/' === $path ? '/' : rtrim( $path, '/' ) . '/';
	return rtrim( $origin, '/' ) . $path;
}

/**
 * Map a page URL to an offline HTML path.
 */
function page_file_path( string $url ): string {
	$path = rawurldecode( parse_url( $url, PHP_URL_PATH ) ?: '/' );
	if ( '/' === $path || '' === trim( $path, '/' ) ) {
		return 'index.html';
	}

	$segments = array_filter( explode( '/', trim( $path, '/' ) ) );
	$segments = array_map(
		static fn( string $segment ): string => preg_replace( '/[^A-Za-z0-9._-]+/', '-', $segment ) ?: 'page',
		$segments
	);
	return implode( '/', $segments ) . '/index.html';
}

/**
 * Calculate a browser-friendly relative path between two generated files.
 */
function relative_file_path( string $from_file, string $to_file ): string {
	$from_parts = array_values( array_filter( explode( '/', dirname( $from_file ) ), static fn( string $part ): bool => '.' !== $part && '' !== $part ) );
	$to_parts   = array_values( array_filter( explode( '/', $to_file ), static fn( string $part ): bool => '' !== $part ) );

	while ( $from_parts && $to_parts && $from_parts[0] === $to_parts[0] ) {
		array_shift( $from_parts );
		array_shift( $to_parts );
	}

	$relative = str_repeat( '../', count( $from_parts ) ) . implode( '/', $to_parts );
	return '' === $relative ? './' : $relative;
}

/**
 * Identify URLs that should be downloaded as static assets.
 */
function is_asset_url( string $url, string $source_host ): bool {
	if ( ! preg_match( '#^https?://#i', $url ) ) {
		return false;
	}

	$parts = parse_url( $url );
	$host  = strtolower( $parts['host'] ?? '' );
	$path  = strtolower( $parts['path'] ?? '' );

	if ( $host === strtolower( $source_host ) && preg_match( '#^/(?:wp-content|wp-includes)/#', $path ) ) {
		return true;
	}

	return (bool) preg_match( '#\.(?:css|js|gif|jpe?g|png|svg|webp|avif|ico|woff2?|ttf|otf|eot|mp4|webm|mp3|wav)(?:$|/)#i', $path );
}

/**
 * Map a remote asset URL to a deterministic local path.
 */
function asset_file_path( string $url, string $source_host ): string {
	$parts = parse_url( $url );
	$host  = strtolower( $parts['host'] ?? '' );
	$path  = rawurldecode( $parts['path'] ?? '' );
	$root_assets = array( '/favicon.ico', '/favicon-32x32.png', '/apple-touch-icon.png' );
	if ( $host === strtolower( $source_host ) && in_array( $path, $root_assets, true ) ) {
		return ltrim( $path, '/' );
	}

	if ( $host === strtolower( $source_host ) && preg_match( '#^/(wp-content|wp-includes)/#', $path ) ) {
		$segments = array_filter( explode( '/', trim( $path, '/' ) ) );
		$segments = array_map(
			static fn( string $segment ): string => preg_replace( '/[^A-Za-z0-9._-]+/', '-', $segment ) ?: 'asset',
			$segments
		);
		return implode( '/', $segments );
	}

	$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	if ( ! preg_match( '/^[a-z0-9]{1,6}$/', $extension ) ) {
		$extension = 'bin';
	}

	$is_instagram_cdn = (bool) preg_match( '/(?:^|\.)cdninstagram\.com$/i', $host );
	if ( $is_instagram_cdn ) {
		// Instagram rotates signed query parameters and edge hostnames even when
		// the public image is unchanged. Its path contains the stable media ID.
		return 'assets/external/cdninstagram.com/' . sha1( $path ) . '.' . $extension;
	}

	$host_directory = preg_replace( '/[^A-Za-z0-9.-]+/', '-', $host ) ?: 'external';
	return 'assets/external/' . $host_directory . '/' . sha1( $url ) . '.' . $extension;
}

/**
 * Write a file, creating its directory first.
 */
function write_generated_file( string $root, string $relative_path, string $contents ): void {
	$destination = $root . '/' . $relative_path;
	$directory   = dirname( $destination );
	if ( ! is_dir( $directory ) ) {
		mkdir( $directory, 0777, true );
	}
	file_put_contents( $destination, $contents );
}

/**
 * Remove scripts that require WordPress while keeping metadata and site.js.
 */
function remove_runtime_scripts( string $html ): string {
	return (string) preg_replace_callback(
		'#<script\b[^>]*>.*?</script>#is',
		static function ( array $match ): string {
			$tag = $match[0];
			if ( preg_match( '#\btype\s*=\s*(["\'])application/ld\+json\1#i', $tag ) || str_contains( $tag, 'lakeuden-kauppaseura-site-js' ) || str_contains( $tag, '/assets/js/site.js' ) ) {
				return $tag;
			}
			return '';
		},
		$html
	);
}

/**
 * Remove unmatched closing paragraph tokens without wrapping block content.
 */
function remove_stray_paragraph_closers( string $html ): string {
	$depth = 0;
	return (string) preg_replace_callback(
		'#</?p\b[^>]*>#i',
		static function ( array $match ) use ( &$depth ): string {
			if ( str_starts_with( strtolower( $match[0] ), '</p' ) ) {
				if ( 0 === $depth ) {
					return '';
				}
				--$depth;
				return $match[0];
			}
			++$depth;
			return $match[0];
		},
		$html
	);
}

/**
 * Add intrinsic dimensions to exported images when the local file is known.
 */
function add_image_dimensions( string $html, string $page_file, string $root ): string {
	return (string) preg_replace_callback(
		'#<img\b[^>]*>#i',
		static function ( array $match ) use ( $page_file, $root ): string {
			$tag = $match[0];
			if ( preg_match( '#\bwidth\s*=#i', $tag ) && preg_match( '#\bheight\s*=#i', $tag ) ) {
				return $tag;
			}
			if ( ! preg_match( '#\bsrc\s*=\s*(["\'])(.*?)\1#i', $tag, $source_match ) ) {
				return $tag;
			}
			$source = html_entity_decode( $source_match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( preg_match( '#^(?:https?:|data:|blob:)#i', $source ) ) {
				return $tag;
			}
			$source_path = parse_url( $source, PHP_URL_PATH ) ?: '';
			$local_path  = ltrim( normalize_path( dirname( '/' . $page_file ) . '/' . $source_path ), '/' );
			$size        = @getimagesize( $root . '/' . $local_path );
			if ( ! $size ) {
				return $tag;
			}
			$attributes = '';
			if ( ! preg_match( '#\bwidth\s*=#i', $tag ) ) {
				$attributes .= ' width="' . (int) $size[0] . '"';
			}
			if ( ! preg_match( '#\bheight\s*=#i', $tag ) ) {
				$attributes .= ' height="' . (int) $size[1] . '"';
			}
			$self_closing = str_ends_with( rtrim( $tag ), '/>' );
			$tag          = (string) preg_replace( '#\s*/?>$#', '', $tag );
			return $tag . $attributes . ( $self_closing ? ' />' : '>' );
		},
		$html
	);
}

/**
 * Add the current-page state to desktop and mobile navigation markup.
 */
function add_current_navigation_state( string $html, string $page_url ): string {
	$path = parse_url( $page_url, PHP_URL_PATH ) ?: '/';
	if ( '/' === $path ) {
		$label = 'Etusivu';
	} elseif ( str_starts_with( $path, '/meista/' ) ) {
		$label = 'Meistä';
	} elseif ( str_starts_with( $path, '/tapahtumat/' ) || str_starts_with( $path, '/tapahtuma/' ) ) {
		$label = 'Tapahtumat';
	} elseif ( str_starts_with( $path, '/yhteystiedot/' ) ) {
		$label = 'Yhteystiedot';
	} elseif ( ! str_starts_with( $path, '/tietosuoja/' ) ) {
		$label = 'Blogi';
	} else {
		return $html;
	}

	return (string) preg_replace_callback(
		'#<nav\b[^>]*aria-label=(["\'])(?:Päävalikko|Mobiilivalikko)\1[^>]*>.*?</nav>#isu',
		static function ( array $nav_match ) use ( $label ): string {
			return (string) preg_replace_callback(
				'#<a\b[^>]*>.*?</a>#isu',
				static function ( array $link_match ) use ( $label ): string {
					$link = preg_replace( '#\saria-current=(["\'])page\1#i', '', $link_match[0] );
					if ( trim( strip_tags( html_entity_decode( $link, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) !== $label ) {
						return $link;
					}
					return preg_replace( '#^<a\b#i', '<a aria-current="page"', $link, 1 ) ?: $link;
				},
				$nav_match[0]
			);
		},
		$html
	);
}

try {
	$source_host = parse_url( $source_origin, PHP_URL_HOST ) ?: '';
	$sitemaps    = array(
		$source_origin . '/wp-sitemap-posts-page-1.xml',
		$source_origin . '/wp-sitemap-posts-post-1.xml',
		$source_origin . '/wp-sitemap-posts-lks_event-1.xml',
	);
	$page_urls = array();

	foreach ( $sitemaps as $sitemap_url ) {
		$sitemap = fetch_url( $sitemap_url )['body'];
		preg_match_all( '#<loc>(.*?)</loc>#i', $sitemap, $matches );
		foreach ( $matches[1] as $url ) {
			$url = html_entity_decode( trim( $url ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( strtolower( parse_url( $url, PHP_URL_HOST ) ?: '' ) === strtolower( $source_host ) ) {
				$page_urls[] = normalized_page_url( $url, $source_origin );
			}
		}
	}

	$page_urls = array_values( array_unique( $page_urls ) );
	usort(
		$page_urls,
		static function ( string $left, string $right ) use ( $source_origin ): int {
			if ( rtrim( $left, '/' ) === rtrim( $source_origin, '/' ) ) {
				return -1;
			}
			if ( rtrim( $right, '/' ) === rtrim( $source_origin, '/' ) ) {
				return 1;
			}
			return strcmp( $left, $right );
		}
	);

	$page_map  = array();
	$page_html = array();

	foreach ( $page_urls as $url ) {
		$key              = normalized_page_url( $url, $source_origin );
		$page_map[ $key ] = page_file_path( $url );
		$page_html[ $key ] = fetch_url( $url )['body'];
		echo "Fetched page: {$url}\n";
	}

	$asset_queue = array();
	$asset_seen  = array();
	$asset_map   = array();

	$queue_asset = static function ( string $asset_url ) use ( &$asset_queue, &$asset_seen, &$asset_map, $source_host ): void {
		$asset_url = html_entity_decode( trim( $asset_url ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( ! is_asset_url( $asset_url, $source_host ) || isset( $asset_seen[ $asset_url ] ) ) {
			return;
		}
		$asset_seen[ $asset_url ] = true;
		$asset_map[ $asset_url ]  = asset_file_path( $asset_url, $source_host );
		$asset_queue[]            = $asset_url;
	};

	foreach ( $page_html as $page_url => $html ) {
		// Keep only the theme's dependency-free accessibility/navigation script.
		preg_match_all( '#<script\b[^>]*\bsrc\s*=\s*(["\'])(.*?/assets/js/site\.js(?:\?[^"\']*)?)\1[^>]*>#is', $html, $site_script_matches );
		foreach ( $site_script_matches[2] as $reference ) {
			$queue_asset( resolve_url( $page_url, $reference ) );
		}

		// Do not mistake other script-module src attributes for portable page assets.
		$asset_html = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $html );

		// Images, video sources and posters.
		preg_match_all( '#\b(?:src|poster|data-src|data-lazy-src)\s*=\s*(["\'])(.*?)\1#is', $asset_html, $asset_matches );
		foreach ( $asset_matches[2] as $reference ) {
			$resolved = resolve_url( $page_url, $reference );
			$queue_asset( $resolved );
		}

		// Responsive image candidates.
		preg_match_all( '#\b(?:srcset|data-srcset)\s*=\s*(["\'])(.*?)\1#is', $asset_html, $srcset_matches );
		foreach ( $srcset_matches[2] as $srcset ) {
			foreach ( explode( ',', html_entity_decode( $srcset, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) as $candidate ) {
				$url = preg_split( '/\s+/', trim( $candidate ) )[0] ?? '';
				if ( $url ) {
					$queue_asset( resolve_url( $page_url, $url ) );
				}
			}
		}

		// Stylesheets and icons.
		preg_match_all( '#<link\b[^>]*\brel\s*=\s*(["\'])(?:stylesheet|icon|shortcut icon|apple-touch-icon)\1[^>]*\bhref\s*=\s*(["\'])(.*?)\2[^>]*>#is', $asset_html, $link_matches );
		foreach ( $link_matches[3] as $reference ) {
			$queue_asset( resolve_url( $page_url, $reference ) );
		}
		// Also support href appearing before rel.
		preg_match_all( '#<link\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*\brel\s*=\s*(["\'])(?:stylesheet|icon|shortcut icon|apple-touch-icon)\3[^>]*>#is', $asset_html, $reverse_link_matches );
		foreach ( $reverse_link_matches[2] as $reference ) {
			$queue_asset( resolve_url( $page_url, $reference ) );
		}
	}

	for ( $index = 0; $index < count( $asset_queue ); $index++ ) {
		$asset_url  = $asset_queue[ $index ];
		$asset_path = $asset_map[ $asset_url ];
		$response    = fetch_url( $asset_url );
		$body        = $response['body'];

		if ( 'text/css' === $response['content_type'] || str_ends_with( strtolower( parse_url( $asset_url, PHP_URL_PATH ) ?: '' ), '.css' ) ) {
			$body = preg_replace_callback(
				'#url\(\s*(["\']?)(.*?)\1\s*\)#i',
				static function ( array $match ) use ( $asset_url, $asset_path, &$asset_map, $queue_asset ): string {
					$reference = trim( $match[2] );
					if ( '' === $reference || str_starts_with( $reference, 'data:' ) || str_starts_with( $reference, '#' ) ) {
						return $match[0];
					}
					$resolved = resolve_url( $asset_url, $reference );
					$queue_asset( $resolved );
					if ( ! isset( $asset_map[ $resolved ] ) ) {
						return $match[0];
					}
					return 'url("' . relative_file_path( $asset_path, $asset_map[ $resolved ] ) . '")';
				},
				$body
			);
		}

		write_generated_file( $output, $asset_path, $body );
		echo "Downloaded asset: {$asset_url}\n";
	}

	$production_base = 'https://addern0b0.github.io/kauppaseura/';
	$redirects       = array(
		'/syksyn-verkostoitumisilta-seinajoella/' => 'tapahtuma/syksyn-verkostoitumisilta-seinajoella/',
		'/ajankohtaiskatsaus-ja-yritysvierailu/' => 'tapahtuma/ajankohtaiskatsaus-ja-yritysvierailu/',
	);

	foreach ( $page_html as $page_url => $html ) {
		$page_file = $page_map[ $page_url ];
		$page_path = parse_url( $page_url, PHP_URL_PATH ) ?: '/';

		if ( isset( $redirects[ $page_path ] ) ) {
			$target_route = $redirects[ $page_path ];
			$canonical    = $production_base . $target_route;
			$target_file  = page_file_path( $source_origin . '/' . $target_route );
			$fallback     = relative_file_path( $page_file, $target_file );
			$theme_css    = relative_file_path( $page_file, 'wp-content/themes/lakeuden-kauppaseura/style.css' );
			$parent_css   = relative_file_path( $page_file, 'wp-content/themes/twentytwentyfive/style.css' );
			$icon         = relative_file_path( $page_file, 'favicon-32x32.png' );
			$redirect_html = '<!doctype html><html lang="fi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Siirrytään tapahtumasivulle – Lakeuden Kauppaseura</title><meta name="robots" content="noindex,follow"><link rel="canonical" href="' . htmlspecialchars( $canonical, ENT_QUOTES, 'UTF-8' ) . '"><meta http-equiv="refresh" content="0;url=' . htmlspecialchars( $canonical, ENT_QUOTES, 'UTF-8' ) . '"><link rel="icon" type="image/png" sizes="32x32" href="' . $icon . '"><link rel="stylesheet" href="' . $parent_css . '"><link rel="stylesheet" href="' . $theme_css . '"></head><body><a class="lks-skip-link" href="#main">Siirry sisältöön</a><div id="main" class="lks-page" role="main"><section class="lks-subpage-hero"><div class="lks-page-shell"><h1>Siirrytään tapahtumasivulle.</h1><p>Jos siirtyminen ei käynnisty automaattisesti, avaa tapahtuman varsinainen sivu.</p><p><a class="lks-button lks-button--gold" href="' . $fallback . '">Siirry tapahtumasivulle</a></p></div></section></div><footer class="lks-footer"><div class="lks-footer__bottom"><p>© Lakeuden Kauppaseura ry</p></div></footer></body></html>';
			write_generated_file( $output, $page_file, $redirect_html );
			echo "Wrote consolidation page: {$page_file}\n";
			continue;
		}

		// Remove dynamic scripts and WordPress discovery/feed metadata.
		$html = remove_runtime_scripts( $html );
		$html = preg_replace( '#<link\b[^>]*\brel\s*=\s*(["\'])(?:alternate|EditURI|https://api\.w\.org/|shortlink)\1[^>]*>#is', '', $html );
		$html = preg_replace( '#<link\b[^>]*\brel\s*=\s*(["\'])(?:modulepreload|preload|prefetch|dns-prefetch|preconnect)\1[^>]*>#is', '', $html );
		$html = preg_replace( '#<link\b[^>]*\bhref\s*=\s*(["\'])[^"\']*wpforms[^"\']*\1[^>]*>#is', '', $html );
		$html = preg_replace( '#<style\b[^>]*(?:id|class)\s*=\s*(["\'])[^"\']*wpforms[^"\']*\1[^>]*>.*?</style>#is', '', $html );
		$html = preg_replace( '~\/\*#\s*sourceURL=.*?\*\/\s*~i', '', $html );

		$rewrite_attribute = static function ( array $match ) use ( $page_url, $page_file, $page_map, $asset_map, $source_origin, $source_host ): string {
			$attribute = $match[1];
			$quote     = $match[2];
			$original  = $match[3];

			if ( '' === trim( $original ) || str_starts_with( trim( $original ), '#' ) || preg_match( '#^(?:data:|blob:|mailto:|tel:|javascript:)#i', trim( $original ) ) ) {
				return $match[0];
			}

			$resolved = resolve_url( $page_url, $original );
			$fragment = parse_url( $resolved, PHP_URL_FRAGMENT );
			$suffix   = $fragment ? '#' . $fragment : '';

			if ( isset( $asset_map[ $resolved ] ) ) {
				return $attribute . '=' . $quote . relative_file_path( $page_file, $asset_map[ $resolved ] ) . $quote;
			}

			$resolved_without_fragment = preg_replace( '/#.*$/', '', $resolved );
			if ( isset( $asset_map[ $resolved_without_fragment ] ) ) {
				return $attribute . '=' . $quote . relative_file_path( $page_file, $asset_map[ $resolved_without_fragment ] ) . $suffix . $quote;
			}

			if ( strtolower( parse_url( $resolved, PHP_URL_HOST ) ?: '' ) === strtolower( $source_host ) ) {
				$page_key = normalized_page_url( $resolved, $source_origin );
				if ( isset( $page_map[ $page_key ] ) ) {
					return $attribute . '=' . $quote . relative_file_path( $page_file, $page_map[ $page_key ] ) . $suffix . $quote;
				}
			}

			return $match[0];
		};

		$html = preg_replace_callback( '#\b(href|src|poster|data-src|data-lazy-src)\s*=\s*(["\'])(.*?)\2#is', $rewrite_attribute, $html );

		$html = preg_replace_callback(
			'#\b(srcset|data-srcset)\s*=\s*(["\'])(.*?)\2#is',
			static function ( array $match ) use ( $page_url, $page_file, $asset_map ): string {
				$candidates = array();
				foreach ( explode( ',', html_entity_decode( $match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) as $candidate ) {
					$parts     = preg_split( '/\s+/', trim( $candidate ), 2 );
					$reference = $parts[0] ?? '';
					$descriptor = isset( $parts[1] ) ? ' ' . $parts[1] : '';
					$resolved   = resolve_url( $page_url, $reference );
					$candidates[] = ( isset( $asset_map[ $resolved ] ) ? relative_file_path( $page_file, $asset_map[ $resolved ] ) : $reference ) . $descriptor;
				}
				return $match[1] . '=' . $match[2] . implode( ', ', $candidates ) . $match[2];
			},
			$html
		);

		$html = add_current_navigation_state( $html, $page_url );
		$html = remove_stray_paragraph_closers( $html );
		$html = add_image_dimensions( $html, $page_file, $output );
		$html = (string) preg_replace( '/[ \t]+$/m', '', $html );

		write_generated_file( $output, $page_file, $html );
		echo "Wrote page: {$page_file}\n";
	}

	$canonical_urls = array();
	foreach ( array_keys( $page_map ) as $page_url ) {
		$path = parse_url( $page_url, PHP_URL_PATH ) ?: '/';
		if ( isset( $redirects[ $path ] ) ) {
			continue;
		}
		$canonical_urls[] = $production_base . ( '/' === $path ? '' : ltrim( $path, '/' ) );
	}
	sort( $canonical_urls );
	$home_index = array_search( $production_base, $canonical_urls, true );
	if ( false !== $home_index ) {
		unset( $canonical_urls[ $home_index ] );
		array_unshift( $canonical_urls, $production_base );
	}

	$sitemap_entries = array_map(
		static fn( string $url ): string => '  <url><loc>' . htmlspecialchars( $url, ENT_XML1 | ENT_QUOTES, 'UTF-8' ) . '</loc></url>',
		$canonical_urls
	);
	$sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n" . implode( "\n", $sitemap_entries ) . "\n</urlset>\n";
	$robots = "User-agent: *\nAllow: /\nSitemap: https://addern0b0.github.io/kauppaseura/sitemap.xml\n";

	$not_found = <<<'HTML'
<!doctype html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,follow">
	<title>Sivua ei löytynyt – Lakeuden Kauppaseura</title>
	<link rel="icon" href="/kauppaseura/favicon.ico" sizes="any">
	<link rel="icon" href="/kauppaseura/favicon-32x32.png" type="image/png" sizes="32x32">
	<link rel="apple-touch-icon" href="/kauppaseura/apple-touch-icon.png">
	<link rel="stylesheet" href="/kauppaseura/wp-content/themes/twentytwentyfive/style.css">
	<link rel="stylesheet" href="/kauppaseura/wp-content/themes/lakeuden-kauppaseura/style.css">
</head>
<body>
	<a class="lks-skip-link" href="#main">Siirry sisältöön</a>
	<header class="lks-site-header"><div class="lks-site-header__inner alignwide"><a href="/kauppaseura/" aria-label="Lakeuden Kauppaseura – etusivu"><img src="/kauppaseura/wp-content/themes/lakeuden-kauppaseura/assets/lakeuden-kauppaseura-logo-transparent.png" width="304" height="138" alt="Lakeuden Kauppaseura ry"></a><nav aria-label="Päävalikko"><a href="/kauppaseura/">Etusivu</a> · <a href="/kauppaseura/meista/">Meistä</a> · <a href="/kauppaseura/tapahtumat/">Tapahtumat</a> · <a href="/kauppaseura/blogi/">Blogi</a> · <a href="/kauppaseura/yhteystiedot/">Yhteystiedot</a></nav></div></header>
	<div id="main" class="lks-page" role="main"><section class="lks-subpage-hero"><div class="lks-page-shell"><p class="lks-kicker lks-kicker--light">Virhe 404</p><h1>Sivua ei löytynyt.</h1><p>Osoite voi olla vanhentunut tai sivu on siirretty. Voit palata etusivulle, katsoa tulevat tapahtumat tai ottaa yhteyttä.</p><div class="lks-home-hero__actions"><a class="lks-button lks-button--gold" href="/kauppaseura/">Etusivulle</a><a class="lks-text-link lks-text-link--light" href="/kauppaseura/tapahtumat/">Katso tapahtumat</a><a class="lks-text-link lks-text-link--light" href="/kauppaseura/yhteystiedot/">Ota yhteyttä</a></div></div></section></div>
	<footer class="lks-footer"><div class="lks-footer__bottom"><p>© Lakeuden Kauppaseura ry</p><p><a href="/kauppaseura/tietosuoja/">Tietosuoja</a></p></div></footer>
</body>
</html>
HTML;

	write_generated_file( $output, 'robots.txt', $robots );
	write_generated_file( $output, 'sitemap.xml', $sitemap );
	write_generated_file( $output, '404.html', $not_found );
	write_generated_file( $output, '.nojekyll', '' );

	$readme = <<<TXT
LAKEUDEN KAUPPASEURA – OFFLINE-SIVUSTO

Näin avaat sivuston:
1. Pura ZIP-paketti kokonaan omaan kansioonsa.
2. Avaa index.html tavallisella verkkoselaimella.

Sivusto toimii paikallisesti ilman WordPressiä, PHP:tä, tietokantaa tai
verkkopalvelinta. Sivut, artikkelit, tapahtumat, tyylit ja kuvat ovat mukana.

Huomioitavaa:
- Instagram-, Facebook- ja alkuperäislähteiden linkit vaativat internetyhteyden.
- Instagramin tämänhetkiset esikatselukuvat on tallennettu pakettiin.
- Tämän kansion HTML-tiedostot ovat WordPress-lähteestä luotu julkaisuversio.
  Suorat muutokset niihin voivat ylikirjoittua seuraavassa viennissä. Tee pysyvät
  sisältö-, teema- ja vientimuutokset ensin WordPress-lähteeseen ja vie sivusto uudelleen.

Luotu: {$source_origin}
TXT;

	write_generated_file( $output, 'README.txt', $readme );
	write_generated_file(
		$output,
		'export-manifest.json',
		json_encode(
			array(
				'source'      => $source_origin,
				'pages'       => $page_map,
				'asset_count' => count( $asset_map ),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		) . "\n"
	);

	echo "\nStatic export complete: " . count( $page_map ) . ' pages, ' . count( $asset_map ) . " assets.\n";
	echo "Output: {$output}\n";
} catch ( Throwable $error ) {
	fwrite( STDERR, "Export failed: {$error->getMessage()}\n" );
	exit( 1 );
}
