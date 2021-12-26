<?php
/**
 *  Framework metabox-options.class file.
 *
 * @package    Location_weather
 * @subpackage Location_weather/framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.

if ( ! class_exists( 'SPLWT_Metabox' ) ) {
	/**
	 *
	 * Metabox Class
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class SPLWT_Metabox extends SPLWT_Abstract {

		/**
		 * Unique ID/Name
		 *
		 * @var string
		 */
		public $unique = '';
		/**
		 * Abstract.
		 *
		 * @var string
		 */
		public $abstract = 'metabox';
		/**
		 * Pre fields.
		 *
		 * @var array
		 */
		public $pre_fields = array();
		/**
		 * Setions.
		 *
		 * @var array
		 */
		public $sections = array();
		/**
		 * Post Types.
		 *
		 * @var array
		 */
		public $post_type = array();
		/**
		 * Default arguments.
		 *
		 * @var array
		 */
		public $args = array(
			'title'              => '',
			'post_type'          => 'post',
			'data_type'          => 'serialize',
			'context'            => 'advanced',
			'priority'           => 'default',
			'exclude_post_types' => array(),
			'page_templates'     => '',
			'post_formats'       => '',
			'show_reset'         => false,
			'show_restore'       => false,
			'enqueue_webfont'    => true,
			'async_webfont'      => false,
			'output_css'         => true,
			'theme'              => 'dark',
			'class'              => '',
			'defaults'           => array(),
		);

		/**
		 * Run metabox construct.
		 *
		 * @param mixed $key The metabox key.
		 * @param array $params The metabox parameters.
		 */
		public function __construct( $key, $params = array() ) {

			$this->unique         = $key;
			$this->args           = apply_filters( "splwt_{$this->unique}_args", wp_parse_args( $params['args'], $this->args ), $this );
			$this->sections       = apply_filters( "splwt_{$this->unique}_sections", $params['sections'], $this );
			$this->post_type      = ( is_array( $this->args['post_type'] ) ) ? $this->args['post_type'] : array_filter( (array) $this->args['post_type'] );
			$this->post_formats   = ( is_array( $this->args['post_formats'] ) ) ? $this->args['post_formats'] : array_filter( (array) $this->args['post_formats'] );
			$this->page_templates = ( is_array( $this->args['page_templates'] ) ) ? $this->args['page_templates'] : array_filter( (array) $this->args['page_templates'] );
			$this->pre_fields     = $this->pre_fields( $this->sections );

			add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );
			add_action( 'save_post', array( &$this, 'save_meta_box' ) );
			add_action( 'edit_attachment', array( &$this, 'save_meta_box' ) );

			if ( ! empty( $this->page_templates ) || ! empty( $this->post_formats ) || ! empty( $this->args['class'] ) ) {
				foreach ( $this->post_type as $post_type ) {
					add_filter( 'postbox_classes_' . $post_type . '_' . $this->unique, array( &$this, 'add_metabox_classes' ) );
				}
			}

			// wp enqeueu for typography and output css.
			parent::__construct();

		}

		/**
		 * Instance.
		 *
		 * @param string $key Key of the metabox.
		 * @param array  $params Array of parameters.
		 * @return statement
		 */
		public static function instance( $key, $params = array() ) {

			return new self( $key, $params );
		}

		/**
		 * Pre fields
		 *
		 * @param array $sections The sections.
		 * @return statement
		 */
		public function pre_fields( $sections ) {

			$result = array();

			foreach ( $sections as $key => $section ) {
				if ( ! empty( $section['fields'] ) ) {
					foreach ( $section['fields'] as $field ) {
						$result[] = $field;
					}
				}
			}

			return $result;

		}

		/**
		 * Add metabox classes.
		 *
		 * @param array $classes The metabox classes.
		 */
		public function add_metabox_classes( $classes ) {

			global $post;

			if ( ! empty( $this->post_formats ) ) {

				$saved_post_format = ( is_object( $post ) ) ? get_post_format( $post ) : false;
				$saved_post_format = ( ! empty( $saved_post_format ) ) ? $saved_post_format : 'default';

				$classes[] = 'splwt-lite-post-formats';

				// Sanitize post format for standard to default.
				$key = array_search( 'standard', $this->post_formats, true );
				if ( ( $key ) !== false ) {
					$this->post_formats[ $key ] = 'default';
				}

				foreach ( $this->post_formats as $format ) {
					$classes[] = 'splwt-lite-post-format-' . $format;
				}

				if ( ! in_array( $saved_post_format, $this->post_formats, true ) ) {
					$classes[] = 'splwt-lite-metabox-hide';
				} else {
					$classes[] = 'splwt-lite-metabox-show';
				}
			}

			if ( ! empty( $this->page_templates ) ) {

				$saved_template = ( is_object( $post ) && ! empty( $post->page_template ) ) ? $post->page_template : 'default';

				$classes[] = 'splwt-lite-page-templates';

				foreach ( $this->page_templates as $template ) {
					$classes[] = 'splwt-lite-page-' . preg_replace( '/[^a-zA-Z0-9]+/', '-', strtolower( $template ) );
				}

				if ( ! in_array( $saved_template, $this->page_templates, true ) ) {
					$classes[] = 'splwt-lite-metabox-hide';
				} else {
					$classes[] = 'splwt-lite-metabox-show';
				}
			}

			if ( ! empty( $this->args['class'] ) ) {
				$classes[] = $this->args['class'];
			}

			return $classes;

		}

		/**
		 * Add metabox
		 *
		 * @param array $post_type The post types.
		 */
		public function add_meta_box( $post_type ) {

			if ( ! in_array( $post_type, $this->args['exclude_post_types'], true ) ) {
				add_meta_box( $this->unique, wp_kses_post( $this->args['title'] ), array( &$this, 'add_meta_box_content' ), $this->post_type, $this->args['context'], $this->args['priority'], $this->args );
			}

		}

		/**
		 * Get default value.
		 *
		 * @param array $field The field value.
		 * @return mixed
		 */
		public function get_default( $field ) {

			$default = ( isset( $field['default'] ) ) ? $field['default'] : '';
			$default = ( isset( $this->args['defaults'][ $field['id'] ] ) ) ? $this->args['defaults'][ $field['id'] ] : $default;

			return $default;

		}

		/**
		 * Get meta value.
		 *
		 * @param object $field The field.
		 * @return statement
		 */
		public function get_meta_value( $field ) {

			global $post;

			$value = null;

			if ( is_object( $post ) && ! empty( $field['id'] ) ) {

				if ( 'serialize' !== $this->args['data_type'] ) {
					$meta  = get_post_meta( $post->ID, $field['id'] );
					$value = ( isset( $meta[0] ) ) ? $meta[0] : null;
				} else {
					$meta  = get_post_meta( $post->ID, $this->unique, true );
					$value = ( isset( $meta[ $field['id'] ] ) ) ? $meta[ $field['id'] ] : null;
				}
			}

			$default = ( isset( $field['id'] ) ) ? $this->get_default( $field ) : '';
			$value   = ( isset( $value ) ) ? $value : $default;

			return $value;

		}

		/**
		 * Add metabox content
		 *
		 * @param object $post The post.
		 * @param array  $callback The callback function.
		 * @return void
		 */
		public function add_meta_box_content( $post, $callback ) {

			global $post;

			$has_nav  = ( count( $this->sections ) > 1 && 'side' !== $this->args['context'] ) ? true : false;
			$show_all = ( ! $has_nav ) ? ' splwt-lite-show-all' : '';
			$errors   = ( is_object( $post ) ) ? get_post_meta( $post->ID, '_splwt_errors_' . $this->unique, true ) : array();
			$errors   = ( ! empty( $errors ) ) ? $errors : array();
			$theme    = ( $this->args['theme'] ) ? ' splwt-lite-theme-' . $this->args['theme'] : '';

			if ( is_object( $post ) && ! empty( $errors ) ) {
				delete_post_meta( $post->ID, '_splwt_errors_' . $this->unique );
			}

			wp_nonce_field( 'splwt_metabox_nonce', 'splwt_metabox_nonce' . $this->unique );

			echo '<div class="splwt-lite splwt-lite-metabox' . esc_attr( $theme ) . '">';

			echo '<div class="splwt-lite-wrapper' . esc_attr( $show_all ) . '">';

			$current_screen        = get_current_screen();
			$the_current_post_type = $current_screen->post_type;
			if ( 'location_weather' === $the_current_post_type ) {
				$_menu_icon = LOCATION_WEATHER_ASSETS . '/images/icons/location-weather-logo.svg';
				echo '<div class="splw-mbf-banner">';
				echo '<div class="splw-mbf-logo">
				<img class="splwt-banner-logo" src="' . esc_url( $_menu_icon ) . '"/>
				<sup class="splw-version">' . esc_html( LOCATION_WEATHER_VERSION ) . '</sup>
		</div>';

				echo '<div class="splwt-submit-options"><span class="spinner"></span>';?>
				<?php if ( isset( $_GET['action'] ) ? 'edit' === $_GET['action'] : '' ) : ?>
					<input name="original_publish" type="hidden" id="<?php echo esc_attr( $post->ID ); ?>" value="<?php esc_attr_e( 'Save' ); ?>" />
					<input name="save" type="submit" class="splw-publish-button" id="publish" tabindex="5" accesskey="p" value="Update">
				<?php else : ?>
					<input name="original_publish" type="hidden" id="<?php echo esc_attr( $post->ID ); ?>" value="<?php esc_attr_e( 'Publish' ); ?>" />
					<input name="publish" type="submit" id="publish" class="splw-publish-button" value="Publish" tabindex="5" accesskey="p">
					<?php
				endif;
				echo '</div></div>';
				?>
		<div class='splwpro_shortcode_divider'> </div>
				<?php
			}
			if ( $has_nav ) {

				echo '<div class="splwt-lite-nav splwt-lite-nav-metabox">';
				echo '<ul>';

				$tab_key = 1;

				foreach ( $this->sections as $section ) {
					$tab_error = ( ! empty( $errors['sections'][ $tab_key ] ) ) ? '<i class="splwt-lite-label-error splwt-lite-error">!</i>' : '';
					$tab_icon  = ( ! empty( $section['icon'] ) ) ? $section['icon'] : '';
					echo '<li><a href="#" data-section="' . esc_attr( $this->unique . '_' . $tab_key ) . '" class="' . esc_attr( $this->unique . '_' . $tab_key ) . '">' . wp_kses_post( $tab_icon . $section['title'] . $tab_error ) . '</a></li>';
					$tab_key++;
				}
				$current_screen        = get_current_screen();
				$the_current_post_type = $current_screen->post_type;
				if ( 'location_weather' === $the_current_post_type ) {
					echo '<li class="splw-code selectable">';
					echo '<div class="splw-copy">';
					?>
					[location-weather <?php echo 'id="' . esc_attr( $post->ID ) . '"'; ?>]</div>
					<div class="splw-after-copy-text"><i class="fa fa-check-circle"></i>  Shortcode  Copied to Clipboard! </div>
				</li>
					<?php
				}

				echo '</ul>';

				echo '</div>';

			}

					echo '<div class="splwt-lite-content">';

					echo '<div class="splwt-lite-sections">';

					$section_key = 1;

			foreach ( $this->sections as $section ) {

				$section_onload = ( ! $has_nav ) ? ' splwt-lite-onload' : '';
				$section_class  = ( ! empty( $section['class'] ) ) ? ' ' . $section['class'] : '';
				$section_title  = ( ! empty( $section['title'] ) ) ? $section['title'] : '';
				$section_icon   = ( ! empty( $section['icon'] ) ) ? '<i class="splwt-lite-section-icon ' . esc_attr( $section['icon'] ) . '"></i>' : '';

				echo '<div  id="splwt-section-' . esc_attr( $this->unique . '_' . $section_key ) . '"  class="splwt-lite-section' . esc_attr( $section_onload . $section_class ) . '">';

				echo ( $section_title || $section_icon ) ? '<div class="splwt-lite-section-title"><h3>' . wp_kses_post( $section_icon . $section_title ) . '</h3></div>' : '';

				if ( ! empty( $section['fields'] ) ) {

					foreach ( $section['fields'] as $field ) {

						if ( ! empty( $field['id'] ) && ! empty( $errors['fields'][ $field['id'] ] ) ) {
							$field['_error'] = $errors['fields'][ $field['id'] ];
						}

						if ( ! empty( $field['id'] ) ) {
							$field['default'] = $this->get_default( $field );
						}

						SPLW::field( $field, $this->get_meta_value( $field ), $this->unique, 'metabox' );

					}
				} else {

						echo '<div class="splwt-lite-no-option">' . esc_html__( 'No data available.', 'location-weather' ) . '</div>';

				}

				echo '</div>';

				$section_key++;

			}

					echo '</div>';

					echo '</div>';

					echo ( $has_nav ) ? '<div class="splwt-lite-nav-background"></div>' : '';

					echo '<div class="clear"></div>';

					echo '</div>';

					echo '</div>';

		}

		/**
		 * Save metabox.
		 *
		 * @param array $post_id The post IDs.
		 * @return statement
		 */
		public function save_meta_box( $post_id ) {

			$count    = 1;
			$data     = array();
			$errors   = array();
			$noncekey = 'splwt_metabox_nonce' . $this->unique;
			$nonce    = ( ! empty( $_POST[ $noncekey ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $noncekey ] ) ) : '';

			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! wp_verify_nonce( $nonce, 'splwt_metabox_nonce' ) ) {
				return $post_id;
			}

			// XSS ok.
			// No worries, This "POST" requests is sanitizing in the below foreach.
			$request = ( ! empty( $_POST[ $this->unique ] ) ) ? $_POST[ $this->unique ] : array(); //phpcs:ignore

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
										$data[ $field_id ]             = $this->get_meta_value( $field );

									}
								}
							}
						}
					}

					$count++;

				}
			}

			$data = apply_filters( "splwt_{$this->unique}_save", $data, $post_id, $this );

			do_action( "splwt_{$this->unique}_save_before", $data, $post_id, $this );

			if ( empty( $data ) || ! empty( $request['_reset'] ) ) {

				if ( 'serialize' !== $this->args['data_type'] ) {
					foreach ( $data as $key => $value ) {
						delete_post_meta( $post_id, $key );
					}
				} else {
						delete_post_meta( $post_id, $this->unique );
				}
			} else {

				if ( 'serialize' !== $this->args['data_type'] ) {
					foreach ( $data as $key => $value ) {
						update_post_meta( $post_id, $key, $value );
					}
				} else {
					update_post_meta( $post_id, $this->unique, $data );
				}

				if ( ! empty( $errors ) ) {
					update_post_meta( $post_id, '_splwt_errors_' . $this->unique, $errors );
				}
			}

			do_action( "splwt_{$this->unique}_saved", $data, $post_id, $this );

			do_action( "splwt_{$this->unique}_save_after", $data, $post_id, $this );

		}
	}
}
