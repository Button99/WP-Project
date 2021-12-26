<?php
/**
 *  Framework widget-options.class file.
 *
 * @package    Location_weather
 * @subpackage Location_weather/framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

if ( ! class_exists( 'SPLWT_Widget' ) ) {
	/**
	 *
	 * Widgets Class
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class SPLWT_Widget extends WP_Widget {

		/**
		 * Unique
		 *
		 * @var string
		 */
		public $unique = '';
		/**
		 * Args
		 *
		 * @var array
		 */
		public $args = array(
			'title'       => '',
			'classname'   => '',
			'description' => '',
			'width'       => '',
			'class'       => '',
			'fields'      => array(),
			'defaults'    => array(),
		);

		/**
		 * Run metabox construct
		 *
		 * @param  string $key key.
		 * @param  array  $params params.
		 * @return void
		 */
		public function __construct( $key, $params ) {

			$widget_ops  = array();
			$control_ops = array();

			$this->unique = $key;
			$this->args   = apply_filters( "splwt_{$this->unique}_args", wp_parse_args( $params, $this->args ), $this );

			// Set control options.
			if ( ! empty( $this->args['width'] ) ) {
				$control_ops['width'] = esc_attr( $this->args['width'] );
			}

			// Set widget options.
			if ( ! empty( $this->args['description'] ) ) {
				$widget_ops['description'] = esc_attr( $this->args['description'] );
			}

			if ( ! empty( $this->args['classname'] ) ) {
				$widget_ops['classname'] = esc_attr( $this->args['classname'] );
			}

			// Set filters.
			$widget_ops  = apply_filters( "splwt_{$this->unique}_widget_ops", $widget_ops, $this );
			$control_ops = apply_filters( "splwt_{$this->unique}_control_ops", $control_ops, $this );

			parent::__construct( $this->unique, esc_attr( $this->args['title'] ), $widget_ops, $control_ops );

		}

		/**
		 * Instance
		 * Register widget with WordPress
		 *
		 * @param  string $key key.
		 * @param  array  $params params.
		 * @return statement
		 */
		public static function instance( $key, $params = array() ) {
			return new self( $key, $params );
		}

		/**
		 * Front-end display of widget.
		 *
		 * @param array $args widget argument.
		 * @param array $instance widget value.
		 * @return void
		 */
		public function widget( $args, $instance ) {
			call_user_func( $this->unique, $args, $instance );
		}

		/**
		 * Get default value
		 *
		 * @param  mixed $field field.
		 * @return string
		 */
		public function get_default( $field ) {

			$default = ( isset( $field['default'] ) ) ? $field['default'] : '';
			$default = ( isset( $this->args['defaults'][ $field['id'] ] ) ) ? $this->args['defaults'][ $field['id'] ] : $default;

			return $default;

		}

		/**
		 * Get widget value
		 *
		 * @param array $instance widget instance value.
		 * @param array $field widget field.
		 * @return string
		 */
		public function get_widget_value( $instance, $field ) {

			$default = ( isset( $field['id'] ) ) ? $this->get_default( $field ) : '';
			$value   = ( isset( $field['id'] ) && isset( $instance[ $field['id'] ] ) ) ? $instance[ $field['id'] ] : $default;

			return $value;

		}

		/**
		 * Back-end widget form.
		 *
		 * @param array $instance widget form instance value.
		 * @return void
		 */
		public function form( $instance ) {

			if ( ! empty( $this->args['fields'] ) ) {

				$class = ( $this->args['class'] ) ? ' ' . $this->args['class'] : '';

				echo '<div class="splwt-lite splwt-lite-widgets splwt-lite-fields' . esc_attr( $class ) . '">';

				foreach ( $this->args['fields'] as $field ) {

					$field_unique = '';

					if ( ! empty( $field['id'] ) ) {

						$field_unique = 'widget-' . $this->unique . '[' . $this->number . ']';

						if ( 'title' === $field['id'] ) {
							$field['attributes']['id'] = 'widget-' . $this->unique . '-' . $this->number . '-title';
						}

						$field['default'] = $this->get_default( $field );

					}

					SPLW::field( $field, $this->get_widget_value( $instance, $field ), $field_unique );

				}

				echo '</div>';

			}

		}

		/**
		 * Sanitize widget form values as they are saved.
		 *
		 * @param array $new_instance widget update value.
		 * @param array $old_instance widget old value.
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) {

			// auto sanitize.
			foreach ( $this->args['fields'] as $field ) {
				if ( ! empty( $field['id'] ) && ( ! isset( $new_instance[ $field['id'] ] ) || is_null( $new_instance[ $field['id'] ] ) ) ) {
					$new_instance[ $field['id'] ] = '';
				}
			}

			$new_instance = apply_filters( "splwt_{$this->unique}_save", $new_instance, $this->args, $this );

			do_action( "splwt_{$this->unique}_save_before", $new_instance, $this->args, $this );

			return $new_instance;

		}
	}
}
