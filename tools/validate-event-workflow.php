<?php
/**
 * Validate the minimal event workflow.
 *
 * Loading WordPress may run the one-time registration opt-in migration. The
 * validator itself does not create events or change editorial content.
 *
 * Usage: php tools/validate-event-workflow.php
 */

declare(strict_types=1);

$workspace = dirname( __DIR__ );
$wp_load   = $workspace . '/wp-load.php';
$errors    = array();

if ( ! is_file( $wp_load ) ) {
	fwrite( STDERR, "WordPress was not found at the repository root.\n" );
	exit( 1 );
}

require_once $wp_load;

if ( ! class_exists( 'LKS_Events_Manager' ) ) {
	fwrite( STDERR, "LKS Events Manager is not active.\n" );
	exit( 1 );
}

$today  = '2026-07-23';
$future = '2026-09-18';
$url    = 'https://example.com/ilmoittautuminen';
$cases  = array(
	'standard event without registration' => array(
		'details' => array( 'date' => $future, 'registration_required' => '0', 'registration_url' => $url ),
		'key'     => 'registration_not_required',
		'label'   => '',
		'action'  => false,
	),
	'registration enabled with URL' => array(
		'details' => array( 'date' => $future, 'registration_required' => '1', 'registration_url' => $url ),
		'key'     => 'registration_open',
		'label'   => 'Ilmoittautuminen on avoinna',
		'action'  => true,
	),
	'registration enabled without URL' => array(
		'details' => array( 'date' => $future, 'registration_required' => '1' ),
		'key'     => 'registration_instructions_later',
		'label'   => 'Ilmoittautumisohjeet julkaistaan myöhemmin',
		'action'  => false,
	),
	'active deadline' => array(
		'details' => array( 'date' => $future, 'registration_required' => '1', 'registration_url' => $url, 'registration_deadline' => '2026-09-10' ),
		'key'     => 'registration_open',
		'label'   => 'Ilmoittautuminen on avoinna',
		'action'  => true,
	),
	'expired deadline' => array(
		'details' => array( 'date' => $future, 'registration_required' => '1', 'registration_url' => $url, 'registration_deadline' => '2026-07-22' ),
		'key'     => 'registration_closed',
		'label'   => 'Ilmoittautuminen on päättynyt',
		'action'  => false,
	),
	'past event' => array(
		'details' => array( 'date' => '2026-07-22', 'registration_required' => '1', 'registration_url' => $url ),
		'key'     => 'past',
		'label'   => 'Tapahtuma on päättynyt',
		'action'  => false,
	),
	'cancelled future event' => array(
		'details' => array( 'date' => $future, 'registration_required' => '1', 'registration_url' => $url, 'cancelled' => '1' ),
		'key'     => 'cancelled',
		'label'   => 'Tapahtuma on peruttu',
		'action'  => false,
	),
);

foreach ( $cases as $name => $case ) {
	$state = LKS_Events_Manager::derive_public_state( $case['details'], $today );
	event_expect( $case['key'] === ( $state['key'] ?? '' ), $errors, "{$name}: expected state {$case['key']}" );
	event_expect( $case['label'] === ( $state['label'] ?? '' ), $errors, "{$name}: expected Finnish visitor message" );
	event_expect( $case['action'] === ! empty( $state['registration_url'] ), $errors, "{$name}: registration action visibility is incorrect" );
}

$unsafe = LKS_Events_Manager::derive_public_state(
	array(
		'date'                  => $future,
		'registration_required' => '1',
		'registration_url'      => 'javascript:alert(1)',
	),
	$today
);
event_expect( 'registration_instructions_later' === ( $unsafe['key'] ?? '' ), $errors, 'unsafe registration scheme is rejected' );
event_expect( '1' === LKS_Events_Manager::registration_required_for_migration( $url ), $errors, 'existing event with a valid external URL is opted in during migration' );
event_expect( '0' === LKS_Events_Manager::registration_required_for_migration( '' ), $errors, 'existing event without an external URL remains opted out during migration' );

ob_start();
LKS_Events_Manager::render_meta_box( (object) array( 'ID' => 0 ) );
$default_meta_box = (string) ob_get_clean();
event_expect( str_contains( $default_meta_box, 'name="lks_event_registration_required"' ), $errors, 'event panel contains the registration-required checkbox' );
event_expect( str_contains( $default_meta_box, 'Tapahtuma vaatii ilmoittautumisen' ), $errors, 'registration checkbox has the requested Finnish label' );
event_expect( preg_match( '/id="lks-event-registration-fields"[^>]*\shidden(?:\s|>)/', $default_meta_box ) === 1, $errors, 'registration fields are hidden by default' );
event_expect( preg_match( '/id="lks-event-registration-url"[^>]*\sdisabled=/', $default_meta_box ) === 1, $errors, 'registration URL is disabled by default' );
event_expect( preg_match( '/id="lks-event-registration-deadline"[^>]*\sdisabled=/', $default_meta_box ) === 1, $errors, 'registration deadline is disabled by default' );
event_expect( preg_match( '/id="lks-event-registration-(?:url|deadline)"[^>]*\srequired(?:\s|=|>)/', $default_meta_box ) !== 1, $errors, 'registration controls are not required fields' );

$events = get_posts(
	array(
		'post_type'      => LKS_Events_Manager::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

if ( ! $events ) {
	$errors[] = 'at least one published event is required for schema fixture validation';
} else {
	$migration_before = event_migration_snapshot();
	LKS_Events_Manager::maybe_migrate_registration_required();
	LKS_Events_Manager::maybe_migrate_registration_required();
	$migration_after = event_migration_snapshot();
	event_expect( $migration_before === $migration_after, $errors, 'registration migration is idempotent and does not alter event prose' );
	event_expect(
		LKS_Events_Manager::MIGRATION_VERSION === get_option( LKS_Events_Manager::MIGRATION_OPTION ),
		$errors,
		'registration migration version is recorded'
	);
	foreach ( $migration_after as $event_state ) {
		event_expect(
			$event_state['registration_required_exists'],
			$errors,
			"migrated event {$event_state['id']} has an explicit registration opt-in value"
		);
		event_expect(
			in_array( $event_state['registration_required'], array( '', '1' ), true ),
			$errors,
			"migrated event {$event_state['id']} uses WordPress's canonical boolean representation"
		);
	}

	$event_id = (int) $events[0]->ID;
	$open     = LKS_Events_Manager::derive_public_state(
		array( 'date' => $future, 'registration_required' => '1', 'registration_url' => $url ),
		$today
	);
	$open_html = LKS_Events_Manager::render_registration_panel( $events[0], $open );
	event_expect( str_contains( $open_html, 'href="' . $url . '"' ), $errors, 'open registration renders the external URL unchanged' );
	event_expect( str_contains( $open_html, '>Ilmoittaudu tapahtumaan ' ), $errors, 'open registration renders the Finnish action label' );
	event_expect( str_contains( $open_html, 'target="_blank"' ) && str_contains( $open_html, 'rel="noopener noreferrer"' ), $errors, 'external registration link uses safe new-tab attributes' );
	event_expect( ! str_contains( $open_html, '<form' ), $errors, 'registration panel never renders a WordPress-dependent form' );

	$standard_html = LKS_Events_Manager::render_registration_panel(
		$events[0],
		LKS_Events_Manager::derive_public_state( array( 'date' => $future, 'registration_required' => '0', 'registration_url' => $url ), $today )
	);
	event_expect( '' === $standard_html, $errors, 'standard event renders no registration panel even when a URL is preserved' );

	$instructions_html = LKS_Events_Manager::render_registration_panel(
		$events[0],
		LKS_Events_Manager::derive_public_state( array( 'date' => $future, 'registration_required' => '1' ), $today )
	);
	event_expect( str_contains( $instructions_html, 'Ilmoittautumisohjeet julkaistaan myöhemmin' ), $errors, 'registration-enabled event without URL renders the instructions-later message' );
	event_expect( ! str_contains( $instructions_html, 'lks-event-registration__action' ), $errors, 'registration-enabled event without URL has no active action' );

	$cancelled_html = LKS_Events_Manager::render_registration_panel(
		$events[0],
		LKS_Events_Manager::derive_public_state( array( 'date' => $future, 'registration_required' => '1', 'registration_url' => $url, 'cancelled' => '1' ), $today )
	);
	event_expect( '' === $cancelled_html, $errors, 'cancelled event renders no registration panel or action' );

	$closed_html = LKS_Events_Manager::render_registration_panel(
		$events[0],
		LKS_Events_Manager::derive_public_state( array( 'date' => $future, 'registration_required' => '1', 'registration_url' => $url, 'registration_deadline' => '2026-07-22' ), $today )
	);
	event_expect( ! str_contains( $closed_html, 'lks-event-registration__action' ), $errors, 'closed event hides the registration action' );

	if ( ! function_exists( 'lakeuden_kauppaseura_event_schema' ) ) {
		$errors[] = 'theme event structured-data builder is unavailable';
	} else {
		$minimal = lakeuden_kauppaseura_event_schema(
			$event_id,
			array(
				'_lks_event_date'                  => $future,
				'_lks_event_time'                  => '',
				'_lks_event_start_time'            => '',
				'_lks_event_end_date'              => '',
				'_lks_event_end_time'              => '',
				'_lks_event_place'                 => '',
				'_lks_event_city'                  => '',
				'_lks_event_attendance_mode'       => '',
				'_lks_event_registration_required' => '0',
				'_lks_event_registration_url'      => '',
				'_lks_event_registration_deadline' => '',
				'_lks_event_cancelled'             => '',
				'_lks_event_price'                 => '',
			)
		);
		event_expect( $future === ( $minimal['startDate'] ?? '' ), $errors, 'event without time keeps only the confirmed date' );
		event_expect( ! isset( $minimal['location'] ), $errors, 'event without place omits location structured data' );
		event_expect( ! isset( $minimal['eventAttendanceMode'] ), $errors, 'event without place does not invent an attendance mode' );
		event_expect( ! isset( $minimal['offers'] ), $errors, 'event without registration URL omits offers' );
	}
}

if ( $errors ) {
	echo count( $errors ) . " event-workflow validation error(s):\n- " . implode( "\n- ", $errors ) . "\n";
	exit( 1 );
}

echo "Minimal event workflow validation passed: registration disabled by default, enabled with/without URL, active/expired deadline, past, cancelled, existing URL migration, idempotence, no forms, missing time and missing place.\n";

/**
 * Capture registration migration state plus a hash of historical prose.
 *
 * @return array<int,array{id:int,registration_url:string,registration_required:string,registration_required_exists:bool,content_hash:string}>
 */
function event_migration_snapshot(): array {
	$snapshot = array();
	$events   = get_posts(
		array(
			'post_type'      => LKS_Events_Manager::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	foreach ( $events as $event ) {
		$snapshot[] = array(
			'id'                    => (int) $event->ID,
			'registration_url'      => (string) get_post_meta( $event->ID, LKS_Events_Manager::META_REGISTRATION_URL, true ),
			'registration_required' => (string) get_post_meta( $event->ID, LKS_Events_Manager::META_REGISTRATION_REQUIRED, true ),
			'registration_required_exists' => metadata_exists( 'post', $event->ID, LKS_Events_Manager::META_REGISTRATION_REQUIRED ),
			'content_hash'          => hash( 'sha256', $event->post_title . "\n" . $event->post_content ),
		);
	}

	return $snapshot;
}

/**
 * Record one assertion.
 *
 * @param array<int,string> $errors Error collection.
 */
function event_expect( bool $condition, array &$errors, string $message ): void {
	if ( ! $condition ) {
		$errors[] = $message;
	}
}
