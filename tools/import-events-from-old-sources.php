<?php
/**
 * Import missing Lakeuden Kauppaseura events found from old/social sources.
 */

require dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

function lks_import_instagram_item_by_permalink( $permalink ) {
	$items = get_option( 'lks_instagram_feed_last_good', array() );

	if ( ! is_array( $items ) ) {
		return null;
	}

	foreach ( $items as $item ) {
		if ( is_array( $item ) && ! empty( $item['permalink'] ) && $permalink === $item['permalink'] ) {
			return $item;
		}
	}

	return null;
}

function lks_import_event_image( $post_id, $event ) {
	if ( empty( $event['instagram_permalink'] ) || has_post_thumbnail( $post_id ) ) {
		return 0;
	}

	$item = lks_import_instagram_item_by_permalink( $event['instagram_permalink'] );

	if ( ! $item || empty( $item['image_url'] ) ) {
		return 0;
	}

	$tmp = download_url( $item['image_url'], 30 );

	if ( is_wp_error( $tmp ) ) {
		return 0;
	}

	$file = array(
		'name'     => sanitize_title( $event['title'] ) . '.jpg',
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $file, $post_id, $event['title'] );

	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		return 0;
	}

	set_post_thumbnail( $post_id, $attachment_id );
	return $attachment_id;
}

$events = array(
	array(
		'title'   => 'Pikkujoulut 15.12',
		'date'    => '2022-12-15',
		'place'   => '',
		'excerpt' => 'Vanhan tapahtumagallerian pikkujoulumerkintä seuran alkuvuosilta.',
		'content' => '<!-- wp:paragraph --><p>Vanhan tapahtumagallerian merkintä nosti esiin tapahtuman: Pikkujoulut 15.12. Joulukuinen kohtaaminen kuuluu Lakeuden Kauppaseuran varhaisiin tapahtumiin perustamisvuoden jälkeen.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Lähde: <a href="https://lakeudenkauppaseur3.wixsite.com/lakeuden-kauppaseura/tapahtumat" target="_blank" rel="noopener noreferrer">vanha tapahtumagalleria</a>.</p><!-- /wp:paragraph -->',
		'source_url' => 'https://lakeudenkauppaseur3.wixsite.com/lakeuden-kauppaseura/tapahtumat',
	),
	array(
		'title'   => 'Puurojuhla',
		'date'    => '2024-12-12',
		'place'   => '',
		'excerpt' => 'Toimintavuoden päätökseksi järjestetty perinteinen Puurojuhla.',
		'content' => '<!-- wp:paragraph --><p>Aktiivisen ja monipuolisen toimintavuoden päätteeksi Lakeuden Kauppaseura järjesti jo perinteisen Puurojuhlan. Puuro oli hyvää ja riittoisa oli kattila.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Tilaisuus kokosi jäseniä rauhoittumaan loppuvuoteen ja katsomaan kohti seuraavaa toimintavuotta.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Lähde: <a href="https://www.facebook.com/kauppaseura/posts/aktiivisen-ja-monipuolisen-toimintavuoden-p%C3%A4%C3%A4tteeksi-lakeuden-kauppaseura-j%C3%A4rjes/826254046968397/" target="_blank" rel="noopener noreferrer">Facebook</a> ja Instagram.</p><!-- /wp:paragraph -->',
		'source_url' => 'https://www.facebook.com/kauppaseura/posts/aktiivisen-ja-monipuolisen-toimintavuoden-p%C3%A4%C3%A4tteeksi-lakeuden-kauppaseura-j%C3%A4rjes/826254046968397/',
	),
	array(
		'title'   => 'LKS Talks',
		'date'    => '2026-01-28',
		'place'   => '',
		'excerpt' => 'Vuoden ensimmäinen jäsentapahtuma, jonka pääpuhujana oli Havu Takamaa.',
		'content' => '<!-- wp:paragraph --><p>Lakeuden Kauppaseuran tämän vuoden ensimmäinen jäsentapahtuma oli LKS Talks. Illan pääpuhujana oli reservin yliluutnantti Havu Takamaa, joka johdatti osallistujat ajankohtaiseen keskusteluun.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Ilta jatkoi seuran tapaa tuoda yhteen asiantuntijapuheenvuoroja, verkostoja ja paikallista keskustelua.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Lähde: <a href="https://www.facebook.com/kauppaseura/posts/lakeuden-kauppaseuran-t%C3%A4m%C3%A4n-vuoden-ensimm%C3%A4inen-j%C3%A4sentapahtuma-oli-lks-talks-illa/861739970086471/" target="_blank" rel="noopener noreferrer">Facebook</a> ja Instagram.</p><!-- /wp:paragraph -->',
		'source_url' => 'https://www.facebook.com/kauppaseura/posts/lakeuden-kauppaseuran-t%C3%A4m%C3%A4n-vuoden-ensimm%C3%A4inen-j%C3%A4sentapahtuma-oli-lks-talks-illa/861739970086471/',
		'instagram_permalink' => 'https://www.instagram.com/p/DUELH3cjG80/',
	),
	array(
		'title'   => 'Vuosipäivän juhla: Elämäni biisi',
		'date'    => '2026-02-12',
		'place'   => '',
		'excerpt' => 'Lakeuden Kauppaseuran vuosipäivää juhlistettiin hieman etukäteen teemalla Elämäni biisi.',
		'content' => '<!-- wp:paragraph --><p>Lakeuden Kauppaseura ry on perustettu ystävänpäivänä 14.2.2022. Vuonna 2026 vuosipäivää juhlittiin hieman etukäteen teemalla Elämäni biisi.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Musiikki, tarinat ja yhteinen ilta toivat seuran syntymäpäivän lähelle jäseniä ja ystäviä.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Lähde: <a href="https://www.instagram.com/p/DUq_8NJjI6I/" target="_blank" rel="noopener noreferrer">Instagram</a> ja Facebook.</p><!-- /wp:paragraph -->',
		'source_url' => 'https://www.instagram.com/p/DUq_8NJjI6I/',
		'instagram_permalink' => 'https://www.instagram.com/p/DUq_8NJjI6I/',
	),
	array(
		'title'   => 'Kevätkokous Etelä-Pohjanmaan Osuuspankilla',
		'date'    => '2026-04-08',
		'place'   => 'Etelä-Pohjanmaan Osuuspankki',
		'excerpt' => 'Kevätkokous pidettiin Etelä-Pohjanmaan Osuuspankin vieraana uusissa väliaikaisissa tiloissa.',
		'content' => '<!-- wp:paragraph --><p>Lakeuden Kauppaseuran kevätkokous pidettiin Etelä-Pohjanmaan Osuuspankin vieraana uusissa hienoissa väliaikaisissa tiloissa, emäntänä Marianne Olli.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Kokouksessa katsottiin toimintaa eteenpäin ja keskusteltiin siitä, mitä vuosi 2026 tuo Lakeuden Kauppaseuran jäsenille.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Lähde: <a href="https://www.instagram.com/p/DW4aeb5jIT9/" target="_blank" rel="noopener noreferrer">Instagram</a>.</p><!-- /wp:paragraph -->',
		'source_url' => 'https://www.instagram.com/p/DW4aeb5jIT9/',
		'instagram_permalink' => 'https://www.instagram.com/p/DW4aeb5jIT9/',
	),
	array(
		'title'   => 'Yritysvierailu Painokeskus ProPrint Oy:ssä',
		'date'    => '2026-04-22',
		'place'   => 'Painokeskus ProPrint Oy',
		'excerpt' => 'Yritysvierailulla tutustuttiin Painokeskus ProPrint Oy:n tarinaan, tiloihin ja tuotteisiin.',
		'content' => '<!-- wp:paragraph --><p>Keskiviikkona pääsimme tutustumaan Painokeskus ProPrint Oy:n tarinaan, tiloihin ja tuotteisiin. Olihan taas mielenkiintoinen ilta!</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Yritysvierailu jatkoi seuran käytännönläheistä tapaa oppia alueen yrityksiltä ja keskustella siitä, miten osaaminen, tuotteet ja ihmiset rakentavat kasvua.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Lähde: <a href="https://www.instagram.com/p/DXfDprBDKIb/" target="_blank" rel="noopener noreferrer">Instagram</a> ja Facebook.</p><!-- /wp:paragraph -->',
		'source_url' => 'https://www.instagram.com/p/DXfDprBDKIb/',
		'instagram_permalink' => 'https://www.instagram.com/p/DXfDprBDKIb/',
	),
);

foreach ( $events as $event ) {
	$slug     = sanitize_title( $event['title'] );
	$existing = get_page_by_path( $slug, OBJECT, 'lks_event' );

	if ( $existing ) {
		echo 'Skipped existing: ' . $event['title'] . PHP_EOL;
		continue;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'lks_event',
			'post_status'  => 'publish',
			'post_title'   => $event['title'],
			'post_name'    => $slug,
			'post_excerpt' => $event['excerpt'],
			'post_content' => $event['content'],
			'post_date'    => $event['date'] . ' 12:00:00',
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		echo 'Failed: ' . $event['title'] . ' - ' . $post_id->get_error_message() . PHP_EOL;
		continue;
	}

	update_post_meta( $post_id, '_lks_event_date', $event['date'] );

	if ( ! empty( $event['place'] ) ) {
		update_post_meta( $post_id, '_lks_event_place', $event['place'] );
	}

	if ( ! empty( $event['source_url'] ) ) {
		update_post_meta( $post_id, '_lks_event_source_url', esc_url_raw( $event['source_url'] ) );
	}

	$attachment_id = lks_import_event_image( $post_id, $event );

	echo 'Added: ' . $event['title'];
	echo $attachment_id ? ' (image ' . $attachment_id . ')' : '';
	echo PHP_EOL;
}
