<?php
/**
 * Apply the verified editorial/event remediation to the local WordPress source.
 *
 * This script is intentionally idempotent. Run it from the WordPress root with:
 * php tools/apply-remediation-content.php
 */

declare(strict_types=1);

if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 404 );
	exit;
}

require dirname( __DIR__ ) . '/wp-load.php';

/**
 * Return a post by type and slug, or fail with an actionable message.
 */
function lks_remediation_post( string $post_type, string $slug ): WP_Post {
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'name'           => $slug,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 1,
		)
	);

	if ( ! $posts ) {
		throw new RuntimeException( "Missing {$post_type}: {$slug}" );
	}

	return $posts[0];
}

/**
 * Update a post and surface WordPress errors as exceptions.
 */
function lks_remediation_update_post( WP_Post $post, array $changes ): WP_Post {
	$result = wp_update_post( array_merge( array( 'ID' => $post->ID ), $changes ), true );

	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $result->get_error_message() );
	}

	return get_post( (int) $result );
}

/**
 * Replace literal editorial defects without rewriting unrelated prose.
 */
function lks_remediation_replace_literals( WP_Post $post, array $replacements ): void {
	$content = str_replace( array_keys( $replacements ), array_values( $replacements ), $post->post_content );

	if ( $content !== $post->post_content ) {
		lks_remediation_update_post( $post, array( 'post_content' => $content ) );
	}
}

try {
	// The front-page template owns the homepage. Removing the obsolete editor
	// content also prevents WPForms from enqueueing assets for a form that is
	// neither rendered nor supported by the static site.
	$front_page = lks_remediation_post( 'page', 'etusivu' );
	lks_remediation_update_post( $front_page, array( 'post_content' => '' ) );

	$blog_page = lks_remediation_post( 'page', 'blogi' );
	delete_post_meta( $blog_page->ID, '_wp_page_template' );

	$september_content = <<<'HTML'
<!-- wp:paragraph -->
<p>Syksyn verkostoitumisilta järjestetään Seinäjoella perjantaina 18. syyskuuta 2026. Illan aikana tavataan tuttuja ja uusia kasvoja, keskustellaan ajankohtaisista aiheista sekä katsotaan yhdessä, mitä alueella juuri nyt tapahtuu.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Tilaisuus on suunnattu kaikille, joita kiinnostavat verkostot, yrittäjyys, alueen kehitys ja hyvä keskustelu. Ohjelmassa on rentoa kohtaamista, lyhyt ajankohtaiskatsaus ja aikaa vapaalle ajatustenvaihdolle.</p>
<!-- /wp:paragraph -->
HTML;

	$october_content = <<<'HTML'
<!-- wp:paragraph -->
<p>Ajankohtaiskatsaus ja yritysvierailu järjestetään torstaina 22. lokakuuta 2026. Tilaisuuden tarkoituksena on yhdistää käytännön yritysesimerkki ja keskustelu siitä, millaiset ilmiöt vaikuttavat tällä hetkellä Etelä-Pohjanmaan elinkeinoelämään.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Yritysvierailut tarjoavat mahdollisuuden nähdä läheltä, miten ihmiset, osaaminen ja toimintaympäristö kohtaavat arjessa. Samalla ne avaavat keskustelua siitä, millaisia edellytyksiä kasvu, kannattavuus ja uudistuminen tarvitsevat.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Tapahtuma on ennakkotieto. Ohjelma, yritys, paikka, kellonaika, osallistumisehdot ja ilmoittautuminen julkaistaan tällä sivulla, kun tiedot on vahvistettu.</p>
<!-- /wp:paragraph -->
HTML;

	$event_updates = array(
		'syksyn-verkostoitumisilta-seinajoella' => array(
			'content' => $september_content,
			'meta'    => array(
				'_lks_event_city'            => 'Seinäjoki',
				'_lks_event_place'           => 'Seinäjoki – tarkempi paikka vahvistetaan',
				'_lks_event_place_label'     => 'Paikka',
				'_lks_event_audience'        => 'Kaikille, joita kiinnostavat verkostot, yrittäjyys, alueen kehitys ja hyvä keskustelu',
				'_lks_event_price'           => 'Vahvistetaan',
				'_lks_event_registration'    => 'Pyydä osallistumisohjeet sähköpostilla',
				'_lks_event_status'          => 'email_instructions',
				'_lks_event_cta_label'       => 'Pyydä osallistumisohjeet',
				'_lks_event_contact_subject' => 'Ilmoittautuminen: Syksyn verkostoitumisilta 18.9.2026',
			),
		),
		'ajankohtaiskatsaus-ja-yritysvierailu' => array(
			'content' => $october_content,
			'meta'    => array(
				'_lks_event_city'            => '',
				'_lks_event_place'           => 'Vahvistetaan',
				'_lks_event_place_label'     => 'Yritys ja paikka',
				'_lks_event_audience'        => 'Vahvistetaan',
				'_lks_event_price'           => 'Vahvistetaan',
				'_lks_event_registration'    => 'Avataan, kun ohjelma ja paikka on vahvistettu',
				'_lks_event_status'          => 'advance_notice',
				'_lks_event_cta_label'       => 'Pyydä lisätiedot',
				'_lks_event_contact_subject' => 'Lisätiedot: Ajankohtaiskatsaus ja yritysvierailu 22.10.2026',
			),
		),
	);

	foreach ( $event_updates as $slug => $update ) {
		$event = lks_remediation_post( 'lks_event', $slug );
		lks_remediation_update_post( $event, array( 'post_content' => $update['content'] ) );
		delete_post_meta( $event->ID, '_lks_event_time' );

		foreach ( $update['meta'] as $key => $value ) {
			if ( '' === $value ) {
				delete_post_meta( $event->ID, $key );
			} else {
				update_post_meta( $event->ID, $key, $value );
			}
		}

		// Retain the legacy posts as discoverable redirect targets for the static
		// exporter, but remove their false or placeholder instructions.
		$legacy = lks_remediation_post( 'post', $slug );
		lks_remediation_update_post( $legacy, array( 'post_content' => $update['content'] ) );
	}

	$pessimismi = lks_remediation_post( 'post', 'pessimismi-ja-todellisuus' );
	$pessimismi_content = preg_replace(
		'#^\s*<!-- wp:paragraph -->\s*<p>07\.03\.2024</p>\s*<!-- /wp:paragraph -->\s*#u',
		'',
		$pessimismi->post_content
	);
	$pessimismi_content = str_replace(
		array( 'saman-aikaisesti', 'toimen-piteistä' ),
		array( 'samanaikaisesti', 'toimenpiteistä' ),
		(string) $pessimismi_content
	);
	lks_remediation_update_post(
		$pessimismi,
		array(
			'post_content' => $pessimismi_content,
			'post_excerpt' => 'Kaiken viisauden alku on tosiasiain tunnustaminen.',
		)
	);
	update_post_meta( $pessimismi->ID, '_lks_original_publication_date', '2024-03-07' );

	$siisteys = lks_remediation_post( 'post', 'siisteys-ja-jarjestys-maatiloilla-lisaavat-turvallisuutta' );
	lks_remediation_replace_literals( $siisteys, array( 'yllä-pitämiseen' => 'ylläpitämiseen' ) );

	$article_metadata = array(
		'yritystemme-menestyminen-on-ratkaiseva-tekija' => array(
			'title'       => 'Yritystemme menestyminen on ratkaiseva tekijä',
			'publication' => 'Ilkka-Pohjalainen',
			'date'        => '2023-05-16',
		),
		'etelapohjalaiset-parjaajiksi-jarviseudun-sanomat-24-5-2023' => array(
			'title'       => 'Eteläpohjalaiset pärjääjiksi',
			'publication' => 'Järviseudun Sanomat',
			'date'        => '2023-05-24',
		),
	);

	foreach ( $article_metadata as $slug => $metadata ) {
		$post = lks_remediation_post( 'post', $slug );
		lks_remediation_update_post( $post, array( 'post_title' => $metadata['title'] ) );
		update_post_meta( $post->ID, '_lks_original_publication_name', $metadata['publication'] );
		update_post_meta( $post->ID, '_lks_original_publication_date', $metadata['date'] );
	}

	$tampere_post = lks_remediation_post( 'post', 'lakeuden-kauppaseura-tampereen-kauppaseuran-vieraana' );
	lks_remediation_update_post( $tampere_post, array( 'post_title' => 'Vierailulla Tampereen Kauppaseurassa' ) );

	$tampere_event = lks_remediation_post( 'lks_event', 'lakeuden-kauppaseura-tampereen-kauppaseuran-vieraana' );
	lks_remediation_update_post( $tampere_event, array( 'post_title' => 'Vierailu Tampereen Kauppaseurassa' ) );

	$construction = lks_remediation_post( 'post', 'rakennusala-hataa-karsimassa-julkiset-investoinnit-eivat-enaa-tahditu-tasaamaan-lamaa' );
	lks_remediation_update_post(
		$construction,
		array(
			'post_title'   => 'Rakennusala hätää kärsimässä',
			'post_excerpt' => 'Julkiset investoinnit eivät enää tahditu tasaamaan lamaa. Vuotuinen rakentamisen arvo normaalitilanteessa on lähes 40 miljardia euroa.',
		)
	);

	$pikkujoulut = lks_remediation_post( 'lks_event', 'pikkujoulut-15-12' );
	lks_remediation_update_post(
		$pikkujoulut,
		array(
			'post_excerpt' => 'Lakeuden Kauppaseuran pikkujoulut järjestettiin 15.12.2022.',
			'post_content' => '<!-- wp:paragraph --><p>Lakeuden Kauppaseura kokoontui pikkujouluihin 15.12.2022. Tapahtuma oli osa seuran ensimmäisen toimintavuoden ohjelmaa.</p><!-- /wp:paragraph -->',
		)
	);

	$privacy = get_page_by_path( 'tietosuoja', OBJECT, 'page' );
	$privacy_data = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Tietosuoja',
		'post_name'    => 'tietosuoja',
		'post_content' => '<!-- wp:shortcode -->[lks_privacy_page]<!-- /wp:shortcode -->',
	);

	if ( $privacy ) {
		$privacy_data['ID'] = $privacy->ID;
		$result = wp_update_post( $privacy_data, true );
	} else {
		$result = wp_insert_post( $privacy_data, true );
	}

	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $result->get_error_message() );
	}

	delete_post_meta( (int) $result, '_wp_page_template' );
	update_option( 'lks_remediation_content_version', '2026-07-20-1', false );

	echo "Lakeuden Kauppaseura content remediation applied.\n";
} catch ( Throwable $error ) {
	fwrite( STDERR, 'Remediation failed: ' . $error->getMessage() . "\n" );
	exit( 1 );
}
