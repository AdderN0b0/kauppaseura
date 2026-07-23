<?php
/**
 * Instagram feed widget.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LKS_Instagram_Feed_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'lks_instagram_feed_widget',
			'LKS Instagram Feed',
			array(
				'description' => 'Displays the cached Lakeuden Kauppaseura Instagram feed.',
			)
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$limit = ! empty( $instance['limit'] ) ? absint( $instance['limit'] ) : 4;

		echo wp_kses_post( $args['before_widget'] );

		if ( '' !== $title ) {
			echo wp_kses_post( $args['before_title'] . esc_html( $title ) . $args['after_title'] );
		}

		echo LKS_Instagram_Feed::render_feed( $limit ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : 'Instagram';
		$limit = isset( $instance['limit'] ) ? absint( $instance['limit'] ) : 4;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>">Number of images</label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" min="1" max="12" value="<?php echo esc_attr( $limit ); ?>" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'limit' => isset( $new_instance['limit'] ) ? min( 12, max( 1, absint( $new_instance['limit'] ) ) ) : 4,
		);
	}
}
