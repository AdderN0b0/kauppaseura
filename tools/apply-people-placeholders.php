<?php
/**
 * Restore editable board-member records and seed testimonial placeholders.
 *
 * The board names and responsibilities come from the theme's former
 * about_board_members source default. The script is idempotent and never
 * overwrites a restored record after it has been created, so administrator
 * edits are safe when the script is run again.
 *
 * Usage:
 * php tools/apply-people-placeholders.php
 */

declare(strict_types=1);

$workspace = dirname( __DIR__ );
$wp_load   = $workspace . '/wp-load.php';

if ( ! is_file( $wp_load ) ) {
	fwrite( STDERR, "WordPress was not found at the repository root.\n" );
	exit( 1 );
}

require_once $wp_load;

if (
	! post_type_exists( Lakeuden_Kauppaseura_People::BOARD_POST_TYPE )
	|| ! post_type_exists( Lakeuden_Kauppaseura_People::TESTIMONIAL_POST_TYPE )
) {
	fwrite( STDERR, "The Lakeuden Kauppaseura people content types are unavailable. Activate the custom theme first.\n" );
	exit( 1 );
}

/**
 * Find a previously seeded record.
 *
 * @param string $post_type Post type.
 * @param string $seed_key  Stable seed key.
 * @return WP_Post|null
 */
function lks_people_find_seed( string $post_type, string $seed_key ): ?WP_Post {
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'trash' ),
			'posts_per_page' => 1,
			'meta_key'       => Lakeuden_Kauppaseura_People::META_SEED_KEY,
			'meta_value'     => $seed_key,
			'no_found_rows'  => true,
		)
	);

	return $posts ? $posts[0] : null;
}

/**
 * Create one seed without replacing later administrator edits.
 *
 * @param array<string,mixed> $seed Seed definition.
 * @return int|WP_Error
 */
function lks_people_create_seed( array $seed ) {
	$existing = lks_people_find_seed( $seed['post_type'], $seed['seed_key'] );
	if ( $existing ) {
		echo "Seeded record {$seed['seed_key']} already exists as post {$existing->ID}; left unchanged.\n";
		return $existing->ID;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => $seed['post_type'],
			'post_status'  => 'publish',
			'post_title'   => $seed['title'] ?? '[NIMI LISÄTÄÄN]',
			'post_content' => $seed['content'],
			'menu_order'   => $seed['order'],
			'meta_input'   => array_merge(
				$seed['meta'],
				array(
					Lakeuden_Kauppaseura_People::META_SEED_KEY => $seed['seed_key'],
				)
			),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		fwrite( STDERR, "Could not create {$seed['seed_key']}: {$post_id->get_error_message()}\n" );
		return $post_id;
	}

	echo "Created seeded record {$seed['seed_key']} as post {$post_id}.\n";
	return $post_id;
}

/**
 * Find an existing board record by its exact public name.
 *
 * @param string $name Approved board-member name.
 * @return WP_Post|null
 */
function lks_people_find_board_member_by_name( string $name ): ?WP_Post {
	$posts = get_posts(
		array(
			'post_type'      => Lakeuden_Kauppaseura_People::BOARD_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ( $posts as $post ) {
		if ( $name === trim( $post->post_title ) ) {
			return $post;
		}
	}

	return null;
}

/**
 * Restore one former board-list entry without replacing administrator edits.
 *
 * The first three unedited development slots are reused. If a slot has
 * already been edited, a separate restored record is created instead.
 *
 * @param array<string,mixed> $person       Board-member definition.
 * @param int                 $legacy_index Previous placeholder slot number.
 * @return int|WP_Error
 */
function lks_people_restore_board_member( array $person, int $legacy_index ) {
	$seed_key = 'board-restored-' . sanitize_title( $person['name'] );
	$existing = lks_people_find_seed( Lakeuden_Kauppaseura_People::BOARD_POST_TYPE, $seed_key );
	if ( $existing ) {
		echo "Restored board member {$person['name']} already exists as post {$existing->ID}; left unchanged.\n";
		return $existing->ID;
	}

	$existing = lks_people_find_board_member_by_name( $person['name'] );
	if ( $existing ) {
		echo "Board member {$person['name']} already exists as post {$existing->ID}; left unchanged.\n";
		return $existing->ID;
	}

	$legacy = lks_people_find_seed( Lakeuden_Kauppaseura_People::BOARD_POST_TYPE, 'board-' . $legacy_index );
	$legacy_is_unedited = $legacy
		&& '[NIMI LISÄTÄÄN]' === trim( $legacy->post_title )
		&& '[HALLITUSROOLI LISÄTÄÄN]' === trim( (string) get_post_meta( $legacy->ID, Lakeuden_Kauppaseura_People::META_BOARD_ROLE, true ) );

	if ( $legacy_is_unedited ) {
		$result = wp_update_post(
			array(
				'ID'           => $legacy->ID,
				'post_title'   => $person['name'],
				'post_content' => '[LYHYT ESITTELY LISÄTÄÄN]',
				'menu_order'   => $person['order'],
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			fwrite( STDERR, "Could not restore {$person['name']}: {$result->get_error_message()}\n" );
			return $result;
		}

		update_post_meta( $legacy->ID, Lakeuden_Kauppaseura_People::META_BOARD_ROLE, $person['role'] );
		$organization = get_post_meta( $legacy->ID, Lakeuden_Kauppaseura_People::META_ORGANIZATION, true );
		if ( lakeuden_kauppaseura_people_value_is_placeholder( $organization ) ) {
			delete_post_meta( $legacy->ID, Lakeuden_Kauppaseura_People::META_ORGANIZATION );
		}
		update_post_meta( $legacy->ID, Lakeuden_Kauppaseura_People::META_SEED_KEY, $seed_key );

		echo "Restored {$person['name']} into development slot {$legacy_index} as post {$legacy->ID}.\n";
		return $legacy->ID;
	}

	return lks_people_create_seed(
		array(
			'post_type' => Lakeuden_Kauppaseura_People::BOARD_POST_TYPE,
			'seed_key'  => $seed_key,
			'title'     => $person['name'],
			'content'   => '[LYHYT ESITTELY LISÄTÄÄN]',
			'order'     => $person['order'],
			'meta'      => array(
				Lakeuden_Kauppaseura_People::META_BOARD_ROLE => $person['role'],
			),
		)
	);
}

$board_members = array(
	array( 'name' => 'Maarit Siik', 'role' => 'Puheenjohtaja', 'order' => 10 ),
	array( 'name' => 'Paula Takamaa', 'role' => 'Varapuheenjohtaja', 'order' => 20 ),
	array( 'name' => 'Jari Puolijoki', 'role' => 'Vaikuttaminen ja kehitys', 'order' => 30 ),
	array( 'name' => 'Minna Petäjävirta', 'role' => 'Viestintä ja tapahtumat', 'order' => 40 ),
	array( 'name' => 'Sanna Piipari', 'role' => 'Rahastonhoitaja', 'order' => 50 ),
	array( 'name' => 'Sampo Siik', 'role' => 'Vaikuttajatiimi ja sihteeri', 'order' => 60 ),
	array( 'name' => 'Heikki Kangas', 'role' => 'Vaikuttaminen ja yhteistyöverkostot', 'order' => 70 ),
	array( 'name' => 'Elisa Lahdenmaa', 'role' => 'Viestintä, some ja tapahtumat', 'order' => 80 ),
);

$failed = false;
foreach ( $board_members as $index => $board_member ) {
	if ( is_wp_error( lks_people_restore_board_member( $board_member, $index + 1 ) ) ) {
		$failed = true;
	}
}

$testimonial_seeds = array();
for ( $index = 1; $index <= 3; $index++ ) {
	$testimonial_seeds[] = array(
		'post_type' => Lakeuden_Kauppaseura_People::TESTIMONIAL_POST_TYPE,
		'seed_key'  => 'testimonial-' . $index,
		'content'   => '[JÄSENEN KOMMENTTI LISÄTÄÄN ENNEN JULKAISUA]',
		'order'     => $index * 10,
		'meta'      => array(
			Lakeuden_Kauppaseura_People::META_ORGANIZATION      => 2 === $index ? '' : '[ORGANISAATIO LISÄTÄÄN]',
			Lakeuden_Kauppaseura_People::META_PROFESSIONAL_ROLE => 3 === $index ? '' : '[AMMATILLINEN ROOLI LISÄTÄÄN]',
		),
	);
}

foreach ( $testimonial_seeds as $seed ) {
	if ( is_wp_error( lks_people_create_seed( $seed ) ) ) {
		$failed = true;
	}
}

if ( $failed ) {
	exit( 1 );
}

echo "Eight restored board records and three testimonial placeholders are ready. Complete them in Hallitus and Jäsenkokemukset in WordPress administration.\n";
