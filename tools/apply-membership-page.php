<?php
/**
 * Create the Jäseneksi page and configure the existing WPForms form.
 *
 * This script is idempotent. It preserves the existing notification recipient
 * and does not overwrite a form already marked with the current schema
 * version, so later edits made in WPForms remain intact.
 *
 * Usage:
 * php tools/apply-membership-page.php
 */

declare(strict_types=1);

$workspace = dirname( __DIR__ );
$wp_load   = $workspace . '/wp-load.php';

if ( ! is_file( $wp_load ) ) {
	fwrite( STDERR, "WordPress was not found at the repository root.\n" );
	exit( 1 );
}

require_once $wp_load;

if ( ! post_type_exists( 'wpforms' ) || ! shortcode_exists( 'wpforms' ) ) {
	fwrite( STDERR, "WPForms Lite must be installed and active before applying the membership page.\n" );
	exit( 1 );
}

const LKS_MEMBERSHIP_FORM_SCHEMA = '3';

/**
 * Find the configured membership form without hardcoding its database ID.
 */
function lks_membership_find_form(): ?WP_Post {
	$options = get_option( 'lakeuden_kauppaseura_page_copy', array() );
	$form_id = is_array( $options ) ? absint( $options['join_form_id'] ?? 0 ) : 0;
	$form    = $form_id ? get_post( $form_id ) : null;

	if ( $form instanceof WP_Post && 'wpforms' === $form->post_type ) {
		return $form;
	}

	$forms = get_posts(
		array(
			'post_type'      => 'wpforms',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 1,
			'title'          => 'Liity mukaan',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	return $forms ? $forms[0] : null;
}

/**
 * Return the existing notification recipient or the site's configured admin.
 *
 * @param array<string,mixed> $existing Existing WPForms form data.
 */
function lks_membership_notification_recipient( array $existing ): string {
	$notifications = $existing['settings']['notifications'] ?? array();
	if ( is_array( $notifications ) ) {
		foreach ( $notifications as $notification ) {
			if ( is_array( $notification ) && ! empty( $notification['email'] ) ) {
				return (string) $notification['email'];
			}
		}
	}

	return '{admin_email}';
}

/**
 * Build the reproducible WPForms schema.
 *
 * @param int                 $form_id  Form post ID.
 * @param array<string,mixed> $existing Existing WPForms form data.
 * @return array<string,mixed>
 */
function lks_membership_form_data( int $form_id, array $existing ): array {
	$recipient = lks_membership_notification_recipient( $existing );

	return array(
		'fields'   => array(
			'1'  => array( 'id' => '1', 'type' => 'text', 'label' => 'Nimi', 'required' => '1', 'size' => 'large' ),
			'2'  => array( 'id' => '2', 'type' => 'email', 'label' => 'Sähköposti', 'required' => '1', 'size' => 'large', 'allowlist' => '', 'denylist' => '', 'default_value' => '' ),
			'3'  => array( 'id' => '3', 'type' => 'text', 'label' => 'Puhelin (vapaaehtoinen)', 'required' => '', 'size' => 'large', 'input_mask' => '' ),
			'4'  => array( 'id' => '4', 'type' => 'text', 'label' => 'Organisaatio', 'required' => '1', 'size' => 'large' ),
			'5'  => array( 'id' => '5', 'type' => 'text', 'label' => 'Rooli', 'required' => '1', 'size' => 'large' ),
			'6'  => array( 'id' => '6', 'type' => 'text', 'label' => 'Kunta', 'required' => '1', 'size' => 'large' ),
			'7'  => array( 'id' => '7', 'type' => 'textarea', 'label' => 'Mikä jäsenyydessä kiinnostaa?', 'required' => '1', 'size' => 'large' ),
			'8'  => array(
				'id'       => '8',
				'type'     => 'radio',
				'label'    => 'Toivottu yhteydenottotapa',
				'required' => '1',
				'size'     => 'medium',
				'choices'  => array(
					'1' => array( 'label' => 'Sähköposti', 'value' => 'Sähköposti' ),
					'2' => array( 'label' => 'Puhelin', 'value' => 'Puhelin' ),
					'3' => array( 'label' => 'Ei väliä', 'value' => 'Ei väliä' ),
				),
			),
			'9'  => array(
				'id'          => '9',
				'type'        => 'checkbox',
				'label'       => 'Tietosuostumus',
				'required'    => '1',
				'description' => 'Tietosuojaseloste on linkitetty lomakkeen yläpuolelle.',
				'choices'     => array(
					'1' => array(
						'label' => 'Hyväksyn antamieni tietojen käsittelyn yhteydenottoa ja jäsenyysasian käsittelyä varten.',
						'value' => '',
					),
				),
			),
			'10' => array(
				'id'          => '10',
				'type'        => 'checkbox',
				'label'       => 'Vapaaehtoinen viestintäsuostumus',
				'required'    => '',
				'description' => 'Tämä suostumus ei ole jäsenyyskyselyn käsittelyn edellytys.',
				'choices'     => array(
					'1' => array(
						'label' => 'Haluan vastaanottaa Lakeuden Kauppaseuran toimintaa ja tapahtumia koskevaa viestintää.',
						'value' => '',
					),
				),
			),
		),
		'field_id' => 11,
		'settings' => array(
			'form_title'             => 'Liity mukaan',
			'form_desc'              => 'Kiinnostuslomake Lakeuden Kauppaseuran jäsenyydestä kiinnostuneille.',
			'submit_text'            => 'Lähetä lomake',
			'submit_text_processing' => 'Lähetetään…',
			'antispam_v3'            => '1',
			'notification_enable'    => '1',
			'notifications'          => array(
				'1' => array(
					'notification_name' => 'Uusi jäsenyyskiinnostus',
					'email'             => $recipient,
					'replyto'           => '{field_id="2"}',
					'subject'           => 'Uusi jäsenyyskiinnostus: {field_id="1"}',
					'sender_name'       => 'Lakeuden Kauppaseura',
					'message'           => "{all_fields}\n\nLomake: {form_name}\nSivu: {page_url}",
				),
			),
			'confirmations'          => array(
				'1' => array(
					'type'           => 'message',
					'message'        => 'Kiitos! Kiinnostuksesi on vastaanotettu. Otamme sinuun yhteyttä valitsemallasi tavalla. Jos haluat varmistaa yhteydenoton, löydät suorat yhteystiedot Yhteystiedot-sivulta.',
					'message_scroll' => '1',
				),
			),
			'ajax_submit'             => '1',
			'store_spam_entries'     => '0',
		),
		'meta'     => array(
			'template'                   => 'simple-contact-form-template',
			'lks_membership_form_schema' => LKS_MEMBERSHIP_FORM_SCHEMA,
		),
		'id'       => $form_id,
	);
}

$form = lks_membership_find_form();

if ( ! $form ) {
	$form_id = wp_insert_post(
		array(
			'post_type'    => 'wpforms',
			'post_status'  => 'publish',
			'post_title'   => 'Liity mukaan',
			'post_content' => '{}',
		),
		true
	);

	if ( is_wp_error( $form_id ) ) {
		fwrite( STDERR, 'Could not create WPForms form: ' . $form_id->get_error_message() . "\n" );
		exit( 1 );
	}
	$form = get_post( $form_id );
	echo "Created WPForms form {$form_id}.\n";
}

$existing = json_decode( (string) $form->post_content, true );
$existing = is_array( $existing ) ? $existing : array();
$version  = (string) ( $existing['meta']['lks_membership_form_schema'] ?? '' );

if ( LKS_MEMBERSHIP_FORM_SCHEMA !== $version ) {
	$form_data = lks_membership_form_data( (int) $form->ID, $existing );
	$result    = wp_update_post(
		array(
			'ID'           => $form->ID,
			'post_status'  => 'publish',
			'post_title'   => 'Liity mukaan',
			'post_content' => wp_slash( wp_json_encode( $form_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ),
		),
		true
	);

	if ( is_wp_error( $result ) ) {
		fwrite( STDERR, 'Could not update WPForms form: ' . $result->get_error_message() . "\n" );
		exit( 1 );
	}
	echo "Applied membership form schema " . LKS_MEMBERSHIP_FORM_SCHEMA . " to form {$form->ID}.\n";
} else {
	echo "WPForms form {$form->ID} already uses membership schema " . LKS_MEMBERSHIP_FORM_SCHEMA . ".\n";
}

$copy = get_option( 'lakeuden_kauppaseura_page_copy', array() );
$copy = is_array( $copy ) ? $copy : array();
$copy['join_form_id'] = (string) $form->ID;
if ( ! array_key_exists( 'join_form_ready', $copy ) ) {
	$copy['join_form_ready'] = '0';
}
update_option( 'lakeuden_kauppaseura_page_copy', $copy, false );

$page = get_page_by_path( 'jaseneksi', OBJECT, 'page' );
if ( ! $page ) {
	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Jäseneksi',
			'post_name'    => 'jaseneksi',
			'post_content' => '[lks_membership_page]',
		),
		true
	);

	if ( is_wp_error( $page_id ) ) {
		fwrite( STDERR, 'Could not create Jäseneksi page: ' . $page_id->get_error_message() . "\n" );
		exit( 1 );
	}
	echo "Created Jäseneksi page {$page_id}.\n";
	flush_rewrite_rules( false );
} elseif ( '[lks_membership_page]' !== trim( (string) $page->post_content ) ) {
	fwrite( STDERR, "The existing /jaseneksi/ page has custom content. Refusing to overwrite it; add [lks_membership_page] manually or review the page first.\n" );
	exit( 1 );
} else {
	if ( 'publish' !== $page->post_status || 'Jäseneksi' !== $page->post_title ) {
		wp_update_post(
			array(
				'ID'          => $page->ID,
				'post_status' => 'publish',
				'post_title'  => 'Jäseneksi',
			)
		);
	}
	echo "Jäseneksi page {$page->ID} is configured.\n";
}

echo "Production form readiness remains " . ( '1' === (string) $copy['join_form_ready'] ? 'enabled' : 'disabled' ) . ".\n";
echo "Review docs/MEMBERSHIP_PAGE.md before enabling the production form.\n";
