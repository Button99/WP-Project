<?php
/**
 *  Framework abstract.class file.
 *
 * @package    Location_weather
 * @subpackage Location_weather/framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

if ( ! class_exists( 'SPLWT_Abstract' ) ) {
	/**
	 *
	 * Abstract Class
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	abstract class SPLWT_Abstract {

		/**
		 * $abstract variable
		 *
		 * @var string
		 */
		public $abstract = '';
		/**
		 * $output_css variable
		 *
		 * @var string
		 */
		public $output_css = '';
		/**
		 * $webfonts variable
		 *
		 * @var string
		 */
		public $webfonts = array();
		/**
		 * $subsets variable
		 *
		 * @var string
		 */
		public $subsets = array();

		/**
		 * Constructor of the class.
		 */
		public function __construct() {

			// Collect output css and typography.
			if ( ! empty( $this->args['output_css'] ) || ! empty( $this->args['enqueue_webfont'] ) ) {
				add_action( 'wp_enqueue_scripts', array( &$this, 'collect_output_css_and_typography' ), 10 );
			}

		}

		/**
		 * Typography css and output css function
		 *
		 * @return void
		 */
		public function collect_output_css_and_typography() {
			$this->recursive_output_css( $this->pre_fields );
		}

		/**
		 * Recursive output CSS.
		 *
		 * @param array $fields Output css fields.
		 * @param array $combine_field Output css combine_field.
		 * @return void
		 */
		public function recursive_output_css( $fields = array(), $combine_field = array() ) {

			if ( ! empty( $fields ) ) {

				foreach ( $fields as $field ) {

					$field_id     = ( ! empty( $field['id'] ) ) ? $field['id'] : '';
					$field_type   = ( ! empty( $field['type'] ) ) ? $field['type'] : '';
					$field_output = ( ! empty( $field['output'] ) ) ? $field['output'] : '';
					$field_check  = ( 'typography' === $field_type || $field_output ) ? true : false;

					if ( $field_type && $field_id ) {

						SPLW::maybe_include_field( $field_type );

						$class_name = 'SPLWT_Field_' . $field_type;

						if ( 'fieldset' === $field_type ) {
							if ( ! empty( $field['fields'] ) ) {
								$this->recursive_output_css( $field['fields'], $field );
							}
						}

						if ( 'accordion' === $field_type ) {
							if ( ! empty( $field['accordions'] ) ) {
								foreach ( $field['accordions'] as $accordion ) {
									$this->recursive_output_css( $accordion['fields'], $field );
								}
							}
						}

						if ( 'tabbed' === $field_type ) {
							if ( ! empty( $field['tabs'] ) ) {
								foreach ( $field['tabs'] as $accordion ) {
									$this->recursive_output_css( $accordion['fields'], $field );
								}
							}
						}

						if ( class_exists( $class_name ) ) {

							if ( method_exists( $class_name, 'output' ) || method_exists( $class_name, 'enqueue_google_fonts' ) ) {

								$field_value = '';

								if ( $field_check && ( 'options' === $this->abstract || 'customize' === $this->abstract ) ) {

									if ( ! empty( $combine_field ) ) {

										$field_value = ( isset( $this->options[ $combine_field['id'] ][ $field_id ] ) ) ? $this->options[ $combine_field['id'] ][ $field_id ] : '';

									} else {

										$field_value = ( isset( $this->options[ $field_id ] ) ) ? $this->options[ $field_id ] : '';

									}
								} elseif ( $field_check && 'metabox' === $this->abstract && is_singular() ) {

									if ( ! empty( $combine_field ) ) {

										$meta_value  = $this->get_meta_value( $combine_field );
										$field_value = ( isset( $meta_value[ $field_id ] ) ) ? $meta_value[ $field_id ] : '';

									} else {

										$meta_value  = $this->get_meta_value( $field );
										$field_value = ( isset( $meta_value ) ) ? $meta_value : '';

									}
								}

								$instance = new $class_name( $field, $field_value, $this->unique, 'wp/enqueue', $this );

								// typography enqueue and embed google web fonts.
								if ( 'typography' === $field_type && $this->args['enqueue_webfont'] && ! empty( $field_value['font-family'] ) ) {

									$method = ( ! empty( $this->args['async_webfont'] ) ) ? 'async' : 'enqueue';
									$family = $instance->enqueue_google_fonts();

									SPLW::$webfonts[ $method ][ $family ] = ( ! empty( $this->webfonts[ $family ] ) ) ? $family . ':' . implode( ',', $this->webfonts[ $family ] ) : $family;
									SPLW::$subsets                        = $this->subsets;

								}

								// output css.
								if ( $field_output && $this->args['output_css'] ) {
									SPLW::$css .= $instance->output();
								}

								unset( $instance );

							}
						}
					}
				}
			}

		}

	}
}
