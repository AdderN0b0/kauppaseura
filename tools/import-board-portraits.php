<?php
/**
 * Import the reviewed board portraits into the local WordPress media library.
 *
 * Usage:
 * php tools/import-board-portraits.php --confirm-publication-permission
 *
 * The confirmation flag records that an association representative has
 * verified permission to publish every mapped portrait. Public availability
 * by itself is not a reuse licence.
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

if (!class_exists('Lakeuden_Kauppaseura_People')) {
	fwrite(STDERR, "The Lakeuden Kauppaseura theme people component is not active.\n");
	exit(1);
}

const LKS_BOARD_PORTRAIT_KEY_META = '_lks_board_portrait_key';
const LKS_BOARD_PORTRAIT_SOURCE_PAGE_META = '_lks_board_portrait_source_page';
const LKS_BOARD_PORTRAIT_SOURCE_URL_META = '_lks_board_portrait_source_url';
const LKS_BOARD_PORTRAIT_PERMISSION_META = '_lks_board_portrait_permission_note';

$portrait_permission = 'Association representative confirmed publication for the board section on 2026-07-24.';
$portraits = array(
	array(
		'name'        => 'Maarit Siik',
		'key'         => 'board-maarit-siik',
		'filename'    => 'maarit-siik.jpg',
		'source_page' => 'https://lakeudenkauppaseur3.wixsite.com/lakeuden-kauppaseura/blank-1',
		'source_url'  => 'https://static.wixstatic.com/media/3c1d1a_6fc19dd3d45c400fb4f5e00731d2717d~mv2.jpg',
	),
	array(
		'name'        => 'Paula Takamaa',
		'key'         => 'board-paula-takamaa',
		'filename'    => 'paula-takamaa.png',
		'source_page' => 'https://lakeudenkauppaseur3.wixsite.com/lakeuden-kauppaseura/blank-1',
		'source_url'  => 'https://static.wixstatic.com/media/3c1d1a_5d2fb4fcd3244ee194b8cd07b4f71fee~mv2.png',
	),
	array(
		'name'        => 'Jari Puolijoki',
		'key'         => 'board-jari-puolijoki',
		'filename'    => 'jari-puolijoki.jpg',
		'source_page' => 'https://lakeudenkauppaseur3.wixsite.com/lakeuden-kauppaseura/blank-1',
		'source_url'  => 'https://static.wixstatic.com/media/3c1d1a_7e3a22a2abd6476d8031702eab2a07a5~mv2.jpg',
	),
	array(
		'name'        => 'Minna Petäjävirta',
		'key'         => 'board-minna-petajavirta',
		'filename'    => 'minna-petajavirta.jpg',
		'source_page' => 'https://www.levyvirta.fi/yhteystiedot/',
		'source_url'  => 'https://www.levyvirta.fi/images/kuvapankki/medium/1621_minna_petajavirta_0008_234.jpg',
	),
	array(
		'name'        => 'Sanna Piipari',
		'key'         => 'board-sanna-piipari',
		'filename'    => 'sanna-piipari.jpg',
		'source_page' => 'https://x.com/SPiipari',
		'source_url'  => 'https://pbs.twimg.com/profile_images/1126506520844193792/tXACst7l_400x400.jpg',
	),
	array(
		'name'               => 'Sampo Siik',
		'key'                => 'board-sampo-siik',
		'filename'           => 'sampo-siik.jpg',
		'source_page'        => 'https://lakeudenkauppaseur3.wixsite.com/lakeuden-kauppaseura/blank-1',
		'source_url'         => 'https://static.wixstatic.com/media/3c1d1a_3da420773663455f8a1eddad720f12b8~mv2.jpg',
	),
	array(
		'name'                 => 'Heikki Kangas',
		'key'                  => 'board-heikki-kangas',
		'existing_asset_key'   => 'wix-author-heikki-kangas',
		'source_page'          => home_url('/blogi/'),
		'source_url'           => '',
	),
	array(
		'name'        => 'Elisa Lahdenmaa',
		'key'         => 'board-elisa-lahdenmaa',
		'filename'    => 'elisa-lahdenmaa.jpg',
		'source_page' => 'https://www.linkedin.com/in/elisa-lahdenmaa-ab1a57b9/',
		'source_url'  => 'https://d2gjqh9j26unp0.cloudfront.net/profilepic/b8f42b5726859acf2c3a044dbd2a2749',
	),
);

/**
 * Find one exact published board record.
 */
function lks_board_portrait_member(string $name): WP_Post
{
	$members = get_posts(
		array(
			'post_type'      => Lakeuden_Kauppaseura_People::BOARD_POST_TYPE,
			'post_status'    => array('publish', 'draft', 'private'),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ($members as $member) {
		if ($name === $member->post_title) {
			return $member;
		}
	}

	throw new RuntimeException("Board member not found: {$name}");
}

/**
 * Find one attachment by an exact private meta value.
 */
function lks_board_portrait_attachment_by_meta(string $meta_key, string $meta_value): int
{
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
 * Download one reviewed image through WordPress and create an attachment.
 *
 * @param array<string,string> $portrait Portrait mapping.
 */
function lks_board_portrait_import(array $portrait, int $member_id): int
{
	$temporary_file = download_url($portrait['source_url'], 60);
	if (is_wp_error($temporary_file)) {
		throw new RuntimeException(
			"Could not download {$portrait['name']}: " . $temporary_file->get_error_message()
		);
	}

	$file = array(
		'name'     => $portrait['filename'],
		'tmp_name' => $temporary_file,
	);
	$attachment_id = media_handle_sideload($file, $member_id, $portrait['name']);
	if (is_wp_error($attachment_id)) {
		@unlink($temporary_file);
		throw new RuntimeException(
			"Could not import {$portrait['name']}: " . $attachment_id->get_error_message()
		);
	}

	return (int) $attachment_id;
}

try {
	foreach ($portraits as $portrait) {
		$member = lks_board_portrait_member($portrait['name']);
		$attachment_id = lks_board_portrait_attachment_by_meta(
			LKS_BOARD_PORTRAIT_KEY_META,
			$portrait['key']
		);

		if (!$attachment_id && !empty($portrait['existing_asset_key'])) {
			$attachment_id = lks_board_portrait_attachment_by_meta(
				'_lks_asset_key',
				$portrait['existing_asset_key']
			);
		}

		$current_portrait = get_post_thumbnail_id($member);
		if (!$attachment_id && $current_portrait) {
			echo "Kept the manually selected portrait for {$portrait['name']}.\n";
			continue;
		}

		if (!$attachment_id) {
			$attachment_id = lks_board_portrait_import($portrait, $member->ID);
			echo "Imported {$portrait['name']} as attachment {$attachment_id}.\n";
		} else {
			echo "Reused attachment {$attachment_id} for {$portrait['name']}.\n";
		}

		update_post_meta($attachment_id, LKS_BOARD_PORTRAIT_KEY_META, $portrait['key']);
		update_post_meta($attachment_id, LKS_BOARD_PORTRAIT_SOURCE_PAGE_META, esc_url_raw($portrait['source_page']));
		update_post_meta($attachment_id, LKS_BOARD_PORTRAIT_SOURCE_URL_META, esc_url_raw($portrait['source_url']));
		update_post_meta($attachment_id, LKS_BOARD_PORTRAIT_PERMISSION_META, $portrait_permission);
		update_post_meta($attachment_id, '_wp_attachment_image_alt', $portrait['name']);
		set_post_thumbnail($member, $attachment_id);

		if ('[LYHYT ESITTELY LISÄTÄÄN]' === trim($member->post_content)) {
			$result = wp_update_post(
				array(
					'ID'           => $member->ID,
					'post_content' => '',
				),
				true
			);
			if (is_wp_error($result)) {
				throw new RuntimeException(
					"Could not clear the seeded introduction for {$portrait['name']}: "
					. $result->get_error_message()
				);
			}
		}
	}

	$page_copy = get_option('lakeuden_kauppaseura_page_copy', array());
	$page_copy = is_array($page_copy) ? $page_copy : array();
	$page_copy['about_board_enabled'] = '1';
	update_option('lakeuden_kauppaseura_page_copy', $page_copy, false);

	echo "The eight board portraits are assigned and the production board section is enabled.\n";
} catch (Throwable $error) {
	fwrite(STDERR, "Board portrait import failed: {$error->getMessage()}\n");
	exit(1);
}
