<?php

/**
 * Modifies Polylang filters
 *
 * @since 0.1
 */
class Lingotek_Filters_Columns extends PLL_Admin_Filters_Columns {
	/**
	 * Lingotek model.
	 *
	 * @var object
	 */
	public $lgtm;
	/**
	 * Constructor
	 *
	 * @since 0.1
	 *
	 * @param object $polylang Polylang object.
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		$this->lgtm = &$GLOBALS['wp_lingotek']->model;
		// FIXME remove quick edit and bulk edit for now waiting for a solution to remove it only for uploaded documents.
		remove_filter( 'quick_edit_custom_box', array( &$this, 'quick_edit_custom_box' ), 10, 2 );
		remove_filter( 'bulk_edit_custom_box', array( &$this, 'quick_edit_custom_box' ), 10, 2 );

		add_filter( 'default_hidden_columns', array( &$this, 'hide_pll_list_columns' ), 10, 2 );
	}

	/**
	 * Hide by default the polylang based columns.
	 *
	 * @param array      $hidden Hidden columns.
	 * @param \WP_Screen $screen Screen object.
	 * @since 1.15.0
	 */
	public function hide_pll_list_columns( $hidden, $screen ) {
		foreach ( $this->model->get_languages_list() as $language ) {
			$hidden[] = 'language_' . $language->locale;
		}
		return $hidden;
	}

	/**
	 * Adds languages and translations columns in posts, pages, media, categories and tags tables
	 * overrides Polylang method to display all languages including the filtered one
	 * as well as displaying a tooltip with the language name and locale when there is no flag
	 *
	 * @since 0.2
	 *
	 * @param array  $columns list of table columns.
	 * @param string $before the column before which we want to add our languages.
	 * @return array modified list of columns
	 */
	protected function add_column( $columns, $before ) {
		$n = array_search( $before, array_keys( $columns ), true );
		if ( $n ) {
			$end     = array_slice( $columns, $n );
			$columns = array_slice( $columns, 0, $n );
		}
		$this->print_patch_error();
		$this->print_cancel_error();
		$columns ['lingotek_source']  = __( 'Lingotek source', 'lingotek-translation' );
		$columns ['lingotek_targets'] = __( 'Lingotek translations', 'lingotek-translation' );
		foreach ( $this->model->get_languages_list() as $language ) {
			$columns[ 'language_' . $language->locale ] = $language->flag ? $language->flag :
				sprintf(
					'<a href="" title="%s">%s</a>',
					esc_html( "$language->name ($language->locale)" ),
					esc_html( $language->slug )
				);
		}

		return isset( $end ) ? array_merge( $columns, $end ) : $columns;
	}

	private function print_cancel_error() {
		$lingotek_log_errors = get_option( 'lingotek_log_errors' );
		$message             = isset( $lingotek_log_errors['disassociate_document_error'] ) ? $lingotek_log_errors['disassociate_document_error'] : '';
		if ( $message === '' ) {
			return;
		}
		if ( get_option( 'disassociate_source_failed', false ) ) {
			$message = str_replace( 'cancel', 'delete', $message );
			printf(
				'<div class="notice notice-warning is-dismissible">
			<p>%s</p>
			</div>',
				esc_html( $message )
			);
			update_option( 'disassociate_source_failed', false );
		} elseif ( get_option( 'disassociate_target_failed', false ) ) {
			$message = str_replace( 'cancel', 'delete', $message );
			printf(
				'<div class="notice notice-warning is-dismissible">
			<p>%s</p>
			</div>',
				esc_html( $message )
			);
			update_option( 'disassociate_target_failed', false );
		} elseif ( get_option( 'cancel_source_failed', false ) ) {
			printf(
				'<div class="notice notice-warning is-dismissible">
			<p>%s</p>
			</div>',
				esc_html( $message )
			);
			update_option( 'cancel_source_failed', false );
		} elseif ( get_option( 'cancel_target_failed', false ) ) {
			printf(
				'<div class="notice notice-warning is-dismissible">
			<p>%s</p>
			</div>',
				esc_html( $message )
			);
			update_option( 'cancel_target_failed', false );
		}//end if
	}

	private function print_patch_error() {
		$lingotek_log_errors = get_option( 'lingotek_log_errors' );
		if ( empty( $lingotek_log_errors['patch_document_error'] ) ) {
			return;
		}
		printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $lingotek_log_errors['patch_document_error'] ) );
		unset( $lingotek_log_errors['patch_document_error'] );
		update_option( 'lingotek_log_errors', $lingotek_log_errors, false );
	}

	/**
	 * Fills the language and translations columns in the posts and taxonomies lists tables
	 * take care that when doing ajax inline edit, the post or term may not be updated in database yet
	 *
	 * @since 0.2
	 *
	 * @param string $type 'post' or 'term'.
	 * @param string $column column name.
	 * @param int    $object_id id of the current object in row.
	 * @param item   $custom_data data.
	 */
	protected function _column( $type, $column, $object_id, $custom_data = null ) {
		$action             = 'post' === $type ? 'inline-save' : 'inline-save-tax';
		$get_action         = filter_input( INPUT_GET, 'action' );
		$inline_lang_choice = filter_input( INPUT_POST, 'inline_lang_choice' );
		$inline             = defined( 'DOING_AJAX' ) && $get_action === $action && ! empty( $inline_lang_choice );
		$lang               = $inline ? $this->model->get_language( $inline_lang_choice ) : ( 'post' === $type ? PLL()->model->post->get_language( $object_id ) : PLL()->model->term->get_language( $object_id ) );

		if ( ( false === strpos( $column, 'lingotek_' ) && false === strpos( $column, 'language_' ) ) || ! $lang ) {
			if ( $custom_data ) {
				return $custom_data;
			} else {
				return '';
			}
		}

		$language = $this->model->get_language( substr( $column, 9 ) );
		$document = $this->lgtm->get_group( $type, $object_id );

		if ( 0 === strpos( $column, 'lingotek_' ) ) {
			$source_language = PLL()->model->post->get_language( $object_id );
			if ( 'lingotek_source' === $column ) {
				return $this->render_source_status( $object_id, $source_language, $document, $document ? $document->status : 'untracked' );
			}
			if ( 'lingotek_targets' === $column ) {
				return $this->render_target_statuses( $object_id, $source_language, $document );
			}
		}

		// FIXME should I suppress quick edit?.
		// yes for uploaded posts, but I will need js as the field is built for all posts.
		// /!\ also take care not add this field two times when translations are managed by Polylang.
		// hidden field containing the post language for quick edit (used to filter categories since Polylang 1.7).
		if ( $column === $this->get_first_language_column() ) {
			printf( '<div class="hidden" id="lang_%d">%s</div>', esc_attr( $object_id ), esc_html( $lang->slug ) );
		}

		$id = ( ( $inline && $lang->slug !== $this->model->get_language( filter_input( INPUT_POST, 'old_lang' ) )->slug ) ?
			( $language->slug === $lang->slug ? $object_id : 0 ) :
			'post' === $type ) ? PLL()->model->post->get( $object_id, $language ) : PLL()->model->term->get( $object_id, $language );

		if ( isset( $document->source ) ) {
			$source_language = 'post' === $type ? PLL()->model->post->get_language( $document->source ) : PLL()->model->term->get_language( $document->source );
			$source_profile  = Lingotek_Model::get_profile( $this->content_type, $source_language, $document->source );
		} else {
			$source_language = $lang;
		}

		// FIXME not very clean.
		$actions   = 'post' === $type ? $GLOBALS['wp_lingotek']->post_actions : $GLOBALS['wp_lingotek']->term_actions;
		$profile   = Lingotek_Model::get_profile( $this->content_type, $language, $object_id );
		$cancelled = isset( $document->status ) ? 'cancelled' === $document->status : false;
		$disabled  = 'disabled' === $profile['profile'] || ! Lingotek::is_allowed_tms_locale( $language->lingotek_locale );
		// post ready for upload.
		if ( ! $cancelled && $this->lgtm->can_upload( $type, $object_id ) && $object_id === $id ) {
			return $disabled ? ( 'post' === $type ? parent::post_column( $column, $object_id ) : parent::term_column( '', $column, $object_id ) )
			: ( $document && ( count( $document->desc_array ) >= 3 ) ? $actions->upload_icon( $object_id, true ) : $actions->upload_icon( $object_id ) );
		} elseif ( ( 'post' === $type || 'term' === $type ) && ( ( isset( $source_profile['targets'][ $language->slug ] ) &&
				'copy' === $source_profile['targets'][ $language->slug ] ) || ( isset( $profile['targets'][ $language->slug ] ) &&
				'copy' === $profile['targets'][ $language->slug ] ) && isset( $document->source ) ) ) {
			if ( isset( $document->desc_array[ $language->slug ] ) ) {
				return ( 'post' === $type ) ? parent::post_column( $column, $object_id ) : parent::term_column( '', $column, $object_id );
			} else {
				if ( $document ) {
					return $actions->copy_icon( $document->source, $language->slug );
				} else {
					return $actions->copy_icon( $object_id, $language->slug );
				}
			}
		} elseif ( ( isset( $document->source ) && $document->is_disabled_target( $source_language, $language ) &&
				! isset( $document->translations[ $language->locale ] ) ) || ! Lingotek::is_allowed_tms_locale( $language->lingotek_locale ) ) {
			// translation disabled.
			return 'post' === $type ? parent::post_column( $column, $object_id ) : parent::term_column( '', $column, $object_id );
		} elseif ( ! $cancelled && isset( $document->source ) && $document->source === $id ) {
			// Source post is uploaded.
			// Source is ready for upload.
			if ( $this->lgtm->can_upload( $type, $id ) ) {
				return $actions->upload_icon( $id );
			}
			// importing source.
			if ( $id === $object_id && 'importing' === $document->status ) {
				return Lingotek_Actions::importing_icon( $document );
			}
			// source failed
			if ( $id === $object_id && 'failed' === $document->status ) {
				return $actions->failed_import_icon( $document->status, $object_id );
			}
			return 'post' === $type ? Lingotek_Post_Actions::uploaded_icon( $id ) : Lingotek_Term_actions::uploaded_icon( $id );
		} elseif ( isset( $document->translations[ $language->locale ] ) || ( isset( $document->source ) && 'current' === $document->status ) ) {
			// translations.
			return Lingotek_Actions::translation_icon( $document, $language );
		} elseif ( ( 'term' === $type && ! isset( $document->translations[ $language->locale ] ) && $document->source !== $object_id ) || ! Lingotek::is_allowed_tms_locale( $language->lingotek_locale ) ) {
			return parent::term_column( '', $column, $object_id );
		} elseif ( empty( $document->source ) ) {
			// translations exist but are not managed by Lingotek TMS.
			return $object_id === $id && ! $disabled ? $actions->upload_icon( $object_id, true ) : ( 'post' === $type ? parent::post_column( $column, $object_id ) : parent::term_column( '', $column, $object_id ) );
		} elseif ( 'failed' === $document->status ) {
			return $actions->failed_import_icon( $document->status, $object_id );
		} elseif ( $this->lgtm->can_upload( $type, $id ) && 'cancelled' === $document->status ) {
			return $disabled ? ( 'post' === $type ? parent::post_column( $column, $object_id ) : parent::term_column( '', $column, $object_id ) )
			: ( $document && ( count( $document->desc_array ) >= 3 ) ? $actions->cancelled_icon( $object_id, false, true ) : $actions->cancelled_icon( $object_id ) );
		} else {
			// no translation.
			/**
			 * There is no record of a translation, but the entire document has been cancelled
			 * so we just mark all locales as cancelled since the user will have to re upload the document
			 */
			if ( 'cancelled' === $document->status ) {
				return $actions->cancelled_icon( $object_id, true );
			}
			return '<div class="lingotek-color dashicons dashicons-no"></div>';
		}//end if
	}

	/**
	 * Fills the language and translations columns in the posts, pages and media library tables
	 * take care that when doing ajax inline edit, the post may not be updated in database yet
	 *
	 * @since 0.1
	 *
	 * @param string $column column name.
	 * @param int    $post_id post id.
	 */
	public function post_column( $column, $post_id ) {
		if ( false === strpos( $column, 'language_' ) && false === strpos( $column, 'lingotek_' ) ) {
			return;
		}
		$this->content_type = get_post_type( $post_id );

		$allowed_html = array(
			'a'      => array(
				'href'   => array(),
				'class'  => array(),
				'title'  => array(),
				'target' => array(),
			),
			'div'    => array(
				'title' => array(),
				'class' => array(),
			),
			'span'   => array(
				'title' => array(),
				'class' => array(),
			),
			'button' => array(
				'title' => array(),
				'class' => array(),
			),
			'ul'     => array(
				'title' => array(),
				'class' => array(),
			),
			'li'     => array(
				'title' => array(),
				'class' => array(),
			),
			'img'    => array(
				'src' => array(),
			),
		);
		echo wp_kses( $this->_column( 'post', $column, $post_id ), $allowed_html );

		/**
		*   Setup workflow specific logic for posts.
		*/
		$post            = get_post( $post_id );
		$source_language = PLL()->model->post->get_language( $post_id );
		$target_language = $this->model->get_language( substr( $column, 9 ) );
		if ( is_object( $source_language ) && is_object( $target_language ) ) {
			$workflow_id = Lingotek_Model::get_profile_option( 'workflow_id', $post->post_type, $source_language, $target_language, $post_id );
			$workflow    = Lingotek_Workflow_Factory::get_workflow_instance( $workflow_id );
			// $workflow->override_events( $workflow_id ); // Loads appropriate .js file.
			// Adds modal html to page.
			$workflow->echo_posts_modal( $post_id, $target_language->locale );
		}

		// checking for api errors.
		$document = $this->lgtm->get_group( 'post', $post_id );
		if ( isset( $document->source ) ) {
			$source_language = PLL()->model->post->get_language( $document->source );
			$this->error_icon_html( $column, $post_id, $source_language->locale );
		} else {
			$this->error_icon_html( $column, $post_id );
		}
	}

	/**
	 * Fills the language and translations columns in the categories and post tags tables
	 * take care that when doing ajax inline edit, the term may not be updated in database yet
	 *
	 * @since 0.2
	 *
	 * @param string $custom_data data.
	 * @param string $column column name.
	 * @param int    $term_id term id.
	 */
	public function term_column( $custom_data, $column, $term_id ) {
		if ( false === strpos( $column, 'language_' ) ) {
			return;
		}
		$allowed_html       = array(
			'a'    => array(
				'href'   => array(),
				'class'  => array(),
				'title'  => array(),
				'target' => array(),
			),
			'div'  => array(
				'title' => array(),
				'class' => array(),
			),
			'span' => array(
				'title' => array(),
				'class' => array(),
			),
			'img'  => array(
				'src' => array(),
			),
		);
		$this->content_type = $GLOBALS['taxonomy'];

		/**
		*   Setup workflow specific logic for terms.
		*/
		$source_language = PLL()->model->term->get_language( $term_id );
		$target_language = $this->model->get_language( substr( $column, 9 ) );
		if ( is_object( $source_language ) && is_object( $target_language ) ) {
			$workflow_id = Lingotek_Model::get_profile_option( 'workflow_id', $this->content_type, $source_language, $target_language );
			$workflow    = Lingotek_Workflow_Factory::get_workflow_instance( $workflow_id );
			// $workflow->override_events( $workflow_id ); // Loads appropriate .js file.
			// Adds modal html to page.
			$workflow->echo_terms_modal( $term_id, $target_language->locale );
		}

		if ( ! $custom_data ) {
			echo wp_kses( $this->_column( 'term', $column, $term_id ), $allowed_html );
		} else {
			echo wp_kses( $this->_column( 'term', $column, $term_id, $custom_data ), $allowed_html );
		}
		// checking for api errors.
		$this->error_icon_html( $column, $term_id );
	}

	/**
	 * Checks for errors in the lingotek_log_errors option and displays an icon
	 *
	 * @since 1.2
	 *
	 * @param string $column column.
	 * @param string $object_id id.
	 * @param item   $source_locale locale.
	 */
	protected function error_icon_html( $column, $object_id, $source_locale = null ) {
		// checking for api errors.
		$source_column        = substr( $column, 9 );
		$column_language_only = substr( $column, 0, 11 );
		$errors               = get_option( 'lingotek_log_errors' );

		if ( $source_column === $source_locale ) {
			if ( isset( $errors[ $object_id ] ) ) {
				$api_error    = Lingotek_Actions::retrieve_api_error( $errors[ $object_id ] );
				$allowed_html = array(
					'a' => array(
						'href'   => array(),
						'class'  => array(),
						'title'  => array(),
						'target' => array(),
					),
				);
				echo wp_kses( Lingotek_Actions::display_error_icon( 'error', $api_error ), $allowed_html );
			}
		}
	}

	/**
	 * Renders the source status.
	 *
	 * @param int    $id
	 * @param object $source_language
	 * @param object $document
	 *
	 * @return string
	 *
	 * @since 1.5.0
	 */
	private function render_source_status( $id, $source_language, $document ) {
		// If the source was uploaded, we want the group source document locale. If not, the document locale.
		$source_language = is_object( $document ) && isset( $document->source ) ?
			PLL()->model->post->get_language( $document->source ) :
			$source_language;
		$source_status   = is_object( $document ) && $document->status ? $document->status : 'untracked';

		$source_chip = new Lingotek_Chip_Source( $id, $document );
		return $source_chip->render( $source_language, $source_status );
	}

	/**
	 * Render target statuses.
	 *
	 * @param int    $id
	 * @param object $source_language
	 * @param object $document
	 *
	 * @return string
	 *
	 * @since 1.5.0
	 */
	private function render_target_statuses( $id, $source_language, $document ) {
		$targets_markup = '';
		$targets        = isset( $document->translations ) ? array_keys( $document->translations ) : array();
		$targets_data   = array();
		// If the source was uploaded, we want the group source document locale. If not, the document locale.
		$wp_source_locale = is_object( $document ) && isset( $document->source ) ?
			PLL()->model->post->get_language( $document->source )->locale :
			$source_language->locale;

		foreach ( $targets as $wp_locale ) {
			$language = PLL()->model->get_language( $wp_locale );
			// If we had a translation status but the language was removed/disabled, language is false.
			if ( $language ) {
				$status                     = $document->translations[ $wp_locale ];
				$targets_data[ $wp_locale ] = array(
					'id'              => $id,
					'source_language' => $source_language,
					'document'        => $document,
					'status'          => $status,
					'language'        => $language,
				);
			}
		}

		// We need to add the locales that were not requested, so they can be requested, but only when the source
		// in a valid state for requesting those.
		if ( is_object( $document ) && isset( $document->status ) && ! in_array( $document->status, array( 'cancelled', 'deleted', 'archived' ), true ) ) {
			foreach ( PLL()->model->get_languages_list() as $pll_language ) {
				$status = 'request';
				if ( ! isset( $targets_data[ $pll_language->locale ] ) && $pll_language->locale !== $wp_source_locale ) {
					$targets_data[ $pll_language->locale ] = array(
						'id'              => $id,
						'source_language' => $source_language,
						'document'        => $document,
						'status'          => $status,
						'language'        => $pll_language,
					);
				}
			}
		}
		foreach ( $targets_data as $locale => $locale_data ) {
			$target_chip     = new Lingotek_Chip_Target( $locale_data['id'], $locale_data['document'] );
			$targets_markup .= $target_chip->render( $locale_data['language'], $locale_data['status'] );
		}
		return $targets_markup;
	}

}
