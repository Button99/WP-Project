<?php
/**
 *  Framework taxonomy-options.class file.
 *
 * @package    Location_weather
 * @subpackage Location_weather/framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

if ( ! class_exists( 'SPLWT_Taxonomy_Options' ) ) {
	/**
	 *
	 * Taxonomy Options Class
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class SPLWT_Taxonomy_Options extends SPLWT_Abstract {

		/**
		 * Unique
		 *
		 * @var string
		 */
		public $unique = '';
		/**
		 * Taxonomy
		 *
		 * @var string
		 */
		public $taxonomy = '';
		/**
		 * Abstract
		 *
		 * @var string
		 */
		public $abstract = 'taxonomy';
		/**
		 * Sections
		 *
		 * @var array
		 */
		public $sections = array();
		/**
		 * Taxonomies
		 *
		 * @var array
		 */
		public $taxonomies = array();
		/**
		 * Default args.
		 *
		 * @var array
		 */
		public $args = array(
			'taxonomy'  => 'category',
			'data_type' => 'serialize',
			'class'     => '',
			'defaults'  => array(),
		);

		/**
		 * Run framework construct.
		 *
		 * @param  mixed $key key.
		 * @param  mixed $params params.
		 * @return void
		 */
		public function __construct( $key, $params ) {

			$this->unique     = $key;
			$this->args       = apply_filters( "splwt_{$this->unique}_args", wp_parse_args( $params['args'], $this->args ), $this );
			$this->sections   = apply_filters( "splwt_{$this->unique}_sections", $params['sections'], $this );
			$this->taxonomies = ( is_array( $this->args['taxonomy'] ) ) ? $this->args['taxonomy'] : array_filter( (array) $this->args['taxonomy'] );
			$this->taxonomy   = ( ! empty( $_REQUEST['taxonomy'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['taxonomy'] ) ) : '';

			if ( ! empty( $this->taxonomies ) && in_array( $this->taxonomy, $this->taxonomies, true ) ) {
				add_action( 'admin_init', array( &$this, 'add_taxonomy_options' ) );
			}

		}

		/**
		 * Instance
		 *
		 * @param  mixed $key key.
		 * @param  mixed $params params.
		 * @return statement
		 */
		public static function instance( $key, $params ) {
			return new self( $key, $params );
		}

		/**
		 * Add taxonomy add/edit fields
		 *
		 * @return void
		 */
		public function add_taxonomy_options() {

			add_action( $this->taxonomy . '_add_form_fields', array( &$this, 'render_taxonomy_form_fields' ) );
			add_action( $this->taxonomy . '_edit_form', array( &$this, 'render_taxonomy_form_fields' ) );

			add_action( 'created_' . $this->taxonomy, array( &$this, 'save_taxonomy' ) );
			add_action( 'edited_' . $this->taxonomy, array( &$this, 'save_taxonomy' ) );

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
		 * Get meta value.
		 *
		 * @param  mixed $term_id term id.
		 * @param  mixed $field field.
		 * @return statement
		 */
		public function get_meta_value( $term_id, $field ) {

			$value = null;

			if ( ! empty( $term_id ) && ! empty( $field['id'] ) ) {

				if ( 'serialize' !== $this->args['data_type'] ) {
					$meta  = get_term_meta( $term_id, $field['id'] );
					$value = ( isset( $meta[0] ) ) ? $meta[0] : null;
				} else {
					$meta  = get_term_meta( $term_id, $this->unique, true );
					$value = ( isset( $meta[ $field['id'] ] ) ) ? $meta[ $field['id'] ] : null;
				}
			}

			$default = ( isset( $field['id'] ) ) ? $this->get_default( $field ) : '';
			$value   = ( isset( $value ) ) ? $value : $default;

			return $value;

		}

		/**
		 * Render taxonomy add/edit form fields.
		 *
		 * @param object $term term.
		 * @return void
		 */
		public function render_taxonomy_form_fields( $term ) {

			$is_term   = ( is_object( $term ) && isset( $term->taxonomy ) ) ? true : false;
			$term_id   = ( $is_term ) ? $term->term_id : 0;
			$taxonomy  = ( $is_term ) ? $term->taxonomy : $term;
			$classname = ( $is_term ) ? 'edit' : 'add';
			$errors    = ( ! empty( $term_id ) ) ? get_term_meta( $term_id, '_splwt_errors_' . $this->unique, true ) : array();
			$errors    = ( ! empty( $errors ) ) ? $errors : array();
			$class     = ( $this->args['class'] ) ? ' ' . $this->args['class'] : '';

			if ( ! empty( $errors ) ) {
				delete_term_meta( $term_id, '_splwt_errors_' . $this->unique );
			}

			wp_nonce_field( 'splwt_taxonomy_nonce', 'splwt_taxonomy_nonce' . $this->unique );

			echo '<div class="splwt-lite splwt-lite-taxonomy splwt-lite-show-all splwt-lite-onload splwt-lite-taxonomy-' . esc_attr( $classname ) . '-fields ' . esc_attr( $class ) . '">';

			foreach ( $this->sections as $section ) {

				if ( $taxonomy === $this->taxonomy ) {

					$section_icon  = ( ! empty( $section['icon'] ) ) ? '<i class="splwt-lite-section-icon ' . esc_attr( $section['icon'] ) . '"></i>' : '';
					$section_title = ( ! empty( $section['title'] ) ) ? $section['title'] : '';

					echo ( $section_title || $section_icon ) ? '<div class="splwt-lite-section-title"><h3>' . wp_kses_post( $section_icon . $section_title ) . '</h3></div>' : '';

					if ( ! empty( $section['fields'] ) ) {
						foreach ( $section['fields'] as $field ) {

							if ( ! empty( $field['id'] ) && ! empty( $errors['fields'][ $field['id'] ] ) ) {
								$field['_error'] = $errors['fields'][ $field['id'] ];
							}

							if ( ! empty( $field['id'] ) ) {
								$field['default'] = $this->get_default( $field );
							}

							SPLW::field( $field, $this->get_meta_value( $term_id, $field ), $this->unique, 'taxonomy' );

						}
					}
				}
			}

			echo '</div>';

		}

		/**
		 * Save taxonomy form fields
		 *
		 * @param int $term_id term id.
		 * @return int
		 */
		public function save_taxonomy( $term_id ) {

			$count    = 1;
			$data     = array();
			$errors   = array();
			$noncekey = 'splwt_taxonomy_nonce' . $this->unique;
			$nonce    = ( ! empty( $_POST[ $noncekey ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $noncekey ] ) ) : '';
			$taxonomy = ( ! empty( $_POST['taxonomy'] ) ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';

			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! wp_verify_nonce( $nonce, 'splwt_taxonomy_nonce' ) ) {
				return $term_id;
			}

			// XSS ok.
			// No worries, This "POST" requests is sanitizing in the below foreach.
			$request = ( ! empty( $_POST[ $this->unique ] ) ) ? $_POST[ $this->unique ] : array(); // phpcs:ignore

			if ( ! empty( $request ) ) {

				foreach ( $this->sections as $section ) {

					if ( ! empty( $section['fields'] ) ) {

						foreach ( $section['fields'] as $field ) {

							if ( ! empty( $field['id'] ) ) {

								$field_id    = $field['id'];
								$field_value = isset( $request[ $field_id ] ) ? $request[ $field_id ] : '';

								// Sanitize "post" request of field.
								if ( ! isset( $field['sanitize'] ) ) {

									if ( is_array( $field_value ) ) {
										$data[ $field_id ] = wp_kses_post_deep( $field_value );
									} else {
										$data[ $field_id ] = wp_kses_post( $field_value );
									}
								} elseif ( isset( $field['sanitize'] ) && is_callable( $field['sanitize'] ) ) {

										$data[ $field_id ] = call_user_func( $field['sanitize'], $field_value );

								} else {

									$data[ $field_id ] = $field_value;

								}

								// Validate "post" request of field.
								if ( isset( $field['validate'] ) && is_callable( $field['validate'] ) ) {

									$has_validated = call_user_func( $field['validate'], $field_value );

									if ( ! empty( $has_validated ) ) {

										$errors['sections'][ $count ]  = true;
										$errors['fields'][ $field_id ] = $has_validated;
										$data[ $field_id ]             = $this->get_meta_value( $term_id, $field );

									}
								}
							}
						}
					}

					$count++;

				}
			}

			$data = apply_filters( "splwt_{$this->unique}_save", $data, $term_id, $this );

			do_action( "splwt_{$this->unique}_save_before", $data, $term_id, $this );

			if ( empty( $data ) ) {

				if ( 'serialize' !== $this->args['data_type'] ) {
					foreach ( $data as $key => $value ) {
						delete_term_meta( $term_id, $key );
					}
				} else {
					delete_term_meta( $term_id, $this->unique );
				}
			} else {

				if ( 'serialize' !== $this->args['data_type'] ) {
					foreach ( $data as $key => $value ) {
						update_term_meta( $term_id, $key, $value );
					}
				} else {
					update_term_meta( $term_id, $this->unique, $data );
				}

				if ( ! empty( $errors ) ) {
					update_term_meta( $term_id, '_splwt_errors_' . $this->unique, $errors );
				}
			}

			do_action( "splwt_{$this->unique}_saved", $data, $term_id, $this );

			do_action( "splwt_{$this->unique}_save_after", $data, $term_id, $this );

		}
	}
}
