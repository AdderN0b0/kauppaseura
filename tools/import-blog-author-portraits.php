<?php
/**
 * Import reviewed portraits for the existing blog-author taxonomy terms.
 *
 * Usage:
 * php tools/import-blog-author-portraits.php --confirm-publication-permission --liisa-file="C:\path\to\liisa-ojala.png"
 *
 * The tool is idempotent. It reuses attachments imported by the board and
 * earlier Wix remediation tools, and never overwrites a later image selected
 * manually in WordPress. Public availability by itself is not a reuse licence.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "Run this tool from the command line.\n");
	exit(1);
}

if (!in_array('--confirm-publication-permission', $argv, true)) {
	fwrite(
		STDERR,
		"Nothing changed. Confirm that the association may publish every portrait, then rerun with --confirm-publication-permission.\n"
	);
	exit(1);
}

$liisa_file = '';
foreach ($argv as $argument) {
	if (str_starts_with($argument, '--liisa-file=')) {
		$liisa_file = trim(substr($argument, strlen('--liisa-file=')), "\"'");
	}
}

const LKS_BLOG_AUTHOR_PORTRAIT_KEY_META = '_lks_blog_author_portrait_key';
const LKS_BLOG_AUTHOR_PORTRAIT_SOURCE_PAGE_META = '_lks_blog_author_portrait_source_page';
const LKS_BLOG_AUTHOR_PORTRAIT_SOURCE_URL_META = '_lks_blog_author_portrait_source_url';
const LKS_BLOG_AUTHOR_PORTRAIT_CREDIT_META = '_lks_blog_author_portrait_credit';
const LKS_BLOG_AUTHOR_PORTRAIT_PERMISSION_META = '_lks_blog_author_portrait_permission_note';

$portraits = array(
	array(
		'name'        => 'Anssi Murtonen',
		'aliases'     => array('Anssi Murto'),
		'slug'        => 'anssi-murtonen',
		'key'         => 'blog-author-anssi-murtonen',
		'filename'    => 'anssi-murtonen.jpg',
		'source_page' => 'https://www.pohjanmaankokoomus.fi/2024/09/28/anssi-murtonen-pohjanmaan-kokoomuksen-toiminnanjohtajaksi/',
		'source_url'  => 'https://www.pohjanmaankokoomus.fi/wp-content/uploads/sites/10/2024/09/WhatsApp-Kuva-2024-09-28-klo-16.14.38_d68c0380.jpg',
		'credit'      => 'Pohjanmaan Kokoomus',
	),
	array(
		'name'                => 'Heikki Kangas',
		'aliases'             => array(),
		'slug'                => 'heikki-kangas',
		'key'                 => 'blog-author-heikki-kangas',
		'existing_meta_key'   => '_lks_asset_key',
		'existing_meta_value' => 'wix-author-heikki-kangas',
		'source_page'         => home_url('/blogi/'),
		'source_url'          => '',
		'credit'              => 'Lakeuden Kauppaseura',
	),
	array(
		'name'        => 'Liisa Ojala',
		'aliases'     => array(),
		'slug'        => 'liisa-ojala',
		'key'         => 'blog-author-liisa-ojala',
		'filename'    => 'liisa-ojala.png',
		'local_file'  => $liisa_file,
		'source_page' => '',
		'source_url'  => '',
		'credit'      => 'Käyttäjän toimittama kuva',
	),
	array(
		'name'                => 'Maarit Siik',
		'aliases'             => array(),
		'slug'                => 'maarit-siik',
		'key'                 => 'blog-author-maarit-siik',
		'filename'            => 'maarit-siik.jpg',
		'existing_meta_key'   => '_lks_board_portrait_key',
		'existing_meta_value' => 'board-maarit-siik',
		'source_page'         => 'https://lakeudenkauppaseur3.wixsite.com/lakeuden-kauppaseura/blank-1',
		'source_url'          => 'https://static.wixstatic.com/media/3c1d1a_6fc19dd3d45c400fb4f5e00731d2717d~mv2.jpg',
		'credit'              => 'Lakeuden Kauppaseuran aiempi verkkosivusto',
	),
	array(
		'name'        => 'Martti Kaunismäki',
		'aliases'     => array(),
		'slug'        => 'martti-kaunismaki',
		'key'         => 'blog-author-martti-kaunismaki',
		'filename'    => 'martti-kaunismaki.jpg',
		'source_page' => 'https://www.aluetaito.fi/yhteys/',
		'source_url'  => 'https://www.aluetaito.fi/wp-content/uploads/2014/10/martti_kaunismaki_aluetaito_oy_big.jpg',
		'credit'      => 'Aluetaito Oy',
	),
	array(
		'name'                => 'Paula Takamaa',
		'aliases'             => array(),
		'slug'                => 'paula-takamaa',
		'key'                 => 'blog-author-paula-takamaa',
		'filename'            => 'paula-takamaa.png',
		'existing_meta_key'   => '_lks_board_portrait_key',
		'existing_meta_value' => 'board-paula-takamaa',
		'source_page'         => 'https://lakeudenkauppaseur3.wixsite.com/lakeuden-kauppaseura/blank-1',
		'source_url'          => 'https://static.wixstatic.com/media/3c1d1a_5d2fb4fcd3244ee194b8cd07b4f71fee~mv2.png',
		'credit'              => 'Lakeuden Kauppaseuran aiempi verkkosivusto',
	),
);

/**
 * Find an existing author and apply an exact reviewed name correction.
 *
 * @param array<string,mixed> $portrait Portrait mapping.
 */
function lks_blog_author_portrait_term(array $portrait): WP_Term
{
	$term = get_term_by('name', $portrait['name'], 'lks_author');

	if (!$term) {
		foreach ($portrait['aliases'] as $alias) {
			$term = get_term_by('name', $alias, 'lks_author');
			if ($term) {
				$result = wp_update_term(
					$term->term_id,
					'lks_author',
					array(
						'name' => $portrait['name'],
						'slug' => $portrait['slug'],
					)
				);
				if (is_wp_error($result)) {
					throw new RuntimeException(
						"Could not rename {$alias}: " . $result->get_error_message()
					);
				}
				$term = get_term((int) $result['term_id'], 'lks_author');
				echo "Korjattiin kirjoittajan nimi: {$alias} → {$portrait['name']}.\n";
				break;
			}
		}
	}

	if (!$term instanceof WP_Term) {
		throw new RuntimeException("Blog author not found: {$portrait['name']}");
	}

	if ($term->slug !== $portrait['slug']) {
		$result = wp_update_term(
			$term->term_id,
			'lks_author',
			array('slug' => $portrait['slug'])
		);
		if (is_wp_error($result)) {
			throw new RuntimeException(
				"Could not update {$portrait['name']} slug: " . $result->get_error_message()
			);
		}
		$term = get_term((int) $result['term_id'], 'lks_author');
	}

	return $term;
}

/**
 * Find one attachment by an exact private meta value.
 */
function lks_blog_author_portrait_attachment_by_meta(string $meta_key, string $meta_value): int
{
	if ('' === $meta_key || '' === $meta_value) {
		return 0;
	}

	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => $meta_key,
			'meta_value'     => $meta_value,
			'no_found_rows'  => true,
		)
	);

	return $attachments ? (int) $attachments[0] : 0;
}

/**
 * Import one reviewed remote or local image through WordPress.
 *
 * @param array<string,mixed> $portrait Portrait mapping.
 */
function lks_blog_author_portrait_import(array $portrait): int
{
	$temporary_file = '';

	if (!empty($portrait['local_file'])) {
		$source_file = realpath((string) $portrait['local_file']);
		if (!$source_file || !is_file($source_file)) {
			throw new RuntimeException("Local portrait not found for {$portrait['name']}.");
		}

		$temporary_file = wp_tempnam((string) $portrait['filename']);
		if (!$temporary_file || !copy($source_file, $temporary_file)) {
			throw new RuntimeException("Could not prepare the local portrait for {$portrait['name']}.");
		}
	} elseif (!empty($portrait['source_url'])) {
		$temporary_file = download_url((string) $portrait['source_url'], 60);
		if (is_wp_error($temporary_file)) {
			throw new RuntimeException(
				"Could not download {$portrait['name']}: " . $temporary_file->get_error_message()
			);
		}
	} else {
		throw new RuntimeException(
			"No reusable or importable portrait is configured for {$portrait['name']}."
		);
	}

	$file = array(
		'name'     => $portrait['filename'],
		'tmp_name' => $temporary_file,
	);
	$attachment_id = media_handle_sideload($file, 0, $portrait['name']);
	if (is_wp_error($attachment_id)) {
		@unlink($temporary_file);
		throw new RuntimeException(
			"Could not import {$portrait['name']}: " . $attachment_id->get_error_message()
		);
	}

	return (int) $attachment_id;
}

try {
	if (!taxonomy_exists('lks_author')) {
		throw new RuntimeException('The lks-blog plugin and lks_author taxonomy must be active.');
	}

	foreach ($portraits as $portrait) {
		$term = lks_blog_author_portrait_term($portrait);
		$attachment_id = lks_blog_author_portrait_attachment_by_meta(
			LKS_BLOG_AUTHOR_PORTRAIT_KEY_META,
			$portrait['key']
		);

		if (
			!$attachment_id
			&& !empty($portrait['existing_meta_key'])
			&& !empty($portrait['existing_meta_value'])
		) {
			$attachment_id = lks_blog_author_portrait_attachment_by_meta(
				(string) $portrait['existing_meta_key'],
				(string) $portrait['existing_meta_value']
			);
		}

		$current_portrait = absint(get_term_meta($term->term_id, 'lks_author_photo_id', true));
		if (!$attachment_id && $current_portrait) {
			$attachment_id = $current_portrait;
			echo "Säilytettiin WordPressissa valittu kuva kirjoittajalle {$portrait['name']}.\n";
		} elseif (!$attachment_id) {
			$attachment_id = lks_blog_author_portrait_import($portrait);
			echo "Tuotiin {$portrait['name']} liitteeksi {$attachment_id}.\n";
		} else {
			echo "Käytettiin olemassa olevaa liitettä {$attachment_id} kirjoittajalle {$portrait['name']}.\n";
		}

		if (!wp_attachment_is_image($attachment_id) || !is_file((string) get_attached_file($attachment_id))) {
			throw new RuntimeException("The portrait attachment is invalid for {$portrait['name']}.");
		}

		update_post_meta($attachment_id, LKS_BLOG_AUTHOR_PORTRAIT_KEY_META, $portrait['key']);
		update_post_meta(
			$attachment_id,
			LKS_BLOG_AUTHOR_PORTRAIT_SOURCE_PAGE_META,
			esc_url_raw((string) $portrait['source_page'])
		);
		update_post_meta(
			$attachment_id,
			LKS_BLOG_AUTHOR_PORTRAIT_SOURCE_URL_META,
			esc_url_raw((string) $portrait['source_url'])
		);
		update_post_meta(
			$attachment_id,
			LKS_BLOG_AUTHOR_PORTRAIT_CREDIT_META,
			sanitize_text_field((string) $portrait['credit'])
		);
		update_post_meta(
			$attachment_id,
			LKS_BLOG_AUTHOR_PORTRAIT_PERMISSION_META,
			'Association representative requested this reviewed author portrait on 2026-07-24. Retain evidence of the right to publish.'
		);
		update_post_meta($attachment_id, '_wp_attachment_image_alt', $portrait['name']);

		$updated = wp_update_post(
			array(
				'ID'         => $attachment_id,
				'post_title' => $portrait['name'],
				'post_name'  => sanitize_title($portrait['name']),
			),
			true
		);
		if (is_wp_error($updated)) {
			throw new RuntimeException(
				"Could not update the media item for {$portrait['name']}: "
				. $updated->get_error_message()
			);
		}

		update_term_meta($term->term_id, 'lks_author_photo_id', $attachment_id);
	}

	echo "Kaikilla kuudella blogikirjoittajalla on kuva ja saavutettava vaihtoehtoinen teksti.\n";
} catch (Throwable $error) {
	fwrite(STDERR, "Blog author portrait import failed: {$error->getMessage()}\n");
	exit(1);
}
