<?php
/**
 *  Framework SPLW file.
 *
 * @package    Location_weather
 * @subpackage Location_weather/framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

if ( ! class_exists( 'SPLW' ) ) {
	/**
	 *
	 * Setup Class
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class SPLW {

		/**
		 * Premium
		 *
		 * @var string
		 */
		public static $premium = true;
		/**
		 * Version
		 *
		 * @var string
		 */
		public static $version = '2.1.8';
		/**
		 * Dir
		 *
		 * @var string
		 */
		public static $dir = '';
		/**
		 * Url
		 *
		 * @var string
		 */
		public static $url = '';
		/**
		 * Css
		 *
		 * @var string
		 */
		public static $css = '';
		/**
		 * Webfonts
		 *
		 * @var array
		 */
		public static $webfonts = array();
		/**
		 * Subsets
		 *
		 * @var array
		 */
		public static $subsets = array();
		/**
		 * Inited
		 *
		 * @var array
		 */
		public static $inited = array();
		/**
		 * Fields
		 *
		 * @var array
		 */
		public static $fields = array();
		/**
		 * Args
		 *
		 * @var array
		 */
		public static $args = array(
			'admin_options'   => array(),
			'metabox_options' => array(),
		);

		/**
		 * Shortcode instances
		 *
		 * @var array
		 */
		public static $shortcode_instances = array();

		/**
		 * Initialize
		 *
		 * @return void
		 */
		public static function init() {

			// Init action.
			do_action( 'splwt_init' );

			// Set directory constants.
			self::constants();

			// Include files.
			self::includes();

			add_action( 'after_setup_theme', array( 'SPLW', 'setup' ) );
			add_action( 'init', array( 'SPLW', 'setup' ) );
			add_action( 'switch_theme', array( 'SPLW', 'setup' ) );
			add_action( 'admin_enqueue_scripts', array( 'SPLW', 'add_admin_enqueue_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( 'SPLW', 'add_typography_enqueue_styles' ), 80 );
			add_action( 'wp_head', array( 'SPLW', 'add_custom_css' ), 80 );
			add_filter( 'admin_body_class', array( 'SPLW', 'add_admin_body_class' ) );

		}

		/**
		 * Setup frameworks
		 *
		 * @return void
		 */
		public static function setup() {

			// Setup admin option framework.
			$params = array();
			if ( ! empty( self::$args['admin_options'] ) ) {
				foreach ( self::$args['admin_options'] as $key => $value ) {
					if ( ! empty( self::$args['sections'][ $key ] ) && ! isset( self::$inited[ $key ] ) ) {

						$params['args']       = $value;
						$params['sections']   = self::$args['sections'][ $key ];
						self::$inited[ $key ] = true;

						SPLWT_Options::instance( $key, $params );
					}
				}
			}

			// Setup metabox option framework.
			$params = array();
			if ( ! empty( self::$args['metabox_options'] ) ) {
				foreach ( self::$args['metabox_options'] as $key => $value ) {
					if ( ! empty( self::$args['sections'][ $key ] ) && ! isset( self::$inited[ $key ] ) ) {

						$params['args']       = $value;
						$params['sections']   = self::$args['sections'][ $key ];
						self::$inited[ $key ] = true;

						SPLWT_Metabox::instance( $key, $params );

					}
				}
			}

			do_action( 'splwt_loaded' );

		}

		/**
		 * Create options
		 *
		 * @param int   $id id.
		 * @param array $args args.
		 * @return void
		 */
		public static function createOptions( $id, $args = array() ) {
			self::$args['admin_options'][ $id ] = $args;
		}

		/**
		 * Create metabox options.
		 *
		 * @param int   $id metabox id.
		 * @param array $args metabox args.
		 * @return void
		 */
		public static function createMetabox( $id, $args = array() ) {
			self::$args['metabox_options'][ $id ] = $args;
		}

		/**
		 * Create section.
		 *
		 * @param int   $id metabox id.
		 * @param array $sections metabox section.
		 * @return void
		 */
		public static function createSection( $id, $sections ) {
			self::$args['sections'][ $id ][] = $sections;
			self::set_used_fields( $sections );
		}

		/**
		 * Set directory constants.
		 *
		 * @return void
		 */
		public static function constants() {

			// We need this path-finder code for set URL of framework.
			$dirname        = wp_normalize_path( dirname( dirname( __FILE__ ) ) );
			$theme_dir      = wp_normalize_path( get_parent_theme_file_path() );
			$plugin_dir     = wp_normalize_path( WP_PLUGIN_DIR );
			$located_plugin = ( preg_match( '#' . self::sanitize_dirname( $plugin_dir ) . '#', self::sanitize_dirname( $dirname ) ) ) ? true : false;
			$directory      = ( $located_plugin ) ? $plugin_dir : $theme_dir;
			$directory_uri  = ( $located_plugin ) ? WP_PLUGIN_URL : get_parent_theme_file_uri();
			$foldername     = str_replace( $directory, '', $dirname );
			$protocol_uri   = ( is_ssl() ) ? 'https' : 'http';
			$directory_uri  = set_url_scheme( $directory_uri, $protocol_uri );

			self::$dir = $dirname;
			self::$url = $directory_uri . $foldername;

		}

		/**
		 * Include file helper
		 *
		 * @param string  $file file include.
		 * @param boolean $load file load.
		 * @return string
		 */
		public static function include_plugin_file( $file, $load = true ) {

			$path     = '';
			$file     = ltrim( $file, '/' );
			$override = apply_filters( 'splwt_override', 'splwt-lite-override' );

			if ( file_exists( get_parent_theme_file_path( $override . '/' . $file ) ) ) {
				$path = get_parent_theme_file_path( $override . '/' . $file );
			} elseif ( file_exists( get_theme_file_path( $override . '/' . $file ) ) ) {
				$path = get_theme_file_path( $override . '/' . $file );
			} elseif ( file_exists( self::$dir . '/' . $override . '/' . $file ) ) {
				$path = self::$dir . '/' . $override . '/' . $file;
			} elseif ( file_exists( self::$dir . '/' . $file ) ) {
				$path = self::$dir . '/' . $file;
			}

			if ( ! empty( $path ) && ! empty( $file ) && $load ) {

				global $wp_query;

				if ( is_object( $wp_query ) && function_exists( 'load_template' ) ) {

					load_template( $path, true );

				} else {

					require_once $path;

				}
			} else {

				return self::$dir . '/' . $file;

			}

		}

		/**
		 * Is active plugin helper
		 *
		 * @param string $file plugin file.
		 * @return boolean
		 */
		public static function is_active_plugin( $file = '' ) {
			return in_array( $file, (array) get_option( 'active_plugins', array() ), true );
		}

		/**
		 * Sanitize dirname
		 *
		 * @param array $dirname directory name.
		 * @return array
		 */
		public static function sanitize_dirname( $dirname ) {
			return preg_replace( '/[^A-Za-z]/', '', $dirname );
		}

		/**
		 * Set url constant
		 *
		 * @param string $file file url.
		 * @return string
		 */
		public static function include_plugin_url( $file ) {
			return esc_url( self::$url ) . '/' . ltrim( $file, '/' );
		}

		/**
		 * Include files
		 *
		 * @return void
		 */
		public static function includes() {

			// Helpers.
			self::include_plugin_file( 'functions/actions.php' );
			self::include_plugin_file( 'functions/helpers.php' );
			self::include_plugin_file( 'functions/sanitize.php' );
			self::include_plugin_file( 'functions/validate.php' );

			// Includes free version classes.
			self::include_plugin_file( 'classes/abstract.class.php' );
			self::include_plugin_file( 'classes/fields.class.php' );
			self::include_plugin_file( 'classes/admin-options.class.php' );

			// Includes premium version classes.
			self::include_plugin_file( 'classes/metabox-options.class.php' );

		}

		/**
		 * Maybe include a field class
		 *
		 * @param string $type include field type.
		 * @return void
		 */
		public static function maybe_include_field( $type = '' ) {
			if ( ! class_exists( 'SPLWT_Field_' . $type ) && class_exists( 'SPLWT_Fields' ) ) {
				self::include_plugin_file( 'fields/' . $type . '/' . $type . '.php' );
			}
		}

		/**
		 * Set all of used fields
		 *
		 * @param array $sections fields section.
		 * @return void
		 */
		public static function set_used_fields( $sections ) {

			if ( ! empty( $sections['fields'] ) ) {

				foreach ( $sections['fields'] as $field ) {

					if ( ! empty( $field['fields'] ) ) {
						self::set_used_fields( $field );
					}

					if ( ! empty( $field['tabs'] ) ) {
						self::set_used_fields( array( 'fields' => $field['tabs'] ) );
					}

					if ( ! empty( $field['accordions'] ) ) {
						self::set_used_fields( array( 'fields' => $field['accordions'] ) );
					}

					if ( ! empty( $field['type'] ) ) {
						self::$fields[ $field['type'] ] = $field;
					}
				}
			}

		}

		/**
		 * Enqueue admin and fields styles and scripts
		 *
		 * @return void
		 */
		public static function add_admin_enqueue_scripts() {

			// Loads scripts and styles only when needed.
			$enqueue  = false;
			$wpscreen = get_current_screen();

			if ( ! empty( self::$args['admin_options'] ) ) {
				foreach ( self::$args['admin_options'] as $argument ) {
					if ( substr( $wpscreen->id, -strlen( $argument['menu_slug'] ) ) === $argument['menu_slug'] ) {
						$enqueue = true;
					}
				}
			}

			if ( ! empty( self::$args['metabox_options'] ) ) {
				foreach ( self::$args['metabox_options'] as $argument ) {
					if ( in_array( $wpscreen->post_type, (array) $argument['post_type'], true ) ) {
						$enqueue = true;
					}
				}
			}

			if ( ! $enqueue ) {
				return;
			}

			// Check for developer mode.
			$min = ( self::$premium && SCRIPT_DEBUG ) ? '' : '.min';

			// Admin utilities.
			wp_enqueue_media();

			// Wp color picker.
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );

			// Font awesome 4 and 5 loader.
			if ( apply_filters( 'splwt_fa4', false ) ) {
				wp_enqueue_style( 'splwt-lite-fa', 'https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome' . $min . '.css', array(), '4.7.0', 'all' );
			} else {
				wp_enqueue_style( 'splwt-lite-fa5', 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.14.0/css/all' . $min . '.css', array(), '5.14.0', 'all' );
				wp_enqueue_style( 'splwt-lite-fa5-v4-shims', 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.14.0/css/v4-shims' . $min . '.css', array(), '5.14.0', 'all' );
			}

			// Main style.
			wp_enqueue_style( 'splwt-lite', self::include_plugin_url( 'assets/css/style' . $min . '.css' ), array(), LOCATION_WEATHER_VERSION, 'all' );

			// Main RTL styles.
			if ( is_rtl() ) {
				wp_enqueue_style( 'splwt-lite-rtl', self::include_plugin_url( 'assets/css/style-rtl' . $min . '.css' ), array(), LOCATION_WEATHER_VERSION, 'all' );
			}

			// Main scripts.
			wp_enqueue_script( 'splwt-lite-plugins', self::include_plugin_url( 'assets/js/plugins' . $min . '.js' ), array(), LOCATION_WEATHER_VERSION, true );
			wp_enqueue_script( 'splwt-lite', self::include_plugin_url( 'assets/js/main' . $min . '.js' ), array( 'splwt-lite-plugins' ), LOCATION_WEATHER_VERSION, true );

			// Main variables.
			wp_localize_script(
				'splwt-lite',
				'splwt_vars',
				array(
					'color_palette' => apply_filters( 'splwt_color_palette', array() ),
					'i18n'          => array(
						'confirm'         => esc_html__( 'Are you sure?', 'location-weather' ),
						/* translators: %1$s is replaced with "string" */
						'typing_text'     => esc_html__( 'Please enter %s or more characters', 'location-weather' ),
						'searching_text'  => esc_html__( 'Searching...', 'location-weather' ),
						'no_results_text' => esc_html__( 'No results found.', 'location-weather' ),
					),
				)
			);

			// Enqueue fields scripts and styles.
			$enqueued = array();

			if ( ! empty( self::$fields ) ) {
				foreach ( self::$fields as $field ) {
					if ( ! empty( $field['type'] ) ) {
						$classname = 'SPLWT_Field_' . $field['type'];
						self::maybe_include_field( $field['type'] );
						if ( class_exists( $classname ) && method_exists( $classname, 'enqueue' ) ) {
							$instance = new $classname( $field );
							if ( method_exists( $classname, 'enqueue' ) ) {
								$instance->enqueue();
							}
							unset( $instance );
						}
					}
				}
			}

			do_action( 'splwt_enqueue' );

		}

		/**
		 * Add typography enqueue styles to front page
		 *
		 * @return void
		 */
		public static function add_typography_enqueue_styles() {

			if ( ! empty( self::$webfonts ) ) {

				if ( ! empty( self::$webfonts['enqueue'] ) ) {

					$api    = '//fonts.googleapis.com/css';
					$query  = array(
						'family'  => implode( '%7C', self::$webfonts['enqueue'] ),
						'display' => 'swap',
					);
					$handle = 'splwt-lite-google-web-fonts';

					if ( ! empty( self::$subsets ) ) {
						$query['subset'] = implode( ',', self::$subsets );
					}

					wp_enqueue_style( $handle, esc_url( add_query_arg( $query, $api ) ), array(), '1.0.0', null );

				} else {

					wp_enqueue_script( 'splwt-lite-google-web-fonts', esc_url( '//ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js' ), array(), '1.0.0', null );
					wp_localize_script( 'splwt-lite-google-web-fonts', 'WebFontConfig', array( 'google' => array( 'families' => array_values( self::$webfonts['async'] ) ) ) );

				}
			}
		}

		/**
		 * Add admin body class
		 *
		 * @param string $classes admin body class.
		 * @return string
		 */
		public static function add_admin_body_class( $classes ) {

			if ( apply_filters( 'splwt_fa4', false ) ) {
				$classes .= 'splwt-lite-fa5-shims';
			}

			return $classes;

		}

		/**
		 * Add custom css to front page
		 *
		 * @return void
		 */
		public static function add_custom_css() {

			if ( ! empty( self::$css ) ) {
				echo '<style type="text/css">' . wp_strip_all_tags( self::$css ) . '</style>'; // phpcs:ignore
			}

		}

		/**
		 * Add a new framework field
		 *
		 * @param array  $field framework field.
		 * @param string $value framework field value.
		 * @param string $unique framework field unique id.
		 * @param string $where framework field where.
		 * @param string $parent framework field parent.
		 * @return void
		 */
		public static function field( $field = array(), $value = '', $unique = '', $where = '', $parent = '' ) {

			// Check for unallow fields.
			if ( ! empty( $field['_notice'] ) ) {

				$field_type = $field['type'];

				$field            = array();
				$field['content'] = esc_html__( 'Oops! Not allowed.', 'location-weather' ) . ' <strong>(' . $field_type . ')</strong>';
				$field['type']    = 'notice';
				$field['style']   = 'danger';

			}

			$depend     = '';
			$visible    = '';
			$unique     = ( ! empty( $unique ) ) ? $unique : '';
			$class      = ( ! empty( $field['class'] ) ) ? ' ' . esc_attr( $field['class'] ) : '';
			$is_pseudo  = ( ! empty( $field['pseudo'] ) ) ? ' splwt-lite-pseudo-field' : '';
			$field_type = ( ! empty( $field['type'] ) ) ? esc_attr( $field['type'] ) : '';

			if ( ! empty( $field['dependency'] ) ) {

				$dependency      = $field['dependency'];
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

				$visible = ( ! empty( $depend_visible ) ) ? ' splwt-lite-depend-visible' : ' splwt-lite-depend-hidden';

			}

			if ( ! empty( $field_type ) ) {

				// These attributes has been sanitized above.
				echo '<div class="splwt-lite-field splwt-lite-field-' . esc_attr( $field_type . $is_pseudo . $class . $visible ) . '"' . wp_kses_post( $depend ) . '>';

				if ( ! empty( $field['fancy_title'] ) ) {
					echo '<div class="splwt-lite-fancy-title">' . wp_kses_post( $field['fancy_title'] ) . '</div>';
				}

				if ( ! empty( $field['title'] ) ) {
					echo '<div class="splwt-lite-title">';
					echo '<h4>' . wp_kses_post( $field['title'] ) . '</h4>';
					echo ( ! empty( $field['subtitle'] ) ) ? '<div class="splwt-lite-subtitle-text">' . wp_kses_post( $field['subtitle'] ) . '</div>' : '';
					echo '</div>';
				}

				echo ( ! empty( $field['title'] ) || ! empty( $field['fancy_title'] ) ) ? '<div class="splwt-lite-fieldset">' : '';

				$value = ( ! isset( $value ) && isset( $field['default'] ) ) ? $field['default'] : $value;
				$value = ( isset( $field['value'] ) ) ? $field['value'] : $value;

				self::maybe_include_field( $field_type );

				$classname = 'SPLWT_Field_' . $field_type;

				if ( class_exists( $classname ) ) {
					$instance = new $classname( $field, $value, $unique, $where, $parent );
					$instance->render();
				} else {
					echo '<p>' . esc_html__( 'Field not found!', 'location-weather' ) . '</p>';
				}
			} else {
				echo '<p>' . esc_html__( 'Field not found!', 'location-weather' ) . '</p>';
			}

			echo ( ! empty( $field['title'] ) || ! empty( $field['fancy_title'] ) ) ? '</div>' : '';
			echo '<div class="clear"></div>';
			echo '</div>';

		}

	}

	SPLW::init();
}
