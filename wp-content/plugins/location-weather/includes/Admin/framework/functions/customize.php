<?php
/**
 *  Framework customize file.
 *
 * @package    Location_weather
 * @subpackage Location_weather/framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.
/**
 *
 * WP Customize custom panel
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! class_exists( 'WP_Customize_Panel_SPLWT' ) && class_exists( 'WP_Customize_Panel' ) ) {
	/**
	 *
	 * WP Customize custom panel
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class WP_Customize_Panel_SPLWT extends WP_Customize_Panel {

		/**
		 * Post type.
		 *
		 * @var string
		 */
		public $type = 'splwt-lite';
	}
}

/**
 *
 * WP Customize custom section
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! class_exists( 'WP_Customize_Section_SPLWT' ) && class_exists( 'WP_Customize_Section' ) ) {
	/**
	 *
	 * WP Customize custom section
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class WP_Customize_Section_SPLWT extends WP_Customize_Section {

		/**
		 * Post type.
		 *
		 * @var string
		 */
		public $type = 'splwt-lite';
	}
}

/**
 *
 * WP Customize custom control
 *
 * @since 1.0.0
 * @version 1.0.0
 */
if ( ! class_exists( 'WP_Customize_Control_SPLWT' ) && class_exists( 'WP_Customize_Control' ) ) {
	/**
	 *
	 * WP Customize custom control
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class WP_Customize_Control_SPLWT extends WP_Customize_Control {

		/**
		 * Post type.
		 *
		 * @var string
		 */
		public $type = 'splwt-lite';
		/**
		 * Post field.
		 *
		 * @var string
		 */
		public $field = '';
		/**
		 * Post unique id.
		 *
		 * @var string
		 */
		public $unique = '';

		/**
		 * Render function.
		 *
		 * @return void
		 */
		protected function render() {

			$depend  = '';
			$visible = '';

			if ( ! empty( $this->field['dependency'] ) ) {

				$dependency      = $this->field['dependency'];
				$depend_visible  = '';
				$data_controller = '';
				$data_condition  = '';
				$data_value      = '';
				$data_global     = '';

				if ( is_array( $dependency[0] ) ) {
					$data_controller = implode( '|', array_column( $dependency, 0 ) );
					$data_condition  = implode( '|', array_column( $dependency, 1 ) );
					$data_value      = implode( '|', array_column( $dependency, 2 ) );
					$data_global     = implode( '|', array_column( $dependency, 3 ) );
					$depend_visible  = implode( '|', array_column( $dependency, 4 ) );
				} else {
					$data_controller = ( ! empty( $dependency[0] ) ) ? $dependency[0] : '';
					$data_condition  = ( ! empty( $dependency[1] ) ) ? $dependency[1] : '';
					$data_value      = ( ! empty( $dependency[2] ) ) ? $dependency[2] : '';
					$data_global     = ( ! empty( $dependency[3] ) ) ? $dependency[3] : '';
					$depend_visible  = ( ! empty( $dependency[4] ) ) ? $dependency[4] : '';
				}

				$depend .= ' data-controller="' . esc_attr( $data_controller ) . '"';
				$depend .= ' data-condition="' . esc_attr( $data_condition ) . '"';
				$depend .= ' data-value="' . esc_attr( $data_value ) . '"';
				$depend .= ( ! empty( $data_global ) ) ? ' data-depend-global="true"' : '';

				$visible  = ' splwt-lite-dependency-control';
				$visible .= ( ! empty( $depend_visible ) ) ? ' splwt-lite-depend-visible' : ' splwt-lite-depend-hidden';

			}

			$id    = 'customize-control-' . str_replace( array( '[', ']' ), array( '-', '' ), $this->id );
			$class = 'customize-control customize-control-' . $this->type . $visible;

			echo '<li id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '"' . wp_kses_post( $depend ) . '>';
			$this->render_content();
			echo '</li>';

		}

		/**
		 * Render content function.
		 *
		 * @return void
		 */
		public function render_content() {

			$complex = array(
				'accordion',
				'background',
				'border',
				'button_set',
				'checkbox',
				'color_group',
				'date',
				'dimensions',
				'fieldset',
				'group',
				'image_select',
				'link_color',
				'media',
				'palette',
				'repeater',
				'sortable',
				'sorter',
				'spacing',
				'switcher',
				'tabbed',
				'typography',
			);

			$field_id   = ( ! empty( $this->field['id'] ) ) ? $this->field['id'] : '';
			$custom     = ( ! empty( $this->field['customizer'] ) ) ? true : false;
			$is_complex = ( in_array( $this->field['type'], $complex, true ) ) ? true : false;
			$class      = ( $is_complex || $custom ) ? ' splwt-lite-customize-complex' : '';
			$atts       = ( $is_complex || $custom ) ? ' data-unique-id="' . esc_attr( $this->unique ) . '" data-option-id="' . esc_attr( $field_id ) . '"' : '';

			if ( ! $is_complex && ! $custom ) {
				$this->field['attributes']['data-customize-setting-link'] = $this->settings['default']->id;
			}

			$this->field['name'] = $this->settings['default']->id;

			$this->field['dependency'] = array();

			echo '<div class="splwt-lite-customize-field' . esc_attr( $class ) . '"' . wp_kses_post( $atts ) . '>';

			SPLW::field( $this->field, $this->value(), $this->unique, 'customize' );

			echo '</div>';

		}

	}
}

