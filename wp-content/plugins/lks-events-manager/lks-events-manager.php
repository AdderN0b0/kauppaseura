<?php
/**
 * Plugin Name: LKS Events Manager
 * Description: Lightweight event management for Lakeuden Kauppaseura.
 * Version: 1.1.0
 * Author: OpenAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LKS_Events_Manager {
	const POST_TYPE = 'lks_event';
	const META_DATE = '_lks_event_date';
	const META_TIME = '_lks_event_time';
	const META_PLACE = '_lks_event_place';
	const META_CITY = '_lks_event_city';
	const META_PLACE_LABEL = '_lks_event_place_label';
	const META_AUDIENCE = '_lks_event_audience';
	const META_PRICE = '_lks_event_price';
	const META_REGISTRATION = '_lks_event_registration';
	const META_REGISTRATION_URL = '_lks_event_registration_url';
	const META_REGISTRATION_DEADLINE = '_lks_event_registration_deadline';
	const META_CANCELLED = '_lks_event_cancelled';
	const META_STATUS = '_lks_event_status';
	const META_CTA_LABEL = '_lks_event_cta_label';
	const META_CONTACT_SUBJECT = '_lks_event_contact_subject';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_event_meta' ) );
		add_shortcode( 'lks_events', array( __CLASS__, 'render_events_shortcode' ) );
		add_shortcode( 'lks_event_single', array( __CLASS__, 'render_event_single_shortcode' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'register_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_admin_columns' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( __CLASS__, 'register_sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'handle_admin_sorting' ) );
	}

	public static function activate() {
		self::register_post_type();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'                  => 'Tapahtumat',
					'singular_name'         => 'Tapahtuma',
					'menu_name'             => 'Tapahtumat',
					'name_admin_bar'        => 'Tapahtuma',
					'add_new'               => 'Lisää uusi',
					'add_new_item'          => 'Lisää uusi tapahtuma',
					'new_item'              => 'Uusi tapahtuma',
					'edit_item'             => 'Muokkaa tapahtumaa',
					'view_item'             => 'Näytä tapahtuma',
					'all_items'             => 'Kaikki tapahtumat',
					'search_items'          => 'Etsi tapahtumia',
					'not_found'             => 'Tapahtumia ei löytynyt.',
					'not_found_in_trash'    => 'Roskakorissa ei ole tapahtumia.',
					'item_published'        => 'Tapahtuma julkaistu.',
					'item_updated'          => 'Tapahtuma päivitetty.',
				),
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-calendar-alt',
				'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
				'has_archive'         => false,
				'rewrite'             => array( 'slug' => 'tapahtuma' ),
				'publicly_queryable'  => true,
				'exclude_from_search' => false,
				'menu_position'       => 21,
			)
		);
	}

	public static function register_meta() {
		$common = array(
			'single'         => true,
			'show_in_rest'   => true,
			'auth_callback'  => static function() {
				return current_user_can( 'edit_posts' );
			},
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_DATE,
			array_merge(
				$common,
				array(
					'type'              => 'string',
					'sanitize_callback' => array( __CLASS__, 'sanitize_event_date' ),
				)
			)
		);

		foreach (
			array(
				self::META_CITY,
				self::META_PLACE_LABEL,
				self::META_AUDIENCE,
				self::META_PRICE,
				self::META_REGISTRATION,
				self::META_STATUS,
				self::META_CTA_LABEL,
				self::META_CONTACT_SUBJECT,
			) as $meta_key
		) {
			register_post_meta(
				self::POST_TYPE,
				$meta_key,
				array_merge(
					$common,
					array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					)
				)
			);
		}

		register_post_meta(
			self::POST_TYPE,
			self::META_TIME,
			array_merge(
				$common,
				array(
					'type'              => 'string',
					'sanitize_callback' => array( __CLASS__, 'sanitize_event_time' ),
				)
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_PLACE,
			array_merge(
				$common,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				)
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_REGISTRATION_URL,
			array_merge(
				$common,
				array(
					'type'              => 'string',
					'sanitize_callback' => array( __CLASS__, 'sanitize_registration_url' ),
				)
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_REGISTRATION_DEADLINE,
			array_merge(
				$common,
				array(
					'type'              => 'string',
					'sanitize_callback' => array( __CLASS__, 'sanitize_event_date' ),
				)
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_CANCELLED,
			array_merge(
				$common,
				array(
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
				)
			)
		);
	}

	public static function sanitize_event_date( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return $value;
		}

		return '';
	}

	public static function sanitize_event_time( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( preg_match( '/^\d{2}:\d{2}$/', $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * Accept only a normal external HTTP(S) registration URL.
	 *
	 * @param mixed $value Submitted URL.
	 * @return string
	 */
	public static function sanitize_registration_url( $value ) {
		$value  = is_string( $value ) ? trim( $value ) : '';
		$url    = esc_url_raw( $value, array( 'http', 'https' ) );
		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );

		return $url && in_array( $scheme, array( 'http', 'https' ), true ) ? $url : '';
	}

	public static function register_meta_box() {
		add_meta_box(
			'lks-event-details',
			'Tapahtuman tiedot',
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'side'
		);
	}

	public static function render_meta_box( $post ) {
		$date             = get_post_meta( $post->ID, self::META_DATE, true );
		$time             = get_post_meta( $post->ID, self::META_TIME, true );
		$place            = get_post_meta( $post->ID, self::META_PLACE, true );
		$city             = get_post_meta( $post->ID, self::META_CITY, true );
		$place_label      = get_post_meta( $post->ID, self::META_PLACE_LABEL, true );
		$audience         = get_post_meta( $post->ID, self::META_AUDIENCE, true );
		$price            = get_post_meta( $post->ID, self::META_PRICE, true );
		$registration     = get_post_meta( $post->ID, self::META_REGISTRATION, true );
		$registration_url = get_post_meta( $post->ID, self::META_REGISTRATION_URL, true );
		$deadline         = get_post_meta( $post->ID, self::META_REGISTRATION_DEADLINE, true );
		$cancelled        = '1' === (string) get_post_meta( $post->ID, self::META_CANCELLED, true );

		wp_nonce_field( 'lks_event_details', 'lks_event_details_nonce' );
		?>
		<p>
			<label for="lks-event-date"><strong>Päivä</strong></label><br />
			<input id="lks-event-date" name="lks_event_date" type="date" value="<?php echo esc_attr( $date ); ?>" style="width:100%" />
		</p>
		<p>
			<label for="lks-event-time"><strong>Kellonaika</strong></label><br />
			<input id="lks-event-time" name="lks_event_time" type="time" value="<?php echo esc_attr( $time ); ?>" style="width:100%" />
		</p>
		<p>
			<label for="lks-event-place"><strong>Paikka</strong></label><br />
			<input id="lks-event-place" name="lks_event_place" type="text" value="<?php echo esc_attr( $place ); ?>" style="width:100%" />
		</p>
		<p>
			<label for="lks-event-city"><strong>Paikkakunta</strong></label><br />
			<input id="lks-event-city" name="lks_event_city" type="text" value="<?php echo esc_attr( $city ); ?>" style="width:100%" />
		</p>
		<p>
			<label for="lks-event-place-label"><strong>Paikkatiedon otsikko</strong></label><br />
			<input id="lks-event-place-label" name="lks_event_place_label" type="text" value="<?php echo esc_attr( $place_label ); ?>" placeholder="Paikka" style="width:100%" />
		</p>
		<p>
			<label for="lks-event-audience"><strong>Kenelle</strong></label><br />
			<textarea id="lks-event-audience" name="lks_event_audience" rows="3" style="width:100%"><?php echo esc_textarea( $audience ); ?></textarea>
		</p>
		<p>
			<label for="lks-event-price"><strong>Hinta</strong></label><br />
			<input id="lks-event-price" name="lks_event_price" type="text" value="<?php echo esc_attr( $price ); ?>" placeholder="Esim. Maksuton tai 25 €" style="width:100%" /><br />
			<small>Jätä tyhjäksi, jos hintaa ei tarvitse näyttää.</small>
		</p>
		<p>
			<label for="lks-event-registration"><strong>Ilmoittautuminen</strong></label><br />
			<textarea id="lks-event-registration" name="lks_event_registration" rows="3" placeholder="Täytä vain, jos tapahtumaan täytyy ilmoittautua" style="width:100%"><?php echo esc_textarea( $registration ); ?></textarea><br />
			<small>Vapaaehtoinen lisätieto, esimerkiksi kenelle tapahtuma on tarkoitettu.</small>
		</p>
		<p>
			<label for="lks-event-registration-url"><strong>Ilmoittautumislinkki</strong></label><br />
			<input id="lks-event-registration-url" name="lks_event_registration_url" type="url" value="<?php echo esc_attr( $registration_url ); ?>" placeholder="https://" style="width:100%" /><br />
			<small>Vapaaehtoinen ulkoisen ilmoittautumissivun osoite.</small>
		</p>
		<p>
			<label for="lks-event-registration-deadline"><strong>Ilmoittautuminen päättyy</strong></label><br />
			<input id="lks-event-registration-deadline" name="lks_event_registration_deadline" type="date" value="<?php echo esc_attr( $deadline ); ?>" style="width:100%" /><br />
			<small>Jätä tyhjäksi, niin ilmoittautuminen pysyy avoinna tapahtumapäivään asti.</small>
		</p>
		<p>
			<label>
				<input name="lks_event_cancelled" type="checkbox" value="1"<?php checked( $cancelled ); ?> />
				<strong>Tapahtuma on peruttu</strong>
			</label><br />
			<small>Peruttu tapahtuma säilyy sivustolla, mutta ilmoittautumispainike piilotetaan.</small>
		</p>
		<p style="margin-bottom:0;color:#50575e;">
			Päivä tarvitaan, jotta tapahtuma näkyy tulevissa tai menneissä tapahtumissa.
		</p>
		<?php
	}

	public static function save_event_meta( $post_id ) {
		if ( ! isset( $_POST['lks_event_details_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lks_event_details_nonce'] ) ), 'lks_event_details' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$date  = isset( $_POST['lks_event_date'] ) ? self::sanitize_event_date( wp_unslash( $_POST['lks_event_date'] ) ) : '';
		$time  = isset( $_POST['lks_event_time'] ) ? self::sanitize_event_time( wp_unslash( $_POST['lks_event_time'] ) ) : '';
		$place = isset( $_POST['lks_event_place'] ) ? sanitize_text_field( wp_unslash( $_POST['lks_event_place'] ) ) : '';
		$registration_url = isset( $_POST['lks_event_registration_url'] ) ? self::sanitize_registration_url( wp_unslash( $_POST['lks_event_registration_url'] ) ) : '';
		$deadline = isset( $_POST['lks_event_registration_deadline'] ) ? self::sanitize_event_date( wp_unslash( $_POST['lks_event_registration_deadline'] ) ) : '';
		$cancelled = isset( $_POST['lks_event_cancelled'] ) && '1' === (string) wp_unslash( $_POST['lks_event_cancelled'] );

		self::update_or_delete_meta( $post_id, self::META_DATE, $date );
		self::update_or_delete_meta( $post_id, self::META_TIME, $time );
		self::update_or_delete_meta( $post_id, self::META_PLACE, $place );
		self::update_or_delete_meta( $post_id, self::META_REGISTRATION_URL, $registration_url );
		self::update_or_delete_meta( $post_id, self::META_REGISTRATION_DEADLINE, $deadline );
		self::update_or_delete_meta( $post_id, self::META_CANCELLED, $cancelled ? '1' : '' );

		foreach (
			array(
				self::META_CITY            => 'lks_event_city',
				self::META_PLACE_LABEL     => 'lks_event_place_label',
				self::META_AUDIENCE        => 'lks_event_audience',
				self::META_PRICE           => 'lks_event_price',
				self::META_REGISTRATION    => 'lks_event_registration',
			) as $meta_key => $field_name
		) {
			$value = isset( $_POST[ $field_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) : '';
			self::update_or_delete_meta( $post_id, $meta_key, $value );
		}
	}

	private static function update_or_delete_meta( $post_id, $key, $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Derive the visitor-facing event state without editor-selected statuses.
	 *
	 * @param array<string,mixed> $details Event date and registration values.
	 * @param string              $today   Testable current date (Y-m-d).
	 * @return array{key:string,label:string,registration_url:string,action_label:string}
	 */
	public static function derive_public_state( $details, $today = '' ) {
		$today            = self::sanitize_event_date( $today ) ?: wp_date( 'Y-m-d' );
		$date             = self::sanitize_event_date( $details['date'] ?? '' );
		$deadline         = self::sanitize_event_date( $details['registration_deadline'] ?? '' );
		$registration_url = self::sanitize_registration_url( $details['registration_url'] ?? '' );
		$cancelled        = in_array( strtolower( trim( (string) ( $details['cancelled'] ?? '' ) ) ), array( '1', 'true', 'yes', 'on', 'kyllä' ), true );

		if ( $cancelled ) {
			return array(
				'key'              => 'cancelled',
				'label'            => 'Tapahtuma on peruttu',
				'registration_url' => '',
				'action_label'     => '',
			);
		}

		if ( $date && $date < $today ) {
			return array(
				'key'              => 'past',
				'label'            => 'Tapahtuma on päättynyt',
				'registration_url' => '',
				'action_label'     => '',
			);
		}

		if ( $registration_url ) {
			$effective_deadline = $deadline ?: $date;
			if ( $effective_deadline && $effective_deadline < $today ) {
				return array(
					'key'              => 'registration_closed',
					'label'            => 'Ilmoittautuminen on päättynyt',
					'registration_url' => '',
					'action_label'     => '',
				);
			}

			return array(
				'key'              => 'registration_open',
				'label'            => 'Ilmoittautuminen on avoinna',
				'registration_url' => $registration_url,
				'action_label'     => 'Ilmoittaudu tapahtumaan',
			);
		}

		return array(
			'key'              => 'details_later',
			'label'            => 'Lisätiedot ja ilmoittautuminen julkaistaan myöhemmin',
			'registration_url' => '',
			'action_label'     => '',
		);
	}

	/**
	 * Return the automatically derived public state for a stored event.
	 *
	 * @param int    $post_id Event post ID.
	 * @param string $today   Optional testable current date.
	 * @return array{key:string,label:string,registration_url:string,action_label:string}
	 */
	public static function get_public_state( $post_id, $today = '' ) {
		return self::derive_public_state(
			array(
				'date'                  => get_post_meta( $post_id, self::META_DATE, true ),
				'registration_url'      => get_post_meta( $post_id, self::META_REGISTRATION_URL, true ),
				'registration_deadline' => get_post_meta( $post_id, self::META_REGISTRATION_DEADLINE, true ),
				'cancelled'             => get_post_meta( $post_id, self::META_CANCELLED, true ),
			),
			$today
		);
	}

	public static function register_admin_columns( $columns ) {
		$updated_columns = array();

		foreach ( $columns as $key => $label ) {
			$updated_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$updated_columns['lks_event_date']  = 'Päivä';
				$updated_columns['lks_event_place'] = 'Paikka';
			}
		}

		return $updated_columns;
	}

	public static function render_admin_columns( $column, $post_id ) {
		if ( 'lks_event_date' === $column ) {
			$date = get_post_meta( $post_id, self::META_DATE, true );
			echo esc_html( self::format_date( $date ) ?: 'Ei asetettu' );
		}

		if ( 'lks_event_place' === $column ) {
			$place = get_post_meta( $post_id, self::META_PLACE, true );
			echo esc_html( $place ?: '-' );
		}
	}

	public static function register_sortable_columns( $columns ) {
		$columns['lks_event_date'] = 'lks_event_date';

		return $columns;
	}

	public static function handle_admin_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( 'lks_event_date' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', self::META_DATE );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	public static function render_events_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'status' => 'upcoming',
				'limit'  => 3,
				'empty'  => '',
			),
			$atts,
			'lks_events'
		);

		$status = 'past' === $atts['status'] ? 'past' : 'upcoming';
		$limit  = max( 1, absint( $atts['limit'] ) );
		$events = get_posts( self::get_events_query_args( $status, $limit ) );

		if ( empty( $events ) ) {
			$empty_message = $atts['empty'];

			if ( '' === $empty_message ) {
				$empty_message = 'past' === $status
					? 'Menneitä tapahtumia ei ole vielä lisätty.'
					: 'Tulevia tapahtumia ei ole juuri nyt.';
			}

			return sprintf(
				'<p class="lks-event-empty">%s</p>',
				esc_html( $empty_message )
			);
		}

		ob_start();
		?>
		<div class="lks-event-list">
			<?php foreach ( $events as $event ) : ?>
				<?php
				$date      = get_post_meta( $event->ID, self::META_DATE, true );
				$time      = get_post_meta( $event->ID, self::META_TIME, true );
				$place     = get_post_meta( $event->ID, self::META_PLACE, true );
				$city      = get_post_meta( $event->ID, self::META_CITY, true );
				$public_state = self::get_public_state( $event->ID );
				$status_label = self::card_status_label( $public_state );
				$summary   = has_excerpt( $event ) ? $event->post_excerpt : wp_trim_words( wp_strip_all_tags( excerpt_remove_blocks( $event->post_content ) ), 24 );
				$meta_bits = array_filter(
					array(
						self::format_date( $date ),
						'upcoming' === $status ? $city : self::format_time( $time ),
						'upcoming' === $status ? '' : $place,
					)
				);
				?>
				<article class="lks-event-card <?php echo esc_attr( 'past' === $status ? 'is-past' : 'is-upcoming' ); ?>">
					<?php if ( has_post_thumbnail( $event ) ) : ?>
						<a class="lks-event-card__image" href="<?php echo esc_url( get_permalink( $event ) ); ?>" tabindex="-1" aria-hidden="true">
							<?php echo get_the_post_thumbnail( $event, 'medium_large', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					<?php endif; ?>

					<?php if ( ! empty( $meta_bits ) || ( 'upcoming' === $status && $status_label ) ) : ?>
						<p class="lks-event-meta">
							<?php foreach ( array_values( $meta_bits ) as $meta_index => $meta_bit ) : ?>
								<?php if ( 0 === $meta_index && $date ) : ?>
									<time datetime="<?php echo esc_attr( $date ); ?>"><?php echo esc_html( $meta_bit ); ?></time>
								<?php else : ?>
									<span><?php echo esc_html( $meta_bit ); ?></span>
								<?php endif; ?>
								<?php if ( 0 === $meta_index && 'upcoming' === $status && $status_label ) : ?>
									<span class="lks-event-status"><?php echo esc_html( $status_label ); ?></span>
								<?php endif; ?>
							<?php endforeach; ?>
							<?php if ( empty( $meta_bits ) && 'upcoming' === $status && $status_label ) : ?>
								<span class="lks-event-status"><?php echo esc_html( $status_label ); ?></span>
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<h3 class="lks-event-title"><?php echo esc_html( get_the_title( $event ) ); ?></h3>

					<?php if ( $summary ) : ?>
						<p class="lks-event-summary"><?php echo esc_html( $summary ); ?></p>
					<?php endif; ?>

					<a class="lks-event-link" href="<?php echo esc_url( get_permalink( $event ) ); ?>">Katso tiedot</a>
				</article>
			<?php endforeach; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	public static function render_event_single_shortcode() {
		$event = get_post();

		if ( ! $event || self::POST_TYPE !== $event->post_type ) {
			return '';
		}

		$date      = get_post_meta( $event->ID, self::META_DATE, true );
		$time      = get_post_meta( $event->ID, self::META_TIME, true );
		$place     = get_post_meta( $event->ID, self::META_PLACE, true );
		$city      = get_post_meta( $event->ID, self::META_CITY, true );
		$place_label = get_post_meta( $event->ID, self::META_PLACE_LABEL, true ) ?: 'Paikka';
		$audience  = get_post_meta( $event->ID, self::META_AUDIENCE, true );
		$price     = trim( (string) get_post_meta( $event->ID, self::META_PRICE, true ) );
		$registration = trim( (string) get_post_meta( $event->ID, self::META_REGISTRATION, true ) );
		$summary   = has_excerpt( $event ) ? get_the_excerpt( $event ) : '';
		$content   = apply_filters( 'the_content', $event->post_content );
		$is_upcoming = is_string( $date ) && '' !== $date && $date >= wp_date( 'Y-m-d' );
		$public_state = self::get_public_state( $event->ID );
		$meta_bits = array_filter(
			array(
				self::format_date( $date ),
				$city ?: ( $is_upcoming ? '' : $place ),
			)
		);
		$show_price = ! in_array( $price, array( '', 'Vahvistetaan' ), true );
		$show_registration = ! in_array(
			$registration,
			array(
				'',
				'Pyydä osallistumisohjeet sähköpostilla',
				'Avataan, kun ohjelma ja paikka on vahvistettu',
			),
			true
		);

		ob_start();
		?>
		<div id="main" class="lks-article lks-event-single" role="main">
			<header class="lks-article__header">
				<div class="lks-article-shell">
					<a class="lks-article__back" href="<?php echo esc_url( home_url( '/tapahtumat/' ) ); ?>">&#8592; Kaikki tapahtumat</a>
					<?php if ( ! empty( $meta_bits ) ) : ?>
						<div class="lks-article__meta">
							<?php foreach ( array_values( $meta_bits ) as $meta_index => $meta_bit ) : ?>
								<?php if ( 0 === $meta_index && $date ) : ?>
									<time datetime="<?php echo esc_attr( $date ); ?>"><?php echo esc_html( $meta_bit ); ?></time>
								<?php else : ?>
									<span><?php echo esc_html( $meta_bit ); ?></span>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<p class="lks-event-status lks-event-status--header <?php echo esc_attr( 'lks-event-status--' . $public_state['key'] ); ?>"><?php echo esc_html( $public_state['label'] ); ?></p>
					<h1><?php echo esc_html( get_the_title( $event ) ); ?></h1>
					<?php if ( $summary ) : ?><p class="lks-article__lead"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
				</div>
			</header>

			<?php if ( has_post_thumbnail( $event ) ) : ?>
				<figure class="lks-article__hero-image">
					<?php echo get_the_post_thumbnail( $event, 'full', array( 'fetchpriority' => 'high', 'loading' => 'eager', 'decoding' => 'async', 'alt' => self::featured_image_alt( $event ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</figure>
			<?php endif; ?>

			<div class="lks-article-shell lks-article__layout">
				<div class="lks-article__body">
					<?php if ( $is_upcoming ) : ?>
						<dl class="lks-event-facts" aria-label="Tapahtuman tiedot">
							<div><dt>Päivä</dt><dd><time datetime="<?php echo esc_attr( $date ); ?>"><?php echo esc_html( self::format_date( $date ) ); ?></time></dd></div>
							<div><dt>Aika</dt><dd><?php if ( $time && preg_match( '/^\d{2}:\d{2}$/', $time ) ) : ?><time datetime="<?php echo esc_attr( $time ); ?>"><?php echo esc_html( self::format_time( $time ) ); ?></time><?php else : ?><?php echo esc_html( $time ? self::format_time( $time ) : 'Vahvistetaan' ); ?><?php endif; ?></dd></div>
							<div><dt><?php echo esc_html( $place_label ); ?></dt><dd><?php echo esc_html( $place ?: 'Vahvistetaan' ); ?></dd></div>
							<div><dt>Kenelle</dt><dd><?php echo esc_html( $audience ?: 'Vahvistetaan' ); ?></dd></div>
							<?php if ( $show_price ) : ?><div><dt>Hinta</dt><dd><?php echo esc_html( $price ); ?></dd></div><?php endif; ?>
							<?php if ( $show_registration ) : ?><div><dt>Ilmoittautuminen</dt><dd><?php echo esc_html( $registration ); ?></dd></div><?php endif; ?>
						</dl>
					<?php endif; ?>
					<?php echo self::render_registration_panel( $event, $public_state ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<?php

		return (string) preg_replace( '/>\s+</', '><', (string) ob_get_clean() );
	}

	/**
	 * Render the static-safe registration state without any submission form.
	 *
	 * The optional state argument keeps the visitor output testable without
	 * creating or changing WordPress event records.
	 *
	 * @param WP_Post                   $event Event post.
	 * @param array<string,string>|null $state Derived state, or null for stored data.
	 * @return string
	 */
	public static function render_registration_panel( $event, $state = null ) {
		if ( ! $event instanceof WP_Post ) {
			return '';
		}

		$state       = is_array( $state ) ? $state : self::get_public_state( $event->ID );
		$state_key   = sanitize_key( $state['key'] ?? '' );
		$message     = sanitize_text_field( $state['label'] ?? '' );
		$action_url  = self::sanitize_registration_url( $state['registration_url'] ?? '' );
		$action_text = sanitize_text_field( $state['action_label'] ?? '' );
		$enquiry_url = 'details_later' === $state_key ? self::event_enquiry_url( $event ) : '';

		ob_start();
		?>
		<section class="lks-event-registration" data-lks-event-state="<?php echo esc_attr( $state_key ); ?>" aria-labelledby="lks-event-registration-title">
			<h2 id="lks-event-registration-title">Ilmoittautuminen</h2>
			<?php if ( 'registration_open' === $state_key && $action_url && $action_text ) : ?>
				<p><?php echo esc_html( $message ); ?></p>
				<a class="lks-button lks-button--gold lks-event-registration__action" href="<?php echo esc_url( $action_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $action_text ); ?> <span class="screen-reader-text">(avautuu uuteen välilehteen)</span></a>
			<?php else : ?>
				<p><?php echo esc_html( $message ); ?></p>
				<?php if ( $enquiry_url ) : ?>
					<a class="lks-text-link lks-event-registration__enquiry" href="<?php echo esc_url( $enquiry_url ); ?>">Kysy lisätietoja sähköpostilla <span aria-hidden="true">&rarr;</span></a>
				<?php endif; ?>
			<?php endif; ?>
		</section>
		<?php

		return (string) preg_replace( '/>\s+</', '><', (string) ob_get_clean() );
	}

	private static function get_events_query_args( $status, $limit ) {
		$today = wp_date( 'Y-m-d' );

		return array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_key'       => self::META_DATE,
			'orderby'        => 'meta_value',
			'order'          => 'past' === $status ? 'DESC' : 'ASC',
			'meta_query'     => array(
				array(
					'key'     => self::META_DATE,
					'value'   => $today,
					'compare' => 'past' === $status ? '<' : '>=',
					'type'    => 'DATE',
				),
			),
		);
	}

	private static function format_date( $date ) {
		if ( ! is_string( $date ) || '' === $date ) {
			return '';
		}

		$timezone = wp_timezone();
		$datetime = date_create_immutable_from_format( 'Y-m-d', $date, $timezone );

		if ( ! $datetime ) {
			return '';
		}

		return wp_date( 'j.n.Y', $datetime->getTimestamp(), $timezone );
	}

	private static function format_time( $time ) {
		if ( ! is_string( $time ) || '' === $time ) {
			return '';
		}

		return 'klo ' . $time;
	}

	/**
	 * Return media-library alt text or a safe event-title fallback.
	 *
	 * @param WP_Post $event Event post.
	 * @return string
	 */
	private static function featured_image_alt( $event ) {
		$fallback      = sprintf( 'Tapahtuman kuvitus: %s', get_the_title( $event ) );
		$attachment_id = get_post_thumbnail_id( $event );

		if ( function_exists( 'lakeuden_kauppaseura_attachment_alt' ) ) {
			return lakeuden_kauppaseura_attachment_alt( $attachment_id, $fallback );
		}

		$alt = trim( wp_strip_all_tags( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
		return $alt ?: $fallback;
	}

	/**
	 * Use shorter state labels on archive cards while keeping their meaning.
	 *
	 * @param array<string,string> $state Derived public state.
	 * @return string
	 */
	private static function card_status_label( $state ) {
		$labels = array(
			'registration_open'   => 'Ilmoittautuminen avoinna',
			'registration_closed' => 'Ilmoittautuminen päättynyt',
			'details_later'       => 'Lisätiedot tulossa',
		);

		return $labels[ $state['key'] ] ?? ( $state['label'] ?? '' );
	}

	/**
	 * Build a static-safe enquiry email link from the public association email.
	 *
	 * @param WP_Post $event Event post.
	 * @return string
	 */
	private static function event_enquiry_url( $event ) {
		$email = function_exists( 'lakeuden_kauppaseura_copy' )
			? sanitize_email( lakeuden_kauppaseura_copy( 'contact_email' ) )
			: '';
		if ( ! $email ) {
			return '';
		}

		return 'mailto:' . $email . '?subject=' . rawurlencode( 'Kysymys tapahtumasta: ' . get_the_title( $event ) );
	}
}

LKS_Events_Manager::init();

register_activation_hook( __FILE__, array( 'LKS_Events_Manager', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LKS_Events_Manager', 'deactivate' ) );
