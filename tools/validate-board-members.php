<?php
/**
 * Validate the local board records before export.
 *
 * Usage: php tools/validate-board-members.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/wp-load.php';

if (PHP_SAPI !== 'cli') {
	exit(1);
}

$errors = array();
$members = get_posts(
	array(
		'post_type'      => Lakeuden_Kauppaseura_People::BOARD_POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array('menu_order' => 'ASC', 'title' => 'ASC'),
		'order'          => 'ASC',
		'no_found_rows'  => true,
	)
);

if (8 !== count($members)) {
	$errors[] = 'Expected eight published board records; found ' . count($members) . '.';
}

foreach ($members as $member) {
	$name = trim($member->post_title);
	$role = trim((string) get_post_meta($member->ID, Lakeuden_Kauppaseura_People::META_BOARD_ROLE, true));
	$portrait_id = (int) get_post_thumbnail_id($member);
	$portrait = $portrait_id ? get_attached_file($portrait_id) : '';

	if (lakeuden_kauppaseura_person_has_placeholder($member)) {
		$errors[] = "{$name}: contains a placeholder or is missing the required name/role.";
	}
	if ('' === $name) {
		$errors[] = "Record {$member->ID}: name is missing.";
	}
	if ('' === $role) {
		$errors[] = "{$name}: board role is missing.";
	}
	if ($portrait_id && (!$portrait || !is_file($portrait))) {
		$errors[] = "{$name}: selected portrait file is missing.";
	}
	if ($portrait_id && $name !== trim((string) get_post_meta($portrait_id, '_wp_attachment_image_alt', true))) {
		$errors[] = "{$name}: portrait alt text must equal the person's public name.";
	}

	printf(
		"%d\t%s\t%s\t%s\t%s\n",
		$member->ID,
		$name,
		$role,
		$portrait_id ? "portrait {$portrait_id}" : 'initials fallback',
		'' === trim($member->post_content) ? 'no introduction' : 'introduction configured'
	);
}

$page_copy = get_option('lakeuden_kauppaseura_page_copy', array());
if ('1' !== (string) ($page_copy['about_board_enabled'] ?? '0')) {
	$errors[] = 'The production board section is disabled.';
}

if ($errors) {
	fwrite(STDERR, count($errors) . " board validation error(s):\n- " . implode("\n- ", $errors) . "\n");
	exit(1);
}

echo "Board validation passed: eight real records, no placeholders, valid roles and accessible portrait alternatives.\n";
