<?php
/**
 * Editable board-member and member-testimonial records and components.
 *
 * @package Lakeuden_Kauppaseura
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Small, non-public content types for people shown by the theme.
 *
 * TODO(lks-people-launch): Replace every seeded placeholder record with
 * approved content, enable its production section, and verify portrait and
 * contact publication permissions.
 */
final class Lakeuden_Kauppaseura_People {
	const BOARD_POST_TYPE       = 'lks_board_member';
	const TESTIMONIAL_POST_TYPE = 'lks_testimonial';

	const META_BOARD_ROLE       = '_lks_board_role';
	const META_ORGANIZATION     = '_lks_person_organization';
	const META_PROFESSIONAL_ROLE = '_lks_person_professional_role';
	const META_EMAIL            = '_lks_person_email';
	const META_SHOW_EMAIL       = '_lks_person_show_email';
	const META_TELEPHONE        = '_lks_person_telephone';
	const META_SHOW_TELEPHONE   = '_lks_person_show_telephone';
	const META_PROFILE_URL      = '_lks_person_profile_url';
	const META_SEED_KEY         = '_lks_people_seed_key';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_' . self::BOARD_POST_TYPE, array( __CLASS__, 'save_board_member' ), 10, 2 );
		add_action( 'save_post_' . self::TESTIMONIAL_POST_TYPE, array( __CLASS__, 'save_testimonial' ), 10, 2 );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
		add_action( 'edit_form_after_title', array( __CLASS__, 'render_editor_help' ) );
		add_filter( 'manage_' . self::BOARD_POST_TYPE . '_posts_columns', array( __CLASS__, 'board_columns' ) );
		add_action( 'manage_' . self::BOARD_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_board_column' ), 10, 2 );
		add_filter( 'manage_' . self::TESTIMONIAL_POST_TYPE . '_posts_columns', array( __CLASS__, 'testimonial_columns' ) );
		add_action( 'manage_' . self::TESTIMONIAL_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_testimonial_column' ), 10, 2 );
		add_action( 'pre_get_posts', array( __CLASS__, 'order_admin_people' ) );
		add_action( 'admin_notices', array( __CLASS__, 'placeholder_notice' ) );
		add_action( 'admin_head-post.php', array( __CLASS__, 'admin_styles' ) );
		add_action( 'admin_head-post-new.php', array( __CLASS__, 'admin_styles' ) );
	}

	/**
	 * Register the two simple editor-facing content types.
	 */
	public static function register_post_types() {
		register_post_type(
			self::BOARD_POST_TYPE,
			array(
				'labels'              => array(
					'name'                  => 'Hallitus',
					'singular_name'         => 'Hallituksen jäsen',
					'menu_name'             => 'Hallitus',
					'name_admin_bar'        => 'Hallituksen jäsen',
					'add_new'               => 'Lisää jäsen',
					'add_new_item'          => 'Lisää hallituksen jäsen',
					'edit_item'             => 'Muokkaa hallituksen jäsentä',
					'new_item'              => 'Uusi hallituksen jäsen',
					'view_item'             => 'Esikatsele hallituksen jäsentä',
					'all_items'             => 'Kaikki hallituksen jäsenet',
					'search_items'          => 'Etsi hallituksen jäseniä',
					'not_found'             => 'Hallituksen jäseniä ei löytynyt.',
					'not_found_in_trash'    => 'Roskakorissa ei ole hallituksen jäseniä.',
					'featured_image'        => 'Muotokuva',
					'set_featured_image'    => 'Valitse muotokuva',
					'remove_featured_image' => 'Poista muotokuva',
					'use_featured_image'    => 'Käytä muotokuvana',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-groups',
				'menu_position'       => 22,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			)
		);

		register_post_type(
			self::TESTIMONIAL_POST_TYPE,
			array(
				'labels'              => array(
					'name'                  => 'Jäsenkokemukset',
					'singular_name'         => 'Jäsenkokemus',
					'menu_name'             => 'Jäsenkokemukset',
					'name_admin_bar'        => 'Jäsenkokemus',
					'add_new'               => 'Lisää jäsenkokemus',
					'add_new_item'          => 'Lisää jäsenkokemus',
					'edit_item'             => 'Muokkaa jäsenkokemusta',
					'new_item'              => 'Uusi jäsenkokemus',
					'view_item'             => 'Esikatsele jäsenkokemusta',
					'all_items'             => 'Kaikki jäsenkokemukset',
					'search_items'          => 'Etsi jäsenkokemuksia',
					'not_found'             => 'Jäsenkokemuksia ei löytynyt.',
					'not_found_in_trash'    => 'Roskakorissa ei ole jäsenkokemuksia.',
					'featured_image'        => 'Muotokuva',
					'set_featured_image'    => 'Valitse muotokuva',
					'remove_featured_image' => 'Poista muotokuva',
					'use_featured_image'    => 'Käytä muotokuvana',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-format-quote',
				'menu_position'       => 23,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			)
		);
	}

	/**
	 * Register private metadata without exposing contact data through REST.
	 */
	public static function register_meta() {
		$common = array(
			'single'        => true,
			'show_in_rest'  => false,
			'auth_callback' => static function() {
				return current_user_can( 'edit_posts' );
			},
		);

		$string_meta = array(
			self::BOARD_POST_TYPE => array(
				self::META_BOARD_ROLE,
				self::META_ORGANIZATION,
				self::META_TELEPHONE,
				self::META_SEED_KEY,
			),
			self::TESTIMONIAL_POST_TYPE => array(
				self::META_ORGANIZATION,
				self::META_PROFESSIONAL_ROLE,
				self::META_SEED_KEY,
			),
		);

		foreach ( $string_meta as $post_type => $keys ) {
			foreach ( $keys as $key ) {
				register_post_meta(
					$post_type,
					$key,
					array_merge(
						$common,
						array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						)
					)
				);
			}
		}

		register_post_meta(
			self::BOARD_POST_TYPE,
			self::META_EMAIL,
			array_merge(
				$common,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
				)
			)
		);

		register_post_meta(
			self::TESTIMONIAL_POST_TYPE,
			self::META_PROFILE_URL,
			array_merge(
				$common,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'esc_url_raw',
				)
			)
		);

		foreach ( array( self::META_SHOW_EMAIL, self::META_SHOW_TELEPHONE ) as $key ) {
			register_post_meta(
				self::BOARD_POST_TYPE,
				$key,
				array_merge(
					$common,
					array(
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
					)
				)
			);
		}
	}

	/**
	 * Add one compact details panel for each record type.
	 */
	public static function register_meta_boxes() {
		add_meta_box(
			'lks-board-member-details',
			'Hallituksen jäsenen tiedot',
			array( __CLASS__, 'render_board_meta_box' ),
			self::BOARD_POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'lks-testimonial-details',
			'Jäsenkokemuksen tiedot',
			array( __CLASS__, 'render_testimonial_meta_box' ),
			self::TESTIMONIAL_POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the board-member details panel.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function render_board_meta_box( $post ) {
		$role           = get_post_meta( $post->ID, self::META_BOARD_ROLE, true );
		$organization   = get_post_meta( $post->ID, self::META_ORGANIZATION, true );
		$email          = get_post_meta( $post->ID, self::META_EMAIL, true );
		$telephone      = get_post_meta( $post->ID, self::META_TELEPHONE, true );
		$show_email     = '1' === (string) get_post_meta( $post->ID, self::META_SHOW_EMAIL, true );
		$show_telephone = '1' === (string) get_post_meta( $post->ID, self::META_SHOW_TELEPHONE, true );

		wp_nonce_field( 'lks_people_save', 'lks_people_nonce' );
		?>
		<div class="lks-people-fields">
			<p>
				<label for="lks-board-role"><strong>Tehtävä hallituksessa</strong></label><br />
				<input class="widefat" id="lks-board-role" name="lks_people[board_role]" type="text" value="<?php echo esc_attr( $role ); ?>" placeholder="Esimerkiksi puheenjohtaja" />
			</p>
			<p>
				<label for="lks-person-organization"><strong>Organisaatio tai ammattinimike</strong> <span class="description">(vapaaehtoinen)</span></label><br />
				<input class="widefat" id="lks-person-organization" name="lks_people[organization]" type="text" value="<?php echo esc_attr( $organization ); ?>" />
			</p>
			<div class="lks-people-fields__contact">
				<p>
					<label for="lks-person-email"><strong>Sähköposti</strong> <span class="description">(vapaaehtoinen)</span></label><br />
					<input class="widefat" id="lks-person-email" name="lks_people[email]" type="email" value="<?php echo esc_attr( $email ); ?>" autocomplete="off" />
					<label><input name="lks_people[show_email]" type="checkbox" value="1"<?php checked( $show_email ); ?> /> Näytä sähköposti julkisella sivulla</label>
				</p>
				<p>
					<label for="lks-person-telephone"><strong>Puhelin</strong> <span class="description">(vapaaehtoinen)</span></label><br />
					<input class="widefat" id="lks-person-telephone" name="lks_people[telephone]" type="tel" value="<?php echo esc_attr( $telephone ); ?>" autocomplete="off" />
					<label><input name="lks_people[show_telephone]" type="checkbox" value="1"<?php checked( $show_telephone ); ?> /> Näytä puhelin julkisella sivulla</label>
				</p>
			</div>
			<p>
				<label for="lks-person-order"><strong>Näyttöjärjestys</strong></label><br />
				<input id="lks-person-order" min="0" name="lks_people[display_order]" step="1" type="number" value="<?php echo esc_attr( (string) $post->menu_order ); ?>" />
				<span class="description">Pienin numero näytetään ensin.</span>
			</p>
			<p class="description"><strong>Tietosuoja:</strong> sähköpostia tai puhelinta ei näytetä, ellei erillistä näyttövalintaa ole rastitettu.</p>
		</div>
		<?php
	}

	/**
	 * Render the member-testimonial details panel.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function render_testimonial_meta_box( $post ) {
		$organization      = get_post_meta( $post->ID, self::META_ORGANIZATION, true );
		$professional_role = get_post_meta( $post->ID, self::META_PROFESSIONAL_ROLE, true );
		$profile_url       = get_post_meta( $post->ID, self::META_PROFILE_URL, true );

		wp_nonce_field( 'lks_people_save', 'lks_people_nonce' );
		?>
		<div class="lks-people-fields">
			<p>
				<label for="lks-testimonial-organization"><strong>Organisaatio</strong> <span class="description">(vapaaehtoinen)</span></label><br />
				<input class="widefat" id="lks-testimonial-organization" name="lks_people[organization]" type="text" value="<?php echo esc_attr( $organization ); ?>" />
			</p>
			<p>
				<label for="lks-testimonial-role"><strong>Ammatillinen rooli</strong> <span class="description">(vapaaehtoinen)</span></label><br />
				<input class="widefat" id="lks-testimonial-role" name="lks_people[professional_role]" type="text" value="<?php echo esc_attr( $professional_role ); ?>" />
			</p>
			<p>
				<label for="lks-testimonial-profile"><strong>Profiililinkki</strong> <span class="description">(vapaaehtoinen)</span></label><br />
				<input class="widefat" id="lks-testimonial-profile" name="lks_people[profile_url]" type="url" value="<?php echo esc_attr( $profile_url ); ?>" placeholder="https://" />
			</p>
			<p>
				<label for="lks-testimonial-order"><strong>Näyttöjärjestys</strong></label><br />
				<input id="lks-testimonial-order" min="0" name="lks_people[display_order]" step="1" type="number" value="<?php echo esc_attr( (string) $post->menu_order ); ?>" />
				<span class="description">Jäseneksi-sivulla näytetään enintään kolme ensimmäistä julkaisukelpoista korttia. Yksi tai kaksi hyväksyttyä korttia riittää.</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Adjust the familiar WordPress editor labels.
	 *
	 * @param string  $placeholder Current title placeholder.
	 * @param WP_Post $post        Current post.
	 * @return string
	 */
	public static function title_placeholder( $placeholder, $post ) {
		if ( in_array( $post->post_type, array( self::BOARD_POST_TYPE, self::TESTIMONIAL_POST_TYPE ), true ) ) {
			return 'Nimi';
		}

		return $placeholder;
	}

	/**
	 * Explain what belongs in the normal content editor.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function render_editor_help( $post ) {
		if ( self::BOARD_POST_TYPE === $post->post_type ) {
			echo '<p class="description"><strong>Lyhyt esittely:</strong> kirjoita esittely alla olevaan tavalliseen sisältöeditoriin. Valitse muotokuva Muotokuva-ruudusta.</p>';
		} elseif ( self::TESTIMONIAL_POST_TYPE === $post->post_type ) {
			echo '<p class="description"><strong>Jäsenen kommentti:</strong> kirjoita hyväksytty sitaatti alla olevaan tavalliseen sisältöeditoriin. Valitse muotokuva Muotokuva-ruudusta.</p>';
		}
	}

	/**
	 * Keep the details panel compact without loading public theme CSS in admin.
	 */
	public static function admin_styles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->post_type, array( self::BOARD_POST_TYPE, self::TESTIMONIAL_POST_TYPE ), true ) ) {
			return;
		}
		?>
		<style>
			.lks-people-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.5rem; }
			.lks-people-fields > p { margin: 0; }
			.lks-people-fields__contact { display: grid; grid-column: 1 / -1; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem; }
			.lks-people-fields__contact p { margin: 0; }
			@media (max-width: 782px) {
				.lks-people-fields,
				.lks-people-fields__contact { grid-template-columns: minmax(0, 1fr); }
			}
		</style>
		<?php
	}

	/**
	 * Save board member fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_board_member( $post_id, $post ) {
		if ( ! self::can_save( $post_id ) ) {
			return;
		}

		$input = isset( $_POST['lks_people'] ) && is_array( $_POST['lks_people'] ) ? wp_unslash( $_POST['lks_people'] ) : array();
		self::save_text_meta( $post_id, self::META_BOARD_ROLE, $input['board_role'] ?? '' );
		self::save_text_meta( $post_id, self::META_ORGANIZATION, $input['organization'] ?? '' );
		self::save_email_meta( $post_id, self::META_EMAIL, $input['email'] ?? '' );
		self::save_text_meta( $post_id, self::META_TELEPHONE, $input['telephone'] ?? '' );
		self::save_boolean_meta( $post_id, self::META_SHOW_EMAIL, ! empty( $input['show_email'] ) && ! empty( $input['email'] ) );
		self::save_boolean_meta( $post_id, self::META_SHOW_TELEPHONE, ! empty( $input['show_telephone'] ) && ! empty( $input['telephone'] ) );
		self::save_display_order( $post_id, $post, $input['display_order'] ?? 0 );
	}

	/**
	 * Save testimonial fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_testimonial( $post_id, $post ) {
		if ( ! self::can_save( $post_id ) ) {
			return;
		}

		$input = isset( $_POST['lks_people'] ) && is_array( $_POST['lks_people'] ) ? wp_unslash( $_POST['lks_people'] ) : array();
		self::save_text_meta( $post_id, self::META_ORGANIZATION, $input['organization'] ?? '' );
		self::save_text_meta( $post_id, self::META_PROFESSIONAL_ROLE, $input['professional_role'] ?? '' );
		self::save_url_meta( $post_id, self::META_PROFILE_URL, $input['profile_url'] ?? '' );
		self::save_display_order( $post_id, $post, $input['display_order'] ?? 0 );
	}

	/**
	 * Check nonce and permissions.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function can_save( $post_id ) {
		return ! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			&& isset( $_POST['lks_people_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lks_people_nonce'] ) ), 'lks_people_save' )
			&& current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Save or remove a plain-text value.
	 */
	private static function save_text_meta( $post_id, $key, $value ) {
		$value = sanitize_text_field( $value );
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Save or remove an email value.
	 */
	private static function save_email_meta( $post_id, $key, $value ) {
		$value = sanitize_email( $value );
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Save or remove a URL value.
	 */
	private static function save_url_meta( $post_id, $key, $value ) {
		$value = esc_url_raw( $value );
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Save an explicit publication toggle.
	 */
	private static function save_boolean_meta( $post_id, $key, $value ) {
		if ( $value ) {
			update_post_meta( $post_id, $key, '1' );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}

	/**
	 * Store display order in WordPress's built-in menu_order field.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Current post.
	 * @param mixed   $value   Submitted order.
	 */
	private static function save_display_order( $post_id, $post, $value ) {
		$order = absint( $value );
		if ( (int) $post->menu_order === $order ) {
			return;
		}

		remove_action( 'save_post_' . $post->post_type, array( __CLASS__, 'save_board_member' ), 10 );
		remove_action( 'save_post_' . $post->post_type, array( __CLASS__, 'save_testimonial' ), 10 );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'menu_order' => $order,
			)
		);
		add_action( 'save_post_' . self::BOARD_POST_TYPE, array( __CLASS__, 'save_board_member' ), 10, 2 );
		add_action( 'save_post_' . self::TESTIMONIAL_POST_TYPE, array( __CLASS__, 'save_testimonial' ), 10, 2 );
	}

	/**
	 * Board-list columns.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public static function board_columns( $columns ) {
		return array(
			'cb'               => $columns['cb'] ?? '',
			'title'            => 'Nimi',
			'lks_board_role'   => 'Tehtävä hallituksessa',
			'lks_organization' => 'Organisaatio / nimike',
			'lks_order'        => 'Järjestys',
			'lks_readiness'    => 'Julkaisuvalmius',
			'date'             => $columns['date'] ?? 'Päiväys',
		);
	}

	/**
	 * Testimonial-list columns.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public static function testimonial_columns( $columns ) {
		return array(
			'cb'               => $columns['cb'] ?? '',
			'title'            => 'Nimi',
			'lks_organization' => 'Organisaatio',
			'lks_person_role'  => 'Ammatillinen rooli',
			'lks_order'        => 'Järjestys',
			'lks_readiness'    => 'Julkaisuvalmius',
			'date'             => $columns['date'] ?? 'Päiväys',
		);
	}

	/**
	 * Render board-list data.
	 */
	public static function render_board_column( $column, $post_id ) {
		if ( 'lks_board_role' === $column ) {
			echo esc_html( get_post_meta( $post_id, self::META_BOARD_ROLE, true ) ?: '—' );
		} elseif ( 'lks_organization' === $column ) {
			echo esc_html( get_post_meta( $post_id, self::META_ORGANIZATION, true ) ?: '—' );
		} elseif ( 'lks_order' === $column ) {
			echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
		} elseif ( 'lks_readiness' === $column ) {
			echo lakeuden_kauppaseura_person_has_placeholder( get_post( $post_id ) ) ? '<strong>Kesken</strong>' : 'Valmis'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Render testimonial-list data.
	 */
	public static function render_testimonial_column( $column, $post_id ) {
		if ( 'lks_organization' === $column ) {
			echo esc_html( get_post_meta( $post_id, self::META_ORGANIZATION, true ) ?: '—' );
		} elseif ( 'lks_person_role' === $column ) {
			echo esc_html( get_post_meta( $post_id, self::META_PROFESSIONAL_ROLE, true ) ?: '—' );
		} elseif ( 'lks_order' === $column ) {
			echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
		} elseif ( 'lks_readiness' === $column ) {
			echo lakeuden_kauppaseura_person_has_placeholder( get_post( $post_id ) ) ? '<strong>Kesken</strong>' : 'Valmis'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Keep editor lists in the same order as the public components.
	 *
	 * @param WP_Query $query Current query.
	 */
	public static function order_admin_people( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		if ( in_array( $post_type, array( self::BOARD_POST_TYPE, self::TESTIMONIAL_POST_TYPE ), true ) && ! $query->get( 'orderby' ) ) {
			$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
			$query->set( 'order', 'ASC' );
		}
	}

	/**
	 * Warn editors when the current people collection still contains seeds.
	 */
	public static function placeholder_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->post_type, array( self::BOARD_POST_TYPE, self::TESTIMONIAL_POST_TYPE ), true ) ) {
			return;
		}

		$count = lakeuden_kauppaseura_count_people_placeholders( $screen->post_type );
		if ( ! $count ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p><strong><?php echo esc_html( (string) $count ); ?> väliaikaista henkilökorttia odottaa täydentämistä.</strong> Korvaa hakasulkeissa olevat tiedot ennen tuotantojulkaisua. Muotokuva on vapaaehtoinen.</p>
		</div>
		<?php
	}
}
Lakeuden_Kauppaseura_People::init();

/**
 * Return published people in explicit display order.
 *
 * @param string $post_type People post type.
 * @param int    $limit     Maximum results, or -1 for all.
 * @return WP_Post[]
 */
function lakeuden_kauppaseura_get_people( $post_type, $limit = -1 ) {
	if ( ! in_array( $post_type, array( Lakeuden_Kauppaseura_People::BOARD_POST_TYPE, Lakeuden_Kauppaseura_People::TESTIMONIAL_POST_TYPE ), true ) ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
}

/**
 * Detect an explicitly temporary value.
 *
 * @param mixed $value Candidate value.
 * @return bool
 */
function lakeuden_kauppaseura_people_value_is_placeholder( $value ) {
	return (bool) preg_match( '/\[[^\]]*(?:LISÄTÄÄN|ENNEN JULKAISUA)[^\]]*\]/u', (string) $value );
}

/**
 * Determine whether a person record contains launch placeholder content.
 *
 * @param WP_Post|null $post Person record.
 * @return bool
 */
function lakeuden_kauppaseura_person_has_placeholder( $post ) {
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$values = array(
		$post->post_title,
		$post->post_content,
		get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_ORGANIZATION, true ),
		get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_PROFESSIONAL_ROLE, true ),
	);

	if ( Lakeuden_Kauppaseura_People::BOARD_POST_TYPE === $post->post_type ) {
		$values[] = get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_BOARD_ROLE, true );
	}

	foreach ( $values as $value ) {
		if ( lakeuden_kauppaseura_people_value_is_placeholder( $value ) ) {
			return true;
		}
	}

	return '' === trim( $post->post_title ) || '' === trim( wp_strip_all_tags( $post->post_content ) );
}

/**
 * Count placeholder records of one type.
 *
 * @param string $post_type People post type.
 * @return int
 */
function lakeuden_kauppaseura_count_people_placeholders( $post_type ) {
	return count(
		array_filter(
			lakeuden_kauppaseura_get_people( $post_type ),
			'lakeuden_kauppaseura_person_has_placeholder'
		)
	);
}

/**
 * Detect a production-facing request for reusable people sections.
 *
 * Local WordPress keeps the clearly marked seeds visible for editing and
 * layout review. The exporter sends an explicit header so generated output
 * follows the production visibility settings even when WordPress runs in its
 * normal local environment.
 *
 * @return bool
 */
function lakeuden_kauppaseura_people_is_production_context() {
	$export_header = isset( $_SERVER['HTTP_X_LKS_STATIC_EXPORT'] )
		? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_LKS_STATIC_EXPORT'] ) )
		: '';

	return '1' === $export_header || 'production' === wp_get_environment_type();
}

/**
 * Return only people records that contain approved, non-placeholder content.
 *
 * A portrait is deliberately not part of this readiness check.
 *
 * @param WP_Post[] $people Candidate records.
 * @return WP_Post[]
 */
function lakeuden_kauppaseura_approved_people( $people ) {
	return array_values(
		array_filter(
			$people,
			static function ( $person ) {
				return $person instanceof WP_Post && ! lakeuden_kauppaseura_person_has_placeholder( $person );
			}
		)
	);
}

/**
 * Prepare one people collection for local editing or production output.
 *
 * @param string $post_type People post type.
 * @param string $setting   Page-copy visibility setting.
 * @param int    $limit     Maximum results, or -1 for all.
 * @return WP_Post[]
 */
function lakeuden_kauppaseura_people_for_display( $post_type, $setting, $limit = -1 ) {
	$people = lakeuden_kauppaseura_get_people( $post_type );

	if ( lakeuden_kauppaseura_people_is_production_context() ) {
		if ( '1' !== lakeuden_kauppaseura_copy( $setting ) ) {
			return array();
		}
		$people = lakeuden_kauppaseura_approved_people( $people );
	}

	if ( -1 !== $limit ) {
		$people = array_slice( $people, 0, max( 0, absint( $limit ) ) );
	}

	return $people;
}

/**
 * Create a short fallback monogram from a person's name.
 *
 * @param string $name Person name.
 * @return string
 */
function lakeuden_kauppaseura_person_initials( $name ) {
	if ( lakeuden_kauppaseura_people_value_is_placeholder( $name ) ) {
		return 'LK';
	}

	$words = preg_split( '/[\s\-]+/u', trim( wp_strip_all_tags( $name ) ) );
	$words = is_array( $words ) ? array_values( array_filter( $words ) ) : array();
	if ( ! $words ) {
		return 'LK';
	}

	$selected = 1 === count( $words ) ? array( $words[0] ) : array( $words[0], $words[ count( $words ) - 1 ] );
	$initials = '';
	foreach ( $selected as $word ) {
		$initials .= function_exists( 'mb_substr' ) ? mb_substr( $word, 0, 1, 'UTF-8' ) : substr( $word, 0, 1 );
	}

	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $initials, 'UTF-8' ) : strtoupper( $initials );
}

/**
 * Render a portrait or accessible neutral monogram.
 *
 * @param WP_Post $post    Person record.
 * @param string  $context Board or testimonial context.
 * @return string
 */
function lakeuden_kauppaseura_render_person_portrait( $post, $context ) {
	$name          = get_the_title( $post );
	$thumbnail_id  = get_post_thumbnail_id( $post );
	$organization  = get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_ORGANIZATION, true );
	$role_meta_key = 'board' === $context ? Lakeuden_Kauppaseura_People::META_BOARD_ROLE : Lakeuden_Kauppaseura_People::META_PROFESSIONAL_ROLE;
	$role          = get_post_meta( $post->ID, $role_meta_key, true );
	$alt_parts     = array_filter( array( $name, $role, $organization ) );
	$fallback_alt  = implode( ', ', $alt_parts );
	$alt           = function_exists( 'lakeuden_kauppaseura_attachment_alt' )
		? lakeuden_kauppaseura_attachment_alt( $thumbnail_id, $fallback_alt )
		: $fallback_alt;

	if ( $thumbnail_id ) {
		return wp_get_attachment_image(
			$thumbnail_id,
			'medium_large',
			false,
			array(
				'class'    => 'lks-person-portrait__image',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'alt'      => $alt,
			)
		);
	}

	// The initials repeat the adjacent visible name and are therefore decorative.
	return '<span class="lks-person-avatar" aria-hidden="true"><span>' . esc_html( lakeuden_kauppaseura_person_initials( $name ) ) . '</span></span>';
}

/**
 * Render a short editor-authored text without permitting heading drift.
 *
 * @param string $content Post content.
 * @return string
 */
function lakeuden_kauppaseura_render_person_text( $content ) {
	$text = trim( wp_strip_all_tags( strip_shortcodes( $content ) ) );

	return '' === $text ? '' : wpautop( esc_html( $text ) );
}

/**
 * Render one reusable board-member card.
 *
 * @param WP_Post $post Board member.
 * @return string
 */
function lakeuden_kauppaseura_render_board_member_card( $post ) {
	$name           = get_the_title( $post );
	$role           = get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_BOARD_ROLE, true );
	$organization   = get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_ORGANIZATION, true );
	$email          = get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_EMAIL, true );
	$telephone      = get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_TELEPHONE, true );
	$show_email     = '1' === (string) get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_SHOW_EMAIL, true ) && is_email( $email );
	$show_telephone = '1' === (string) get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_SHOW_TELEPHONE, true ) && '' !== trim( $telephone );
	$is_placeholder = lakeuden_kauppaseura_person_has_placeholder( $post );
	$phone_target   = preg_replace( '/[^0-9+]/', '', $telephone );

	ob_start();
	?>
	<article class="lks-person-card lks-board-member-card"<?php echo $is_placeholder ? ' data-lks-person-placeholder="true"' : ''; ?>>
		<div class="lks-person-portrait"><?php echo lakeuden_kauppaseura_render_person_portrait( $post, 'board' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<div class="lks-person-card__body">
			<?php if ( '' !== trim( $role ) ) : ?><p class="lks-person-card__eyebrow"><?php echo esc_html( $role ); ?></p><?php endif; ?>
			<h3><?php echo esc_html( $name ); ?></h3>
			<?php if ( '' !== trim( $organization ) ) : ?><p class="lks-person-card__organization"><?php echo esc_html( $organization ); ?></p><?php endif; ?>
			<div class="lks-person-card__introduction"><?php echo lakeuden_kauppaseura_render_person_text( $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php if ( $show_email || $show_telephone ) : ?>
				<ul class="lks-person-card__contacts" aria-label="<?php echo esc_attr( $name . ': yhteystiedot' ); ?>">
					<?php if ( $show_email ) : ?><li><a href="<?php echo esc_url( 'mailto:' . $email ); ?>">Sähköposti</a></li><?php endif; ?>
					<?php if ( $show_telephone && $phone_target ) : ?><li><a href="<?php echo esc_url( 'tel:' . $phone_target ); ?>"><?php echo esc_html( $telephone ); ?></a></li><?php endif; ?>
				</ul>
			<?php endif; ?>
		</div>
	</article>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render the board-member card collection.
 *
 * @return string
 */
function lakeuden_kauppaseura_render_board_members() {
	$members = lakeuden_kauppaseura_people_for_display(
		Lakeuden_Kauppaseura_People::BOARD_POST_TYPE,
		'about_board_enabled'
	);
	if ( ! $members ) {
		return '';
	}

	ob_start();
	?>
	<div class="lks-about-board__members">
		<?php foreach ( $members as $member ) : ?>
			<?php echo lakeuden_kauppaseura_render_board_member_card( $member ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endforeach; ?>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render one reusable member-testimonial card.
 *
 * @param WP_Post $post Testimonial.
 * @return string
 */
function lakeuden_kauppaseura_render_member_testimonial_card( $post ) {
	$name              = get_the_title( $post );
	$organization      = get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_ORGANIZATION, true );
	$professional_role = get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_PROFESSIONAL_ROLE, true );
	$profile_url       = get_post_meta( $post->ID, Lakeuden_Kauppaseura_People::META_PROFILE_URL, true );
	$is_placeholder    = lakeuden_kauppaseura_person_has_placeholder( $post );

	ob_start();
	?>
	<article class="lks-person-card lks-member-testimonial-card"<?php echo $is_placeholder ? ' data-lks-testimonial-placeholder="true" data-lks-person-placeholder="true"' : ''; ?>>
		<div class="lks-person-portrait"><?php echo lakeuden_kauppaseura_render_person_portrait( $post, 'testimonial' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<div class="lks-person-card__body">
			<blockquote><?php echo lakeuden_kauppaseura_render_person_text( $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></blockquote>
			<div class="lks-member-testimonial-card__identity">
				<h3><?php echo esc_html( $name ); ?></h3>
				<?php if ( '' !== trim( $organization ) ) : ?><p><?php echo esc_html( $organization ); ?></p><?php endif; ?>
				<?php if ( '' !== trim( $professional_role ) ) : ?><p><?php echo esc_html( $professional_role ); ?></p><?php endif; ?>
			</div>
			<?php if ( $profile_url ) : ?>
				<a class="lks-text-link" href="<?php echo esc_url( $profile_url ); ?>" target="_blank" rel="noopener noreferrer">Tutustu profiiliin <span class="screen-reader-text">(avautuu uuteen välilehteen)</span><span aria-hidden="true">&nearr;</span></a>
			<?php endif; ?>
		</div>
	</article>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render up to three published testimonials, with no minimum count.
 *
 * @param int $limit Maximum number of cards.
 * @return string
 */
function lakeuden_kauppaseura_render_member_testimonials( $limit = 3 ) {
	$testimonials = lakeuden_kauppaseura_people_for_display(
		Lakeuden_Kauppaseura_People::TESTIMONIAL_POST_TYPE,
		'join_testimonials_enabled',
		max( 1, absint( $limit ) )
	);
	if ( ! $testimonials ) {
		return '';
	}

	ob_start();
	?>
	<div class="lks-membership-testimonials__grid">
		<?php foreach ( $testimonials as $testimonial ) : ?>
			<?php echo lakeuden_kauppaseura_render_member_testimonial_card( $testimonial ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endforeach; ?>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Reusable board grid shortcode.
 *
 * @return string
 */
function lakeuden_kauppaseura_board_members_shortcode() {
	return lakeuden_kauppaseura_render_board_members();
}
add_shortcode( 'lks_board_members', 'lakeuden_kauppaseura_board_members_shortcode' );

/**
 * Reusable testimonial grid shortcode.
 *
 * @param array<string,mixed> $attributes Shortcode attributes.
 * @return string
 */
function lakeuden_kauppaseura_member_testimonials_shortcode( $attributes ) {
	$attributes = shortcode_atts( array( 'limit' => 3 ), $attributes, 'lks_member_testimonials' );

	return lakeuden_kauppaseura_render_member_testimonials( (int) $attributes['limit'] );
}
add_shortcode( 'lks_member_testimonials', 'lakeuden_kauppaseura_member_testimonials_shortcode' );
