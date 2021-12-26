<?php
/**
 * Lingotek Admin Class
 */
class Lingotek_Admin {
	/**
	 * Ajax dashboard language endpoint.
	 *
	 * @var string
	 */
	private $ajax_dashboard_language_endpoint = 'lingotek_language';
	/**
	 * Setups filters and action needed on all admin pages and on plugins page
	 *
	 * @since 0.1
	 */
	public function __construct() {
		$plugin            = Lingotek::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		$this->dashboard   = new Lingotek_Dashboard( $plugin );

		$this->pllm = $GLOBALS['polylang']->model;

		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

		// adds a 'settings' link in the plugins table.
		add_filter( 'plugin_action_links_' . LINGOTEK_BASENAME, array( &$this, 'plugin_action_links' ) );

		// adds the link to the languages panel in the WordPress admin menu.
		add_action( 'admin_menu', array( &$this, 'add_menus' ) );

		add_action( 'load-translation_page_lingotek-translation_manage', array( &$this, 'load_manage_page' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );

		add_action( 'wp_ajax_' . $this->ajax_dashboard_language_endpoint, array( &$this->dashboard, 'ajax_language_dashboard' ) );
		add_action( 'wp_ajax_get_current_status', array( $this, 'ajax_get_current_status' ) );
		// Network admin menu.
		add_action( 'network_admin_menu', array( $this, 'add_network_admin_menu' ) );
		add_action( 'wp_ajax_lingotek_authorization_action', array( $this, 'lingotek_authorization_action' ) );
	}

	public function lingotek_authorization_action() {
		$access_token = isset( $_SERVER['HTTP_TOKEN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_TOKEN'] ) ) : null;
		if ( is_null( $access_token ) ) {
			Lingotek_Logger::error( 'access_token was empty in lingotek_authorization_action().' );
		}
		update_option( 'lingotek_token', array( 'access_token' => $access_token ) );
	}

	/**
	 * Gets current status.
	 */
	public function ajax_get_current_status() {
		$lgtm       = &$GLOBALS['wp_lingotek']->model;
		$pllm       = $GLOBALS['polylang']->model;
		$languages  = pll_languages_list( array( 'fields' => 'locale' ) );
		$post_vars  = filter_input_array( INPUT_POST );
		$object_ids = isset( $post_vars['check_ids'] ) ? $post_vars['check_ids'] : null;
		if ( null === $object_ids ) {
			return;
		}
		$terms_translations = $this->get_if_exists( $post_vars, 'terms_translations' );
		$terms              = ! empty( $terms_translations );

		// The main array consists of ids and nonces. Each id has a source language, languages with statuses, and a workbench link.
		$content_metadata = array();
		foreach ( $object_ids as $object_id ) {
			$id       = $object_id;
			$type     = $terms ? 'term' : 'post';
			$taxonomy = $this->get_if_exists( $post_vars, 'taxonomy' );
			if ( ! empty( $taxonomy ) ) {
				if ( strpos( $taxonomy, '&' ) ) {
					$taxonomy = strstr( $taxonomy, '&', true );
				}
			} else {
				$taxonomy = get_post_type( $id );
			}
			$content_metadata[ $id ] = array(
				'existing_trans' => false,
				'source'         => false,
				'doc_id'         => null,
				'source_id'      => null,
				'source_status'  => null,
			);

			$document = $lgtm->get_group( $type, $object_id );
			if ( $document && ! isset( $document->source ) && count( $document->desc_array ) >= 3 ) {
				$content_metadata[ $id ]['existing_trans'] = true;
			}
			if ( $document && isset( $document->source ) && isset( $document->document_id ) && isset( $document->status ) && isset( $document->translations ) ) {
				if ( $document->source !== (int) $object_id ) {
					$document = $lgtm->get_group( $type, $document->source );
				}
				$source_id             = null !== $document->source ? $document->source : $object_id;
				$source_language       = $terms ? pll_get_term_language( $document->source, 'locale' )
					: pll_get_post_language( $document->source, 'locale' );
				$existing_translations = ( 'term' === $type ) ? PLL()->model->term->get_translations( $source_id ) : PLL()->model->post->get_translations( $source_id );

				if ( count( $existing_translations ) > 1 ) {
					$content_metadata[ $id ]['existing_trans'] = true;
				}
				$content_metadata[ $id ]['source']        = $source_language;
				$content_metadata[ $id ]['doc_id']        = $document->document_id;
				$content_metadata[ $id ]['source_id']     = $document->source;
				$content_metadata[ $id ]['source_status'] = $document->status;
				$target_status                            = 'edited' === $document->status || null === $document->status ? 'edited' : $document->status;
				$content_metadata[ $id ][ $source_language ]['status'] = $document->source === $object_id ? $document->status : $target_status;
				if ( is_array( $document->translations ) ) {
					foreach ( $document->translations as $locale => $translation_status ) {
						$content_metadata[ $id ][ $locale ]['status'] = $translation_status;
						// If the target was marked as deleted, there's no workbench link.
						if ( 'deleted' !== $translation_status ) {
							$workbench_link                                       = Lingotek_Actions::workbench_link( $document->document_id, $locale );
							$content_metadata[ $id ][ $locale ]['workbench_link'] = $workbench_link;
						}
					}
				}

					// fills in missing languages, makes life easier for the updater.
				foreach ( $languages as $language ) {
					if ( ! Lingotek::is_allowed_tms_locale( $language ) ) {
						continue; }
					foreach ( $content_metadata as $group => $status ) {
						$language_obj    = $pllm->get_language( $source_language );
						$target_lang_obj = $pllm->get_language( $language );
						$profile         = Lingotek_Model::get_profile( $taxonomy, $language_obj, $group );
						if ( 'disabled' !== $profile['profile'] && false !== $status['source'] ) {
							if ( ! isset( $status[ $language ] ) ) {
								$content_metadata[ $group ][ $language ]['status'] = 'none';
								if ( $document->is_disabled_target( $pllm->get_language( $source_language ), $pllm->get_language( $language ) ) || ( isset( $document->desc_array[ $target_lang_obj->slug ] ) && ! isset( $document->source ) ) ) {
									$content_metadata[ $group ][ $language ]['status'] = 'disabled';
								}
							}
						}
					}
				}
			}//end if

			$language = 'post' === $type ? pll_get_post_language( $id ) : pll_get_term_language( $id );
			$language = $pllm->get_language( $language );
			if ( $language ) {
				$profile = Lingotek_Model::get_profile( $taxonomy, $language, $id );
				if ( 'disabled' === $profile['profile'] && false === $content_metadata[ $id ]['source'] ) {
					$content_metadata[ $id ]['source'] = 'disabled';
				}
			}
		}//end foreach

		// get the nonces associated with the different actions.
		$content_metadata['request_nonce']  = $this->lingotek_get_matching_nonce( 'lingotek-request' );
		$content_metadata['download_nonce'] = $this->lingotek_get_matching_nonce( 'lingotek-download' );
		$content_metadata['upload_nonce']   = $this->lingotek_get_matching_nonce( 'lingotek-upload' );
		$content_metadata['status_nonce']   = $this->lingotek_get_matching_nonce( 'lingotek-status' );
		wp_send_json( $content_metadata );
	}

	/**
	 * Gets the matching nonce.
	 *
	 * @param  string $action action.
	 * @return string         nonce.
	 */
	public function lingotek_get_matching_nonce( $action ) {
		$upload_link = wp_nonce_url( add_query_arg( array( 'action' => $action ) ), $action );
		$nonce_begin = strpos( $upload_link, 'wpnonce=' ) + 8;
		$nonce       = substr( $upload_link, $nonce_begin );
		return $nonce;
	}

	/**
	 * Gets placeholder string.
	 *
	 * @param  array $items list of items.
	 * @return string        string of placeholders.
	 */
	public function lingotek_get_placeholders( $items ) {
		foreach ( $items as $item ) {
			$placeholders .= '%s,';
		}
		$placeholders = rtrim( $placeholders, ',' );
		return $placeholders;
	}

	/**
	 * Gets dashboard endpoint.
	 *
	 * @return url endpoint.
	 */
	public function get_dashboard_endpoint() {
		return site_url( 'wp-admin/admin-ajax.php?action=' . $this->ajax_dashboard_language_endpoint );
	}

	/**
	 * Setup js scripts & css styles (only on the relevant pages)
	 *
	 * @since 0.1
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();

		// FIXME no minified file for now.
		$suffix = '';
		// $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';.
		// for each script:.
		// js_filename => array([0],[1],[2])
		// 0 => array of pages on which to load the script.
		// 1 => array of registered script handles this script depends on (see wp_enqueue_script in wp doc)
		// 2 => int,bool 1 to enqueue script before </body> instead of in the head. Default is false (see wp_enqueue_script in wp doc)
		// FIXME: check if I can load more scripts in footer.
		$scripts = array(
			'progress' => array( array( 'edit', 'upload', 'edit-tags', 'translation_page_lingotek-translation_manage', 'translation_page_lingotek-translation_settings' ), array( 'jquery-ui-progressbar', 'jquery-ui-dialog', 'wp-ajax-response' ), 1 ),
			'updater'  => array( array( 'edit', 'upload', 'edit-tags' ), array( 'jquery-ui-progressbar', 'jquery-ui-dialog', 'wp-ajax-response' ), 1 ),
		);

		$styles = array(
			'admin' => array( array( 'edit', 'upload', 'edit-tags', 'translation_page_lingotek-translation_manage', 'translation_page_lingotek-translation_settings' ), array( 'wp-jquery-ui-dialog' ) ),
		);

		foreach ( $scripts as $script => $v ) {
			if ( in_array( $screen->base, $v[0], true ) ) {
				wp_enqueue_script( 'lingotek_' . $script, LINGOTEK_URL . '/js/' . $script . $suffix . '.js', $v[1], LINGOTEK_VERSION, $v[2] );
				$lingotek_prefs       = get_option( 'lingotek_prefs' );
				$auto_update_interval = isset( $lingotek_prefs['auto_update_status'] ) ? intval( $lingotek_prefs['auto_update_status'] ) : 10;
				wp_localize_script( 'lingotek_' . $script, 'updater_data', array( 'interval' => $auto_update_interval ) );
			}
		}

		foreach ( $styles as $style => $v ) {
			if ( in_array( $screen->base, $v[0], true ) ) {
				wp_enqueue_style( 'lingotek_' . $style, LINGOTEK_URL . '/css/' . $style . $suffix . '.css', $v[1], LINGOTEK_VERSION );
			}
		}

		//for target select dropdown modal
		if ( in_array( $screen->base, array( 'edit', 'upload', 'edit-tags' ), true ) ) {
			wp_enqueue_script( 'lingotek_select2_js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js', array(), LINGOTEK_VERSION, false );
			wp_enqueue_style( 'lingotek_select2_css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/css/select2.min.css', array(), LINGOTEK_VERSION );
		}
	}

	/**
	 * Adds a 'settings' link in the plugins table
	 *
	 * @since 0.1
	 *
	 * @param array $links list of links associated to the plugin.
	 * @return array modified list of links
	 */
	public function plugin_action_links( $links ) {
		array_unshift( $links, '<a href="admin.php?page=lingotek-translation">' . __( 'Settings', 'lingotek-translation' ) . '</a>' );
		return $links;
	}

	/**
	 * Adds the links to the Lingotek panels in the WordPress admin menu
	 *
	 * @since 0.0.1
	 */
	public function add_menus() {
		$title = __( 'Translation', 'lingotek-translation' );
		add_menu_page(
			$title,
			$title,
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_dashboard_page' ),
			'dashicons-translation'
		);

		add_submenu_page( $this->plugin_slug, __( 'Translation Dashboard', 'lingotek-translation' ), __( 'Dashboard', 'lingotek-translation' ), 'manage_options', $this->plugin_slug, array( $this, 'display_dashboard_page' ) );
		add_submenu_page( $this->plugin_slug, __( 'Translation Management', 'lingotek-translation' ), __( 'Manage', 'lingotek-translation' ), 'manage_options', $this->plugin_slug . '_manage', array( $this, 'display_manage_page' ) );
		add_submenu_page( $this->plugin_slug, __( 'Translation Settings', 'lingotek-translation' ), __( 'Settings', 'lingotek-translation' ), 'manage_options', $this->plugin_slug . '_settings', array( $this, 'display_settings_page' ) );
		add_submenu_page( $this->plugin_slug, __( 'Lingotek Tutorial', 'lingotek-translation' ), __( 'Tutorial', 'lingotek-translation' ), 'manage_options', $this->plugin_slug . '_tutorial', array( $this, 'display_tutorial_page' ) );

		$selected_options = Lingotek_Model::get_prefs();
		if ( isset( $selected_options['import_enabled'] ) && '1' === $selected_options['import_enabled']['enabled'] ) {
			add_submenu_page( $this->plugin_slug, __( 'Lingotek Import', 'lingotek-translation' ), __( 'Import', 'lingotek-translation' ), 'manage_options', $this->plugin_slug . '_import', array( $this, 'display_import_page' ) );   }
	}
	/**
	 * Displays the settings page
	 *
	 * @since 0.0.1
	 */
	public function display_settings_page() {

		// disconnect Lingotek account.
		$delete_access_token = filter_input( INPUT_GET, 'delete_access_token' );
		if ( ! empty( $delete_access_token ) ) {
			delete_option( 'lingotek_token' );
			delete_option( 'lingotek_community' );
			delete_option( 'lingotek_defaults' );
		}

		// connect Lingotek account.
		$database_token_details = get_option( 'lingotek_token', array() );
		$access_token           = isset( $database_token_details['access_token'] ) ? $database_token_details['access_token'] : null;
		if ( ! empty( $access_token ) && empty( $delete_access_token ) && ! isset( $database_token_details['login_id'] ) ) {
			// Set and get token details, but only if the login_id is not already set.
			$this->refresh_lingotek_token( $access_token );
		}

		// set page key primarily used for form submissions.
		$page_key = $this->plugin_slug . '_settings';
		$sm       = filter_input( INPUT_GET, 'sm' );
		if ( ! empty( $sm ) ) {
			$page_key .= '&sm=' . sanitize_text_field( $sm );
		}

		// set community.
		$lingotek_community = filter_input( INPUT_POST, 'lingotek_community' );
		if ( ! empty( $lingotek_community ) && strlen( $lingotek_community ) ) {
			check_admin_referer( $page_key, '_wpnonce_' . $page_key );
			update_option( 'lingotek_community', $lingotek_community );
			add_settings_error( 'lingotek_community', 'update', __( 'Your community has been successfully saved.', 'lingotek-translation' ), 'updated' );
			$this->set_community_resources( $lingotek_community );
			$this->refresh_lingotek_token( $access_token );
		}
		$community_id = get_option( 'lingotek_community' );
		if ( ! $community_id ) {
			add_settings_error( 'lingotek_community', 'error', __( 'Select and save the community that you would like to use.', 'lingotek-translation' ), 'error' );
		}

		$token_details = self::has_token_details();
		$redirect_url  = admin_url( 'admin.php?page=' . $this->plugin_slug . '_settings&sm=account' );

		if ( $token_details ) {
			$access_token = $token_details['access_token'];
			$login_id     = $token_details['login_id'];
			$base_url     = get_option( 'lingotek_base_url' );
			include LINGOTEK_ADMIN_INC . '/settings.php';
		} else {
			$connect_url = '';
			// connect cloak redirect.
			$connect = filter_input( INPUT_GET, 'connect' );
			if ( ! empty( $connect ) ) {
				update_option( 'lingotek_base_url', Lingotek_API::PRODUCTION_URL );
				$client = new Lingotek_API();
				echo '<div class="wrap"><p class="description">' . esc_html( __( 'Redirecting to Lingotek to connect your account...', 'lingotek-translation' ) ) . '</p></div>';

				$connect_url = ( 0 === strcasecmp( $connect, 'new' ) ) ? $client->get_new_url( $redirect_url ) : $client->get_connect_url( $redirect_url );
			}
			$connect_account_cloak_url_new  = admin_url( 'admin.php?page=' . $this->plugin_slug . '_settings&connect=new' );
			$connect_account_cloak_url_prod = admin_url( 'admin.php?page=' . $this->plugin_slug . '_settings&connect=production' );
			include LINGOTEK_ADMIN_INC . '/settings/connect-account.php';
		}
	}

	/**
	 * Refreshes the lingotek access token and stores the login id.
	 *
	 * @param string $access_token
	 *   The access token to be refreshed.
	 *
	 * @since 1.5.2
	 */
	public function refresh_lingotek_token( $access_token ) {
		$client        = new Lingotek_API();
		$token_details = $client->get_token_details( $access_token );
		if ( $token_details && strlen( $token_details->login_id ) ) {
			update_option(
				'lingotek_token',
				array(
					'access_token' => $access_token,
					'login_id'     => $token_details->login_id,
				)
			);
			add_settings_error( 'lingotek_token', 'account-connection', __( 'Your Lingotek account has been successfully connected.', 'lingotek-translation' ), 'updated' );
		} else {
			add_settings_error( 'lingotek_token', 'account-connection', __( 'Your Lingotek account was not connected.	The Access Token received was invalid.', 'lingotek-translation' ), 'error' );
		}
	}

	/**
	 * Get possible settings for defaults or translation profiles
	 *
	 * @since 0.2
	 *
	 * @param bool $defaults defaults flag.
	 * @return array
	 */
	public function get_profiles_settings( $defaults = false ) {
		$resources = get_option( 'lingotek_community_resources' );
		$options   = array(
			'manual'    => __( 'Manual', 'lingotek-translation' ),
			'automatic' => __( 'Automatic', 'lingotek-translation' ),
		);

		$download_options = array(
			'manual'    => __( 'Manual', 'lingotek-translation' ),
			'automatic' => __( 'Automatic', 'lingotek-translation' ),
		);

		return array(
			'upload'              => array(
				'label'       => __( 'Upload content', 'lingotek-translation' ),
				'options'     => $options,
				'description' => __( 'How should new and modified content be uploaded to Lingotek?', 'lingotek-translation' ),
			),
			'download'            => array(
				'label'       => __( 'Download translations', 'lingotek-translation' ),
				'options'     => $download_options,
				'description' => __( 'How should completed translations be downloaded to WordPress?', 'lingotek-translation' ),
			),
			'project_id'          => array(
				'label'       => $defaults ? __( 'Default Project', 'lingotek-translation' ) : __( 'Project', 'lingotek-translation' ),
				'options'     => $resources['projects'],
				'description' => __( 'Changes will affect new entities only', 'lingotek-translation' ),
			),
			'workflow_id'         => array(
				'label'       => $defaults ? __( 'Default Workflow', 'lingotek-translation' ) : __( 'Workflow', 'lingotek-translation' ),
				'options'     => $resources['workflows'],
				'description' => __( 'Changes will affect new entities only', 'lingotek-translation' ),
			),
			'primary_filter_id'   => array(
				'label'       => $defaults ? __( 'Primary Filter', 'lingotek-translation' ) : __( 'Primary Filter', 'lingotek-translation' ),
				'options'     => $resources['filters'],
				'description' => __( 'Changes will affect new entities only', 'lingotek-translation' ),
			),
			'secondary_filter_id' => array(
				'label'       => $defaults ? __( 'Secondary Filter', 'lingotek-translation' ) : __( 'Secondary Filter', 'lingotek-translation' ),
				'options'     => $resources['filters'],
				'description' => __( 'Changes will affect new entities only', 'lingotek-translation' ),
			),
		);
	}

	/**
	 * Get usage for all profiles
	 *
	 * @since 0.2
	 *
	 * @param arry $profiles profiles.
	 * @return array profiles with added usage column
	 */
	public function get_profiles_usage( $profiles ) {
		$content_types = get_option( 'lingotek_content_type' );

		// initialize usage column.
		foreach ( $profiles as $key => $profile ) {
			$profiles[ $key ]['usage'] = 0;
		}

		// fill usage column.
		foreach ( array_merge( $this->pllm->get_translated_post_types(), $this->pllm->get_translated_taxonomies(), array( 'string' ) ) as $type ) {
			if ( isset( $content_types[ $type ]['profile'] ) ) {
				$profiles[ $content_types[ $type ]['profile'] ]['usage'] += 1;
			} elseif ( 'post' === $type ) {
				$profiles['automatic']['usage'] += 1;
			} else {
				$profiles['manual']['usage'] += 1;
			}

			if ( isset( $content_types[ $type ]['sources'] ) ) {
				foreach ( $content_types[ $type ]['sources'] as $profile ) {
					$profiles[ $profile ]['usage'] += 1;
				}
			}
		}
		return $profiles;
	}

	/**
	 * Store community options and set any missing defaults to the first available option
	 *
	 * @since 0.1.0
	 *
	 * @param string $community_id communit id.
	 * @param bool   $update_first_project_callback boolean flag.
	 */
	public function set_community_resources( $community_id, $update_first_project_callback = false ) {
		$client          = new Lingotek_API();
		$refresh_success = array(
			'projects'  => false,
			'workflows' => false,
		);

		$api_data = $client->get_projects( $community_id );
		$projects = array();
		if ( false !== $api_data ) {
			foreach ( $api_data->entities as $project ) {
				$projects[ $project->properties->id ] = $project->properties->title;
			}
			if ( 1 === $api_data->properties->total ) {
				if ( ! $project->properties->callback_url ) {
					$client->update_callback_url( $project->properties->id );
				}
			}
			// Order by title (case-insensitive).
			natcasesort( $projects );
			$refresh_success['projects'] = true;
		}

		$api_data          = $client->get_workflows( $community_id );
		$default_workflows = array(
			'c675bd20-0688-11e2-892e-0800200c9a66' => 'Machine Translation',
			'ddf6e3c0-0688-11e2-892e-0800200c9a66' => 'Machine Translation + Post-Edit',
			'6ff1b470-33fd-11e2-81c1-0800200c9a66' => 'Machine Translation + Translate',
			'2b5498e0-f3c7-4c49-9afa-cca4b3345af7' => 'Translation + 1 review',
			'814172a6-3744-4da7-b932-5857c1c20976' => 'Translation + 2 reviews',
			'2210b148-0c44-4ae2-91d0-ca2ee47c069e' => 'Translation + 3 reviews',
			'7993b4d7-4ada-46d0-93d5-858db46c4c7d' => 'Translation Only',
		);
		$workflows         = array();
		if ( $api_data ) {
			foreach ( $api_data->entities as $workflow ) {
				$workflows[ $workflow->properties->id ] = $workflow->properties->title;
			}
			$diff = array_diff_key( $workflows, $default_workflows );
			if ( empty( $diff ) ) {
				$workflows = array(
					'c675bd20-0688-11e2-892e-0800200c9a66' => 'Machine Translation',
				);
			}
			// Order by title (case-insensitive).
			natcasesort( $workflows );
			if ( Lingotek_Professional_Translation_Workflow::is_allowed_user() ) {
				$workflows['ltk-professional-translation'] = 'Lingotek Professional Translation';
			}
			$workflows                    = array_merge( array( 'project-default' => 'Use Project Default' ), $workflows );
			$refresh_success['workflows'] = true;
		}

		$api_data = $client->get_filters();
		$filters  = array();
		if ( $api_data && $api_data->properties->total > 0 ) {
			foreach ( $api_data->entities as $filter ) {
				if ( ! $filter->properties->is_public ) {
					$filters[ $filter->properties->id ] = $filter->properties->title;
				}
				if ( 'okf_json@with-html-subfilter.fprm' === $filter->properties->title || 'okf_html@wordpress.fprm' === $filter->properties->title ) {
					$filters[ $filter->properties->id ] = $filter->properties->title;
				}
			}
			$primary_filter_id   = array_search( 'okf_json@with-html-subfilter.fprm', $filters, true );
			$secondary_filter_id = array_search( 'okf_html@wordpress.fprm', $filters, true );
			$defaults            = get_option( 'lingotek_defaults', array() );
			if ( empty( $defaults ) ) {
				$defaults['primary_filter_id']   = $primary_filter_id;
				$defaults['secondary_filter_id'] = $secondary_filter_id;
				update_option( 'lingotek_defaults', $defaults );
			}
		}

		$resources = array(
			'projects'  => $projects,
			'workflows' => $workflows,
			'filters'   => $filters,
		);

		if ( true === $refresh_success['projects'] || true === $refresh_success['workflows'] ) {
			update_option( 'lingotek_community_resources', $resources );
			$this->ensure_valid_defaults();
		}
		return $refresh_success;
	}

	/**
	 * Ensures valid defaults.
	 */
	public function ensure_valid_defaults() {
		$resources     = get_option( 'lingotek_community_resources' );
		$defaults      = get_option( 'lingotek_defaults', array() );
		$valid_default = array();
		foreach ( $resources as $resource_key => $options ) {
			$key                   = substr( $resource_key, 0, strlen( $resource_key ) - 1 ) . '_id';
			$valid_default[ $key ] = 0;
			if ( empty( $defaults ) ) {
				continue;
			}
			foreach ( $options as $option_key => $option_val ) {
				if ( ! array_key_exists( $key, $defaults ) ) {
					continue;
				}
				if ( $option_key === $defaults[ $key ] ) {
					$valid_default[ $key ] = 1;
					break;
				}
			}
		}
		foreach ( $valid_default as $key => $valid ) {
			$resource_key = substr( $key, 0, strpos( $key, '_' ) ) . 's';
			if ( $valid ) {
				continue;
			} else {
				$defaults[ $key ] = current( array_keys( $resources[ $resource_key ] ) );
			}
		}
		$num_valid_defaults = array_sum( $valid_default );

		if ( $num_valid_defaults < count( $valid_default ) ) {
			/* translators: %s: Admin url for Lingotek translation settings. */
			add_settings_error( 'lingotek_defaults', 'community-selected', sprintf( __( 'Your <a href="%s"><i>Defaults</i></a> have been updated to valid options for this community.', 'lingotek-translation' ), admin_url( 'admin.php?page=lingotek-translation_settings&sm=defaults' ) ), 'updated' );
		}
		unset( $defaults['filter_id'] );
		update_option( 'lingotek_defaults', $defaults );
	}

	/**
	 * Displays the admin manage page
	 *
	 * @since 0.1.0
	 */
	public function display_manage_page() {

		/**
		 * If we need to perform a redirect we don't want to get the Cannot Modify Headers error so we write
		 * our data to the output buffer and before performing any sort of redirection we can flush the output buffer.
		 */
		ob_start();
		if ( self::has_token_details() ) {
			include LINGOTEK_ADMIN_INC . '/view-manage.php';
		} else {
			$this->display_settings_page();
		}
		ob_end_flush();
	}

	/**
	 * Add screen option on translations->manage page
	 *
	 * @since 0.2
	 */
	public function load_manage_page() {
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Strings groups', 'lingotek-translation' ),
				'default' => 10,
				'option'  => 'lingotek_strings_per_page',
			)
		);
	}

	/**
	 * Save screen option from translations->manage page
	 *
	 * @since 0.2
	 *
	 * @param item   $status status.
	 * @param string $option option.
	 * @param item   $value value.
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( 'lingotek_strings_per_page' === $option ) {
			return $value;
		}
		return $status;
	}

	/**
	 * Displays the admin manage page
	 *
	 * @since 0.1.0
	 */
	public function display_dashboard_page() {
		$token_details = self::has_token_details();
		if ( $token_details ) {
			$community_id = get_option( 'lingotek_community' );
			$defaults     = get_option( 'lingotek_defaults', array() );
			$user         = wp_get_current_user();

			// The data that will be passed to the Lingotek GMC dashboard.
			$cms_data = array(
				// lingotek.
				'community_id'   => $community_id,
				'external_id'    => $token_details['login_id'],
				'workflow_id'    => $defaults['workflow_id'],
				'project_id'     => $defaults['project_id'],
				'first_name'     => $user->display_name,
				'last_name'      => '',
				'email'          => get_bloginfo( 'admin_email' ),
				// cms.
				'cms_site_id'    => site_url(),
				'cms_site_key'   => site_url(),
				'cms_site_name'  => get_bloginfo( 'name' ),
				'cms_type'       => 'Wordpress',
				'cms_version'    => get_bloginfo( 'version' ),
				'cms_tag'        => LINGOTEK_PLUGIN_SLUG,
				'locale'         => pll_current_language( 'lingotek_locale' ),
				'module_version' => LINGOTEK_VERSION,
				'endpoint_url'   => $this->get_dashboard_endpoint(),
			);
			include LINGOTEK_ADMIN_INC . '/view-dashboard.php';
		} else {
			$this->display_settings_page();
		}//end if
	}

	/**
	 * Returns the access token when present, otherwise returns FALSE
	 *
	 * @since 0.1.0
	 */
	public static function has_token_details() {
		$token_details = get_option( 'lingotek_token' );
		$has_token     = false;
		if ( false !== $token_details && key_exists( 'access_token', $token_details ) && key_exists( 'login_id', $token_details ) && strlen( $token_details['access_token'] ) && strlen( $token_details['login_id'] ) ) {
			$has_token = true;
			return $token_details;
		}
		return $has_token;
	}

	/**
	 * Displays network settings page.
	 */
	public function display_network_settings_page() {
		if ( is_multisite() && self::has_token_details() ) {
			include LINGOTEK_ADMIN_INC . '/view-network.php';
		} else {
			$this->display_settings_page();
		}
	}

	/**
	 * Adds a Lingotek settings option in the network admin page
	 *
	 * @since 0.1.0
	 */
	public function add_network_admin_menu() {
		add_submenu_page( 'settings.php', __( 'Lingotek Settings', 'lingotek-translation' ), __( 'Lingotek Settings', 'lingotek-translation' ), 'manage_network_options', $this->plugin_slug . '_network', array( $this, 'display_network_settings_page' ), 'dashicons-translation' );
	}

	/**
	 * Displays tutorial page.
	 */
	public function display_tutorial_page() {
		if ( self::has_token_details() ) {
			include LINGOTEK_ADMIN_INC . '/view-tutorial.php';
		} else {
			$this->display_settings_page();
		}
	}

	/**
	 * Displays import page.
	 */
	public function display_import_page() {
		if ( self::has_token_details() ) {
			settings_errors();
			include LINGOTEK_ADMIN_INC . '/import.php';
		} else {
			$this->display_settings_page();
		}
	}

	private function get_if_exists( $array, $key ) {
		return is_array( $array ) && array_key_exists( $key, $array ) ? $array[ $key ] : null;
	}

}
