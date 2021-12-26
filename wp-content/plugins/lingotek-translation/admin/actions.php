<?php

/**
 * Base class to add row and bulk actions to posts, media and terms list
 * Bulk actions management is inspired by http://www.foxrunsoftware.net/articles/wordpress/add-custom-bulk-action/
 *
 * @since 0.2
 */
abstract class Lingotek_Actions {
	/**
	 * Polylang model.
	 *
	 * @var obj
	 */
	public $pllm;
	/**
	 * Lingotek model.
	 *
	 * @var object
	 */
	public $lgtm;
	/**
	 * Must be defined in child class: 'post' or 'term'.
	 *
	 * @var string
	 */
	public $type;
	/**
	 * Actions
	 *
	 * @var array
	 */
	public static $actions;
	/**
	 * Icons
	 *
	 * @var list
	 */
	public static $icons;
	/**
	 * Confirm_message
	 *
	 * @var string
	 */
	public static $confirm_message;

	/**
	 * Confirm_delete
	 *
	 * @var string
	 */
	public static $confirm_delete;

	/**
	 * Confirm_cancel
	 *
	 * @var string
	 */
	public static $confirm_cancel;

	/**
	 * Confirm_delete_translation
	 *
	 * @var string
	 */
	public static $confirm_delete_translation;

	/**
	 * Confirm_cancel_translation
	 *
	 * @var string
	 */
	public static $confirm_cancel_translation;

	/**
	 * Constructor
	 *
	 * @since 0.2
	 */
	public function __construct( $type ) {
		// confirm message.
		self::$confirm_message            = sprintf( ' onclick = "return confirm(\'%s\');"', __( 'You are about to overwrite existing translations. Are you sure?', 'lingotek-translation' ) );
		self::$confirm_delete             = sprintf( ' onclick = "return confirm(\'%s\');"', __( 'Content will be deleted from WordPress. The associated content in the TMS will be cancelled. Are you sure?', 'lingotek-translation' ) );
		self::$confirm_cancel             = sprintf( ' onclick = "return confirm(\'%s\');"', __( 'You are about to cancel existing translations in your Lingotek community. Are you sure?', 'lingotek-translation' ) );
		self::$confirm_delete_translation = sprintf( ' onclick = "return confirm(\'%s\');"', __( 'Translation will be deleted from WordPress. The associated translation in the TMS will be cancelled. Are you sure?', 'lingotek-translation' ) );
		self::$confirm_cancel_translation = sprintf( ' onclick = "return confirm(\'%s\');"', __( 'You are about to cancel this translation in your Lingotek community. Are you sure?', 'lingotek-translation' ) );
		// row actions.
		self::$actions = array(
			'upload'             => array(
				'action'      => __( 'Upload to Lingotek', 'lingotek-translation' ),
				'progress'    => __( 'Uploading...', 'lingotek-translation' ),
				'description' => __( 'Upload this item to Lingotek TMS', 'lingotek-translation' ),
				'per_locale'  => false,
			),

			'request'            => array(
				'action'      => __( 'Request translations', 'lingotek-translation' ),
				'progress'    => __( 'Requesting translations...', 'lingotek-translation' ),
				'description' => __( 'Request translations of this item to Lingotek TMS', 'lingotek-translation' ),
				'per_locale'  => false,
			),

			'status'             => array(
				'action'      => __( 'Update translations status', 'lingotek-translation' ),
				'progress'    => __( 'Updating translations status...', 'lingotek-translation' ),
				'description' => __( 'Update translations status of this item in Lingotek TMS', 'lingotek-translation' ),
				'per_locale'  => false,
			),

			'download'           => array(
				'action'      => __( 'Download translations', 'lingotek-translation' ),
				'progress'    => __( 'Downloading translations...', 'lingotek-translation' ),
				'description' => __( 'Download translations of this item from Lingotek TMS', 'lingotek-translation' ),
				'per_locale'  => false,
			),

			'cancel'             => array(
				'action'      => __( 'Cancel translations', 'lingotek-translation' ),
				'progress'    => __( 'Cancelling translations...', 'lingotek-translation' ),
				'description' => __( 'Cancel the translations of this item from Lingotek TMS', 'lingotek-translation' ),
				'per_locale'  => false,
			),

			'delete'             => array(
				'action'      => __( 'Delete translations', 'lingotek-translation' ),
				'progress'    => __( 'Deleting translations...', 'lingotek-translation' ),
				'description' => __( 'Delete the translations of this item from Lingotek TMS', 'lingotek-translation' ),
				'per_locale'  => false,
			),

			'cancel-translation' => array(
				'action'      => __( 'Cancel translation', 'lingotek-translation' ),
				'progress'    => __( 'Cancelling translation', 'lingotek-translation' ),
				'description' => __( 'Cancel this translation from Lingotek TMS', 'lingotek-translation' ),
				'per_locale'  => true,
			),

			'delete-translation' => array(
				'action'      => __( 'Delete translation', 'lingotek-translation' ),
				'progress'    => __( 'Deleting translation', 'lingotek-translation' ),
				'description' => __( 'Delete this translation from Lingotek TMS', 'lingotek-translation' ),
				'per_locale'  => false,
			),
		);

		// action icons
		self::$icons = array(
			'upload'      => array(
				'title' => __( 'Upload Now', 'lingotek-translation' ),
				'icon'  => 'upload',
			),

			'importing'   => array(
				'title' => __( 'Importing source', 'lingotek-translation' ),
				'icon'  => 'clock',
			),

			'failed'      => array(
				'title' => __( 'Upload Failed. Click to re-upload', 'lingotek-translation' ),
				'icon'  => 'no',
				'class' => 'lingotek-failed-color',
			),

			'uploaded'    => array(
				'title' => __( 'Source uploaded', 'lingotek-translation' ),
				'icon'  => 'yes',
			),

			'request'     => array(
				'title' => __( 'Request a translation', 'lingotek-translation' ),
				'icon'  => 'plus',
			),

			'pending'     => array(
				'title' => __( 'In Progress', 'lingotek-translation' ),
				'icon'  => 'clock',
			),

			'ready'       => array(
				'title' => __( 'Ready to download', 'lingotek-translation' ),
				'icon'  => 'download',
			),

			'interim'     => array(
				'title' => __( 'Interim Translation Downloaded', 'lingotek-translation' ),
				'icon'  => 'edit',
			),

			'current'     => array(
				'title' => __( 'Current', 'lingotek-translation' ),
				'icon'  => 'edit',
			),

			'not-current' => array(
				'title' => __( 'The target translation is no longer current as the source content has been updated', 'lingotek-translation' ),
				'icon'  => 'edit',
			),

			'error'       => array(
				'title' => __( 'There was an error contacting Lingotek', 'lingotek-translation' ),
				'icon'  => 'no',
			),

			'copy'        => array(
				'title' => __( 'Copy source language', 'lingotek-translation' ),
				'icon'  => 'welcome-add-page',
			),

			'cancelled'   => array(
				'title' => __( 'Translation has been cancelled', 'lingotek-translation' ),
				'icon'  => 'warning',
			),

			'deleted'     => array(
				'title' => __( 'Translation has been deleted', 'lingotek-translation' ),
				'icon'  => 'remove',
			),

			'archived'    => array(
				'title' => __( 'Document has been archived', 'lingotek-translation' ),
				'icon'  => 'remove',
			),
		);

		$this->type = $type;
		$this->pllm = $GLOBALS['polylang']->model;
		$this->lgtm = $GLOBALS['wp_lingotek']->model;

		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_estimate_cost', array( &$this, 'ajax_estimate_cost' ) );
		add_action( 'wp_ajax_request_professional_translation', array( &$this, 'ajax_request_professional_translation' ) );
		add_action( 'wp_ajax_get_user_payment_information', array( &$this, 'ajax_get_user_payment_information' ) );
		add_action( 'wp_ajax_get_ltk_terms_and_conditions', array( &$this, 'ajax_get_ltk_terms_and_conditions' ) );

		foreach ( self::$actions as $action => $action_data ) {
			if ( strpos( $action, '-translation' ) && ! $action_data['per_locale'] ) {
				continue;
			}
			add_action( 'wp_ajax_lingotek_progress_' . $this->type . '_' . $action, array( &$this, 'ajax_' . str_replace( '-', '_', $action ) ) );
		}
	}

	/**
	 * Generates a workbench link
	 *
	 * @since 0.1
	 *
	 * @param string $document_id
	 * @param string $locale Lingotek locale.
	 * @return string Workbench link.
	 */
	public static function workbench_link( $document_id, $locale ) {
		$document_id = ! empty( $document_id ) ? $document_id : '';
		$locale      = $locale ? Lingotek::map_to_lingotek_locale( $locale ) : '';
		return Lingotek_API::PRODUCTION_URL . "/workbench/document/$document_id/locale/$locale";
	}

	/**
	 * Generates a request target link
	 *
	 * @since 1.4.14
	 *
	 * @param string $document_id
	 * @param string $wp_locale WordPress locale.
	 * @return string Workbench link.
	 */
	public static function request_target_link( $document_id, $wp_locale ) {
		$link = wp_nonce_url(
			add_query_arg(
				array(
					'document_id' => $document_id,
					'locale'      => $wp_locale,
					'action'      => 'lingotek-request',
					'noheader'    => true,
				),
				defined( 'DOING_AJAX' ) && DOING_AJAX ? wp_get_referer() : wp_get_referer()
			),
			'lingotek-request'
		);
		return $link;
	}

	/**
	 * Outputs an action icon
	 *
	 * @since 0.2
	 *
	 * @param string $name
	 * @param string $link
	 * @param string $additional Parameters to add (js, target).
	 */
	public static function display_icon( $name, $link, $additional = '' ) {
		self::link_to_settings_if_not_connected( $link );
		if ( 'interim' === $name ) {
			return sprintf(
				'<a class="lingotek-interim-color dashicons dashicons-%s dashicons-%s-lingotek" title="%s" href="%s"%s></a>',
				self::$icons[ $name ]['icon'],
				self::$icons[ $name ]['icon'],
				self::$icons[ $name ]['title'],
				esc_url( $link ),
				$additional
			);
		}
		return sprintf(
			'<a class="lingotek-color dashicons dashicons-%s dashicons-%s-lingotek" title="%s" href="%s"%s></a>',
			self::$icons[ $name ]['icon'],
			self::$icons[ $name ]['icon'],
			self::$icons[ $name ]['title'],
			esc_url( $link ),
			$additional
		);
	}

	/**
	 * Outputs an API error icon
	 *
	 * @since 1.2
	 *
	 * @param string $name
	 * @param string $api_error
	 * @param string $additional Parameters to add (js, target).
	 */
	public static function display_error_icon( $name, $api_error, $additional = '' ) {
		return sprintf(
			'<span class="lingotek-error dashicons dashicons-%s" title="%s"></span>',
			self::$icons[ $name ]['icon'],
			self::$icons[ $name ]['title'] . "\n" . $api_error,
			$additional
		);
	}

	/**
	 * Outputs an upload icon
	 *
	 * @since 0.2
	 *
	 * @param int|string $object_id
	 * @param bool       $confirm
	 */
	public function upload_icon( $object_id, $confirm = false ) {
		$link = $this->upload_url( $object_id );

		return self::display_icon( 'upload', $link, $confirm ? self::$confirm_message : '' );
	}

	/**
	 * Outputs a cancelled icon
	 *
	 * @since 1.4.13
	 *
	 * @param int|string $object_id
	 *   id of the object
	 * @param bool       $target
	 *   boolean to show if icon is for a target or source document
	 * @param bool       $confirm
	 *   boolean to enable the cancel confirm pop up
	 */
	public function cancelled_icon( $object_id, $target = false, $confirm = false ) {
		$action = $target ? 'edit' : 'lingotek-upload';
		$args   = array(
			$this->type => $object_id,
			'action'    => $action,
			'noheader'  => true,
		);
		if ( isset( $args['string'] ) ) {
			$args['string'] = rawurlencode( $args['string'] );
		}
		$link = wp_nonce_url( defined( 'DOING_AJAX' ) && DOING_AJAX ? add_query_arg( $args, wp_get_referer() ) : add_query_arg( $args ), $action );
		self::link_to_settings_if_not_connected( $link );
		return self::display_icon( 'cancelled', $link, $confirm ? self::$confirm_message : '' );
	}

	/**
	 * Outputs a copy icon
	 *
	 * @param int|string $object_id
	 * @param string     $target
	 * @param bool       $confirm
	 */
	public function copy_icon( $object_id, $target, $confirm = false ) {
		$args = array(
			$this->type => $object_id,
			'target'    => $target,
			'action'    => 'lingotek-copy',
			'noheader'  => true,
		);
		$link = wp_nonce_url( defined( 'DOING_AJAX' ) && DOING_AJAX ? add_query_arg( $args, wp_get_referer() ) : add_query_arg( $args ), 'lingotek-copy' );
		self::link_to_settings_if_not_connected( $link );
		return self::display_icon( 'copy', $link, $confirm ? self::$confirm_message : '' );
	}

	/**
	 * Outputs an importing icon
	 *
	 * @since 0.2
	 *
	 * @param object $document
	 */
	public static function importing_icon( $document ) {
		$args = array(
			'document_id' => $document->document_id,
			'action'      => 'lingotek-status',
			'noheader'    => true,
		);
		$link = wp_nonce_url( defined( 'DOING_AJAX' ) && DOING_AJAX ? add_query_arg( $args, wp_get_referer() ) : add_query_arg( $args ), 'lingotek-status' );
		self::link_to_settings_if_not_connected( $link );
		return self::display_icon( 'importing', $link );
	}

	/**
	 * Outputs icons for translations
	 *
	 * @since 0.2
	 *
	 * @param object $document
	 * @param object $language
	 */
	public static function translation_icon( $document, $language ) {
		if ( isset( $document->translations[ $language->locale ] ) ) {
			if ( 'ready' === $document->translations[ $language->locale ] ) {
				$link = self::request_target_link( $document->document_id, $language->locale );
				self::link_to_settings_if_not_connected( $link );
				return self::display_icon( $document->translations[ $language->locale ], $link );
			} elseif ( 'not-current' === $document->translations[ $language->locale ] ) {
				return '<div class="lingotek-color dashicons dashicons-no"></div>';
				// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found,Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
			} elseif ( 'current' !== $document->translations[ $language->locale ] && $custom_icon = $document->get_custom_in_progress_icon( $language ) ) {
				return $custom_icon;
			} else {
				$link         = self::workbench_link( $document->document_id, $language->lingotek_locale );
				$target_blank = ' target="_blank"';
				if ( 'deleted' === $document->translations[ $language->locale ] ) {
					$link         = self::request_target_link( $document->document_id, $language->locale );
					$target_blank = '';
				}
				self::link_to_settings_if_not_connected( $link );
				return self::display_icon( $document->translations[ $language->locale ], $link, $target_blank );
			}//end if
		} else {
			$link = self::request_target_link( $document->document_id, $language->locale );
			self::link_to_settings_if_not_connected( $link );
			return self::display_icon( 'request', $link );
		}//end if
	}

	/**
	 * Outputs icon for failed import callback
	 *
	 * @since 1.4.3
	 */
	public function failed_import_icon( $name, $object_id ) {
		$link = $this->upload_url( $object_id );
		return sprintf(
			'<a class="%s dashicons dashicons-%s" title="%s" href="%s"></a>',
			self::$icons[ $name ]['class'],
			self::$icons[ $name ]['icon'],
			self::$icons[ $name ]['title'],
			esc_url( $link )
		);
	}

	/**
	 * Creates an html action link
	 *
	 * @since 0.2
	 *
	 * @param array $args Parameters to add to the link.
	 * @param bool  $warning Whether to display an alert or not, optional, defaults to false.
	 * @return string
	 */
	protected function get_action_link( $args, $warning = false ) {
		$action           = $args['action'];
		$args['action']   = 'lingotek-' . $action;
		$args['noheader'] = true;
		if ( isset( $args['string'] ) ) {
			$args['string'] = rawurlencode( $args['string'] );
		}
		$link = wp_nonce_url( defined( 'DOING_AJAX' ) && DOING_AJAX ? add_query_arg( $args, wp_get_referer() ) : add_query_arg( $args ), 'lingotek-' . $action );
		self::link_to_settings_if_not_connected( $link );

		switch ( $action ) {
			case 'delete-translation':
				$message = self::$confirm_delete_translation;
				break;
			case 'delete':
				$message = self::$confirm_delete;
				break;
			case 'cancel-translation':
				$message = self::$confirm_cancel_translation;
				break;
			case 'cancel':
				$message = self::$confirm_cancel;
				break;
			default:
				$message = self::$confirm_message;
				break;
		}

		return sprintf(
			'<a class="lingotek-color" title="%s" href="%s"%s>%s</a>',
			self::$actions[ $action ]['description'],
			$link,
			empty( $warning ) ? '' : $message,
			self::$actions[ $action ]['action']
		);
	}

	private static function link_to_settings_if_not_connected( &$link ) {
		if ( ! get_option( 'lingotek_token' ) || ! get_option( 'lingotek_community' ) ) {
			$link = get_site_url( null, '/wp-admin/admin.php?page=lingotek-translation_settings' );
		}
	}

	/**
	 * Adds a row action link
	 *
	 * @since 0.2
	 *
	 * @param array $actions List of action links.
	 * @param mixed $id Object id.
	 * @return array
	 */
	protected function _row_actions( $actions, $id ) {
		// first check that a language is associated to this object
		if ( ! $this->get_language( $id ) ) {
			return $actions;
		}
		$document = $this->lgtm->get_group( $this->type, $id );

		$target_id = $id;
		if ( 'string' !== $this->type && isset( $document->desc_array['lingotek']['source'] ) ) {
			$id = $document->desc_array['lingotek']['source'];
		}

		if ( $this->lgtm->can_upload( $this->type, $id ) || ( isset( $document->source ) && 'string' !== $this->type && $this->lgtm->can_upload( $this->type, $document->source ) ) ) {
			if ( $document ) {
				$desc_array = $document->desc_array;
				unset( $desc_array['lingotek'] );
				$data = array(
					$this->type => $id,
					'action'    => 'upload',
				);
				if ( count( $desc_array ) >= 2 ) {
					$actions['lingotek-upload'] = $this->get_action_link( $data, true );
				} else {
					$actions['lingotek-upload'] = $this->get_action_link( $data );
				}

				/**
				* If a document has been changed but still has translations or is importing we still want to have the
				* update translation status option.
				*/
				if ( 'importing' === $document->status || $document->has_translation_status( 'pending' ) ) {
					$actions['lingotek-status'] = $this->get_action_link(
						array(
							'document_id' => $document->document_id,
							'action'      => 'status',
						)
					);
				}

				if ( $document->has_translation_status( 'ready' ) ) {
					$actions['lingotek-download'] = $this->get_action_link(
						array(
							'document_id' => $document->document_id,
							'action'      => 'download',
						)
					);
				}
			} else {
				$actions['lingotek-upload'] = $this->get_action_link(
					array(
						$this->type => $id,
						'action'    => 'upload',
					)
				);
			}//end if
		} elseif ( isset( $document->translations ) ) {
			// translations to download ?
			if ( $document->has_translation_status( 'ready' ) ) {
				$actions['lingotek-download'] = $this->get_action_link(
					array(
						'document_id' => $document->document_id,
						'action'      => 'download',
					)
				);
			}

			if ( $document->has_translation_status( 'interim' ) ) {
				$actions['lingotek-status'] = $this->get_action_link(
					array(
						'document_id' => $document->document_id,
						'action'      => 'status',
					)
				);
			}

			//need to request translations?
			$language    = $this->get_language( $document->source );
			$all_locales = array_flip( $this->pllm->get_languages_list( array( 'fields' => 'locale' ) ) );
			// In case a language has been deleted.
			if ( ! empty( $language ) ) {
				unset( $all_locales[ $language->locale ] );
			}
			$untranslated = array_diff_key( $all_locales, $document->translations );

			// remove disabled target language from untranslated languages list
			foreach ( $untranslated as $k => $v ) {
				if ( $document->is_disabled_target( $language, $this->pllm->get_language( $k ) ) ) {
					unset( $untranslated[ $k ] );
				}
			}

			if ( 'current' === $document->status && ! empty( $untranslated ) ) {
				$actions['lingotek-request'] = $this->get_action_link(
					array(
						'document_id' => $document->document_id,
						'action'      => 'request',
					)
				);
			}

			// offers to update translations status
			if ( 'importing' === $document->status || $document->has_translation_status( 'pending' ) ) {
				$actions['lingotek-status'] = $this->get_action_link(
					array(
						'document_id' => $document->document_id,
						'action'      => 'status',
					)
				);
			}
		} elseif ( empty( $document->source ) ) {
			$actions['lingotek-upload'] = $this->get_action_link(
				array(
					$this->type => $id,
					'action'    => 'upload',
				),
				true
			);
		}//end if

		$target_locale = $this->get_language( $target_id )->locale;
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( $id == $target_id && isset( $document->source ) && 'cancelled' !== $document->status ) {
			$actions['lingotek-cancel'] = $this->get_action_link(
				array(
					'document_id' => $document->document_id,
					'action'      => 'cancel',
				),
				true
			);
			$actions['lingotek-delete'] = $this->get_action_link(
				array(
					'document_id' => $document->document_id,
					'action'      => 'delete',
				),
				true
			);
		} elseif ( isset( $document->source ) && isset( $document->translations[ $target_locale ] ) && 'cancelled' !== $document->translations[ $target_locale ] ) {
			$actions['lingotek-cancel-translation'] = $this->get_action_link(
				array(
					'document_id'   => $document->document_id,
					'target_id'     => $id,
					'target_locale' => $target_locale,
					'action'        => 'cancel-translation',
				),
				true
			);
			$actions['lingotek-delete-translation'] = $this->get_action_link(
				array(
					'document_id'   => $document->document_id,
					'target_id'     => $target_id,
					'target_locale' => $target_locale,
					'action'        => 'delete-translation',
				),
				true
			);
		}//end if

		return $actions;
	}

	/**
	 * Adds actions to bulk dropdown list table using a javascript hack
	 * as the existing filter does not allow to *add* actions
	 * also displays the progress dialog placeholder
	 *
	 * @since 0.2
	 */
	protected function _add_bulk_actions( $bulk_actions ) {
		foreach ( self::$actions as $action => $action_data ) {
			if ( strpos( $action, '-translation' ) && ! $action_data['per_locale'] ) {
				continue;
			}
			if ( !$action_data['per_locale'] ) {
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain
				$bulk_actions[ 'bulk-lingotek-' . $action ] = __( $action_data['action'], $action );
			}
			if ( null !== filter_input( INPUT_GET, 'bulk-lingotek-' . $action ) ) {
				$text = $action_data['progress'];
			}
			if ( isset( $action_data['per_locale'] ) && true === $action_data['per_locale'] ) {
				foreach ( PLL()->model->get_languages_list() as $language ) {
					$bulk_actions[ 'bulk-lingotek-' . $action . ':' . $language->locale ] = __( $action_data['action'] . ' for ' . $language->lingotek_locale, 'lingotek-translation' );
				}
			}
		}
		if ( ! empty( $text ) ) {
			printf( '<div id="lingotek-progressdialog" style="display:none" title="%s"><div id="lingotek-progressbar"></div></div>', esc_html( $text ) );
		}

		return $bulk_actions;
	}

	/**
	 * Outputs javascript data for progress.js
	 * wp_localize_script($handle, $name, $data) $handle needs to be lingotek_myhandle
	 * where myhandle is handle of script you loaded previously with wp_enqueue_script (probably in admin.php)
	 *
	 * @since 0.1
	 */
	public function admin_enqueue_scripts() {
		foreach ( array_keys( self::$actions ) as $action ) {
			if ( null !== filter_input( INPUT_GET, "bulk-lingotek-$action" ) ) {
				$action_message = 'delete' === $action ? 'cancel' : $action;
				$data           = array(
					'action'   => null === filter_input( INPUT_GET, 'page' ) ? ( null === filter_input( INPUT_GET, 'taxonomy' ) ? "post_$action" : "term_$action" ) : "string_$action",
					'taxonomy' => null === filter_input( INPUT_GET, 'taxonomy' ) || ! taxonomy_exists( wp_unslash( filter_input( INPUT_GET, 'taxonomy' ) ) ) ? '' : filter_input( INPUT_GET, 'taxonomy' ),
					'sendback' => remove_query_arg( array( "bulk-lingotek-$action", 'ids', 'lingotek_warning', 'locales' ), wp_get_referer() ),
					'ids'      => array_map( 'intval', explode( ',', filter_input( INPUT_GET, 'ids' ) ) ),
					// TODO: Fix this based on the possible $action_message values.
					// phpcs:ignore WordPress.WP.I18n.InterpolatedVariableText
					'warning'  => null === filter_input( INPUT_GET, 'lingotek_warning' ) ? ( null === filter_input( INPUT_GET, 'lingotek_remove' ) ? '' : __( "You are about to $action_message existing translations from your Lingotek community. Are you sure?", 'lingotek-translation' ) ) : __( 'You are about to overwrite existing translations. Are you sure?', 'lingotek-translation' ),
					'nonce'    => wp_create_nonce( 'lingotek_progress' ),
				);
				if ( null !== filter_input( INPUT_GET, 'target_locale' ) ) {
					$data['target_locale'] = filter_input( INPUT_GET, 'target_locale' );
				}
				if ( null !== filter_input( INPUT_GET, 'locales' ) ) {
					$data['locales'] = explode( ',', filter_input( INPUT_GET, 'locales' ) );
				}
				wp_localize_script( 'lingotek_progress', 'lingotek_data', $data );
				return;
			}//end if
		}//end foreach
	}

	/**
	 * Manages actions driven by document_id
	 *
	 * @since 0.2
	 *
	 * @param string $action Action name.
	 * @return bool True if the action was managed, false otherwise.
	 */
	protected function _manage_actions( $action ) {
		if ( null !== filter_input( INPUT_GET, 'document_id' ) ) {
			$document_id = filter_input( INPUT_GET, 'document_id' );
			$document    = $this->lgtm->get_group_by_id( $document_id );
		}

		switch ( $action ) {
			case 'lingotek-status':
				check_admin_referer( 'lingotek-status' );
				$document->source_status();
				$document->translations_status();
				break;

			case 'lingotek-request':
				check_admin_referer( 'lingotek-request' );
				Lingotek_Logger::info(
					'User requested to translate an item',
					array(
						'document_id' => isset( $document_id ) ? $document_id : '',
						'locale'      => filter_input(
							INPUT_GET,
							'locale'
						),
					)
				);
				null !== filter_input( INPUT_GET, 'locale' ) ? $document->request_translation( filter_input( INPUT_GET, 'locale' ) ) : $document->request_translations();
				break;

			case 'lingotek-download':
				check_admin_referer( 'lingotek-download' );
				Lingotek_Logger::info(
					'User requested to download translation',
					array(
						'document_id' => isset( $document_id ) ? $document_id : '',
						'locale'      => filter_input(
							INPUT_GET,
							'locale'
						),
					)
				);
				null !== filter_input( INPUT_GET, 'locale' ) ? $document->create_translation( filter_input( INPUT_GET, 'locale' ) ) : $document->create_translations();
				break;

			case 'lingotek-delete':
				check_admin_referer( 'lingotek-delete' );
				/**it was left as disassociate so that the delete functionality wouldn't break as it
				* did when changing disassociate to delete/cancel in other connectors
				*/
				$document->delete();
				if ( null !== filter_input( INPUT_GET, 'lingotek_redirect' ) && filter_input( INPUT_GET, 'lingotek_redirect' ) === true ) {
					$site_id = get_current_blog_id();
					wp_safe_redirect( get_site_url( $site_id, '/wp-admin/edit.php?post_type=page' ) );
					exit();
				}
				break;

			case 'lingotek-cancel':
				check_admin_referer( 'lingotek-cancel' );
				$document->cancel();
				if ( null !== filter_input( INPUT_GET, 'lingotek_redirect' ) && filter_input( INPUT_GET, 'lingotek_redirect' ) === true ) {
					$site_id = get_current_blog_id();
					wp_safe_redirect( get_site_url( $site_id, '/wp-admin/edit.php?post_type=page' ) );
					exit();
				}
				break;

			case 'lingotek-delete-translation':
				check_admin_referer( 'lingotek-delete-translation' );
				$target_locale = filter_input( INPUT_GET, 'target_locale' );
				$target_id     = filter_input( INPUT_GET, 'target_id' );
				$language      = PLL()->model->post->get_language( $target_id );
				$document->delete_translation( $language, $target_id );
				if ( null !== filter_input( INPUT_GET, 'lingotek_redirect' ) && filter_input( INPUT_GET, 'lingotek_redirect' ) === true ) {
					$site_id = get_current_blog_id();
					wp_safe_redirect( get_site_url( $site_id, '/wp-admin/edit.php?post_type=page' ) );
					exit();
				}
				break;

			case 'lingotek-cancel-translation':
				check_admin_referer( 'lingotek-cancel-translation' );
				$target_locale = filter_input( INPUT_GET, 'target_locale' );
				$document_id   = filter_input( INPUT_GET, 'document_id' );
				$document->cancel_translation( $document_id, $target_locale );
				if ( null !== filter_input( INPUT_GET, 'lingotek_redirect' ) && filter_input( INPUT_GET, 'lingotek_redirect' ) === true ) {
					$site_id = get_current_blog_id();
					wp_safe_redirect( get_site_url( $site_id, '/wp-admin/edit.php?post_type=page' ) );
					exit();
				}
				break;

			default:
				return false;
		}//end switch

		return true;
	}

	/**
	 * Ajax response to download translations and showing progress
	 *
	 * @since 0.1
	 */
	public function ajax_download() {
		check_ajax_referer( 'lingotek_progress', '_lingotek_nonce' );
		$document = $this->lgtm->get_group( $this->type, filter_input( INPUT_POST, 'id' ) );
		if ( $document ) {
			foreach ( $document->translations as $locale => $status ) {
				if ( 'pending' === $status || 'ready' === $status || 'interim' === $status || 'current' === $status ) {
					$document->create_translation( $locale );
				}
			}
		}
		die();
	}

	/**
	 * Ajax response to request translations and showing progress
	 *
	 * @since 0.2
	 */
	public function ajax_request() {
		check_ajax_referer( 'lingotek_progress', '_lingotek_nonce' );
		$document = $this->lgtm->get_group( $this->type, filter_input( INPUT_POST, 'id' ) );
		if ( $document ) {
			$document->request_translations();
		}
		die();
	}

	/**
	 * Ajax response to check translation status and showing progress
	 *
	 * @since 0.1
	 */
	public function ajax_status() {
		check_ajax_referer( 'lingotek_progress', '_lingotek_nonce' );
		$document = $this->lgtm->get_group( $this->type, filter_input( INPUT_POST, 'id' ) );
		if ( $document ) {
			$document->source_status();
			$document->translations_status();
		}
		die();
	}

	/**
	 * Ajax response delete translations and showing progress
	 *
	 * @since 0.2
	 */
	public function ajax_delete() {
		check_ajax_referer( 'lingotek_progress', '_lingotek_nonce' );
		$id       = filter_input( INPUT_POST, 'id' );
		$language = PLL()->model->post->get_language( $id );
		$document = $this->lgtm->get_group( $this->type, $id );
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( $document && $document->source == $id ) {
			/**it was left as disassociate so that the delete functionality wouldn't break as it
			* did when changing disassociate to delete/cancel in other connectors
			*/
			$document->delete();
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		} elseif ( $document && $document->source != $id && $language ) {
			$document->delete_translation( $language, $id );
		}
		die();
	}

	/**
	 * Ajax response cancel translations and showing progress
	 */
	public function ajax_cancel() {
		check_ajax_referer( 'lingotek_progress', '_lingotek_nonce' );
		$id       = filter_input( INPUT_POST, 'id' );
		$language = PLL()->model->post->get_language( $id );
		$document = $this->lgtm->get_group( $this->type, $id );
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( $document && $document->source == $id ) {
			$document->cancel();
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		} elseif ( $document && $document->source != $id && $language ) {
			$document->cancel_translation( $document->document_id, $language->lingotek_locale );
		}
		die();
	}

	/**
	 * Ajax response cancel only one target and showing progress
	 *
	 * @since 1.5.0
	 */
	public function ajax_cancel_translation() {
		check_ajax_referer( 'lingotek_progress', '_lingotek_nonce' );
		$id            = filter_input( INPUT_POST, 'id' );
		$target_locale = filter_input( INPUT_POST, 'target_locale' );
		$document      = $this->lgtm->get_group( $this->type, $id );
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( $document && $target_locale ) {
			$document->cancel_translation( $document->document_id, $target_locale );
		}
		die();
	}
	/**
	 * Ajax call to get the price estimation of a given document.
	 */
	public function ajax_estimate_cost() {
		check_ajax_referer( 'lingotek_professional', '_lingotek_nonce' );
		$document_id   = filter_input( INPUT_GET, 'document_id' );
		$locale        = filter_input( INPUT_GET, 'locale' );
		$lingotek_auth = filter_input( INPUT_GET, 'Authorization-Lingotek' );
		$client        = new Lingotek_API();
		$response      = $client->get_cost_estimate( $lingotek_auth, $document_id, $locale );
		echo wp_json_encode( $response );
		die();
	}

	/**
	 * Ajax call to request professional translation of a document through bridge.
	 */
	public function ajax_request_professional_translation() {
		check_ajax_referer( 'lingotek_professional', '_lingotek_nonce' );
		$post_vars = filter_input_array( INPUT_POST );
		$client    = new Lingotek_API();
		$response  = $client->request_professional_translation_bulk( $post_vars['workflow_id'], $post_vars['translations'], $post_vars['total_estimate'], $post_vars['summary'] );
		if ( true === $response['data']['transaction_approved'] ) {
			foreach ( $post_vars['translations'] as $document_id => $locales ) {
				$document = $this->lgtm->get_group( $post_vars['type'], $post_vars['ids'][ $document_id ] );
				if ( $document ) {
					foreach ( $locales as $locale ) {
						$locale = $post_vars['lingotek_locale_to_wp_locale'][ $locale ];
						$document->update_translation_status( $locale, 'pending' );
					}
				} else {
					// TODO: what if a document doesn't exists? T_T
				}
			}
		}

		echo wp_json_encode( $response );
		die();
	}

	public function ajax_get_ltk_terms_and_conditions() {
		check_ajax_referer( 'lingotek_professional', '_lingotek_nonce' );
		$client = new Lingotek_API();
		echo wp_json_encode( $client->get_lingotek_terms_and_conditions() );
		die();
	}

	public function ajax_get_user_payment_information() {
		check_ajax_referer( 'lingotek_professional', '_lingotek_nonce' );
		$client   = new Lingotek_API();
		$response = $client->get_user_payment_information();
		echo wp_json_encode( $response );
		die();
	}

	/**
	 * Collects and returns all API errors
	 *
	 * @since 1.1
	 *
	 * @param string $errors
	 */
	public static function retrieve_api_error( $errors ) {
		$api_error = "\n";

		foreach ( $errors as $error => $error_message ) {
			if ( is_array( $error_message ) ) {
				if ( ! empty( $error_message ) ) {
					foreach ( $error_message as $locale => $message ) {
						$api_error = $api_error . $message . "\n";
					}
				}
			} else {
				$api_error = $api_error . $error_message . "\n";
			}
		}

		return $api_error;
	}

	/**
	 * Gets the upload url.
	 *
	 * @param int         $object_id
	 * @param string|null $locale
	 *
	 * @return string
	 *
	 * @since 1.5.0
	 */
	public function upload_url( $object_id, $locale = null ) {
		$args = array(
			$this->type => $object_id,
			'action'    => 'lingotek-upload',
			'noheader'  => true,
		);
		if ( null !== $locale ) {
			$args['locale'] = $locale;
		}
		if ( isset( $args['string'] ) ) {
			$args['string'] = rawurlencode( $args['string'] );
		}
		$link = wp_nonce_url( defined( 'DOING_AJAX' ) && DOING_AJAX ? add_query_arg( $args, wp_get_referer() ) : add_query_arg( $args ), 'lingotek-upload' );
		self::link_to_settings_if_not_connected( $link );

		return $link;
	}

	/**
	 * Gets the request translation url.
	 *
	 * @param int    $object_id
	 * @param object $document
	 * @param string $ltk_locale
	 *
	 * @return string
	 *
	 * @since 1.5.0
	 */
	public function request_translation_url( $object_id, $document, $ltk_locale ) {
		$link = wp_nonce_url(
			add_query_arg(
				array(
					'document_id' => $document->document_id,
					'locale'      => $ltk_locale,
					'action'      => 'lingotek-request',
					'noheader'    => true,
				),
				defined( 'DOING_AJAX' ) && DOING_AJAX ? wp_get_referer() : wp_get_referer()
			),
			'lingotek-request'
		);
		return $link;
	}

	/**
	 * Gets the check source status url.
	 *
	 * @param int    $object_id
	 * @param object $document
	 * @param string $ltk_locale
	 *
	 * @return string
	 *
	 * @since 1.5.0
	 */
	public function check_source_url( $object_id, $document, $ltk_locale ) {
		$link = wp_nonce_url(
			add_query_arg(
				array(
					'document_id' => $document->document_id,
					'action'      => 'lingotek-status',
					'noheader'    => true,
				),
				defined( 'DOING_AJAX' ) && DOING_AJAX ? wp_get_referer() : wp_get_referer()
			),
			'lingotek-status'
		);
		return $link;
	}

	/**
	 * Gets the check translation status url.
	 *
	 * @param int    $object_id
	 * @param object $document
	 * @param string $ltk_locale
	 *
	 * @return string
	 *
	 * @since 1.5.0
	 */
	public function check_translation_url( $object_id, $document, $ltk_locale ) {
		$link = wp_nonce_url(
			add_query_arg(
				array(
					'document_id' => $document->document_id,
					'action'      => 'lingotek-status',
					'noheader'    => true,
				),
				defined( 'DOING_AJAX' ) && DOING_AJAX ? wp_get_referer() : wp_get_referer()
			),
			'lingotek-status'
		);
		return $link;
	}

	/**
	 * Gets the cancel translation target url.
	 *
	 * @param int    $object_id
	 * @param object $document
	 * @param string $ltk_locale
	 *
	 * @return string
	 *
	 * @since 1.5.0
	 */
	public function cancel_translation_url( $object_id, $document, $ltk_locale ) {
		$link = wp_nonce_url(
			add_query_arg(
				array(
					'document_id'   => $document->document_id,
					'id'            => $object_id,
					'target_locale' => $ltk_locale,
					'action'        => 'lingotek-cancel-translation',
					'noheader'      => true,
				),
				defined( 'DOING_AJAX' ) && DOING_AJAX ? wp_get_referer() : wp_get_referer()
			),
			'lingotek-cancel-translation'
		);

		return $link;
	}

	/**
	 * Gets the download translation url.
	 *
	 * @param int    $object_id
	 * @param object $document
	 * @param string $ltk_locale
	 *
	 * @return string
	 *
	 * @since 1.5.0
	 */
	public function download_translation_url( $object_id, $document, $ltk_locale ) {
		$link = wp_nonce_url(
			add_query_arg(
				array(
					'document_id' => $document->document_id,
					'locale'      => $ltk_locale,
					'action'      => 'lingotek-download',
					'noheader'    => true,
				),
				defined( 'DOING_AJAX' ) && DOING_AJAX ? wp_get_referer() : wp_get_referer()
			),
			'lingotek-download'
		);
		return $link;
	}

	/**
	 * Gets the workbench access url.
	 *
	 * @param int    $object_id
	 * @param object $document
	 * @param string $ltk_locale
	 *
	 * @return string
	 *
	 * @since 1.5.0
	 */
	public function workbench_url( $object_id, $document, $ltk_locale ) {
		$link = self::workbench_link( $document->document_id, $ltk_locale );
		self::link_to_settings_if_not_connected( $link );
		return $link;
	}
}
