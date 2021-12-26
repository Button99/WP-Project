<?php
/**
 *  Framework switcher field file.
 *
 * @package    Location_weather
 * @subpackage Location_weather/framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

if ( ! class_exists( 'SPLWT_Field_switcher' ) ) {
	/**
	 *
	 * Field: switcher
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class SPLWT_Field_switcher extends SPLWT_Fields {

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

			$active     = ( ! empty( $this->value ) ) ? ' splwt-lite--active' : '';
			$text_on    = ( ! empty( $this->field['text_on'] ) ) ? $this->field['text_on'] : esc_html__( 'On', 'location-weather' );
			$text_off   = ( ! empty( $this->field['text_off'] ) ) ? $this->field['text_off'] : esc_html__( 'Off', 'location-weather' );
			$text_width = ( ! empty( $this->field['text_width'] ) ) ? ' style="width: ' . esc_attr( $this->field['text_width'] ) . 'px;"' : '';

			echo $this->field_before(); // phpcs:ignore

			echo '<div class="splwt-lite--switcher' . esc_attr( $active ) . '"' . $text_width . '>'; // phpcs:ignore
			echo '<span class="splwt-lite--on">' . esc_attr( $text_on ) . '</span>';
			echo '<span class="splwt-lite--off">' . esc_attr( $text_off ) . '</span>';
			echo '<span class="splwt-lite--ball"></span>';
			echo '<input type="text" name="' . esc_attr( $this->field_name() ) . '" value="' . esc_attr( $this->value ) . '"' . $this->field_attributes() . ' />'; // phpcs:ignore
			echo '</div>';

			echo ( ! empty( $this->field['label'] ) ) ? '<span class="splwt-lite--label">' . esc_attr( $this->field['label'] ) . '</span>' : '';

			echo $this->field_after(); // phpcs:ignore

		}

	}
}
