<?php
/**
 * Dedicated membership page and reusable membership components.
 *
 * @package Lakeuden_Kauppaseura
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render one membership-fact value according to its canonical presentation.
 *
 * @param array<string,mixed> $fact Membership fact.
 * @return string
 */
function lakeuden_kauppaseura_render_membership_fact_value( $fact ) {
	ob_start();

	if ( 'list' === $fact['display'] ) {
		?>
		<ul>
			<?php foreach ( lakeuden_kauppaseura_copy_list( $fact['key'] ) as $item ) : ?>
				<li><?php echo esc_html( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php
	} else {
		echo esc_html( $fact['value'] );
	}

	return (string) ob_get_clean();
}

/**
 * Render the canonical membership facts without duplicating them in templates.
 *
 * @param string $class Optional additional class.
 * @return string
 */
function lakeuden_kauppaseura_render_membership_facts( $class = '' ) {
	$classes = trim( 'lks-membership-facts ' . sanitize_html_class( $class ) );
	ob_start();
	?>
	<dl class="<?php echo esc_attr( $classes ); ?>">
		<?php foreach ( lakeuden_kauppaseura_membership_facts() as $fact ) : ?>
			<?php if ( $fact['unresolved'] ) : ?>
				<?php continue; ?>
			<?php endif; ?>
			<div class="lks-membership-facts__item">
				<dt><?php echo esc_html( $fact['label'] ); ?></dt>
				<dd data-lks-membership-fact="<?php echo esc_attr( $fact['key'] ); ?>" data-lks-launch-required="<?php echo $fact['launch_required'] ? 'true' : 'false'; ?>">
					<?php echo lakeuden_kauppaseura_render_membership_fact_value( $fact ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</dd>
			</div>
		<?php endforeach; ?>
	</dl>
	<?php

	return (string) ob_get_clean();
}

/**
 * Get a canonical membership fact by key.
 *
 * @param string $key Membership fact key.
 * @return array<string,mixed>
 */
function lakeuden_kauppaseura_membership_fact( $key ) {
	foreach ( lakeuden_kauppaseura_membership_facts() as $fact ) {
		if ( $key === $fact['key'] ) {
			return $fact;
		}
	}

	return array(
		'key'             => $key,
		'label'           => '',
		'value'           => lakeuden_kauppaseura_membership_placeholder(),
		'display'         => 'text',
		'launch_required' => true,
		'unresolved'      => true,
	);
}

/**
 * Determine whether an HTTP(S) member image URL can be rendered.
 *
 * Local media URLs are accepted in the editing environment and rewritten by
 * the static exporter. Output is still escaped at render time.
 *
 * @param string $url Candidate image URL.
 * @return bool
 */
function lakeuden_kauppaseura_membership_photo_is_valid( $url ) {
	$parts = wp_parse_url( $url );

	return is_array( $parts )
		&& in_array( strtolower( (string) ( $parts['scheme'] ?? '' ) ), array( 'http', 'https' ), true )
		&& ! empty( $parts['host'] );
}

/**
 * Return the configured WPForms form ID.
 *
 * @return int
 */
function lakeuden_kauppaseura_membership_form_id() {
	return absint( lakeuden_kauppaseura_copy( 'join_form_id' ) );
}

/**
 * Determine whether the dynamic form may be shown in the current environment.
 *
 * Production is deliberately gated until notification delivery and privacy
 * setup have been verified. Non-production environments may render the form
 * for local validation without implying that it is launch-ready.
 *
 * @return bool
 */
function lakeuden_kauppaseura_membership_form_is_available() {
	$form_id = lakeuden_kauppaseura_membership_form_id();
	$is_ready = '1' === lakeuden_kauppaseura_copy( 'join_form_ready' )
		|| 'production' !== wp_get_environment_type();

	return $is_ready
		&& $form_id
		&& 'wpforms' === get_post_type( $form_id )
		&& 'publish' === get_post_status( $form_id )
		&& shortcode_exists( 'wpforms' );
}

/**
 * Keep WPForms visitor-facing validation messages in Finnish.
 *
 * @param mixed  $value         Current setting value.
 * @param string $key           WPForms setting key.
 * @param mixed  $default_value Default setting value.
 * @param string $option        Settings option name.
 * @return mixed
 */
function lakeuden_kauppaseura_localize_wpforms_setting( $value, $key, $default_value, $option ) {
	if ( 'wpforms_settings' !== $option ) {
		return $value;
	}

	$messages = array(
		'validation-required'             => 'Tämä kenttä on pakollinen.',
		'validation-email'                => 'Anna kelvollinen sähköpostiosoite.',
		'validation-email-suggestion'     => 'Tarkoititko osoitetta {suggestion}?',
		'validation-email-restricted'     => 'Tätä sähköpostiosoitetta ei voi käyttää.',
		'validation-number'               => 'Anna kelvollinen numero.',
		'validation-number-positive'      => 'Anna nollaa suurempi numero.',
		'validation-confirm'              => 'Kenttien arvot eivät täsmää.',
		'validation-inputmask-incomplete' => 'Täytä tieto pyydetyssä muodossa.',
		'validation-check-limit'          => 'Valitsit liian monta vaihtoehtoa. Enimmäismäärä on {#}.',
		'recaptcha-fail-msg'              => 'Roskapostitarkistus epäonnistui. Yritä myöhemmin uudelleen.',
		'turnstile-fail-msg'              => 'Roskapostitarkistus epäonnistui. Yritä myöhemmin uudelleen.',
	);

	return $messages[ $key ] ?? $value;
}
add_filter( 'wpforms_setting', 'lakeuden_kauppaseura_localize_wpforms_setting', 10, 4 );

/**
 * Localize WPForms strings that do not come through the settings API.
 *
 * @param array<string,mixed> $strings Frontend strings.
 * @return array<string,mixed>
 */
function lakeuden_kauppaseura_localize_wpforms_frontend_strings( $strings ) {
	$strings['val_email_suggestion_title'] = 'Hyväksy ehdotus';
	$strings['val_minimum_price'] = 'Summa alittaa vaaditun vähimmäismäärän.';
	$strings['val_limit_characters'] = 'Merkkejä {count}/{limit}.';
	$strings['val_limit_words'] = 'Sanoja {count}/{limit}.';
	$strings['val_min'] = 'Arvon on oltava vähintään {0}.';
	$strings['val_max'] = 'Arvo saa olla enintään {0}.';
	$strings['val_requiredpayment'] = 'Maksu on pakollinen.';
	$strings['val_creditcard'] = 'Anna kelvollinen maksukortin numero.';
	$strings['country_list_label'] = 'Maaluettelo';
	$strings['formErrorMessagePrefix'] = 'Lomakkeen virheviesti';
	$strings['errorMessagePrefix'] = 'Virheviesti';
	$strings['submitBtnDisabled'] = 'Lähetyspainike ei ole käytettävissä lähetyksen aikana.';
	$strings['error_updating_token'] = 'Roskapostitarkistuksen päivittäminen epäonnistui. Yritä uudelleen tai ota yhteyttä tukeen.';
	$strings['network_error'] = 'Verkkoyhteys palvelimeen epäonnistui. Tarkista yhteys tai yritä myöhemmin uudelleen.';

	return $strings;
}
add_filter( 'wpforms_frontend_strings', 'lakeuden_kauppaseura_localize_wpforms_frontend_strings', 1000 );

/**
 * Localize the server-side required-field error.
 *
 * @return string
 */
function lakeuden_kauppaseura_wpforms_required_error() {
	return 'Tämä kenttä on pakollinen.';
}
add_filter( 'wpforms_required_label', 'lakeuden_kauppaseura_wpforms_required_error' );

/**
 * Localize the no-JavaScript form warning.
 *
 * @return string
 */
function lakeuden_kauppaseura_wpforms_noscript_error() {
	return 'Ota JavaScript käyttöön selaimessa, jotta voit täyttää tämän lomakkeen.';
}
add_filter( 'wpforms_frontend_noscript_error_message', 'lakeuden_kauppaseura_wpforms_noscript_error' );

/**
 * Supply Finnish fallbacks for WPForms runtime errors when a language pack is
 * unavailable in the local installation.
 *
 * @param string $translation Current translation.
 * @param string $text        Source text.
 * @param string $domain      Text domain.
 * @return string
 */
function lakeuden_kauppaseura_translate_wpforms_runtime_errors( $translation, $text, $domain ) {
	if ( 'wpforms-lite' !== $domain ) {
		return $translation;
	}

	$messages = array(
		'Attempt to submit corrupted post data.' => 'Lomakkeen lähetyksen tiedot olivat virheelliset. Päivitä sivu ja yritä uudelleen.',
		'Your form has not been submitted because data is missing from the entry.' => 'Lomaketta ei lähetetty, koska lähetyksestä puuttui tietoja.',
		'Please reload the page and try submitting the form again.' => 'Päivitä sivu ja yritä lähettää lomake uudelleen.',
		'Form has not been submitted, please see the errors below.' => 'Lomaketta ei lähetetty. Tarkista alla olevat virheet.',
		'Form error message' => 'Lomakkeen virheviesti',
		'The provided email is not valid.' => 'Anna kelvollinen sähköpostiosoite.',
		'The provided emails do not match.' => 'Sähköpostiosoitteet eivät täsmää.',
		'Antispam token is invalid.' => 'Roskapostitarkistus ei ollut enää voimassa. Päivitä sivu ja yritä uudelleen.',
		'Antispam filter did not allow your data to pass through.' => 'Roskapostitarkistus esti lähetyksen. Tarkista tiedot ja yritä uudelleen.',
	);

	return $messages[ $text ] ?? $translation;
}
add_filter( 'gettext', 'lakeuden_kauppaseura_translate_wpforms_runtime_errors', 10, 3 );

/**
 * Render the safe no-submit fallback used by static output and unavailable WP.
 *
 * @param bool $static_preview Whether this is the GitHub Pages preview.
 * @param bool $hidden         Whether the exporter-only fallback starts hidden.
 * @return string
 */
function lakeuden_kauppaseura_render_membership_form_fallback( $static_preview = false, $hidden = false ) {
	$title = $static_preview
		? lakeuden_kauppaseura_copy( 'join_static_form_title' )
		: 'Kiinnostuslomake ei ole vielä käytettävissä.';
	$text = $static_preview
		? lakeuden_kauppaseura_copy( 'join_static_form_text' )
		: 'Tietojasi ei yritetä lähettää. Ota yhteyttä yhteystietosivun kautta, jotta viestisi menee varmasti perille.';

	ob_start();
	?>
	<div class="lks-membership-form-fallback" data-lks-static-membership-form<?php echo $hidden ? ' hidden' : ''; ?>>
		<h3><?php echo esc_html( $title ); ?></h3>
		<p><?php echo esc_html( $text ); ?></p>
		<a class="lks-button lks-button--gold" href="<?php echo esc_url( home_url( '/yhteystiedot/' ) ); ?>"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_static_form_link' ) ); ?></a>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render the WPForms form plus an exporter-controlled safe fallback.
 *
 * @return string
 */
function lakeuden_kauppaseura_render_membership_form() {
	if ( ! lakeuden_kauppaseura_membership_form_is_available() ) {
		return lakeuden_kauppaseura_render_membership_form_fallback();
	}

	$form_id = lakeuden_kauppaseura_membership_form_id();
	ob_start();
	?>
	<!-- LKS_DYNAMIC_MEMBERSHIP_FORM_START -->
	<div class="lks-membership-form-live" data-lks-live-membership-form>
		<p class="lks-membership-form__required"><span aria-hidden="true">*</span> Pakollinen tieto</p>
		<p class="lks-membership-form__privacy">Tutustu <a href="<?php echo esc_url( home_url( '/tietosuoja/' ) ); ?>">tietosuojaselosteeseen</a> ennen lomakkeen lähettämistä.</p>
		<?php echo do_shortcode( '[wpforms id="' . $form_id . '" title="false" description="false"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<!-- LKS_DYNAMIC_MEMBERSHIP_FORM_END -->
	<?php echo lakeuden_kauppaseura_render_membership_form_fallback( true, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render the FAQ answer for a single fact.
 *
 * @param string $key Membership fact key.
 * @return string
 */
function lakeuden_kauppaseura_render_membership_faq_fact( $key ) {
	return lakeuden_kauppaseura_render_membership_fact_value( lakeuden_kauppaseura_membership_fact( $key ) );
}

/**
 * Render the dedicated Jäseneksi page.
 *
 * @return string
 */
function lakeuden_kauppaseura_render_membership_page() {
	$nomination        = lakeuden_kauppaseura_membership_fact( 'membership_nomination' );
	$approval          = lakeuden_kauppaseura_membership_fact( 'membership_approval_process' );
	$eligibility       = lakeuden_kauppaseura_membership_fact( 'membership_eligibility' );
	$nonmember_events  = lakeuden_kauppaseura_membership_fact( 'membership_nonmember_events' );
	$annual_fee        = lakeuden_kauppaseura_membership_fact( 'membership_annual_fee' );
	$joining_fee       = lakeuden_kauppaseura_membership_fact( 'membership_joining_fee' );
	$processing_time   = lakeuden_kauppaseura_membership_fact( 'membership_processing_time' );
	$membership_items  = lakeuden_kauppaseura_membership_fact( 'membership_includes' );
	$extra_event_fees  = lakeuden_kauppaseura_membership_fact( 'membership_extra_event_fees' );
	$testimonials      = array_values(
		array_filter(
			lakeuden_kauppaseura_membership_testimonials(),
			static function ( $testimonial ) {
				return empty( $testimonial['unresolved'] );
			}
		)
	);

	ob_start();
	?>
	<div id="main" class="lks-page lks-membership-page" role="main">
		<section class="lks-membership-hero" aria-labelledby="lks-join-title">
			<div class="lks-page-shell lks-membership-hero__grid">
				<div>
					<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_hero_kicker' ) ); ?></p>
					<h1 id="lks-join-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_hero_title' ) ); ?></h1>
				</div>
				<div class="lks-membership-hero__intro">
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_hero_lead' ) ); ?></p>
					<div class="lks-membership-hero__actions">
						<a class="lks-button lks-button--gold" href="#kiinnostus"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_primary_cta' ) ); ?></a>
						<a class="lks-text-link lks-text-link--light" href="<?php echo esc_url( home_url( '/tapahtumat/' ) ); ?>"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_secondary_cta' ) ); ?> <span aria-hidden="true">&rarr;</span></a>
					</div>
				</div>
			</div>
		</section>

		<section class="lks-page-section lks-membership-audience" aria-labelledby="lks-join-audience-title">
			<div class="lks-page-shell">
				<div class="lks-membership-section-heading">
					<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_audience_kicker' ) ); ?></p>
					<h2 id="lks-join-audience-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_audience_title' ) ); ?></h2>
				</div>
				<ul class="lks-membership-audience__list">
					<?php foreach ( lakeuden_kauppaseura_copy_list( 'join_audience_items' ) as $index => $item ) : ?>
						<li><span><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><strong><?php echo esc_html( $item ); ?></strong></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>

		<section class="lks-page-section lks-membership-benefits" aria-labelledby="lks-join-benefits-title">
			<div class="lks-page-shell">
				<div class="lks-membership-section-heading">
					<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_benefits_kicker' ) ); ?></p>
					<h2 id="lks-join-benefits-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_benefits_title' ) ); ?></h2>
				</div>
				<ul class="lks-membership-benefits__list">
					<?php foreach ( lakeuden_kauppaseura_copy_list( 'join_benefits_items' ) as $index => $item ) : ?>
						<li><span aria-hidden="true">&rarr;</span><strong><?php echo esc_html( $item ); ?></strong></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>

		<section class="lks-page-section lks-membership-practical" aria-labelledby="lks-join-facts-title">
			<div class="lks-page-shell lks-membership-practical__grid">
				<div class="lks-membership-practical__heading">
					<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_facts_kicker' ) ); ?></p>
					<h2 id="lks-join-facts-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_facts_title' ) ); ?></h2>
					<a class="lks-button lks-button--gold lks-membership-practical__cta" href="#kiinnostus"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_facts_cta' ) ); ?></a>
				</div>
				<?php echo lakeuden_kauppaseura_render_membership_facts( 'lks-membership-facts--light' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>

		<section class="lks-page-section lks-membership-steps" aria-labelledby="lks-join-steps-title">
			<div class="lks-page-shell">
				<div class="lks-membership-section-heading">
					<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_steps_kicker' ) ); ?></p>
					<h2 id="lks-join-steps-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_steps_title' ) ); ?></h2>
				</div>
				<ol class="lks-membership-steps__list">
					<li><span>01</span><div><h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_step_1_title' ) ); ?></h3><p><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_step_1_text' ) ); ?></p></div></li>
					<li><span>02</span><div><h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_step_2_title' ) ); ?></h3><p><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_step_2_text' ) ); ?></p></div></li>
					<li><span>03</span><div><h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_step_3_title' ) ); ?></h3><p class="<?php echo $nomination['unresolved'] ? 'is-unresolved' : ''; ?>"><?php echo esc_html( $nomination['value'] ); ?></p></div></li>
					<li><span>04</span><div><h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_step_4_title' ) ); ?></h3><p class="<?php echo $approval['unresolved'] ? 'is-unresolved' : ''; ?>"><?php echo esc_html( $approval['value'] ); ?></p></div></li>
					<li><span>05</span><div><h3><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_step_5_title' ) ); ?></h3><p><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_step_5_text' ) ); ?></p></div></li>
				</ol>
			</div>
		</section>

		<?php if ( $testimonials ) : ?>
			<section class="lks-page-section lks-membership-testimonials" aria-labelledby="lks-join-testimonials-title">
				<div class="lks-page-shell">
					<div class="lks-membership-section-heading">
						<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_testimonials_kicker' ) ); ?></p>
						<h2 id="lks-join-testimonials-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_testimonials_title' ) ); ?></h2>
					</div>
					<div class="lks-membership-testimonials__grid">
						<?php foreach ( $testimonials as $testimonial ) : ?>
							<article>
								<div class="lks-membership-testimonial__photo">
									<img src="<?php echo esc_url( $testimonial['photo'] ); ?>" width="640" height="640" loading="lazy" decoding="async" alt="<?php echo esc_attr( $testimonial['name'] . ', ' . $testimonial['org'] ); ?>" />
								</div>
								<blockquote><?php echo esc_html( $testimonial['quote'] ); ?></blockquote>
								<p><strong><?php echo esc_html( $testimonial['name'] ); ?></strong><span><?php echo esc_html( $testimonial['org'] ); ?></span></p>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="lks-page-section lks-membership-faq" aria-labelledby="lks-join-faq-title">
			<div class="lks-page-shell lks-membership-faq__grid">
				<div>
					<p class="lks-kicker"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_faq_kicker' ) ); ?></p>
					<h2 id="lks-join-faq-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_faq_title' ) ); ?></h2>
				</div>
				<div class="lks-membership-faq__items">
					<?php if ( ! $eligibility['unresolved'] ) : ?>
						<details><summary>Onko minun oltava yrittäjä?</summary><div><?php echo lakeuden_kauppaseura_render_membership_faq_fact( 'membership_eligibility' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></details>
					<?php endif; ?>
					<?php if ( ! $nonmember_events['unresolved'] ) : ?>
						<details><summary>Voinko osallistua ennen liittymistä?</summary><div><?php echo lakeuden_kauppaseura_render_membership_faq_fact( 'membership_nonmember_events' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></details>
					<?php endif; ?>
					<?php if ( ! $nomination['unresolved'] ) : ?>
						<details><summary>Tarvitsenko esittäjän?</summary><div><?php echo lakeuden_kauppaseura_render_membership_faq_fact( 'membership_nomination' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></details>
					<?php endif; ?>
					<?php if ( ! $annual_fee['unresolved'] && ! $joining_fee['unresolved'] ) : ?>
						<details><summary>Mitä jäsenyys maksaa?</summary><div><p><strong><?php echo esc_html( $annual_fee['label'] ); ?>:</strong> <?php echo lakeuden_kauppaseura_render_membership_faq_fact( 'membership_annual_fee' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p><p><strong><?php echo esc_html( $joining_fee['label'] ); ?>:</strong> <?php echo lakeuden_kauppaseura_render_membership_faq_fact( 'membership_joining_fee' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p></div></details>
					<?php endif; ?>
					<?php if ( ! $processing_time['unresolved'] ) : ?>
						<details><summary>Kuinka kauan käsittely kestää?</summary><div><?php echo lakeuden_kauppaseura_render_membership_faq_fact( 'membership_processing_time' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></details>
					<?php endif; ?>
					<?php if ( ! $membership_items['unresolved'] ) : ?>
						<details><summary>Mitä jäsenyyteen kuuluu?</summary><div><?php echo lakeuden_kauppaseura_render_membership_faq_fact( 'membership_includes' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php if ( ! $extra_event_fees['unresolved'] ) : ?><p><strong><?php echo esc_html( $extra_event_fees['label'] ); ?>:</strong> <?php echo lakeuden_kauppaseura_render_membership_faq_fact( 'membership_extra_event_fees' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p><?php endif; ?></div></details>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<section id="kiinnostus" class="lks-page-section lks-membership-form-section" aria-labelledby="lks-join-form-title">
			<div class="lks-page-shell lks-membership-form-section__grid">
				<div>
					<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_form_kicker' ) ); ?></p>
					<h2 id="lks-join-form-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_form_title' ) ); ?></h2>
					<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'join_form_lead' ) ); ?></p>
				</div>
				<div class="lks-membership-form-section__form">
					<?php echo lakeuden_kauppaseura_render_membership_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</section>
	</div>
	<?php

	return (string) preg_replace( '/>\s+</', '><', (string) ob_get_clean() );
}
add_shortcode( 'lks_membership_page', 'lakeuden_kauppaseura_render_membership_page' );
