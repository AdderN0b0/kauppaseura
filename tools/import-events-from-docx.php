<?php
/**
 * Import past Lakeuden Kauppaseura events and their featured images from the
 * 2024 and 2025 activity reports.
 *
 * The importer is intentionally idempotent. It never creates an event unless
 * the matching image can first be read and validated from the source DOCX.
 *
 * Usage:
 * php tools/import-events-from-docx.php --dry-run
 * php tools/import-events-from-docx.php
 */

declare(strict_types=1);

require dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$dry_run  = in_array( '--dry-run', $argv, true );
$home     = (string) getenv( 'USERPROFILE' );
$downloads = rtrim( str_replace( '\\', '/', $home ), '/' ) . '/Downloads';

$documents = array(
	'2024' => $downloads . '/Lakeuden Kauppaseuran toimintakertomus vuodelta 2024 painoon 1.docx',
	'2025' => $downloads . '/Lakeuden Kauppaseuran toimintakertomus vuodelta 2025 hallituksen nähtäväksi.docx',
);

/**
 * Read and validate a raster image stored inside a DOCX archive.
 *
 * @return array{bytes:string,mime:string,extension:string}|WP_Error
 */
function lks_docx_event_image( string $document, string $member ) {
	if ( ! is_file( $document ) ) {
		return new WP_Error( 'missing_document', 'Source document not found: ' . $document );
	}

	$zip = new ZipArchive();
	if ( true !== $zip->open( $document ) ) {
		return new WP_Error( 'invalid_document', 'Could not open source document: ' . $document );
	}

	$bytes = $zip->getFromName( $member );
	$zip->close();

	if ( false === $bytes || '' === $bytes ) {
		return new WP_Error( 'missing_image', 'Image not found in source document: ' . $member );
	}

	$image_info = getimagesizefromstring( $bytes );
	if ( false === $image_info || empty( $image_info['mime'] ) ) {
		return new WP_Error( 'invalid_image', 'The matched document item is not a valid raster image: ' . $member );
	}

	$extensions = array(
		'image/jpeg' => 'jpg',
		'image/png'  => 'png',
		'image/webp' => 'webp',
	);
	$mime = strtolower( (string) $image_info['mime'] );

	if ( ! isset( $extensions[ $mime ] ) ) {
		return new WP_Error( 'unsupported_image', 'Unsupported event image type: ' . $mime );
	}

	return array(
		'bytes'     => $bytes,
		'mime'      => $mime,
		'extension' => $extensions[ $mime ],
	);
}

/**
 * Find an existing event by its canonical slug or a known legacy slug.
 */
function lks_docx_existing_event( array $event ): ?WP_Post {
	$slugs = array_merge(
		array( sanitize_title( $event['title'] ) ),
		$event['existing_slugs'] ?? array()
	);

	foreach ( array_unique( $slugs ) as $slug ) {
		$existing = get_page_by_path( $slug, OBJECT, 'lks_event' );
		if ( $existing instanceof WP_Post ) {
			return $existing;
		}
	}

	return null;
}

/**
 * Store a validated document image as the event's featured image.
 *
 * @param array{bytes:string,mime:string,extension:string} $image
 * @return int|WP_Error
 */
function lks_docx_attach_event_image( int $post_id, array $event, array $image ) {
	$filename = sanitize_title( $event['title'] ) . '.' . $image['extension'];
	$upload   = wp_upload_bits( $filename, null, $image['bytes'], $event['date'] );

	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'upload_failed', (string) $upload['error'] );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $image['mime'],
			'post_title'     => $event['title'],
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$upload['file'],
		$post_id,
		true
	);

	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $upload['file'] );
		return $attachment_id;
	}

	$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
	if ( is_wp_error( $metadata ) || empty( $metadata ) ) {
		wp_delete_attachment( $attachment_id, true );
		return new WP_Error( 'metadata_failed', 'Could not create image metadata.' );
	}

	wp_update_attachment_metadata( $attachment_id, $metadata );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $event['image_alt'] );

	if ( ! set_post_thumbnail( $post_id, $attachment_id ) ) {
		wp_delete_attachment( $attachment_id, true );
		return new WP_Error( 'thumbnail_failed', 'Could not set the featured image.' );
	}

	return $attachment_id;
}

/**
 * Build compact block-editor content from source-document facts.
 */
function lks_docx_event_content( array $event ): string {
	$paragraphs   = $event['paragraphs'];
	$paragraphs[] = sprintf(
		'Lähde: Lakeuden Kauppaseuran toimintakertomus vuodelta %s.',
		$event['source_year']
	);

	return implode(
		'',
		array_map(
			static fn( string $paragraph ): string => '<!-- wp:paragraph --><p>' . esc_html( $paragraph ) . '</p><!-- /wp:paragraph -->',
			$paragraphs
		)
	);
}

$events = array(
	array(
		'title'       => 'Keilailuilta Seinäjoen Keilahallissa',
		'date'        => '2024-01-15',
		'time'        => '18:00',
		'place'       => 'Seinäjoen Keilahalli',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Vuoden tapahtumakausi käynnistyi rennolla keilailuillalla.',
		'paragraphs'  => array( 'Vuoden 2024 tapahtumakausi käynnistyi keilailuillalla Seinäjoen Keilahallissa. Tilaisuus oli tarkoitettu jäsenille aveceineen.' ),
		'source_year' => '2024',
		'image'       => 'word/media/image2.png',
		'image_alt'   => 'Lakeuden Kauppaseuran jäseniä keilaamassa Seinäjoen Keilahallissa.',
	),
	array(
		'title'       => 'LKS Talks ja Nuorkauppakamarin tapaaminen 2024',
		'date'        => '2024-01-30',
		'time'        => '18:00',
		'place'       => 'Alma',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Yhteisillassa tutustuttiin järjestöjen toimintaan ja kuultiin puheenvuoroja.',
		'paragraphs'  => array( 'Lakeuden Kauppaseura ja Seinäjoen Seudun Nuorkauppakamari tapasivat LKS Talks -illassa. Osallistujat tutustuivat molempien järjestöjen toiminta-ajatuksiin ja kuulivat mielenkiintoisia puheenvuoroja.' ),
		'source_year' => '2024',
		'image'       => 'word/media/image4.jpeg',
		'image_alt'   => 'Puheenvuoro LKS Talks- ja Nuorkauppakamarin yhteisillassa.',
	),
	array(
		'title'       => 'Ystävänpäivän ilta 2024',
		'date'        => '2024-02-14',
		'time'        => '18:00',
		'place'       => 'Alma',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Ystävänpäivänä verkostoiduttiin ja juhlistettiin seuran kaksivuotispäivää.',
		'paragraphs'  => array( 'Ystävänpäivän jäsenillassa verkostoiduttiin ja vietettiin aikaa yhdessä. Samalla juhlistettiin kaksivuotiasta Lakeuden Kauppaseuraa.' ),
		'source_year' => '2024',
		'image'       => 'word/media/image6.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran kaksivuotispäivän ystävänpäivätilaisuus.',
	),
	array(
		'title'       => 'Strategiailta 2024',
		'date'        => '2024-03-11',
		'time'        => '18:00',
		'place'       => 'M-talo',
		'city'        => '',
		'excerpt'     => 'Jäsenistö kokoontui työstämään Lakeuden Kauppaseuran strategiaa.',
		'paragraphs'  => array( 'Jäsenistö kokoontui M-talolle työstämään Lakeuden Kauppaseuran strategiaa. Strategiaprosessiin osallistui lähes koko jäsenistö.' ),
		'source_year' => '2024',
		'image'       => 'word/media/image8.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran strategiaillan esitys.',
	),
	array(
		'title'       => 'Kevätkokous 2024',
		'date'        => '2024-04-16',
		'time'        => '18:00',
		'place'       => 'Etappi',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Lakeuden Kauppaseuran kevätkokous pidettiin Etapissa.',
		'paragraphs'  => array( 'Lakeuden Kauppaseuran kevätkokous pidettiin Etapissa Seinäjoella. Kokouksessa hyväksyttiin vuoden 2023 tilinpäätös ja toimintakertomus.' ),
		'source_year' => '2024',
		'image'       => 'word/media/image10.jpeg',
		'image_alt'   => 'Osallistujia Lakeuden Kauppaseuran kevätkokouksessa 2024.',
	),
	array(
		'title'          => 'Tutustuminen Tampereen Kauppaseuraan 2024',
		'date'           => '2024-05-17',
		'time'           => '',
		'place'          => 'Tampereen Kauppaseura',
		'city'           => 'Tampere',
		'excerpt'        => 'Lakeuden Kauppaseura tutustui Tampereen Kauppaseuran toimintaan.',
		'paragraphs'     => array( 'Lakeuden Kauppaseuran jäsenet matkustivat junalla Tampereelle ja tutustuivat Tampereen Kauppaseuran toimintaan.' ),
		'source_year'    => '2024',
		'image'          => 'word/media/image12.jpeg',
		'image_alt'      => 'Lakeuden Kauppaseuran vierailijoita Tampereen Kauppaseurassa.',
		'existing_slugs' => array( 'lakeuden-kauppaseura-tampereen-kauppaseuran-vieraana' ),
		'sync_existing'  => true,
	),
	array(
		'title'       => 'Vierailu Lapualla 2024',
		'date'        => '2024-06-07',
		'time'        => '',
		'place'       => '',
		'city'        => 'Lapua',
		'excerpt'     => 'Kesäkuun vierailu suuntautui Lapualle Harri Seppälän isännöimänä.',
		'paragraphs'  => array( 'Kesäkuussa Lakeuden Kauppaseura vieraili Lapualla. Illan isäntänä toimi Harri Seppälä.' ),
		'source_year' => '2024',
		'image'       => 'word/media/image14.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran jäseniä vierailulla Lapualla.',
	),
	array(
		'title'       => 'Uusien jäsenten ilta ja pizzailta',
		'date'        => '2024-10-08',
		'time'        => '18:00',
		'place'       => 'Kabinetto',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Uusien jäsenten illassa tutustuttiin rennosti pizzan äärellä.',
		'paragraphs'  => array( 'Lakeuden Kauppaseuran uusien jäsenten ilta järjestettiin Kabinetossa Seinäjoella. Osallistujat tutustuivat toisiinsa rennosti pizzan äärellä.' ),
		'source_year' => '2024',
		'image'       => 'word/media/image21.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran jäseniä uusien jäsenten pizzaillassa.',
	),
	array(
		'title'       => 'Meidän ilta 2024',
		'date'        => '2024-10-25',
		'time'        => '18:30',
		'place'       => 'Fooninki',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Lokakuun Meidän ilta kokosi jäsenet Fooninkiin.',
		'paragraphs'  => array( 'Lokakuun Meidän ilta järjestettiin Fooningissa Seinäjoella. Jäsenet kokoontuivat yhteisen illan ja musiikin äärelle.' ),
		'source_year' => '2024',
		'image'       => 'word/media/image23.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran Meidän ilta Fooningissa.',
	),
	array(
		'title'       => 'Syyskokous 2024',
		'date'        => '2024-11-12',
		'time'        => '18:00',
		'place'       => 'Atria',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Lakeuden Kauppaseuran syyskokous pidettiin Atrian vieraana.',
		'paragraphs'  => array( 'Lakeuden Kauppaseuran syyskokous pidettiin Atrian vieraana Seinäjoella. Kokouksessa esiteltiin seuraavan vuoden toimintasuunnitelma ja budjetti.' ),
		'source_year' => '2024',
		'image'       => 'word/media/image25.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran osallistujia Atrian tiloissa.',
	),
	array(
		'title'          => 'Jouluinen ilta 2024',
		'date'           => '2024-12-16',
		'time'           => '18:00',
		'place'          => 'Käpälikkö',
		'city'           => 'Seinäjoki',
		'excerpt'        => 'Toimintavuosi päättyi joululahjojen ja joululaulujen tunnelmaan.',
		'paragraphs'     => array( 'Toimintavuosi päättyi Käpälikössä järjestettyyn jouluiseen iltaan, jonka ohjelmassa oli joululahjoja, joululauluja ja yhteistä aikaa.' ),
		'source_year'    => '2024',
		'image'          => 'word/media/image29.jpeg',
		'image_alt'      => 'Lakeuden Kauppaseuran jäseniä jouluisessa illassa.',
		'existing_slugs' => array( 'puurojuhla' ),
	),
	array(
		'title'       => 'LKS Talks 2025: yhteisilta Nuorkauppakamarin kanssa',
		'date'        => '2025-01-16',
		'time'        => '18:00',
		'place'       => 'Kabackan alakerta',
		'city'        => '',
		'excerpt'     => 'Vuosi käynnistyi LKS Talks -yhteisillalla Nuorkauppakamarin kanssa.',
		'paragraphs'  => array( 'Vuoden 2025 tapahtumakausi käynnistyi LKS Talks -illalla yhdessä Seinäjoen Seudun Nuorkauppakamarin kanssa.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image2.jpeg',
		'image_alt'   => 'Puheenvuoro LKS Talks -yhteisillassa tammikuussa 2025.',
	),
	array(
		'title'       => 'Hallitus tiedottaa ja musiikki-ilta',
		'date'        => '2025-01-28',
		'time'        => '',
		'place'       => 'Pölkkytie 13',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Tammikuun toisessa tapahtumassa yhdistyivät ajankohtaiset asiat ja musiikki.',
		'paragraphs'  => array( 'Tammikuun toisessa tapahtumassa hallitus kertoi ajankohtaisista asioista ja iltaa vietettiin musiikin merkeissä.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image4.jpeg',
		'image_alt'   => 'Osallistujat soittimien kanssa Lakeuden Kauppaseuran musiikki-illassa.',
	),
	array(
		'title'       => 'Viestintätiimin tapaaminen 2025',
		'date'        => '2025-03-06',
		'time'        => '',
		'place'       => 'Pölkkytie 13',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Viestintätiimi kokoontui suunnittelemaan seuran viestintää.',
		'paragraphs'  => array( 'Lakeuden Kauppaseuran viestintätiimi kokoontui suunnittelemaan seuran viestintää ja tulevia sisältöjä.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image6.png',
		'image_alt'   => 'Lakeuden Kauppaseuran viestintätiimi suunnittelutapaamisessa.',
	),
	array(
		'title'       => 'Kevätkokous 2025',
		'date'        => '2025-04-02',
		'time'        => '',
		'place'       => 'Amarillo',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Kevätkokouksessa käsiteltiin yhdistyksen asiat ja kiitettiin perustajajäsentä.',
		'paragraphs'  => array( 'Lakeuden Kauppaseuran kevätkokous pidettiin Amarillossa Seinäjoella. Kokouksessa kiitettiin perustajajäsen Heikki Kangasta yhdistyksen viirillä merkittävästä panoksesta seuran hyväksi.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image8.png',
		'image_alt'   => 'Lakeuden Kauppaseuran kevätkokous Amarillossa vuonna 2025.',
	),
	array(
		'title'       => 'Tutustuminen hankinta- ja logistiikkapalveluihin',
		'date'        => '2025-04-16',
		'time'        => '',
		'place'       => 'Seinäjoen keskussairaala',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Vierailulla tutustuttiin Seinäjoen keskussairaalan hankinta- ja logistiikkapalveluihin.',
		'paragraphs'  => array( 'Huhtikuun vierailulla Lakeuden Kauppaseura tutustui Seinäjoen keskussairaalan hankinta- ja logistiikkapalveluihin.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image10.jpeg',
		'image_alt'   => 'Esittely Seinäjoen keskussairaalan hankinta- ja logistiikkapalveluista.',
	),
	array(
		'title'       => 'Tutustuminen Suomen eduskuntaan',
		'date'        => '2025-05-14',
		'time'        => '',
		'place'       => 'Eduskunta',
		'city'        => 'Helsinki',
		'excerpt'     => 'Lakeuden Kauppaseura matkusti junalla tutustumaan Suomen eduskuntaan.',
		'paragraphs'  => array( 'Lakeuden Kauppaseura matkusti junalla Helsinkiin ja tutustui Suomen eduskuntaan.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image12.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran vierailuryhmä Suomen eduskunnassa.',
	),
	array(
		'title'       => 'Lapuan ilta 2025',
		'date'        => '2025-06-03',
		'time'        => '',
		'place'       => '',
		'city'        => 'Lapua',
		'excerpt'     => 'Kesäkuun vierailuiltaa Lapualla isännöivät Martti Kaunismäki ja Lapuan jäsenistö.',
		'paragraphs'  => array( 'Kesäkuun vierailuiltaa vietettiin Lapualla. Isäntänä toimi Martti Kaunismäki yhdessä Lapuan jäsenistön kanssa.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image14.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran osallistujia vierailulla Lapualla.',
	),
	array(
		'title'       => 'Kesäteatteri: Vaimoni on toista maata',
		'date'        => '2025-06-11',
		'time'        => '',
		'place'       => '',
		'city'        => '',
		'excerpt'     => 'Kesäkuussa nautittiin yhdessä Vaimoni on toista maata -näytelmästä.',
		'paragraphs'  => array( 'Lakeuden Kauppaseuran jäsenet viettivät yhteistä kesäiltaa katsomalla Vaimoni on toista maata -näytelmän.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image16.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran ryhmä kesäteatterissa.',
	),
	array(
		'title'       => 'Ulkoilutapahtuma Paukanevalla',
		'date'        => '2025-09-02',
		'time'        => '',
		'place'       => 'Paukanevan pitkospuut ja nuotiopaikka',
		'city'        => '',
		'excerpt'     => 'Syksyn ulkoilutapahtumassa kuljettiin Paukanevan pitkospuilla ja kokoonnuttiin nuotiolle.',
		'paragraphs'  => array( 'Syksyn ulkoilutapahtumassa kuljettiin Paukanevan pitkospuilla ja kokoonnuttiin nuotiopaikalle.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image18.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran osallistujia Paukanevan nuotiopaikalla.',
	),
	array(
		'title'       => 'LKS Talks 2025 syksyllä',
		'date'        => '2025-09-11',
		'time'        => '',
		'place'       => '',
		'city'        => '',
		'excerpt'     => 'Syyskuun LKS Talks järjestettiin yhteistyössä Seinäjoen Seudun Nuorkauppakamarin kanssa.',
		'paragraphs'  => array( 'Syyskuun LKS Talks -ilta järjestettiin yhteistyössä Seinäjoen Seudun Nuorkauppakamarin kanssa.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image20.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran ja Nuorkauppakamarin osallistujia LKS Talks -illassa.',
	),
	array(
		'title'       => 'Meidän ilta Kurikan Lakkitehtaalla',
		'date'        => '2025-10-24',
		'time'        => '',
		'place'       => 'Kurikan Lakkitehdas',
		'city'        => 'Kurikka',
		'excerpt'     => 'Meidän illassa tutustuttiin Kurikan Lakkitehtaan toimintaan.',
		'paragraphs'  => array( 'Lokakuun Meidän illassa Lakeuden Kauppaseura tutustui Kurikan Lakkitehtaan toimintaan.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image22.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran Meidän ilta Kurikan Lakkitehtaalla.',
	),
	array(
		'title'       => 'Syyskokous 2025',
		'date'        => '2025-11-11',
		'time'        => '',
		'place'       => 'Sorsanpesä Winston',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Syyskokouksessa esiteltiin vuoden 2026 toimintasuunnitelma ja budjetti.',
		'paragraphs'  => array( 'Lakeuden Kauppaseuran syyskokous pidettiin Sorsanpesä Winstonissa Seinäjoella. Kokouksessa esiteltiin vuoden 2026 toimintasuunnitelma ja budjetti.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image24.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran syyskokous Sorsanpesä Winstonissa.',
	),
	array(
		'title'       => 'Jouluinen ilta 2025',
		'date'        => '2025-12-09',
		'time'        => '',
		'place'       => 'Lehmussaarenkatu 2',
		'city'        => 'Seinäjoki',
		'excerpt'     => 'Toimintavuosi päättyi jäsenten yhteiseen jouluiseen iltaan.',
		'paragraphs'  => array( 'Vuoden 2025 tapahtumakausi päättyi jäsenten yhteiseen jouluiseen iltaan Seinäjoella.' ),
		'source_year' => '2025',
		'image'       => 'word/media/image26.jpeg',
		'image_alt'   => 'Lakeuden Kauppaseuran jäseniä jouluisessa illassa vuonna 2025.',
	),
);

$today   = wp_date( 'Y-m-d' );
$created = 0;
$updated = 0;
$skipped = 0;
$failed  = 0;

foreach ( $events as $event ) {
	$document = $documents[ $event['source_year'] ] ?? '';

	if ( $event['date'] >= $today ) {
		echo 'SKIP future/not-past: ' . $event['title'] . PHP_EOL;
		++$skipped;
		continue;
	}

	$image = lks_docx_event_image( $document, $event['image'] );
	if ( is_wp_error( $image ) ) {
		echo 'SKIP no valid picture: ' . $event['title'] . ' - ' . $image->get_error_message() . PHP_EOL;
		++$skipped;
		continue;
	}

	$existing = lks_docx_existing_event( $event );
	if ( $existing ) {
		$expected_post_date = $event['date'] . ' ' . ( $event['time'] ?: '12:00' ) . ':00';
		$needs_sync = ! empty( $event['sync_existing'] )
			&& (
				$expected_post_date !== $existing->post_date
				|| $event['date'] !== get_post_meta( $existing->ID, '_lks_event_date', true )
				|| $event['time'] !== get_post_meta( $existing->ID, '_lks_event_time', true )
				|| $event['place'] !== get_post_meta( $existing->ID, '_lks_event_place', true )
				|| $event['city'] !== get_post_meta( $existing->ID, '_lks_event_city', true )
			);

		if ( $needs_sync ) {
			if ( $dry_run ) {
				echo 'WOULD SYNC existing details: ' . $existing->post_title . ' (#' . $existing->ID . ')' . PHP_EOL;
				++$updated;
			} else {
				wp_update_post(
					array(
						'ID'        => $existing->ID,
						'post_date' => $expected_post_date,
					)
				);
				update_post_meta( $existing->ID, '_lks_event_date', $event['date'] );
				update_post_meta( $existing->ID, '_lks_event_time', $event['time'] );
				update_post_meta( $existing->ID, '_lks_event_place', $event['place'] );
				update_post_meta( $existing->ID, '_lks_event_city', $event['city'] );
				update_post_meta( $existing->ID, '_lks_event_source_document', basename( $document ) );
				update_post_meta( $existing->ID, '_lks_event_source_image', $event['image'] );
				echo 'SYNCED existing details: ' . $existing->post_title . ' (#' . $existing->ID . ')' . PHP_EOL;
				++$updated;
			}
		}

		if ( has_post_thumbnail( $existing->ID ) ) {
			echo 'SKIP existing with picture: ' . $existing->post_title . ' (#' . $existing->ID . ')' . PHP_EOL;
			++$skipped;
			continue;
		}

		if ( $dry_run ) {
			echo 'WOULD ADD picture to existing: ' . $existing->post_title . ' (#' . $existing->ID . ')' . PHP_EOL;
			++$updated;
			continue;
		}

		$attachment_id = lks_docx_attach_event_image( $existing->ID, $event, $image );
		if ( is_wp_error( $attachment_id ) ) {
			echo 'FAIL picture for existing event: ' . $existing->post_title . ' - ' . $attachment_id->get_error_message() . PHP_EOL;
			++$failed;
			continue;
		}

		echo 'ADDED picture to existing: ' . $existing->post_title . ' (image #' . $attachment_id . ')' . PHP_EOL;
		++$updated;
		continue;
	}

	if ( $dry_run ) {
		echo 'WOULD CREATE with picture: ' . $event['date'] . ' - ' . $event['title'] . PHP_EOL;
		++$created;
		continue;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'lks_event',
			'post_status'  => 'publish',
			'post_title'   => $event['title'],
			'post_name'    => sanitize_title( $event['title'] ),
			'post_excerpt' => $event['excerpt'],
			'post_content' => lks_docx_event_content( $event ),
			'post_date'    => $event['date'] . ' ' . ( $event['time'] ?: '12:00' ) . ':00',
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		echo 'FAIL event: ' . $event['title'] . ' - ' . $post_id->get_error_message() . PHP_EOL;
		++$failed;
		continue;
	}

	update_post_meta( $post_id, '_lks_event_date', $event['date'] );
	update_post_meta( $post_id, '_lks_event_time', $event['time'] );
	update_post_meta( $post_id, '_lks_event_place', $event['place'] );
	update_post_meta( $post_id, '_lks_event_city', $event['city'] );
	update_post_meta( $post_id, '_lks_event_source_document', basename( $document ) );
	update_post_meta( $post_id, '_lks_event_source_image', $event['image'] );

	$attachment_id = lks_docx_attach_event_image( $post_id, $event, $image );
	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_post( $post_id, true );
		echo 'FAIL event picture; rolled back event: ' . $event['title'] . ' - ' . $attachment_id->get_error_message() . PHP_EOL;
		++$failed;
		continue;
	}

	echo 'CREATED: ' . $event['date'] . ' - ' . $event['title'] . ' (#' . $post_id . ', image #' . $attachment_id . ')' . PHP_EOL;
	++$created;
}

echo sprintf(
	'Summary: created=%d, pictures_added=%d, skipped=%d, failed=%d%s',
	$created,
	$updated,
	$skipped,
	$failed,
	$dry_run ? ' (dry run)' : ''
) . PHP_EOL;

exit( $failed > 0 ? 1 : 0 );
