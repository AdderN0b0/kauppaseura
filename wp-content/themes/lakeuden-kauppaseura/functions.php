<?php
/**
 * Theme bootstrap and dynamic homepage for Lakeuden Kauppaseura.
 *
 * @package Lakeuden_Kauppaseura
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/inc/site-configuration.php';
require_once get_stylesheet_directory() . '/inc/page-copy.php';
require_once get_stylesheet_directory() . '/inc/people.php';
require_once get_stylesheet_directory() . '/inc/membership-page.php';
require_once get_stylesheet_directory() . '/inc/privacy-page.php';
require_once get_stylesheet_directory() . '/inc/structured-data.php';

/**
 * Return an attachment's editor-authored alt text or a safe contextual fallback.
 *
 * WordPress attachment titles frequently start as filenames. Those are not
 * useful alternatives, so they are never exposed as alt text by this helper.
 *
 * @param int    $attachment_id Attachment post ID.
 * @param string $fallback      Context known by the calling component.
 * @return string
 */
function lakeuden_kauppaseura_attachment_alt( $attachment_id, $fallback = '' ) {
	$attachment_id = absint( $attachment_id );
	$alt           = $attachment_id ? trim( wp_strip_all_tags( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) : '';
	$file          = $attachment_id ? (string) get_attached_file( $attachment_id ) : '';
	$file_stem     = $file ? pathinfo( $file, PATHINFO_FILENAME ) : '';

	if ( $alt && ( ! $file_stem || sanitize_title( $alt ) !== sanitize_title( $file_stem ) ) ) {
		return $alt;
	}

	return trim( wp_strip_all_tags( $fallback ) );
}

/**
 * Load the parent theme and the site styles.
 */
function lakeuden_kauppaseura_enqueue_styles() {
	wp_enqueue_style(
		'lakeuden-kauppaseura-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme( get_template() )->get( 'Version' )
	);

	wp_enqueue_style(
		'lakeuden-kauppaseura-style',
		get_stylesheet_uri(),
		array( 'lakeuden-kauppaseura-parent-style' ),
		filemtime( get_stylesheet_directory() . '/style.css' )
	);

	wp_enqueue_script(
		'lakeuden-kauppaseura-site',
		get_stylesheet_directory_uri() . '/assets/js/site.js',
		array(),
		filemtime( get_stylesheet_directory() . '/assets/js/site.js' ),
		array( 'strategy' => 'defer', 'in_footer' => true )
	);
}
add_action( 'wp_enqueue_scripts', 'lakeuden_kauppaseura_enqueue_styles' );

/**
 * Load WPForms assets only on the dedicated membership page.
 */
function lakeuden_kauppaseura_remove_unused_form_assets() {
	if ( ! is_admin() && ! is_page( 'jaseneksi' ) ) {
		wp_dequeue_style( 'wpforms-modern-full' );
		wp_dequeue_style( 'wpforms-full' );
	}
}
add_action( 'wp_enqueue_scripts', 'lakeuden_kauppaseura_remove_unused_form_assets', 100 );

/**
 * Match the editor canvas to the public site.
 */
function lakeuden_kauppaseura_editor_styles() {
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'lakeuden_kauppaseura_editor_styles' );

/**
 * Return the next published event, or null when no future event exists.
 *
 * @return WP_Post|null
 */
function lakeuden_kauppaseura_get_next_event() {
	$events = get_posts(
		array(
			'post_type'      => 'lks_event',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_lks_event_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_lks_event_date',
					'value'   => wp_date( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		)
	);

	return $events ? $events[0] : null;
}

/**
 * Return the newest post in the Blogi category.
 *
 * @return WP_Post|null
 */
function lakeuden_kauppaseura_get_latest_blog_post() {
	$posts = get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'category_name'       => 'blogi',
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		)
	);

	return $posts ? $posts[0] : null;
}

/**
 * Format an event date into a Finnish calendar tile and countdown.
 *
 * @param string $date Date in Y-m-d format.
 * @return array{day:string,month:string,full:string,countdown:string}
 */
function lakeuden_kauppaseura_format_event_date( $date ) {
	$empty = array(
		'day'       => '',
		'month'     => '',
		'full'      => '',
		'countdown' => '',
	);

	if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return $empty;
	}

	$datetime = date_create_immutable_from_format( '!Y-m-d', $date, wp_timezone() );

	if ( ! $datetime ) {
		return $empty;
	}

	$timestamp = $datetime->getTimestamp();
	$today     = date_create_immutable( 'today', wp_timezone() );
	$countdown = '';

	if ( $today ) {
		$days = (int) $today->diff( $datetime )->format( '%r%a' );

		if ( 0 === $days ) {
			$countdown = 'Tänään';
		} elseif ( 1 === $days ) {
			$countdown = 'Huomenna';
		} elseif ( $days > 1 ) {
			$countdown = sprintf( '%d päivää tapahtumaan', $days );
		}
	}

	return array(
		'day'       => wp_date( 'j', $timestamp, wp_timezone() ),
		'month'     => wp_date( 'F', $timestamp, wp_timezone() ),
		'full'      => wp_date( 'j.n.Y', $timestamp, wp_timezone() ),
		'countdown' => $countdown,
	);
}

/**
 * Render the dynamic homepage. Posts, events and Instagram remain editable in
 * their normal WordPress admin screens while the presentation stays consistent.
 *
 * @return string
 */
function lakeuden_kauppaseura_render_homepage() {
	$hero_base   = get_stylesheet_directory_uri() . '/assets/images/hero/lakeuden-kauppaseura-hero-';
	$next_event  = lakeuden_kauppaseura_get_next_event();
	$latest_post = lakeuden_kauppaseura_get_latest_blog_post();

	ob_start();
	?>
	<div id="main" class="lks-home" role="main">
		<section class="lks-home-hero" aria-labelledby="lks-home-title">
			<picture>
				<source type="image/webp" srcset="<?php echo esc_url( $hero_base . '640.webp' ); ?> 640w, <?php echo esc_url( $hero_base . '960.webp' ); ?> 960w, <?php echo esc_url( $hero_base . '1440.webp' ); ?> 1440w, <?php echo esc_url( $hero_base . '1920.webp' ); ?> 1920w" sizes="100vw" />
				<img class="lks-home-hero__image" src="<?php echo esc_url( $hero_base . '1920.jpg' ); ?>" srcset="<?php echo esc_url( $hero_base . '640.jpg' ); ?> 640w, <?php echo esc_url( $hero_base . '960.jpg' ); ?> 960w, <?php echo esc_url( $hero_base . '1440.jpg' ); ?> 1440w, <?php echo esc_url( $hero_base . '1920.jpg' ); ?> 1920w" sizes="100vw" width="1920" height="1280" alt="Lakeuden viljapelto avaran taivaan alla" fetchpriority="high" loading="eager" decoding="async" />
			</picture>
			<div class="lks-home-hero__shade" aria-hidden="true"></div>
			<div class="lks-home-hero__content">
				<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_hero_kicker' ) ); ?></p>
				<h1 id="lks-home-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_hero_title' ) ); ?></h1>
				<p class="lks-home-hero__lead"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_hero_lead' ) ); ?></p>
				<div class="lks-home-hero__actions">
					<a class="lks-button lks-button--gold" href="#seuraava-tapahtuma"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_hero_event_link' ) ); ?></a>
					<a class="lks-text-link lks-text-link--light" href="<?php echo esc_url( home_url( '/jaseneksi/' ) ); ?>"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_hero_member_link' ) ); ?> <span aria-hidden="true">→</span></a>
				</div>
			</div>
			<p class="lks-home-hero__place">ETELÄ-POHJANMAA <span>62°47′N</span></p>
		</section>

		<section id="seuraava-tapahtuma" class="lks-home-section lks-current lks-next-event" aria-labelledby="next-event-title">
			<div class="lks-section-heading lks-section-heading--bordered">
				<div><p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_event_kicker' ) ); ?></p><h2 id="next-event-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_event_title' ) ); ?></h2></div>
			</div>
			<?php if ( $next_event ) : ?>
				<?php
				$event_date = get_post_meta( $next_event->ID, '_lks_event_date', true );
				$event_city = get_post_meta( $next_event->ID, '_lks_event_city', true );
				$formatted  = lakeuden_kauppaseura_format_event_date( $event_date );
				?>
				<article class="lks-event-feature">
					<div class="lks-event-feature__date-panel">
						<?php if ( $formatted['day'] ) : ?>
							<time class="lks-event-feature__date" datetime="<?php echo esc_attr( $event_date ); ?>">
								<span><?php echo esc_html( $formatted['day'] ); ?></span>
								<strong><?php echo esc_html( $formatted['month'] ); ?></strong>
							</time>
						<?php endif; ?>
						<?php if ( $formatted['countdown'] ) : ?><p class="lks-event-feature__countdown" data-event-countdown data-event-date="<?php echo esc_attr( $event_date ); ?>"><?php echo esc_html( $formatted['countdown'] ); ?></p><?php endif; ?>
						<?php if ( $event_city ) : ?><p class="lks-event-feature__place"><?php echo esc_html( $event_city ); ?></p><?php endif; ?>
					</div>
					<div class="lks-event-feature__body">
						<h3><?php echo esc_html( get_the_title( $next_event ) ); ?></h3>
						<?php if ( has_excerpt( $next_event ) ) : ?><p><?php echo esc_html( get_the_excerpt( $next_event ) ); ?></p><?php endif; ?>
						<a class="lks-arrow-link" href="<?php echo esc_url( get_permalink( $next_event ) ); ?>"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_event_link' ) ); ?> <span aria-hidden="true">→</span></a>
					</div>
				</article>
			<?php else : ?>
				<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_event_empty' ) ); ?></p>
			<?php endif; ?>
		</section>

		<section class="lks-home-section lks-member-values" aria-labelledby="member-values-title">
			<div class="lks-section-heading lks-section-heading--bordered"><div><p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_values_kicker' ) ); ?></p><h2 id="member-values-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_values_title' ) ); ?></h2></div></div>
			<div class="lks-member-values__grid">
				<article><span>01</span><h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_value_1_title' ) ); ?></h3><p><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_value_1_text' ) ); ?></p></article>
				<article><span>02</span><h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_value_2_title' ) ); ?></h3><p><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_value_2_text' ) ); ?></p></article>
				<article><span>03</span><h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_value_3_title' ) ); ?></h3><p><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_value_3_text' ) ); ?></p></article>
			</div>
		</section>

		<section class="lks-home-section lks-intro" aria-labelledby="lks-intro-title">
			<div class="lks-intro__content"><p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_intro_kicker' ) ); ?></p><h2 id="lks-intro-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_intro_title' ) ); ?></h2><p class="lks-intro__statement"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_intro_text' ) ); ?></p><a class="lks-arrow-link" href="<?php echo esc_url( home_url( '/jaseneksi/' ) ); ?>"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_intro_link' ) ); ?> <span aria-hidden="true">→</span></a></div>
		</section>

		<?php if ( $latest_post ) : ?>
			<section class="lks-home-section lks-current lks-latest-article" aria-labelledby="latest-title">
				<div class="lks-section-heading lks-section-heading--bordered">
					<div><p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_blog_kicker' ) ); ?></p><h2 id="latest-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_blog_title' ) ); ?></h2></div>
				</div>
				<article class="lks-post-feature">
					<?php if ( has_post_thumbnail( $latest_post ) ) : ?><a class="lks-post-feature__image" href="<?php echo esc_url( get_permalink( $latest_post ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo get_the_post_thumbnail( $latest_post, 'large', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a><?php endif; ?>
					<div class="lks-post-feature__body"><div class="lks-post-feature__meta"><time datetime="<?php echo esc_attr( get_the_date( 'c', $latest_post ) ); ?>"><?php echo esc_html( get_the_date( 'j.n.Y', $latest_post ) ); ?></time></div><h3><a href="<?php echo esc_url( get_permalink( $latest_post ) ); ?>"><?php echo esc_html( get_the_title( $latest_post ) ); ?></a></h3><?php if ( has_excerpt( $latest_post ) ) : ?><p><?php echo esc_html( get_the_excerpt( $latest_post ) ); ?></p><?php endif; ?><a class="lks-arrow-link" href="<?php echo esc_url( get_permalink( $latest_post ) ); ?>"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_blog_link' ) ); ?> <span aria-hidden="true">→</span></a></div>
				</article>
			</section>
		<?php endif; ?>

		<section id="instagram" class="lks-home-section lks-instagram-showcase" aria-labelledby="instagram-title">
			<div class="lks-section-heading lks-instagram-showcase__heading"><h2 id="instagram-title"><a href="https://www.instagram.com/lakeudenkauppaseura/" target="_blank" rel="noopener noreferrer"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_instagram_title' ) ); ?> <span><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_instagram_handle' ) ); ?></span><span class="screen-reader-text">(avautuu uuteen välilehteen)</span><i aria-hidden="true">↗</i></a></h2></div>
			<?php echo do_shortcode( '[lks_instagram_feed limit="4" class="lks-instagram-home-grid"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</section>

		<section class="lks-home-section lks-join" aria-labelledby="join-title">
			<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_join_kicker' ) ); ?></p>
			<div class="lks-join__grid">
				<h2 id="join-title"><?php echo nl2br( esc_html( lakeuden_kauppaseura_copy( 'home_join_title' ) ) ); ?></h2>
				<div>
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_join_text' ) ); ?></p>
					<a class="lks-button lks-button--gold" href="<?php echo esc_url( home_url( '/jaseneksi/' ) ); ?>"><?php echo esc_html( lakeuden_kauppaseura_copy( 'home_join_button' ) ); ?></a>
				</div>
			</div>
		</section>
	</div>
	<?php

	// Keep the shortcode block from applying wpautop() inside the component tree.
	$html = (string) ob_get_clean();

	return (string) preg_replace( '/>\s+</', '><', $html );
}
add_shortcode( 'lks_homepage', 'lakeuden_kauppaseura_render_homepage' );

/**
 * Render the editorial About page.
 *
 * The long-form copy is based on the association description from the former
 * Lakeuden Kauppaseura website. Keeping the presentation in the theme makes
 * the page consistent with the homepage and blog while the page itself stays
 * easy to route and edit in WordPress.
 *
 * @return string
 */
function lakeuden_kauppaseura_render_about_page() {
	$board_cards = lakeuden_kauppaseura_render_board_members();

	ob_start();
	?>
	<div id="main" class="lks-page lks-about-page" role="main">
		<section class="lks-about-hero" aria-labelledby="lks-about-title">
			<div class="lks-page-shell lks-about-hero__grid">
				<div class="lks-about-hero__title">
					<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_hero_kicker' ) ); ?></p>
					<h1 id="lks-about-title"><?php echo nl2br( esc_html( lakeuden_kauppaseura_copy( 'about_hero_title' ) ) ); ?></h1>
				</div>
				<div class="lks-about-hero__intro">
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_hero_text_1' ) ); ?></p>
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_hero_text_2' ) ); ?></p>
					<a class="lks-text-link lks-text-link--light" href="#tarkoitus"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_hero_link' ) ); ?> <span aria-hidden="true">&darr;</span></a>
				</div>
			</div>
			<div class="lks-about-hero__signature" aria-hidden="true">
				<span>Sein&auml;joki</span>
				<span>Etel&auml;-Pohjanmaa</span>
			</div>
		</section>

		<section id="tarkoitus" class="lks-page-section lks-about-purpose" aria-labelledby="lks-purpose-title">
			<div class="lks-page-shell lks-about-purpose__grid">
				<div class="lks-about-purpose__heading">
					<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_purpose_kicker' ) ); ?></p>
					<h2 id="lks-purpose-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_purpose_title' ) ); ?></h2>
				</div>
				<div class="lks-about-purpose__copy">
					<p class="lks-about-purpose__lead"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_purpose_text_1' ) ); ?></p>
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_purpose_text_2' ) ); ?></p>
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_purpose_text_3' ) ); ?></p>
				</div>
			</div>
		</section>

		<section class="lks-about-roles" aria-label="Lakeuden Kauppaseuran roolit">
			<div class="lks-page-shell lks-about-roles__grid">
				<article>
					<span>01</span>
					<h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_role_1_title' ) ); ?></h3>
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_role_1_text' ) ); ?></p>
				</article>
				<article>
					<span>02</span>
					<h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_role_2_title' ) ); ?></h3>
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_role_2_text' ) ); ?></p>
				</article>
				<article>
					<span>03</span>
					<h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_role_3_title' ) ); ?></h3>
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_role_3_text' ) ); ?></p>
				</article>
			</div>
		</section>

		<section class="lks-page-section lks-about-strategy" aria-labelledby="lks-strategy-title">
			<div class="lks-page-shell">
				<div class="lks-about-strategy__heading">
					<div>
						<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_strategy_kicker' ) ); ?></p>
						<h2 id="lks-strategy-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_strategy_title' ) ); ?></h2>
					</div>
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_strategy_intro' ) ); ?></p>
				</div>
				<div class="lks-about-strategy__statements">
					<article>
						<p class="lks-kicker lks-kicker--light">Missio</p>
						<blockquote><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_strategy_mission' ) ); ?></blockquote>
					</article>
					<article>
						<p class="lks-kicker lks-kicker--light">Visio</p>
						<blockquote><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_strategy_vision' ) ); ?></blockquote>
					</article>
				</div>
				<div class="lks-about-strategy__values">
					<p class="lks-kicker">Arvot</p>
					<ul>
						<?php foreach ( lakeuden_kauppaseura_copy_list( 'about_strategy_values' ) as $value ) : ?>
							<li><?php echo esc_html( $value ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="lks-about-strategy__choices">
					<article>
						<p class="lks-kicker">Strateginen valinta</p>
						<h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_strategy_member_title' ) ); ?></h3>
						<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_strategy_member_text' ) ); ?></p>
					</article>
					<article>
						<p class="lks-kicker">Strateginen valinta</p>
						<h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_strategy_impact_title' ) ); ?></h3>
						<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_strategy_impact_text' ) ); ?></p>
					</article>
				</div>
			</div>
		</section>

		<section class="lks-page-section lks-about-action" aria-labelledby="lks-action-title">
			<div class="lks-page-shell">
				<div class="lks-section-heading lks-section-heading--bordered">
					<div>
						<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_action_kicker' ) ); ?></p>
						<h2 id="lks-action-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_action_title' ) ); ?></h2>
					</div>
					<p class="lks-about-action__lead"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_action_text' ) ); ?></p>
				</div>
				<ul class="lks-about-action__list">
					<?php foreach ( lakeuden_kauppaseura_copy_list( 'about_action_items' ) as $item ) : ?>
						<li><strong><?php echo esc_html( $item ); ?></strong></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>

		<?php if ( $board_cards ) : ?>
			<section class="lks-page-section lks-about-board" aria-labelledby="lks-board-title">
				<div class="lks-page-shell">
					<div class="lks-about-board__heading">
						<div>
							<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_board_kicker' ) ); ?></p>
							<h2 id="lks-board-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_board_title' ) ); ?></h2>
						</div>
						<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_board_intro' ) ); ?></p>
					</div>
					<?php echo $board_cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div class="lks-about-board__notes">
						<article>
							<h3>Työskentelytapa</h3>
							<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_board_note' ) ); ?></p>
						</article>
						<article>
							<h3>Vastuualueet</h3>
							<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_board_responsibilities' ) ); ?></p>
						</article>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section id="jasenyys" class="lks-about-membership" aria-labelledby="lks-membership-title">
			<div class="lks-page-shell lks-about-membership__grid">
				<div class="lks-about-membership__heading">
					<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_member_kicker' ) ); ?></p>
					<h2 id="lks-membership-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_member_title' ) ); ?></h2>
				</div>
				<figure class="lks-about-pin">
					<div class="lks-about-pin__visual">
						<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/lks-member-pin.png' ) ); ?>" width="1254" height="1254" loading="lazy" decoding="async" alt="Lakeuden Kauppaseuran hopeanv&auml;rinen LK-j&auml;senmerkki" />
					</div>
					<figcaption>
						<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_pin_kicker' ) ); ?></p>
						<h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_pin_title' ) ); ?></h3>
						<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_pin_text' ) ); ?></p>
					</figcaption>
				</figure>
				<div class="lks-about-membership__copy">
					<?php echo lakeuden_kauppaseura_render_membership_facts(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<a class="lks-button lks-button--gold" href="<?php echo esc_url( home_url( '/jaseneksi/' ) ); ?>"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_member_button' ) ); ?></a>
				</div>
			</div>
		</section>

		<section class="lks-about-closing" aria-label="Lakeuden Kauppaseuran toimintatapa">
			<div class="lks-page-shell">
				<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_closing_kicker' ) ); ?></p>
				<blockquote><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_closing_quote' ) ); ?></blockquote>
				<a class="lks-arrow-link" href="<?php echo esc_url( home_url( '/tapahtumat/' ) ); ?>"><?php echo esc_html( lakeuden_kauppaseura_copy( 'about_closing_link' ) ); ?> <span aria-hidden="true">&rarr;</span></a>
			</div>
		</section>
	</div>
	<?php

	return (string) preg_replace( '/>\s+</', '><', (string) ob_get_clean() );
}
add_shortcode( 'lks_about_page', 'lakeuden_kauppaseura_render_about_page' );

/**
 * Render the Events landing page with a live Instagram-led hero.
 *
 * @return string
 */
function lakeuden_kauppaseura_render_events_page() {
	ob_start();
	?>
	<div id="main" class="lks-page lks-events-page" role="main">
		<section class="lks-events-hero" aria-labelledby="lks-events-title">
			<div class="lks-page-shell lks-events-hero__inner">
				<div class="lks-events-hero__content">
					<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_hero_kicker' ) ); ?></p>
					<h1 id="lks-events-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_hero_title' ) ); ?></h1>
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_hero_text' ) ); ?></p>
					<a class="lks-button lks-button--gold" href="#tulevat"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_hero_button' ) ); ?></a>
				</div>
			</div>
			<div class="lks-events-hero__media" aria-label="Tunnelmia Lakeuden Kauppaseuran Instagramista">
				<?php echo do_shortcode( '[lks_instagram_feed limit="7" class="lks-events-hero__gallery"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>

		<section class="lks-page-section lks-events-intro" aria-labelledby="lks-events-intro-title">
			<div class="lks-page-shell lks-events-intro__grid">
				<div>
					<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_intro_kicker' ) ); ?></p>
					<h2 id="lks-events-intro-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_intro_title' ) ); ?></h2>
				</div>
				<div>
					<p class="lks-events-intro__statement"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_intro_text' ) ); ?></p>
					<ul class="lks-events-intro__types" aria-label="Tapahtumien muotoja">
						<?php foreach ( lakeuden_kauppaseura_copy_list( 'events_intro_types' ) as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?>
					</ul>
				</div>
			</div>
		</section>

		<section id="tulevat" class="lks-events-section lks-events-section--upcoming" aria-labelledby="lks-upcoming-title">
			<div class="lks-page-shell">
				<div class="lks-section-heading lks-section-heading--bordered">
					<div>
						<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_upcoming_kicker' ) ); ?></p>
						<h2 id="lks-upcoming-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_upcoming_title' ) ); ?></h2>
					</div>
					<p class="lks-events-section__note"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_upcoming_note' ) ); ?></p>
				</div>
				<?php echo do_shortcode( '[lks_events status="upcoming" limit="6" empty="' . esc_attr( lakeuden_kauppaseura_copy( 'events_upcoming_empty' ) ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>

		<section class="lks-events-section lks-events-section--past" aria-labelledby="lks-past-title">
			<div class="lks-page-shell">
				<div class="lks-section-heading lks-section-heading--bordered">
					<div>
						<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_past_kicker' ) ); ?></p>
						<h2 id="lks-past-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_past_title' ) ); ?></h2>
					</div>
					<a class="lks-text-link" href="https://www.instagram.com/lakeudenkauppaseura/" target="_blank" rel="noopener noreferrer"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_instagram_link' ) ); ?> <span class="screen-reader-text">(avautuu uuteen v&auml;lilehteen)</span><span aria-hidden="true">&nearr;</span></a>
				</div>
				<div data-past-events-archive>
					<?php echo do_shortcode( '[lks_events status="past" limit="50" empty="' . esc_attr( lakeuden_kauppaseura_copy( 'events_past_empty' ) ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div class="lks-events-load-more" data-past-events-controls hidden>
						<button class="lks-button lks-button--gold" type="button" data-past-events-more aria-controls="lks-past-events-list">
							Lisää menneitä tapahtumia
						</button>
						<p data-past-events-status aria-live="polite"></p>
					</div>
				</div>
			</div>
		</section>

		<section class="lks-events-cta" aria-labelledby="lks-events-cta-title">
			<div class="lks-page-shell lks-events-cta__grid">
				<div>
					<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_cta_kicker' ) ); ?></p>
					<h2 id="lks-events-cta-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_cta_title' ) ); ?></h2>
				</div>
				<div>
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_cta_text' ) ); ?></p>
					<a class="lks-button lks-button--gold" href="<?php echo esc_url( home_url( '/yhteystiedot/' ) ); ?>"><?php echo esc_html( lakeuden_kauppaseura_copy( 'events_cta_button' ) ); ?></a>
				</div>
			</div>
		</section>
	</div>
	<?php

	return (string) preg_replace( '/>\s+</', '><', (string) ob_get_clean() );
}
add_shortcode( 'lks_events_page', 'lakeuden_kauppaseura_render_events_page' );

/**
 * Render the Contact page in the same editorial system as the other landing
 * pages.
 *
 * @return string
 */
function lakeuden_kauppaseura_render_contact_page() {
	ob_start();
	?>
	<div id="main" class="lks-page lks-contact-page" role="main">
		<section class="lks-contact-hero" aria-labelledby="lks-contact-title">
			<div class="lks-page-shell lks-contact-hero__grid">
				<div>
					<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'contact_hero_kicker' ) ); ?></p>
					<h1 id="lks-contact-title"><?php echo nl2br( esc_html( lakeuden_kauppaseura_copy( 'contact_hero_title' ) ) ); ?></h1>
				</div>
				<div class="lks-contact-hero__intro">
					<p><?php echo nl2br( esc_html( lakeuden_kauppaseura_copy( 'contact_hero_text' ) ) ); ?></p>
					<a class="lks-text-link lks-text-link--light" href="#yhteystiedot"><?php echo esc_html( lakeuden_kauppaseura_copy( 'contact_hero_link' ) ); ?> <span aria-hidden="true">&darr;</span></a>
				</div>
			</div>
		</section>

		<section id="yhteystiedot" class="lks-page-section lks-contact-details" aria-labelledby="lks-contact-details-title">
			<div class="lks-page-shell">
				<div class="lks-section-heading lks-section-heading--bordered">
					<div>
						<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'contact_details_kicker' ) ); ?></p>
						<h2 id="lks-contact-details-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'contact_details_title' ) ); ?></h2>
					</div>
					<p class="lks-contact-details__lead"><?php echo esc_html( lakeuden_kauppaseura_copy( 'contact_details_text' ) ); ?></p>
				</div>

				<div class="lks-contact-details__grid">
					<a href="<?php echo esc_url( 'mailto:' . sanitize_email( lakeuden_kauppaseura_copy( 'contact_email' ) ) ); ?>">
						<span>S&auml;hk&ouml;posti</span>
						<strong><?php echo esc_html( lakeuden_kauppaseura_copy( 'contact_email' ) ); ?></strong>
						<i aria-hidden="true">&nearr;</i>
					</a>
					<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^\d+]/', '', lakeuden_kauppaseura_copy( 'contact_phone_link' ) ) ); ?>">
						<span>Puhelin</span>
						<strong><?php echo esc_html( lakeuden_kauppaseura_copy( 'contact_phone' ) ); ?></strong>
						<i aria-hidden="true">&nearr;</i>
					</a>
				</div>

				<aside class="lks-contact-location" aria-label="Osoite">
					<span>Osoite</span>
					<p><?php echo nl2br( esc_html( lakeuden_kauppaseura_copy( 'contact_address' ) ) ); ?></p>
				</aside>
			</div>
		</section>

		<section class="lks-contact-social" aria-labelledby="lks-contact-social-title">
			<div class="lks-page-shell lks-contact-social__grid">
				<div>
					<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'contact_social_kicker' ) ); ?></p>
					<h2 id="lks-contact-social-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'contact_social_title' ) ); ?></h2>
				</div>
				<nav aria-label="Lakeuden Kauppaseura sosiaalisessa mediassa">
					<a href="https://www.instagram.com/lakeudenkauppaseura/" target="_blank" rel="noopener noreferrer">Instagram <span class="screen-reader-text">(avautuu uuteen v&auml;lilehteen)</span><span aria-hidden="true">&nearr;</span></a>
					<a href="https://www.facebook.com/kauppaseura" target="_blank" rel="noopener noreferrer">Facebook <span class="screen-reader-text">(avautuu uuteen v&auml;lilehteen)</span><span aria-hidden="true">&nearr;</span></a>
				</nav>
			</div>
		</section>
	</div>
	<?php

	return (string) preg_replace( '/>\s+</', '><', (string) ob_get_clean() );
}
add_shortcode( 'lks_contact_page', 'lakeuden_kauppaseura_render_contact_page' );

/**
 * Replace the block-theme footer-injected skip link with one at body start.
 */
function lakeuden_kauppaseura_setup_accessibility() {
	remove_action( 'wp_footer', 'the_block_template_skip_link' );
}
add_action( 'after_setup_theme', 'lakeuden_kauppaseura_setup_accessibility', 20 );

/**
 * Print the first focusable control in the document.
 */
function lakeuden_kauppaseura_skip_link() {
	echo '<a class="lks-skip-link" href="#main">Siirry sis&auml;lt&ouml;&ouml;n</a>';
}
add_action( 'wp_body_open', 'lakeuden_kauppaseura_skip_link', 1 );

/**
 * Determine the clean production route for the current request.
 *
 * @return string
 */
function lakeuden_kauppaseura_production_path() {
	if ( is_front_page() ) {
		return '';
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$prefix = 'lks_event' === $post->post_type ? 'tapahtuma/' : '';
			return $prefix . $post->post_name . '/';
		}
	}

	return '';
}

/**
 * Return redirect information for duplicate event-announcement posts.
 *
 * @return string Empty string or the target route without a leading slash.
 */
function lakeuden_kauppaseura_duplicate_event_target() {
	if ( ! is_singular( 'post' ) ) {
		return '';
	}

	$targets = array(
		'syksyn-verkostoitumisilta-seinajoella' => 'tapahtuma/syksyn-verkostoitumisilta-seinajoella/',
		'ajankohtaiskatsaus-ja-yritysvierailu' => 'tapahtuma/ajankohtaiskatsaus-ja-yritysvierailu/',
	);
	$slug    = get_post_field( 'post_name', get_queried_object_id() );

	return $targets[ $slug ] ?? '';
}

/**
 * Return the canonical production URL.
 *
 * @return string
 */
function lakeuden_kauppaseura_canonical_url() {
	$base   = lakeuden_kauppaseura_production_base_url();
	$target = lakeuden_kauppaseura_duplicate_event_target();
	return $base . ( $target ?: lakeuden_kauppaseura_production_path() );
}

/**
 * Build a concise, unique description from verified page content.
 *
 * @return string
 */
function lakeuden_kauppaseura_meta_description() {
	$primary = array(
		''              => 'Lakeuden Kauppaseura kokoaa Etel&auml;-Pohjanmaan yritt&auml;j&auml;t, asiantuntijat ja alueen kehitt&auml;j&auml;t keskusteluihin, vierailuille ja verkostoihin.',
		'meista/'        => 'Tutustu Lakeuden Kauppaseuran tarkoitukseen, strategiaan, hallitukseen, toimintaan ja j&auml;senyyteen Etel&auml;-Pohjanmaalla.',
		'jaseneksi/'     => lakeuden_kauppaseura_copy( 'join_meta_description' ),
		'tapahtumat/'    => 'Katso Lakeuden Kauppaseuran tulevat tapahtumat, yritysvierailut ja ajankohtaiset keskustelut Etel&auml;-Pohjanmaalla.',
		'blogi/'         => 'Lakeuden Kauppaseuran kirjoituksia yritt&auml;jyydest&auml;, alueen elinvoimasta ja Etel&auml;-Pohjanmaan ajankohtaisista kysymyksist&auml;.',
		'yhteystiedot/'  => 'Ota yhteytt&auml; Lakeuden Kauppaseuraan j&auml;senyydest&auml;, tapahtumista tai toiminnasta. Katso yhteystiedot ja sosiaalisen median kanavat.',
		'tietosuoja/'    => 'Lue, miten Lakeuden Kauppaseuran verkkosivusto toimii henkil&ouml;tietojen, yhteydenottojen ja ulkoisten palvelujen n&auml;k&ouml;kulmasta.',
	);
	$route   = lakeuden_kauppaseura_production_path();

	if ( isset( $primary[ $route ] ) ) {
		return html_entity_decode( $primary[ $route ], ENT_QUOTES, 'UTF-8' );
	}

	$post = get_queried_object();
	if ( $post instanceof WP_Post ) {
		$text = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
		$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( $text ) ) ) );
		if ( $text ) {
			return wp_html_excerpt( $text, 155, '&hellip;' );
		}
	}

	return 'Lakeuden Kauppaseuran ajankohtaiset sis&auml;ll&ouml;t, tapahtumat ja verkostot Etel&auml;-Pohjanmaalla.';
}

/**
 * Use the editable, unique SEO title on the dedicated membership page.
 *
 * @param string $title Existing document title.
 * @return string
 */
function lakeuden_kauppaseura_membership_document_title( $title ) {
	if ( is_page( 'jaseneksi' ) ) {
		return lakeuden_kauppaseura_copy( 'join_meta_title' );
	}

	return $title;
}
add_filter( 'pre_get_document_title', 'lakeuden_kauppaseura_membership_document_title', 20 );

/**
 * Convert a local media URL into its production equivalent.
 *
 * @param string $url Local URL.
 * @return string
 */
function lakeuden_kauppaseura_production_asset_url( $url ) {
	$path = wp_parse_url( $url, PHP_URL_PATH );
	return lakeuden_kauppaseura_production_url( ltrim( (string) $path, '/' ) );
}

/**
 * Return social image details for the current page.
 *
 * @return array{url:string,width:int,height:int,alt:string}
 */
function lakeuden_kauppaseura_social_image() {
	$post_id = get_queried_object_id();
	if ( $post_id && has_post_thumbnail( $post_id ) ) {
		$image_id = get_post_thumbnail_id( $post_id );
		$image    = wp_get_attachment_image_src( $image_id, 'full' );
		if ( $image ) {
			return array(
				'url'    => lakeuden_kauppaseura_production_asset_url( $image[0] ),
				'width'  => (int) $image[1],
				'height' => (int) $image[2],
				'alt'    => lakeuden_kauppaseura_attachment_alt( $image_id, get_the_title( $post_id ) ),
			);
		}
	}

	return array(
		'url'    => lakeuden_kauppaseura_production_url( 'wp-content/themes/lakeuden-kauppaseura/assets/images/hero/lakeuden-kauppaseura-hero-1920.jpg' ),
		'width'  => 1920,
		'height' => 1280,
		'alt'    => 'Lakeuden viljapelto avaran taivaan alla',
	);
}

/**
 * Add search, sharing, favicon and verified structured metadata.
 */
function lakeuden_kauppaseura_document_metadata() {
	if ( is_admin() || is_404() ) {
		return;
	}

	$canonical  = lakeuden_kauppaseura_canonical_url();
	$description = lakeuden_kauppaseura_meta_description();
	$title       = wp_get_document_title();
	$image       = lakeuden_kauppaseura_social_image();
	$type        = is_singular( 'post' ) || is_singular( 'lks_event' ) ? 'article' : 'website';
	?>
	<meta name="description" content="<?php echo esc_attr( $description ); ?>" />
	<link rel="canonical" href="<?php echo esc_url( $canonical ); ?>" />
	<meta property="og:locale" content="fi_FI" />
	<meta property="og:site_name" content="Lakeuden Kauppaseura" />
	<meta property="og:type" content="<?php echo esc_attr( $type ); ?>" />
	<meta property="og:title" content="<?php echo esc_attr( $title ); ?>" />
	<meta property="og:description" content="<?php echo esc_attr( $description ); ?>" />
	<meta property="og:url" content="<?php echo esc_url( $canonical ); ?>" />
	<meta property="og:image" content="<?php echo esc_url( $image['url'] ); ?>" />
	<meta property="og:image:width" content="<?php echo esc_attr( (string) $image['width'] ); ?>" />
	<meta property="og:image:height" content="<?php echo esc_attr( (string) $image['height'] ); ?>" />
	<meta property="og:image:alt" content="<?php echo esc_attr( $image['alt'] ); ?>" />
	<meta name="twitter:card" content="summary_large_image" />
	<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>" />
	<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>" />
	<meta name="twitter:image" content="<?php echo esc_url( $image['url'] ); ?>" />
	<meta name="twitter:image:alt" content="<?php echo esc_attr( $image['alt'] ); ?>" />
	<link rel="icon" href="<?php echo esc_url( home_url( '/favicon.ico' ) ); ?>" sizes="any" />
	<link rel="icon" href="<?php echo esc_url( home_url( '/favicon-32x32.png' ) ); ?>" type="image/png" sizes="32x32" />
	<link rel="apple-touch-icon" href="<?php echo esc_url( home_url( '/apple-touch-icon.png' ) ); ?>" />
	<?php
	$duplicate_target = lakeuden_kauppaseura_duplicate_event_target();
	if ( $duplicate_target ) {
		echo '<meta http-equiv="refresh" content="0;url=' . esc_url( $canonical ) . '" />';
	}

	if ( ! $duplicate_target ) {
		$schema = lakeuden_kauppaseura_schema_graph( $canonical, $description, $image );
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
	}
}
remove_action( 'wp_head', 'rel_canonical' );
add_action( 'wp_head', 'lakeuden_kauppaseura_document_metadata', 2 );

/**
 * Keep duplicate announcements out of indexes while allowing consolidation.
 *
 * @param array<string,bool> $robots Existing directives.
 * @return array<string,bool>
 */
function lakeuden_kauppaseura_robots_directives( $robots ) {
	if ( lakeuden_kauppaseura_duplicate_event_target() ) {
		return array( 'noindex' => true, 'follow' => true );
	}
	return $robots;
}
add_filter( 'wp_robots', 'lakeuden_kauppaseura_robots_directives' );
