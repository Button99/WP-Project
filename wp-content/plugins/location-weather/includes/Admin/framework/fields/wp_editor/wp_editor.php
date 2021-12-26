<?php
/**
 *  Framework wp_editor field file.
 *
 * @package    Location_weather
 * @subpackage Location_weather/framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

if ( ! class_exists( 'SPLWT_Field_wp_editor' ) ) {
	/**
	 *
	 * Field: wp_editor
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class SPLWT_Field_wp_editor extends SPLWT_Fields {

		/**
		 * Column field constructor.
		 *
		 * @param array  $field The field type.
		 * @param string $value The values of the field.
		 * @param string $unique The unique ID for the field.
		 * @param string $where To where show the output CSS.
		 * @param string $parent The parent args.
		 */
		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
			parent::__construct( $field, $value, $unique, $where, $parent );
		}

		/**
		 * Render field
		 *
		 * @return void
		 */
		public function render() {

			$args = wp_parse_args(
				$this->field,
				array(
					'tinymce'       => true,
					'quicktags'     => true,
					'media_buttons' => true,
					'height'        => '',
				)
			);

			$attributes = array(
				'rows'         => 10,
				'class'        => 'wp-editor-area',
				'autocomplete' => 'off',
			);

			$editor_height = ( ! empty( $args['height'] ) ) ? ' style="height:' . esc_attr( $args['height'] ) . ';"' : '';

			$editor_settings = array(
				'tinymce'       => $args['tinymce'],
				'quicktags'     => $args['quicktags'],
				'media_buttons' => $args['media_buttons'],
			);

			echo $this->field_before(); // phpcs:ignore

			echo ( splwt_wp_editor_api() ) ? '<div class="splwt-lite-wp-editor" data-editor-settings="' . esc_attr( wp_json_encode( $editor_settings ) ) . '">' : '';

			echo '<textarea name="' . esc_attr( $this->field_name() ) . '"' . $this->field_attributes( $attributes ) . $editor_height . '>' . wp_kses_post( $this->value ) . '</textarea>'; // phpcs:ignore

			echo ( splwt_wp_editor_api() ) ? '</div>' : '';

			echo $this->field_after();// phpcs:ignore

		}

		/**
		 * Enqueue function
		 *
		 * @return void
		 */
		public function enqueue() {

			if ( splwt_wp_editor_api() && function_exists( 'wp_enqueue_editor' ) ) {

				wp_enqueue_editor();

				$this->setup_wp_editor_settings();

				add_action( 'print_default_editor_scripts', array( &$this, 'setup_wp_editor_media_buttons' ) );

			}

		}

		/**
		 * Setup wp editor media buttons
		 *
		 * @return void
		 */
		public function setup_wp_editor_media_buttons() {

			ob_start();
			echo '<div class="wp-media-buttons">';
			do_action( 'media_buttons' );
			echo '</div>';
			$media_buttons = ob_get_clean();

			echo '<script type="text/javascript">';
			echo 'var splwt_media_buttons = ' . wp_json_encode( $media_buttons ) . ';';
			echo '</script>';

		}

		/**
		 * Setup wp editor settings
		 *
		 * @return void
		 */
		public function setup_wp_editor_settings() {

			if ( splwt_wp_editor_api() && class_exists( '_WP_Editors' ) ) {

				$defaults = apply_filters(
					'splwt_wp_editor',
					array(
						'tinymce' => array(
							'wp_skip_init' => true,
						),
					)
				);

				$setup = _WP_Editors::parse_settings( 'splwt_wp_editor', $defaults );

				_WP_Editors::editor_settings( 'splwt_wp_editor', $setup );

			}

		}

	}
}
