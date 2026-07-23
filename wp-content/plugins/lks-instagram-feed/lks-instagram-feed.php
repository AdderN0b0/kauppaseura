<?php
/**
 * Plugin Name: LKS Instagram Feed
 * Description: Cached Instagram media feed for Lakeuden Kauppaseura.
 * Version: 2.0.0
 * Author: OpenAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LKS_Instagram_Feed {
	const OPTION_KEY          = 'lks_instagram_feed_settings';
	const LAST_GOOD_OPTION    = 'lks_instagram_feed_last_good';
	const TRANSIENT_PREFIX    = 'lks_instagram_feed_';
	const PROFILE_URL         = 'https://www.instagram.com/lakeudenkauppaseura/';
	const API_HOST            = 'https://graph.instagram.com';
	const DEFAULT_API_VERSION = 'v25.0';
	const CRON_HOOK           = 'lks_instagram_refresh_access_token';
	const REFRESH_AFTER       = 25 * DAY_IN_SECONDS;

	public static function init() {
		add_shortcode( 'lks_instagram_feed', array( __CLASS__, 'render_shortcode' ) );
		add_filter( 'render_block', array( __CLASS__, 'replace_static_strip_block' ), 10, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_widget' ) );
		add_action( 'init', array( __CLASS__, 'ensure_refresh_schedule' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'refresh_access_token' ) );
	}

	public static function activate() {
		self::ensure_refresh_schedule();
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	public static function ensure_refresh_schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit' => 4,
				'class' => '',
			),
			$atts,
			'lks_instagram_feed'
		);

		return self::render_feed( absint( $atts['limit'] ), sanitize_html_class( $atts['class'] ) );
	}

	public static function replace_static_strip_block( $block_content, $block ) {
		if ( is_admin() || empty( $block['attrs']['className'] ) ) {
			return $block_content;
		}

		$class_name = (string) $block['attrs']['className'];

		if ( false === strpos( $class_name, 'lks-instagram-strip' ) ) {
			return $block_content;
		}

		return self::render_feed( 4, 'lks-instagram-strip' );
	}

	public static function register_settings_page() {
		add_options_page(
			'LKS Instagram Feed',
			'LKS Instagram Feed',
			'manage_options',
			'lks-instagram-feed',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'lks_instagram_feed',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::default_settings(),
			)
		);
	}

	public static function sanitize_settings( $input ) {
		$old   = self::get_stored_settings();
		$input = is_array( $input ) ? $input : array();

		$settings = array_merge(
			self::default_settings(),
			$old,
			array(
				'profile_url'   => isset( $input['profile_url'] ) ? esc_url_raw( $input['profile_url'] ) : self::PROFILE_URL,
				'api_version'   => isset( $input['api_version'] ) ? sanitize_text_field( $input['api_version'] ) : self::DEFAULT_API_VERSION,
				'cache_minutes' => isset( $input['cache_minutes'] ) ? absint( $input['cache_minutes'] ) : 120,
			)
		);

		if ( ! preg_match( '/^v\d+\.\d+$/', $settings['api_version'] ) ) {
			$settings['api_version'] = self::DEFAULT_API_VERSION;
		}

		if ( '' === $settings['profile_url'] ) {
			$settings['profile_url'] = self::PROFILE_URL;
		}

		$settings['cache_minutes'] = min( 1440, max( 15, $settings['cache_minutes'] ) );

		$new_token = isset( $input['access_token'] ) ? trim( (string) $input['access_token'] ) : '';

		if ( '' !== $new_token ) {
			$new_token = preg_replace( '/\s+/', '', $new_token );
			$profile   = self::request_profile( $new_token, $settings['api_version'] );

			if ( is_wp_error( $profile ) ) {
				add_settings_error(
					self::OPTION_KEY,
					'lks_instagram_token_invalid',
					'Instagram connection failed: ' . $profile->get_error_message(),
					'error'
				);
			} else {
				$settings['access_token']      = $new_token;
				$settings['ig_user_id']        = $profile['user_id'];
				$settings['username']          = $profile['username'];
				$settings['token_saved_at']    = time();
				$settings['token_refreshed_at'] = 0;
				$settings['last_checked']      = time();
				$settings['last_error']        = '';
				$settings['refresh_error']     = '';
				$settings['cache_buster']      = absint( $settings['cache_buster'] ) + 1;

				delete_option( self::LAST_GOOD_OPTION );

				add_settings_error(
					self::OPTION_KEY,
					'lks_instagram_connected',
					sprintf( 'Instagram connected successfully to @%s.', $profile['username'] ),
					'success'
				);
			}
		}

		return $settings;
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings       = self::get_settings();
		$access_token   = self::get_access_token( $settings );
		$ig_user_id     = self::get_ig_user_id( $settings );
		$is_connected   = '' !== $access_token && '' !== $ig_user_id;
		$username       = isset( $settings['username'] ) ? (string) $settings['username'] : '';
		$last_checked   = ! empty( $settings['last_checked'] ) ? wp_date( 'j.n.Y H:i', absint( $settings['last_checked'] ) ) : '';
		$last_refreshed = ! empty( $settings['token_refreshed_at'] ) ? wp_date( 'j.n.Y H:i', absint( $settings['token_refreshed_at'] ) ) : '';
		?>
		<div class="wrap">
			<h1>LKS Instagram Feed</h1>
			<?php settings_errors( self::OPTION_KEY ); ?>

			<div style="max-width: 760px; margin: 18px 0; padding: 18px 20px; border: 1px solid <?php echo $is_connected ? '#8cbd8c' : '#dcdcde'; ?>; border-left-width: 4px; background: #fff;">
				<?php if ( $is_connected ) : ?>
					<p style="margin: 0 0 6px;"><strong style="color: #147a36;">Connected</strong><?php echo $username ? ' to @' . esc_html( $username ) : ''; ?></p>
					<p style="margin: 0; color: #50575e;">
						Account ID: <?php echo esc_html( $ig_user_id ); ?>
						<?php if ( $last_checked ) : ?> &middot; Last checked: <?php echo esc_html( $last_checked ); ?><?php endif; ?>
						<?php if ( $last_refreshed ) : ?> &middot; Token refreshed: <?php echo esc_html( $last_refreshed ); ?><?php endif; ?>
					</p>
					<?php if ( ! empty( $settings['last_error'] ) ) : ?>
						<p style="margin: 8px 0 0; color: #b32d2e;">Latest feed check: <?php echo esc_html( $settings['last_error'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $settings['refresh_error'] ) ) : ?>
						<p style="margin: 8px 0 0; color: #b32d2e;">Automatic token refresh: <?php echo esc_html( $settings['refresh_error'] ); ?></p>
					<?php endif; ?>
				<?php else : ?>
					<p style="margin: 0;"><strong>Not connected yet.</strong> Paste the access token generated in the Meta dashboard below.</p>
				<?php endif; ?>
			</div>

			<p style="max-width: 760px;">
				The token stays on the WordPress server and is never sent to site visitors. The plugin detects the Instagram account automatically, caches the feed, and attempts to refresh a long-lived token before it expires.
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'lks_instagram_feed' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="lks-instagram-token">Instagram access token</label></th>
						<td>
							<input id="lks-instagram-token" class="large-text" type="password" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[access_token]" value="" autocomplete="new-password" spellcheck="false" />
							<p class="description">
								<?php echo $access_token ? 'A token is currently saved. Leave this empty unless you are replacing it.' : 'Paste the complete token from Meta. It will not be shown again after saving.'; ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="lks-instagram-profile-url">Profile URL</label></th>
						<td>
							<input id="lks-instagram-profile-url" class="regular-text" type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[profile_url]" value="<?php echo esc_attr( $settings['profile_url'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="lks-instagram-cache">Cache duration</label></th>
						<td>
							<input id="lks-instagram-cache" class="small-text" type="number" min="15" max="1440" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[cache_minutes]" value="<?php echo esc_attr( $settings['cache_minutes'] ); ?>" /> minutes
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="lks-instagram-api-version">Instagram Graph API version</label></th>
						<td>
							<input id="lks-instagram-api-version" class="small-text" type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[api_version]" value="<?php echo esc_attr( $settings['api_version'] ); ?>" />
							<p class="description">Advanced setting. Change only when upgrading the Meta API version.</p>
						</td>
					</tr>
				</table>
				<?php submit_button( $is_connected ? 'Save settings' : 'Connect Instagram' ); ?>
			</form>

			<h2>Usage</h2>
			<p>Shortcode: <code>[lks_instagram_feed limit="4"]</code></p>
			<p>The homepage already uses this feed and will update automatically when new Instagram posts are published.</p>
		</div>
		<?php
	}

	public static function register_widget() {
		if ( class_exists( 'WP_Widget' ) ) {
			if ( ! class_exists( 'LKS_Instagram_Feed_Widget' ) ) {
				require_once __DIR__ . '/class-lks-instagram-feed-widget.php';
			}

			register_widget( 'LKS_Instagram_Feed_Widget' );
		}
	}

	public static function render_feed( $limit = 4, $extra_class = '' ) {
		$limit    = min( 12, max( 1, absint( $limit ) ) );
		$settings = self::get_settings();
		$result   = self::get_media_items( $limit );
		$is_live  = ! is_wp_error( $result );
		$items    = $is_live ? $result : self::get_fallback_items();
		$classes  = array_filter(
			array(
				'lks-instagram-feed',
				$extra_class,
				$is_live ? 'is-live' : 'is-fallback',
			)
		);

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<?php foreach ( array_slice( $items, 0, $limit ) as $item_index => $item ) : ?>
				<?php
				$permalink = ! empty( $item['permalink'] ) ? $item['permalink'] : $settings['profile_url'];
				$label     = ! empty( $item['media_type'] ) && 'VIDEO' === $item['media_type'] ? 'Lakeuden Kauppaseuran Instagram-video' : 'Lakeuden Kauppaseuran Instagram-julkaisu';
				$alt       = isset( $item['alt'] ) ? trim( wp_strip_all_tags( (string) $item['alt'] ) ) : '';
				if ( '' === $alt || 'Lakeuden Kauppaseura Instagram image' === $alt ) {
					$alt = $label;
				}
				$is_lcp    = 0 === $item_index && false !== strpos( $extra_class, 'lks-events-hero__gallery' );
				?>
				<figure class="lks-instagram-item">
					<a class="lks-instagram-media" href="<?php echo esc_url( $permalink ); ?>" target="_blank" rel="noopener noreferrer">
						<img loading="<?php echo $is_lcp ? 'eager' : 'lazy'; ?>" decoding="async"<?php echo $is_lcp ? ' fetchpriority="high"' : ''; ?> src="<?php echo esc_url( $item['image_url'] ); ?>" alt="<?php echo esc_attr( $alt ); ?>" />
						<span class="screen-reader-text">(avautuu uuteen välilehteen)</span>
					</a>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php if ( ! $is_live && current_user_can( 'manage_options' ) ) : ?>
			<p class="lks-feed-admin-note">
				Instagram is showing fallback images. Connect the account in Settings &rarr; LKS Instagram Feed.
			</p>
		<?php endif; ?>
		<?php

		return (string) ob_get_clean();
	}

	public static function refresh_access_token() {
		$settings = self::get_settings();

		if ( defined( 'LKS_INSTAGRAM_ACCESS_TOKEN' ) || empty( $settings['access_token'] ) ) {
			return;
		}

		$last_token_update = max( absint( $settings['token_saved_at'] ), absint( $settings['token_refreshed_at'] ) );

		if ( $last_token_update && ( time() - $last_token_update ) < self::REFRESH_AFTER ) {
			return;
		}

		$url = add_query_arg(
			array(
				'grant_type'  => 'ig_refresh_token',
				'access_token' => $settings['access_token'],
			),
			self::API_HOST . '/refresh_access_token'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		$result   = self::decode_response( $response, 'Instagram could not refresh the access token.' );
		$stored   = self::get_stored_settings();

		if ( is_wp_error( $result ) || empty( $result['access_token'] ) ) {
			$stored['refresh_error'] = is_wp_error( $result ) ? $result->get_error_message() : 'Instagram returned no replacement token.';
			update_option( self::OPTION_KEY, $stored, false );
			return;
		}

		$stored['access_token']       = preg_replace( '/\s+/', '', (string) $result['access_token'] );
		$stored['token_refreshed_at'] = time();
		$stored['refresh_error']      = '';
		update_option( self::OPTION_KEY, $stored, false );
	}

	private static function get_media_items( $limit ) {
		$settings     = self::get_settings();
		$access_token = self::get_access_token( $settings );
		$ig_user_id   = self::get_ig_user_id( $settings );

		if ( '' === $access_token ) {
			return new WP_Error( 'lks_instagram_missing_token', 'Instagram is not connected.' );
		}

		if ( '' === $ig_user_id ) {
			$profile = self::request_profile( $access_token, $settings['api_version'] );

			if ( is_wp_error( $profile ) ) {
				return self::get_last_good_or_error( $profile );
			}

			$ig_user_id = $profile['user_id'];
			self::store_detected_profile( $profile );
		}

		$cache_key = self::TRANSIENT_PREFIX . md5(
			implode(
				'|',
				array(
					$settings['api_version'],
					$ig_user_id,
					$limit,
					absint( $settings['cache_buster'] ),
				)
			)
		);
		$cached = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$url = add_query_arg(
			array(
				'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username',
				'limit'  => $limit,
			),
			sprintf(
				'%s/%s/%s/media',
				self::API_HOST,
				rawurlencode( $settings['api_version'] ),
				rawurlencode( $ig_user_id )
			)
		);

		$body = self::api_get( $url, $access_token, 'Instagram feed request failed.' );

		if ( is_wp_error( $body ) || empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
			$error = is_wp_error( $body )
				? $body
				: new WP_Error( 'lks_instagram_empty_response', 'Instagram returned no media.' );
			self::store_connection_error( $error );
			return self::get_last_good_or_error( $error );
		}

		$items = array();

		foreach ( $body['data'] as $media ) {
			if ( ! is_array( $media ) ) {
				continue;
			}

			$image_url = '';

			if ( ! empty( $media['thumbnail_url'] ) ) {
				$image_url = esc_url_raw( $media['thumbnail_url'] );
			} elseif ( ! empty( $media['media_url'] ) ) {
				$image_url = esc_url_raw( $media['media_url'] );
			}

			if ( '' === $image_url || empty( $media['permalink'] ) ) {
				continue;
			}

			$caption = isset( $media['caption'] ) ? wp_strip_all_tags( (string) $media['caption'] ) : '';

			$items[] = array(
				'id'         => isset( $media['id'] ) ? sanitize_text_field( $media['id'] ) : '',
				'image_url'  => $image_url,
				'permalink'  => esc_url_raw( $media['permalink'] ),
				'alt'        => '' !== $caption ? wp_trim_words( $caption, 14, '' ) : 'Lakeuden Kauppaseuran Instagram-julkaisu',
				'media_type' => isset( $media['media_type'] ) ? sanitize_text_field( $media['media_type'] ) : '',
				'timestamp'  => isset( $media['timestamp'] ) ? sanitize_text_field( $media['timestamp'] ) : '',
			);
		}

		if ( empty( $items ) ) {
			$error = new WP_Error( 'lks_instagram_no_usable_media', 'Instagram returned no usable images.' );
			self::store_connection_error( $error );
			return self::get_last_good_or_error( $error );
		}

		set_transient( $cache_key, $items, max( 15, absint( $settings['cache_minutes'] ) ) * MINUTE_IN_SECONDS );
		update_option( self::LAST_GOOD_OPTION, $items, false );
		self::store_connection_success( count( $items ) );

		return $items;
	}

	private static function request_profile( $access_token, $api_version ) {
		$url = add_query_arg(
			array( 'fields' => 'user_id,username' ),
			sprintf( '%s/%s/me', self::API_HOST, rawurlencode( $api_version ) )
		);
		$body = self::api_get( $url, $access_token, 'Instagram could not verify this token.' );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$user_id  = isset( $body['user_id'] ) ? sanitize_text_field( $body['user_id'] ) : '';
		$username = isset( $body['username'] ) ? sanitize_text_field( $body['username'] ) : '';

		if ( '' === $user_id || '' === $username ) {
			return new WP_Error( 'lks_instagram_invalid_profile', 'The token did not return an Instagram professional account.' );
		}

		return array(
			'user_id'  => $user_id,
			'username' => $username,
		);
	}

	private static function api_get( $url, $access_token, $fallback_message ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . $access_token,
				),
			)
		);

		return self::decode_response( $response, $fallback_message );
	}

	private static function decode_response( $response, $fallback_message ) {
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'lks_instagram_http_error', $fallback_message );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 && is_array( $body ) ) {
			return $body;
		}

		$message = $fallback_message;

		if ( is_array( $body ) && ! empty( $body['error']['message'] ) ) {
			$message = sanitize_text_field( $body['error']['message'] );
		} elseif ( is_array( $body ) && ! empty( $body['error_message'] ) ) {
			$message = sanitize_text_field( $body['error_message'] );
		}

		return new WP_Error( 'lks_instagram_api_error', $message );
	}

	private static function store_detected_profile( $profile ) {
		$stored                       = self::get_stored_settings();
		$stored['ig_user_id']         = $profile['user_id'];
		$stored['username']           = $profile['username'];
		$stored['last_checked']       = time();
		$stored['last_error']         = '';
		$stored['cache_buster']       = isset( $stored['cache_buster'] ) ? absint( $stored['cache_buster'] ) + 1 : 2;
		update_option( self::OPTION_KEY, $stored, false );
	}

	private static function store_connection_success( $media_count ) {
		$stored                      = self::get_stored_settings();
		$stored['last_checked']      = time();
		$stored['last_error']        = '';
		$stored['last_media_count']  = absint( $media_count );
		update_option( self::OPTION_KEY, $stored, false );
	}

	private static function store_connection_error( $error ) {
		$stored                 = self::get_stored_settings();
		$stored['last_checked'] = time();
		$stored['last_error']   = is_wp_error( $error ) ? $error->get_error_message() : 'Unknown Instagram error.';
		update_option( self::OPTION_KEY, $stored, false );
	}

	private static function get_last_good_or_error( $error ) {
		$last_good = get_option( self::LAST_GOOD_OPTION );

		if ( is_array( $last_good ) && ! empty( $last_good ) ) {
			return $last_good;
		}

		return $error;
	}

	private static function get_stored_settings() {
		$settings = get_option( self::OPTION_KEY, array() );
		return is_array( $settings ) ? $settings : array();
	}

	private static function get_settings() {
		return array_merge( self::default_settings(), self::get_stored_settings() );
	}

	private static function default_settings() {
		return array(
			'profile_url'        => self::PROFILE_URL,
			'ig_user_id'         => '',
			'username'           => '',
			'access_token'       => '',
			'api_version'        => defined( 'LKS_INSTAGRAM_API_VERSION' ) ? (string) LKS_INSTAGRAM_API_VERSION : self::DEFAULT_API_VERSION,
			'cache_minutes'      => 120,
			'cache_buster'       => 1,
			'token_saved_at'     => 0,
			'token_refreshed_at' => 0,
			'last_checked'       => 0,
			'last_error'         => '',
			'refresh_error'      => '',
			'last_media_count'   => 0,
		);
	}

	private static function get_ig_user_id( $settings ) {
		if ( defined( 'LKS_INSTAGRAM_USER_ID' ) && '' !== (string) LKS_INSTAGRAM_USER_ID ) {
			return trim( (string) LKS_INSTAGRAM_USER_ID );
		}

		return isset( $settings['ig_user_id'] ) ? trim( (string) $settings['ig_user_id'] ) : '';
	}

	private static function get_access_token( $settings ) {
		if ( defined( 'LKS_INSTAGRAM_ACCESS_TOKEN' ) && '' !== (string) LKS_INSTAGRAM_ACCESS_TOKEN ) {
			return trim( (string) LKS_INSTAGRAM_ACCESS_TOKEN );
		}

		return isset( $settings['access_token'] ) ? trim( (string) $settings['access_token'] ) : '';
	}

	private static function get_fallback_items() {
		$profile_url = self::get_settings()['profile_url'];
		$base_url    = content_url( 'uploads/2026/06/' );

		return array(
			array(
				'image_url'  => $base_url . '3c1d1a_9ae29c5d39f04fa8adbfc565a9312fd1mv2.jpg',
				'permalink'  => $profile_url,
				'alt'        => 'Etelä-Pohjanmaan lakeus',
				'media_type' => 'IMAGE',
			),
			array(
				'image_url'  => $base_url . '3c1d1a_acb109394a6e43c986b9d450bca58908mv2.png',
				'permalink'  => $profile_url,
				'alt'        => 'Keskustelu Lakeuden Kauppaseuran tapahtumassa',
				'media_type' => 'IMAGE',
			),
			array(
				'image_url'  => $base_url . '3c1d1a_e84053badb3047eea17cb3794dd55171mv2.png',
				'permalink'  => $profile_url,
				'alt'        => 'Lakeuden Kauppaseuran ajankohtaisia aiheita',
				'media_type' => 'IMAGE',
			),
			array(
				'image_url'  => $base_url . '3c1d1a_b91dd6f0cada4463975398082e5346cfmv2-1024x768.jpg',
				'permalink'  => $profile_url,
				'alt'        => 'Lakeuden Kauppaseuran kokoontuminen',
				'media_type' => 'IMAGE',
			),
		);
	}
}

LKS_Instagram_Feed::init();
register_activation_hook( __FILE__, array( 'LKS_Instagram_Feed', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LKS_Instagram_Feed', 'deactivate' ) );
