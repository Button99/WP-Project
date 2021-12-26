<?php
/**
 * The LW_Widget file.
 *
 * @package Location_Weather_Pro
 */

namespace ShapedPlugin\Weather\Admin;

/**
 *  Location Weather Pro Widget class.
 *
 * @since 2.0.0
 */
class LW_Widget extends \WP_Widget {

	/**
	 * Class Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'LW_Widget',
			__( 'Location Weather', 'location-weather' ),
			array(
				'description' => __( 'Display Location Weather.', 'location-weather' ),
			)
		);
	}

	/**
	 * Arguments of the widget.
	 *
	 * @var array $args array
	 */
	public $args = array(
		'before_widget' => '<div class="widget-wrap">',
		'after_widget'  => '</div>',
	);

	/**
	 * Provide the content of the widget.
	 *
	 * @param array $args The arguments.
	 * @param array $instance The instance.
	 */
	public function widget( $args, $instance ) {

		$shortcode_id = isset( $instance['shortcode_id'] ) ? absint( $instance['shortcode_id'] ) : 0;

		if ( ! $shortcode_id ) {
			return;
		}

		echo wp_kses_post( $args['before_widget'] );
		echo do_shortcode( '[location-weather id="' . $shortcode_id . '"]' );
		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options.
	 */
	public function form( $instance ) {
		$shortcodes   = $this->shortcodes_list();
		$shortcode_id = ! empty( $instance['shortcode_id'] ) ? absint( $instance['shortcode_id'] ) : null;

		if ( count( $shortcodes ) > 0 ) {

			echo sprintf( '<p><label>%s</label>', esc_html__( 'Select Shortcode:', 'location-weather' ) );
			echo sprintf( '<select class="widefat" name="%s">', esc_attr( $this->get_field_name( 'shortcode_id' ) ) );
			foreach ( $shortcodes as $shortcode ) {
				$selected = $shortcode->id === $shortcode_id ? 'selected="selected"' : '';
				echo sprintf(
					'<option value="%1$d" %3$s>%2$s</option>',
					esc_attr( $shortcode->id ),
					esc_html( $shortcode->title ),
					wp_kses_post( $selected )
				);
			}
			echo '</select></p>';

		} else {
			echo sprintf(
				'<p>%1$s <a href="' . esc_url( admin_url( 'post-new.php?post_type=location_weather' ) ) . '">%$s</a> %3$s</p>',
				esc_html__( 'You did not generate any weather yet.', 'location-weather' ),
				esc_html__( 'click here', 'location-weather' ),
				esc_html__( 'to generate a new location weather now.', 'location-weather' )
			);
		}
	}

	/**
	 * Processing widget options on save.
	 *
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                 = array();
		$instance['shortcode_id'] = absint( $new_instance['shortcode_id'] );

		return $instance;
	}

	/**
	 * The generated shortcodes list.
	 *
	 * @return array
	 */
	private function shortcodes_list() {
		$shortcodes = get_posts(
			array(
				'post_type'   => 'location_weather',
				'post_status' => 'publish',
			)
		);

		if ( count( $shortcodes ) < 1 ) {
			return array();
		}

		return array_map(
			function ( $shortcode ) {
					return (object) array(
						'id'    => absint( $shortcode->ID ),
						'title' => esc_html( $shortcode->post_title ),
					);
			},
			$shortcodes
		);
	}

}
