<?php
/**
	Plugin name: Lingotek Translation
	Plugin URI: http://lingotek.com/wordpress#utm_source=wpadmin&utm_medium=plugin&utm_campaign=wplingotektranslationplugin
	Version: 1.5.2
	Author: Lingotek and Frédéric Demarle
	Author uri: http://lingotek.com
	Description: Lingotek offers convenient cloud-based localization and translation.
	Text Domain: lingotek-translation
	Domain Path: /languages
	GitHub Plugin URI: https://github.com/lingotek/lingotek-translation
 *
 * @package Lingotek
 */

// don't access directly.
if ( ! function_exists( 'add_action' ) ) {
	exit();
}

// Plugin version (should match above meta).
define( 'LINGOTEK_VERSION', '1.5.2' );
define( 'LINGOTEK_MIN_PLL_VERSION', '1.8' );
// Plugin name as known by WordPress.
define( 'LINGOTEK_BASENAME', plugin_basename( __FILE__ ) );
// plugin slug (should match above meta: Text Domain).
define( 'LINGOTEK_PLUGIN_SLUG', 'lingotek-translation' );
// our directory.
define( 'LINGOTEK_DIR', dirname( __FILE__ ) );
define( 'LINGOTEK_INC', LINGOTEK_DIR . '/include' );
define( 'LINGOTEK_ADMIN_INC', LINGOTEK_DIR . '/admin' );
define( 'LINGOTEK_WORKFLOWS', LINGOTEK_ADMIN_INC . '/workflows' );
define( 'LINGOTEK_URL', plugins_url( '', __FILE__ ) );
define( 'BRIDGE_URL', 'https://marketplace.lingotek.com' );

class Lingotek {
	/**
	 *  Lingotek model.
	 *
	 *  @var object
	 */
	public $model;

	/**
	 *  Callback method
	 *
	 *  @var method
	 */
	public $callback;

	/**
	 *  Array to map Lingotek locales to WP locales.
	 *  map as 'WP locale' => 'Lingotek locale'.
	 *
	 * TODO: update this list!!!!
	 *
	 *  @var array
	 */
	public static $lingotek_locales = array(
		'af'     => 'af-ZA',
		'ak'     => 'ak-GH',
		'am'     => 'am-ET',
		'ar'     => 'ar',
		'ar_AE'  => 'ar-AE',
		'ar_AF'  => 'ar-AF',
		'ar_BH'  => 'ar-BH',
		'ar_DZ'  => 'ar-DZ',
		'ar_EG'  => 'ar-EG',
		'ar_IQ'  => 'ar-IQ',
		'ar_JO'  => 'ar-JO',
		'ar_LY'  => 'ar-LY',
		'ar_MA'  => 'ar-MA',
		'ar_MR'  => 'ar-MR',
		'ar_OM'  => 'ar-OM',
		'ar_SA'  => 'ar-SA',
		'ar_SD'  => 'ar-SD',
		'ar_SY'  => 'ar-SY',
		'ar_TD'  => 'ar-TD',
		'ar_TN'  => 'ar-TN',
		'ar_UZ'  => 'ar-UZ',
		'ar_YE'  => 'ar-YE',
		'as'     => 'as-IN',
		'az'     => 'az-AZ',
		'ba'     => 'ba-RU',
		'bel'    => 'be-BY',
		'bg_BG'  => 'bg-BG',
		'bn_BD'  => 'bn-BD',
		'bo'     => 'bo-CN',
		'bs_BA'  => 'bs-BA',
		'ca'     => 'ca-ES',
		'ca_ES'  => 'ca-ES',
		'co'     => 'co-FR',
		'cs_CZ'  => 'cs-CZ',
		'cy'     => 'cy-GB',
		'cy_GB'  => 'cy-GB',
		'de_AT'  => 'de-AT',
		'da_DK'  => 'da-DK',
		'de_CH'  => 'de-CH',
		'de_DE'  => 'de-DE',
		'dv'     => 'dv-MV',
		'el'     => 'el-GR',
		'el_GR'  => 'el-GR',
		'en_AU'  => 'en-AU',
		'en_CA'  => 'en-CA',
		'en_GB'  => 'en-GB',
		'en_US'  => 'en-US',
		'en_IE'  => 'en-IE',
		'en_IN'  => 'en-IN',
		'en_ZA'  => 'en-ZA',
		'eo'     => 'eo-FR',
		'es_419' => 'es-419',
		'es_AR'  => 'es-AR',
		'es_BO'  => 'es-BO',
		'es_CL'  => 'es-CL',
		'es_CO'  => 'es-CO',
		'es_ES'  => 'es-ES',
		'es_MX'  => 'es-MX',
		'es_PE'  => 'es-PE',
		'es_PR'  => 'es-PR',
		'es_VE'  => 'es-VE',
		'es_HN'  => 'es-HN',
		'es_CR'  => 'es-CR',
		'es_CU'  => 'es-CU',
		'es_DO'  => 'es-DO',
		'es_EC'  => 'es-EC',
		'es_GT'  => 'es-GT',
		'es_NI'  => 'es-NI',
		'es_PA'  => 'es-PA',
		'es_PY'  => 'es-PY',
		'es_SV'  => 'es-SV',
		'es_UY'  => 'es-UY',
		'es_US'  => 'es-US',
		'et'     => 'et-EE',
		'et_EE'  => 'et-EE',
		'eu'     => 'eu-ES',
		'fa_IR'  => 'fa-IR',
		'fi'     => 'fi-FI',
		'fi_FI'  => 'fi-FI',
		'fr_FR'  => 'fr-FR',
		'fr_BE'  => 'fr-BE',
		'fr_CA'  => 'fr-CA',
		'fr_CH'  => 'fr-CH',
		'fr_US'  => 'fr-US',
		'ga'     => 'ga-IE',
		'gd'     => 'gd-GB',
		'gl_ES'  => 'gl-ES',
		'gn'     => 'gn-BO',
		'haw_US' => 'haw-US',
		'he_IL'  => 'he-IL',
		'hi_IN'  => 'hi-IN',
		'hr'     => 'hr-HR',
		'ht_HT'  => 'ht-HT',
		'hu_HU'  => 'hu-HU',
		'hy'     => 'hy-AM',
		'id_ID'  => 'id-ID',
		'is_IS'  => 'is-IS',
		'it_IT'  => 'it-IT',
		'it_CH'  => 'it-CH',
		'ja'     => 'ja-JP',
		'ja_JP'  => 'ja-JP',
		'jv_ID'  => 'jv-ID',
		'ka_GE'  => 'ka-GE',
		'kin'    => 'kin-RW',
		'kk'     => 'kk-KZ',
		'kn'     => 'kn-IN',
		'ko_KR'  => 'ko-KR',
		'ky_KY'  => 'ky-KG',
		'lb_LU'  => 'lb-LU',
		'lo'     => 'lo-LA',
		'lt_LT'  => 'lt-LT',
		'lv'     => 'lv-LV',
		'lv_LV'  => 'lv-LV',
		'mg_MG'  => 'mg-MG',
		'mk_MK'  => 'mk-MK',
		'ml_IN'  => 'ml-IN',
		'mn'     => 'mn-MN',
		'mr'     => 'mr-IN',
		'ms_MY'  => 'ms-MY',
		'mt_MT'  => 'mt-MT',
		'my_MM'  => 'my-MM',
		'ne_NP'  => 'ne-NP',
		'nl_BE'  => 'nl-BE',
		'nl_NL'  => 'nl-NL',
		'nn_NO'  => 'nn-NO',
		'no_NO'  => 'no-NO',
		'pa_IN'  => 'pa-IN',
		'pl_PL'  => 'pl-PL',
		'ps'     => 'ps-AF',
		'pt_BR'  => 'pt-BR',
		'pt_PT'  => 'pt-PT',
		'ro_RO'  => 'ro-RO',
		'ru_RU'  => 'ru-RU',
		'sa_IN'  => 'sa-IN',
		'sd_PK'  => 'sd-PK',
		'si_LK'  => 'si-LK',
		'sk_SK'  => 'sk-SK',
		'sl_SI'  => 'sl-SI',
		'so_SO'  => 'so-SO',
		'sq'     => 'sq-SQ',
		'sr_RS'  => 'sr-CS',
		'su_ID'  => 'su-ID',
		'sv_SE'  => 'sv-SE',
		'sw'     => 'sw-TZ',
		'sw_TZ'  => 'sw-TZ',
		'ta_IN'  => 'ta-IN',
		'te'     => 'te-IN',
		'tg'     => 'tg-TJ',
		'th'     => 'th-TH',
		'th_TH'  => 'th-TH',
		'tir'    => 'ti-ER',
		'tl'     => 'tl-PH',
		'tr_TR'  => 'tr-TR',
		'ug_CN'  => 'ug-CN',
		'uk'     => 'uk-UA',
		'uk_UA'  => 'uk-UA',
		'ur'     => 'ur-PK',
		'ur_PK'  => 'ur-PK',
		'uz_UZ'  => 'uz-UZ',
		'vi'     => 'vi-VN',
		'vi_VN'  => 'vi-VN',
		'zh_CN'  => 'zh-CN',
		'zh_HK'  => 'zh-HK',
		'zh_TW'  => 'zh-TW',
		'zh_SG'  => 'zh-SG',
	);


	/**
	 * Verifies that a lingotek-locale (ie. es-ES) is an allowed
	 * TMS locale.
	 *
	 * @param string $lingotek_locale
	 * @return boolean
	 */
	public static function is_allowed_tms_locale( $lingotek_locale ) {
		return isset( self::$lingotek_locales[ $lingotek_locale ] ) || in_array( $lingotek_locale, self::$lingotek_locales );
	}

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0==
	 *
	 * @var      string
	 */
	protected $plugin_slug = LINGOTEK_PLUGIN_SLUG;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	protected static $logging = false;

	/**
	 * Constructor
	 *
	 * @since 0.1
	 */
	public function __construct() {
		// manages plugin activation and deactivation.
		register_activation_hook( __FILE__, array( &$this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

		$action = filter_input( INPUT_GET, 'action' );
		$plugin = filter_input( INPUT_GET, 'plugin' );

		// stopping here if we are going to deactivate the plugin (avoids breaking rewrite rules).
		if ( ! empty( $action ) && ! empty( $plugin ) && 'deactivate' === $action && plugin_basename( __FILE__ ) === $plugin ) {
			return;
		}

		$action = isset( $action ) ? $action : filter_input( INPUT_POST, 'action' );
		// loads the admin side of Polylang for the dashboard.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $action ) && 'lingotek_language' === $action ) {
			define( 'PLL_AJAX_ON_FRONT', false );

			add_filter( 'pll_model', array( &$this, 'PLL_Admin_Model' ) );
		}

		// Autoload classes.
		spl_autoload_register( array( &$this, 'autoload' ) );

		// init.
		add_filter( 'pll_model', array( &$this, 'pll_model' ) );
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );

		// add Lingotek locale to languages.
		add_filter( 'pll_languages_list', array( &$this, 'pre_set_languages_list' ) );

		// flag title.
		add_filter( 'pll_flag_title', array( &$this, 'pll_flag_title' ), 10, 3 );

		// adds a pointer upon plugin activation to draw attention to Lingotek.
		if ( ! get_option( 'lingotek_token' ) ) {
			add_action( 'init', array( &$this, 'lingotek_activation_pointer' ) );
		}
		add_action( 'init', array( &$this, 'lingotek_professional_translation_pointer' ) );

		// adds extra plugin compatibility - borrowed from Polylang.
		if ( ! defined( 'LINGOTEK_PLUGINS_COMPAT' ) || LINGOTEK_PLUGINS_COMPAT ) {
			Lingotek_Plugins_Compat::instance();
		}

		add_action( 'plugins_loaded', array( &$this, 'lingotek_plugin_migration' ) );

		/**
		 * We temporarely inform of the new UI. We might want to remove this after some releases.
		 *
		 * @since 1.15.0
		 */
		add_action( 'init', array( &$this, 'lingotek_new_ui' ), 10, 2 );
	}

	/**
	 * Show the bubble announcing the new UI.
	 *
	 * @since 1.15.0
	 */
	public function lingotek_new_ui() {
		$content = __( 'Lingotek new UI is ready! If you still need the Polylang based columns, customize those in "Screen Options"', 'lingotek-translation' );

		$buttons = array(
			array(
				'label' => __( 'Close' ),
			),
		);

		$args = array(
			'pointer'  => 'lingotek-new-ui',
			'id'       => 'lingotek_targets',
			'position' => array(
				'edge'  => 'top',
				'align' => 'left',
			),
			'width'    => 380,
			'title'    => __( 'Lingotek New UI', 'lingotek-translation' ),
			'content'  => $content,
			'buttons'  => $buttons,
		);

		new Lingotek_Pointer( $args );
	}

	public function lingotek_plugin_migration() {
		$updates_have_run = false;
		if ( $this->needs_to_run_updates( '1.4.11' ) ) {
			// Add the updates introduced in 1.4.11.
			$this->update_1_4_11_for_removing_custom_fields_from_postTypes_settings();
			$this->update_1_4_11_for_removing_custom_fields_autoload();
			$this->update_1_4_11_for_removing_community_resources_autoload();
			$updates_have_run = true;
		}
		if ( $this->needs_to_run_updates( '1.4.13' ) ) {
			// Add the updates introduced in 1.4.13.
			$this->update_1_4_13_for_disabling_autoload_for_all_wpoptions();
			$updates_have_run = true;
		}
		// If any update happened, inform the user and update the version.
		if ( $updates_have_run ) {
			update_option( 'lingotek_plugin_version', LINGOTEK_VERSION, false );
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success is-dismissible">
                <p>' . __( 'Upgrades for the lingotek translation module have completed.', 'lingotek-translation' ) . '</p>
              </div>';
				}
			);
		}
	}

	/**
	 * Check if we need to run these updates.
	 *
	 * It will check that the installed version is newer than the db one, even if we
	 * had skipped any version, and ensures we don't run any update if we already did.
	 *
	 * @param $version_updates
	 *   The version where these updates were introduced.
	 * @return bool
	 *   true if needs to run, false otherwise
	 *
	 * @since 1.4.11
	 */
	public function needs_to_run_updates( $version_updates ) {
		$db_version = get_option( 'lingotek_plugin_version' );
		return ( $db_version !== false &&
			version_compare( LINGOTEK_VERSION, $version_updates, '>=' ) &&
			version_compare( $version_updates, $db_version, '>' ) );
	}

	/**
	 * As we already have the metas in the lingotek_custom_fields options, we can
	 * remove them from the lingotek_content_type option.
	 *
	 * @since 1.4.11
	 */
	public function update_1_4_11_for_removing_custom_fields_from_postTypes_settings() {
		$settings = get_option( 'lingotek_content_type', array() );
		foreach ( $settings as $enabled_post_type => &$enabled_post_type_settings ) {
			unset( $enabled_post_type_settings['fields']['metas'] );
		}
		update_option( 'lingotek_content_type', $settings, false );
	}

	/**
	 * Set the lingotek_custom_fields option as not autoloaded.
	 *
	 * @since 1.4.11
	 */
	public function update_1_4_11_for_removing_custom_fields_autoload() {
		$settings = get_option( 'lingotek_custom_fields' );
		update_option( 'lingotek_custom_fields', 'fakeValueSoWeForceAutoloadToFalse', false );
		update_option( 'lingotek_custom_fields', $settings, false );
	}

	/**
	 * Set the lingotek_community_resources option as not autoloaded.
	 *
	 * @since 1.4.11
	 */
	public function update_1_4_11_for_removing_community_resources_autoload() {
		$settings = get_option( 'lingotek_community_resources' );
		update_option( 'lingotek_community_resources', 'fakeValueSoWeForceAutoloadToFalse', false );
		update_option( 'lingotek_community_resources', $settings, false );
	}

	/**
	 * Set the lingotek_custom_fields option as not autoloaded.
	 *
	 * @since 1.4.13
	 */
	public function update_1_4_13_for_disabling_autoload_for_all_wpoptions() {
		$wpoptions = array(
			'lingotek_base_url',
			'lingotek_plugin_version',
			'lingotek_community',
			'lingotek_defaults',
			'lingotek_profiles',
			'lingotek_token',
		);
		foreach ( $wpoptions as $wpoption ) {
			$settings = get_option( $wpoption );
			if ( $wpoption !== false ) {
				update_option( $wpoption, 'fakeValueSoWeForceAutoloadToFalse', false );
				update_option( $wpoption, $settings, false );
			}
		}
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    0.1
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activation or deactivation for all blogs
	 * method taken from Polylang
	 *
	 * @since 0.1
	 *
	 * @param string $what either 'activate' or 'deactivate'.
	 */
	protected function do_for_all_blogs( $what ) {
		// network.
		$network_wide = filter_input( INPUT_GET, 'networkwide' );

		if ( is_multisite() && ! empty( $network_wide ) && ( 1 === $network_wide ) ) {
			global $wpdb;

			foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
				switch_to_blog( $blog_id );
				'activate' === $what ? $this->_activate() : $this->_deactivate();
			}
			restore_current_blog();
		} //end if
		else {
			'activate' === $what ? $this->_activate() : $this->_deactivate();
		}
	}

	/**
	 * Plugin activation for multisite
	 *
	 * @since 0.1
	 */
	public function activate() {
		$this->do_for_all_blogs( 'activate' );
	}

	/**
	 * Plugin activation
	 *
	 * @since 0.1
	 */
	protected function _activate() {
		global $polylang;

		if ( isset( $polylang ) ) {
			// To add lingotek_locale property.
			$polylang->model->clean_languages_cache();
		}

		// default profiles.
		if ( false === get_option( 'lingotek_profiles' ) ) {
			update_option( 'lingotek_profiles', self::get_default_profiles() );
		}

		// for the end point for the Lingoteck callback in rewrite rules.
		// don't use flush_rewrite_rules at network activation. See #32471.
		delete_option( 'rewrite_rules' );

		// Update the option for the installed version if it's the first time is activated,
		// so updates are not attempted to run when they shouldn't.
		if ( ! get_option( 'lingotek_plugin_version', false ) ) {
			update_option( 'lingotek_plugin_version', LINGOTEK_VERSION );
		}
	}

	/**
	 * Provides localized version of the canned translation profiles
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_profiles() {
		$default_profiles = self::get_default_profiles();
		$profiles         = get_option( 'lingotek_profiles' );
		if ( is_array( $profiles ) ) {
			$profiles = array_merge( $default_profiles, $profiles );
		} else {
			$profiles = $default_profiles;
		}

		// localize canned profile names.
		foreach ( $profiles as $k => $v ) {
			if ( in_array( $k, array( 'automatic', 'manual', 'disabled' ), true ) ) {
				$profile_name = $profiles[ $k ]['name'];
				// Localize canned profile names.
				$profiles[ $k ]['name'] = __( $profile_name, 'lingotek-translation' );
			}
		}

		update_option( 'lingotek_profiles', $profiles );
		return $profiles;
	}

	/**
	 * Plugin deactivation for multisite
	 *
	 * @since 0.1
	 */
	public function deactivate() {
		$this->do_for_all_blogs( 'deactivate' );
	}

	/**
	 * Plugin deactivation
	 *
	 * @since 0.5
	 */
	protected function _deactivate() {
		delete_option( 'rewrite_rules' );
	}

	/**
	 * Blog creation on multisite (to set default options)
	 *
	 * @since 0.1
	 *
	 * @param int $blog_id blog id.
	 */
	public function wpmu_new_blog( $blog_id ) {
		switch_to_blog( $blog_id );
		$this->_activate();
		restore_current_blog();
	}

	/**
	 * Autoload classes
	 *
	 * @since 0.1
	 *
	 * @param string $class class name.
	 */
	public function autoload( $class ) {
		// not a Lingotek class.
		if ( 0 !== strncmp( 'Lingotek_', $class, 9 ) ) {
			return;
		}

		$class = self::convert_class_to_file( $class );
		foreach ( array( LINGOTEK_WORKFLOWS, LINGOTEK_INC, LINGOTEK_ADMIN_INC ) as $path ) {
			if ( file_exists( $file = "$path/$class.php" ) ) {
				require_once $file;
				break;
			}
		}
	}

	/**
	 * Set the Polylang model class to PLL_Admin_Model on Lingotek admin pages
	 *
	 * @since 0.2
	 *
	 * @param string $class class name.
	 * @return string modified class 'PLL_Model' | 'PLL_Admin_Model'.
	 */
	public function pll_model( $class ) {
		$page = filter_input( INPUT_GET, 'page' );

		if ( PLL_ADMIN && ! empty( $page ) && in_array( $page, array( 'lingotek-translation', 'lingotek-translation_manage', 'lingotek-translation_settings', 'lingotek-translation_network' ), true ) ) {
			return 'PLL_Admin_Model';
		}
		return $class;
	}

	/**
	 * Setups Lingotek model and callback
	 * sets filters to call Lingotek child classes instead of Polylang classes
	 *
	 * @since 0.1
	 */
	public function init() {
		if ( ! defined( 'POLYLANG_VERSION' ) ) {
			return;
		}

		add_rewrite_rule( 'lingotek/?$', 'index.php?lingotek=1&$matches[1]', 'top' );

		if ( is_admin() ) {
			new Lingotek_Admin();
		}

		// admin side.
		if ( PLL_ADMIN && ! PLL_SETTINGS ) {
			$this->model = new Lingotek_Model();
			// overrides Polylang classes.
			$classes = array( 'Filters_Post', 'Filters_Term', 'Filters_Media', 'Filters_Columns' );
			foreach ( $classes as $class ) {
				$method = "Lingotek_$class";

				add_filter( 'pll_' . strtolower( $class ), array( &$this, $method ) );
			}

			// add actions to posts, media and terms list.
			// no need to load this if there is no language yet.
			if ( $GLOBALS['polylang']->model->get_languages_list() ) {
				$this->post_actions   = new Lingotek_Post_Actions();
				$this->term_actions   = new Lingotek_Term_Actions();
				$this->string_actions = new Lingotek_String_actions();
				// Autoloads class.
				new Lingotek_Workflow_Factory();
			}

			$this->utilities = new Lingotek_Utilities();
		} //end if
		elseif ( ! PLL_ADMIN && ! PLL_AJAX_ON_FRONT ) {
			$GLOBALS['wp']->add_query_var( 'lingotek' );

			$this->model    = new Lingotek_Model();
			$this->callback = new Lingotek_Callback( $this->model );
		}
	}

	public function PLL_Admin_Model() {
		return 'PLL_Admin_Model';
	}

	public function Lingotek_Filters_Post() {
		return 'Lingotek_Filters_Post';
	}

	public function Lingotek_Filters_Term() {
		return 'Lingotek_Filters_Term';
	}

	public function Lingotek_Filters_Media() {
		return 'Lingotek_Filters_Media';
	}

	public function Lingotek_Filters_Columns() {
		return 'Lingotek_Filters_Columns';
	}

	/**
	 * Some init
	 *
	 * @since 0.1
	 */
	public function admin_init() {
		// plugin i18n, only needed for backend.
		load_plugin_textdomain( 'lingotek-translation', false, basename( LINGOTEK_DIR ) . '/languages' );

		if ( ! defined( 'POLYLANG_VERSION' ) ) {
			add_action( 'all_admin_notices', array( &$this, 'pll_inactive_notice' ) );
		} elseif ( version_compare( POLYLANG_VERSION, LINGOTEK_MIN_PLL_VERSION, '<' ) ) {
			add_action( 'all_admin_notices', array( &$this, 'pll_old_notice' ) );
		} elseif ( isset( $GLOBALS['polylang'] ) && ! count( $GLOBALS['polylang']->model->get_languages_list() ) ) {
			self::create_first_language();
		}

		wp_enqueue_style( 'lingotek_admin', LINGOTEK_URL . '/css/admin.css', array(), LINGOTEK_VERSION );
		wp_enqueue_style( 'lingotek_statuses', LINGOTEK_URL . '/css/statuses.css', array(), LINGOTEK_VERSION );

		wp_enqueue_script( 'lingotek_actions', LINGOTEK_URL . '/js/actions.js', array( 'jquery' ), LINGOTEK_VERSION, true );
		wp_enqueue_style( 'lingotek_actions', LINGOTEK_URL . '/css/actions.css', array(), LINGOTEK_VERSION );
	}

	/**
	 * Displays a notice if Polylang is inactive
	 *
	 * @since 0.1
	 */
	public function pll_inactive_notice() {
		$action = 'install-plugin';
		$slug   = 'polylang';
		$url    = wp_nonce_url(
			add_query_arg(
				array(
					'action' => $action,
					'plugin' => $slug,
				),
				admin_url( 'update.php' )
			),
			$action . '_' . $slug
		);
		printf(
			'<div class="error" style="height:55px"><p style="font-size:1.5em">%s<a href="%s">%s</a></p></div>',
			esc_html( __( 'Lingotek Translation requires Polylang to work. ', 'lingotek-translation' ) ),
			esc_url( $url ),
			esc_html( __( 'Install Polylang', 'lingotek-translation' ) )
		);
	}

	/**
	 * Displays a notice if Polylang is obsolete
	 *
	 * @since 0.1
	 */
	public function pll_old_notice() {
		$allowed_html = array(
			'strong' => array(),
		);
		printf(
			'<div class="error"><p>%s</p></div>',
			sprintf(
				esc_html( __( 'Lingotek Translation requires Polylang %s to work. Please upgrade Polylang.', 'lingotek-translation' ) ),
				wp_kses( '<strong>' . LINGOTEK_MIN_PLL_VERSION . '</strong>', $allowed_html )
			)
		);
	}

	/**
	 * Creates at least on language to avoid breaking the Lingotek Dashboard
	 *
	 * @since 0.2
	 */
	protected static function create_first_language() {
		global $polylang;

		$language;
		if ( file_exists( PLL_ADMIN_INC . '/languages.php' ) ) {
			include PLL_ADMIN_INC . '/languages.php';
			$locale = get_locale();

			// attempts to set the default language from the current locale.
			foreach ( $languages as $lang ) {
				if ( get_locale() === $lang[1] ) {
					$language = $lang;
				}
			}
		}

		// defaults to en_US.
		if ( empty( $language ) ) {
			$language = array( 'en', 'en_US', 'English' );
		}

		// Need admin model.
		$pll_model = new PLL_Admin_Model( $polylang->options );
		$pll_model->add_language(
			array(
				'slug'       => $language[0],
				'locale'     => $language[1],
				'name'       => $language[2],
				'rtl'        => isset( $language[3] ) ? 1 : 0,
				'term_group' => 0,
			)
		);
	}

	/**
	 * Adds Lingotek locale to the PLL_Language objects
	 * uses the map otherwise uses a stupid fallback
	 *
	 * @since 0.1
	 *
	 * @param array $languages list of language objects.
	 * @return array
	 */
	public function pre_set_languages_list( $languages ) {
		foreach ( $languages as $key => $language ) {
			if ( is_object( $language ) ) {
				// Backward compatibility with Polylang < 1.7.3.
				$languages[ $key ]->lingotek_locale = self::map_to_lingotek_locale( $language->locale );
			} else {
				$languages[ $key ]['lingotek_locale'] = self::map_to_lingotek_locale( $language['locale'] );
			}
		}

		return $languages;
	}

	/**
	 * Maps a Lingotek locale to a WordPress locale
	 *
	 * @since 0.3
	 *
	 * @param string $lingotek_locale Lingotek locale.
	 * @return string WordPress locale.
	 */
	public static function map_to_wp_locale( $lingotek_locale ) {
		// look for the locale in the map (take care that Lingotek sends locales with either '_' or '-'.
		// if not found just replace '-' by '_'.
		$wp_locale = array_search( str_replace( '_', '-', $lingotek_locale ), self::$lingotek_locales, true );
		return $wp_locale ? $wp_locale : str_replace( '-', '_', $lingotek_locale );
	}

	/**
	 *  Converts the class name to the appropriate file name for class loading.
	 *  @param string $class the class name.
	 */
	public static function convert_class_to_file( $class ) {
		return str_replace( '_', '-', strtolower( substr( $class, 9 ) ) );
	}

	/**
	 * Maps a WordPress locale to a Lingotek locale
	 *
	 * @since 0.3
	 *
	 * @param string $wp_locale WordPress locale.
	 * @return string Lingotek locale
	 */
	public static function map_to_lingotek_locale( $wp_locale ) {
		// look for the locale in the map.
		// if not found just replace '_ 'by '-'.
		return isset( self::$lingotek_locales[ $wp_locale ] ) ? self::$lingotek_locales[ $wp_locale ] : str_replace( '_', '-', $wp_locale );
	}

	/**
	 * Modifies the flag title to add the locale
	 *
	 * @since 0.3
	 *
	 * @param string $name language name.
	 * @param string $slug language code.
	 * @param string $locale language locale.
	 * @return string
	 */
	public function pll_flag_title( $name, $slug, $locale ) {
		return "$name ($locale)";
	}

	/**
	 *  Writes data to a log.
	 *
	 *  @param multiple $data the data to be written.
	 *  @param string   $label the label to identify the data.
	 */
	public static function log( $data, $label = null ) {
		if ( self::$logging ) {
			$log_string = '';
			if ( is_string( $label ) ) {
				$log_string .= $label . "\n";
			}
			if ( is_string( $data ) ) {
				$log_string .= $data;
			} else {
				$log_string .= print_r( $data, true );
			}
			error_log( $log_string );
		}
	}

	/**
	 * Creates a pointer to draw attention to the new Lingotek menu item upon plugin activation
	 * code borrowed from Polylang
	 *
	 * @since 1.0.1
	 */
	public function lingotek_activation_pointer() {
		$content = __( 'You’ve just installed Lingotek Translation! Click below to activate your account and automatically translate your website for free!', 'lingotek-translation' );

		$buttons = array(
			array(
				'label' => __( 'Close' ),
			),
			array(
				'label' => __( 'Activate Account', 'lingotek-translation' ),
				'link'  => admin_url( 'admin.php?page=' . $this->plugin_slug . '_settings&connect=new' ),
			),
		);

		$args = array(
			'pointer'  => 'lingotek-translation',
			'id'       => 'toplevel_page_lingotek-translation',
			'position' => array(
				'edge'  => 'bottom',
				'align' => 'left',
			),
			'width'    => 380,
			'title'    => __( 'Congratulations!', 'lingotek-translation' ),
			'content'  => $content,
			'buttons'  => $buttons,
		);

		new Lingotek_Pointer( $args );
	}

	public function lingotek_professional_translation_pointer() {
		$content = __( 'Lingotek Professional Translation is now available!', 'lingotek-translation' );

		$buttons = array(
			array(
				'label' => __( 'Close' ),
			),
			array(
				'label' => __( 'Learn More', 'lingotek-translation' ),
				'link'  => admin_url( 'admin.php?page=lingotek-translation_tutorial&sm=content&tutorial=ltk-prof#ltk-prof-trans-header' ),
			),
		);

		$args = array(
			'pointer'  => 'lingotek-professional-translation',
			'id'       => 'toplevel_page_lingotek-translation',
			'position' => array(
				'edge'  => 'bottom',
				'align' => 'left',
			),
			'width'    => 380,
			'title'    => __( 'New Feature', 'lingotek-translation' ),
			'content'  => $content,
			'buttons'  => $buttons,
		);

		new Lingotek_Pointer( $args );
	}

	public static function get_default_profiles() {
		$default_profiles = array();

		if ( /**get_option('lingotek_automatic_enabled')*/ true ) {
			$default_profiles['automatic'] = array(
				'profile'  => 'automatic',
				'name'     => __( 'Automatic', 'lingotek-translation' ),
				'upload'   => 'automatic',
				'download' => 'automatic',
			);
		}

		$default_profiles['manual'] = array(
			'profile'  => 'manual',
			'name'     => __( 'Manual', 'lingotek-translation' ),
			'upload'   => 'manual',
			'download' => 'manual',
		);

		$default_profiles['disabled'] = array(
			'profile' => 'disabled',
			'name'    => __( 'Disabled', 'lingotek-translation' ),
		);

		return $default_profiles;
	}
	public static function mylog( $message = '' ) {
		file_put_contents( 'php://stdout', "$message", FILE_APPEND );
	}
}

require_once 'sanitization.php';
$GLOBALS['wp_lingotek'] = Lingotek::get_instance();
