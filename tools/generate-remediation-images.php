<?php
/**
 * Generate deterministic responsive hero and favicon assets using GD.
 *
 * Usage: php tools/generate-remediation-images.php
 */

declare(strict_types=1);

if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 404 );
	exit;
}

if ( ! extension_loaded( 'gd' ) || ! function_exists( 'imagewebp' ) ) {
	fwrite( STDERR, "The GD extension with WebP support is required.\n" );
	exit( 1 );
}

$root        = dirname( __DIR__ );
$hero_source = $root . '/wp-content/uploads/2026/06/3c1d1a_9ae29c5d39f04fa8adbfc565a9312fd1mv2.jpg';
$hero_dir    = $root . '/wp-content/themes/lakeuden-kauppaseura/assets/images/hero';
$logo_source = $root . '/wp-content/themes/lakeuden-kauppaseura/assets/lakeuden-kauppaseura-logo-clean.png';

if ( ! is_file( $hero_source ) || ! is_file( $logo_source ) ) {
	fwrite( STDERR, "Approved source artwork is missing.\n" );
	exit( 1 );
}

if ( ! is_dir( $hero_dir ) ) {
	mkdir( $hero_dir, 0777, true );
}

/**
 * Resize a true-colour image without changing its aspect ratio.
 */
function lks_resize( GdImage $source, int $source_width, int $source_height, int $target_width ): GdImage {
	$target_height = (int) round( $source_height * ( $target_width / $source_width ) );
	$target        = imagecreatetruecolor( $target_width, $target_height );
	imagealphablending( $target, false );
	imagesavealpha( $target, true );
	imagecopyresampled( $target, $source, 0, 0, 0, 0, $target_width, $target_height, $source_width, $source_height );
	return $target;
}

$hero = imagecreatefromjpeg( $hero_source );

if ( ! $hero ) {
	fwrite( STDERR, "Could not decode the homepage hero.\n" );
	exit( 1 );
}

$hero_width  = imagesx( $hero );
$hero_height = imagesy( $hero );

foreach ( array( 640, 960, 1440, 1920 ) as $width ) {
	$resized = lks_resize( $hero, $hero_width, $hero_height, $width );
	$base    = $hero_dir . '/lakeuden-kauppaseura-hero-' . $width;
	imageinterlace( $resized, true );
	imagejpeg( $resized, $base . '.jpg', 78 );
	imagewebp( $resized, $base . '.webp', 76 );
	imagedestroy( $resized );
}

imagedestroy( $hero );

// The approved wordmark contains a self-contained shield on its left. Crop
// that existing emblem rather than redrawing or inventing a favicon.
$logo = imagecreatefrompng( $logo_source );

if ( ! $logo ) {
	fwrite( STDERR, "Could not decode the approved logo.\n" );
	exit( 1 );
}

$emblem_width = min( 84, imagesx( $logo ) );
$emblem       = imagecrop( $logo, array( 'x' => 0, 'y' => 0, 'width' => $emblem_width, 'height' => imagesy( $logo ) ) );
imagedestroy( $logo );

if ( ! $emblem ) {
	fwrite( STDERR, "Could not crop the approved emblem.\n" );
	exit( 1 );
}

foreach ( array( 32 => 'favicon-32x32.png', 180 => 'apple-touch-icon.png' ) as $size => $filename ) {
	$canvas = imagecreatetruecolor( $size, $size );
	imagealphablending( $canvas, false );
	imagesavealpha( $canvas, true );
	$transparent = imagecolorallocatealpha( $canvas, 0, 0, 0, 127 );
	imagefill( $canvas, 0, 0, $transparent );

	$padding       = max( 2, (int) round( $size * 0.08 ) );
	$target_height = $size - ( 2 * $padding );
	$target_width  = (int) round( imagesx( $emblem ) * ( $target_height / imagesy( $emblem ) ) );
	$x             = (int) floor( ( $size - $target_width ) / 2 );
	imagecopyresampled( $canvas, $emblem, $x, $padding, 0, 0, $target_width, $target_height, imagesx( $emblem ), imagesy( $emblem ) );
	imagepng( $canvas, $root . '/' . $filename, 9 );
	imagedestroy( $canvas );
}

// Browsers still request favicon.ico implicitly. GD cannot encode ICO, so use
// the valid 32 px PNG payload at that conventional path; modern browsers sniff
// and render it correctly, while the explicit PNG link remains authoritative.
copy( $root . '/favicon-32x32.png', $root . '/favicon.ico' );
imagedestroy( $emblem );

echo "Responsive hero and favicon assets generated.\n";
