<?php
/**
 *  Framework metabox-options.class file.
 *
 * @package    Location_weather
 * @subpackage Location_weather/framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

if ( ! class_exists( 'SPLWT_Nav_Menu_Options' ) ) {
	/**
	 *
	 * Nav Menu Options Class
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class SPLWT_Nav_Menu_Options extends SPLWT_Abstract {

		/**
		 * Unique
		 *
		 * @var string
		 */
		public $unique = '';
		/**
		 * Abstract
		 *
		 * @var string
		 */
		public $abstract = 'menu';
		/**
		 * Sections
		 *
		 * @var array
		 */
		public $sections = array();
		/**
		 * Default args.
		 *
		 * @var array
		 */
		public $args = array(
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

			$this->unique   = $key;
			$this->args     = apply_filters( "splwt_{$this->unique}_args", wp_parse_args( $params['args'], $this->args ), $this );
			$this->sections = apply_filters( "splwt_{$this->unique}_sections", $params['sections'], $this );

			add_action( 'wp_nav_menu_item_custom_fields', array( &$this, 'wp_nav_menu_item_custom_fields' ), 10, 4 );
			add_action( 'wp_update_nav_menu_item', array( &$this, 'wp_update_nav_menu_item' ), 10, 3 );

			add_filter( 'wp_edit_nav_menu_walker', array( &$this, 'wp_edit_nav_menu_walker' ), 10, 2 );

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
		 * Wp edit nav menu walker.
		 *
		 * @param  mixed $class class.
		 * @param  mixed $menu_id menu id.
		 * @return statement
		 */
		public function wp_edit_nav_menu_walker( $class, $menu_id ) {

			global $wp_version;

			if ( version_compare( $wp_version, '5.4.0', '<' ) ) {

				if ( ! class_exists( 'SPLWT_Walker_Nav_Menu_Edit' ) ) {
					SPLW::include_plugin_file( 'functions/walker.php' );
				}

				return 'SPLWT_Walker_Nav_Menu_Edit';

			}

			return $class;

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
		 * @param  mixed $menu_item_id menu item id.
		 * @param  mixed $field field.
		 * @return statement
		 */
		public function get_meta_value( $menu_item_id, $field ) {

			$value = null;

			if ( ! empty( $menu_item_id ) && ! empty( $field['id'] ) ) {

				if ( 'serialize' !== $this->args['data_type'] ) {
					$meta  = get_post_meta( $menu_item_id, $field['id'] );
					$value = ( isset( $meta[0] ) ) ? $meta[0] : null;
				} else {
					$meta  = get_post_meta( $menu_item_id, $this->unique, true );
					$value = ( isset( $meta[ $field['id'] ] ) ) ? $meta[ $field['id'] ] : null;
				}
			}

			$default = ( isset( $field['id'] ) ) ? $this->get_default( $field ) : '';
			$value   = ( isset( $value ) ) ? $value : $default;

			return $value;

		}

		/**
		 * Wp nav menu item custom fields.
		 *
		 * @param  mixed $menu_item_id menu item id.
		 * @param  mixed $item menu item.
		 * @param  mixed $depth menu item depth.
		 * @param  mixed $args menu item args.
		 * @return void
		 */
		public function wp_nav_menu_item_custom_fields( $menu_item_id, $item, $depth, $args ) {

			$errors = ( ! empty( $menu_item_id ) ) ? get_post_meta( $menu_item_id, '_splwt_errors_' . $this->unique, true ) : array();
			$errors = ( ! empty( $errors ) ) ? $errors : array();
			$class  = ( $this->args['class'] ) ? ' ' . $this->args['class'] : '';

			if ( ! empty( $errors ) ) {
				delete_post_meta( $menu_item_id, '_splwt_errors_' . $this->unique );
			}

			echo '<div class="splwt-lite splwt-lite-nav-menu-options' . esc_attr( $class ) . '">';

			foreach ( $this->sections as $section ) {

				$section_icon  = ( ! empty( $section['icon'] ) ) ? '<i class="splwt-lite-nav-menu-icon ' . esc_attr( $section['icon'] ) . '"></i>' : '';
				$section_title = ( ! empty( $section['title'] ) ) ? $section['title'] : '';

				echo '<div class="splwt-lite-fields">';

				echo ( $section_title || $section_icon ) ? '<div class="splwt-lite-nav-menu-title"><h4>' . wp_kses_post( $section_icon . $section_title ) . '</h4></div>' : '';

				if ( ! empty( $section['fields'] ) ) {

					foreach ( $section['fields'] as $field ) {

						if ( ! empty( $field['id'] ) && ! empty( $errors['fields'][ $field['id'] ] ) ) {
							$field['_error'] = $errors['fields'][ $field['id'] ];
						}

						if ( ! empty( $field['id'] ) ) {
							$field['default'] = $this->get_default( $field );
						}

						SPLW::field( $field, $this->get_meta_value( $menu_item_id, $field ), $this->unique . '[' . $menu_item_id . ']', 'menu' );

					}
				}

				echo '</div>';

			}

			echo '</div>';

		}

		/**
		 * Wp nav menu item update.
		 *
		 * @param  mixed $menu_id menu item id.
		 * @param  mixed $menu_item_db_id menu item db id.
		 * @param  mixed $menu_item_args menu item args.
		 */
		public function wp_update_nav_menu_item( $menu_id, $menu_item_db_id, $menu_item_args ) {

			$count    = 1;
			$data     = array();
			$errors   = array();
			$noncekey = 'update-nav-menu-nonce';
			$nonce    = ( ! empty( $_POST[ $noncekey ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $noncekey ] ) ) : '';

			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! wp_verify_nonce( $nonce, 'update-nav_menu' ) ) {
				return $menu_item_db_id;
			}

			// XSS ok.
			// No worries, This "POST" requests is sanitizing in the below foreach.
			$request = ( ! empty( $_POST[ $this->unique ][ $menu_item_db_id ] ) ) ? $_POST[ $this->unique ][ $menu_item_db_id ] : array();

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
										$data[ $field_id ]             = $this->get_meta_value( $menu_item_db_id, $field );

									}
								}
							}
						}
					}

					$count++;

				}
			}

			$data = apply_filters( "splwt_{$this->unique}_save", $data, $menu_item_db_id, $this );

			do_action( "splwt_{$this->unique}_save_before", $data, $menu_item_db_id, $this );

			if ( empty( $data ) ) {

				if ( 'serialize' !== $this->args['data_type'] ) {
					foreach ( $data as $key => $value ) {
						delete_post_meta( $menu_item_db_id, $key );
					}
				} else {
					delete_post_meta( $menu_item_db_id, $this->unique );
				}
			} else {

				if ( 'serialize' !== $this->args['data_type'] ) {
					foreach ( $data as $key => $value ) {
						update_post_meta( $menu_item_db_id, $key, $value );
					}
				} else {
					update_post_meta( $menu_item_db_id, $this->unique, $data );
				}

				if ( ! empty( $errors ) ) {
					update_post_meta( $menu_item_db_id, '_splwt_errors_' . $this->unique, $errors );
				}
			}

			do_action( "splwt_{$this->unique}_saved", $data, $menu_item_db_id, $this );

			do_action( "splwt_{$this->unique}_save_after", $data, $menu_item_db_id, $this );

		}

	}
}
