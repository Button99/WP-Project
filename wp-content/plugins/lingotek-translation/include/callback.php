<?php

/*
 * a class to handle Lingotek callbacks
 *
 * @since 0.1
 */
class Lingotek_Callback {
	public $lgtm;

	/*
	 * Constructor
	 *
	 * @since 0.1
	 */
	public function __construct( &$model ) {
		$this->lgtm = &$model;
		add_filter( 'request', array( &$this, 'request' ) );
	}

	/*
	 * dispatches the Lingotek callback and dies
	 *
	 * @since 0.1
	 *
	 * @param array $query_vars query vars known to WordPress
	 * @return array unmodified query vars if the request is not a Lingotek callback
	 */
	public function request( $query_vars ) {
		if ( empty( $query_vars['lingotek'] ) ) {
			return $query_vars;
		}
		$type = $_GET['type'];
		if ( isset( $type, $_GET['document_id'] ) && $document = $this->lgtm->get_group_by_id( $_GET['document_id'] ) ) {
			switch ( $type ) {
				case 'get':
					$this->handleGet( $document );
					break;
				case 'target':
				case 'phase':
				case 'download_interim_translation':
					$this->handleTargetOrPhaseOrDownloadInterimTranslation( $document, $type );
					break;
				case 'target_cancelled':
					$this->handleTargetCancelled( $document );
					break;
				case 'target_deleted':
					$this->handleTargetDeleted( $document );
					break;
				case 'document_uploaded':
				case 'document_updated':
					$this->handleDocumentUploadedOrUpdated( $document );
					break;
				case 'import_failure':
					$this->handleImportFailure( $document );
					break;
				case 'document_archived':
					$this->handleDocumentArchived( $document );
					break;
				case 'document_deleted':
					$this->handleDocumentDeleted( $document );
					break;
				case 'document_cancelled';
					$this->handleDocumentCancelled( $document );
					break;
				default:
					Lingotek_Logger::info( 'Callback received, no handler for type', array( 'Type' => $type ) );
			}//end switch
			// Useless as it the default value.
			status_header( 200 );
			die();
		}//end if
		// No document found.
		status_header( 404 );
		die();
	}

	public function handleGet( $document ) {
		// url for in context review
		if ( isset( $_GET['locale'] ) ) {
			// Map to WordPress locale.
			$locale = Lingotek::map_to_wp_locale( $_GET['locale'] );
			Lingotek_Logger::info( 'Callback received', array( 'Callback Parameters' => $_GET ) );
			// posts
			if ( post_type_exists( $document->type ) ) {
				if ( $id = PLL()->model->post->get( $document->source, $locale ) ) {
					wp_redirect( get_permalink( $id ), 301 );
					exit();
				} else {
					wp_redirect( get_permalink( $document->source ), 302 );
					exit();
				}
			}
			// taxonomy terms
			elseif ( taxonomy_exists( $document->type ) && $id = $document->pllm->get_term( $document->source, $locale ) ) {
				wp_redirect( get_term_link( $id, $document->type ), 301 );
				exit();
			}

			// No document found.
			status_header( 404 );
			die();
		}//end if
	}

	public function handleTargetOrPhaseOrDownloadInterimTranslation( $document, $type ) {
		if ( isset( $_GET['locale'] ) ) {
			$callback_parameters = array(
				'Target Locale'   => isset( $_GET['locale_code'] ) ? $_GET['locale_code'] : null,
				'Phase Name'      => isset( $_GET['phase_name'] ) ? $_GET['phase_name'] : null,
				'Status'          => isset( $_GET['status'] ) ? $_GET['status'] : null,
				'Document ID'     => isset( $_GET['document_id'] ) ? $_GET['document_id'] : null,
				'Project ID'      => isset( $_GET['projectId'] ) ? (int) $_GET['projectId'] : null,
				'Community ID'    => isset( $_GET['community_id'] ) ? $_GET['community_id'] : null,
				'Progress'        => isset( $_GET['progress'] ) ? (int) $_GET['progress'] : null,
				'Complete'        => isset( $_GET['complete'] ) ? $_GET['complete'] : null,
				'Type'            => isset( $_GET['type'] ) ? $_GET['type'] : null,
				'Document Status' => isset( $_GET['doc_status'] ) ? $_GET['doc_status'] : null,
			);
			Lingotek_Logger::info( 'Callback received', array( 'Callback Parameters' => $callback_parameters ) );
			// We will need access to PLL_Admin_Sync::copy_post_metas
			global $polylang;
			$polylang->sync = new PLL_Admin_Sync( $polylang );
			// Map to WordPress locale.
			$locale = Lingotek::map_to_wp_locale( $_GET['locale'] );
			$document->is_automatic_download( $locale ) ? $document->create_translation( $locale, true, $type ) : $document->translation_ready( $locale );
		}//end if
	}

	public function handleDocumentUploadedOrUpdated( $document ) {
		$document->source_ready();
		$callback_parameters = array(
			'Document ID'     => isset( $_GET['document_id'] ) ? $_GET['document_id'] : null,
			'Project ID'      => isset( $_GET['projectId'] ) ? (int) $_GET['projectId'] : null,
			'Community ID'    => isset( $_GET['community_id'] ) ? $_GET['community_id'] : null,
			'Progress'        => isset( $_GET['progress'] ) ? (int) $_GET['progress'] : null,
			'Complete'        => isset( $_GET['complete'] ) ? $_GET['complete'] : null,
			'Type'            => isset( $_GET['type'] ) ? $_GET['type'] : null,
			'Document Status' => isset( $_GET['doc_status'] ) ? $_GET['doc_status'] : null,
		);
		Lingotek_Logger::info( 'Callback received', array( 'Callback Parameters' => $callback_parameters ) );
		if ( $document->is_automatic_upload() ) {
			$document->request_translations();
		}
	}

	public function handleImportFailure( $document ) {
		$callback_parameters = array(
			'Document ID' => isset( $_GET['document_id'] ) ? $_GET['document_id'] : null,
			'Type'        => isset( $_GET['type'] ) ? $_GET['type'] : null,
		);
		if ( isset( $_GET['prev_document_id'] ) ) {
			Lingotek_Logger::info( 'Document update failed. Reverting to previous id', $callback_parameters );
			$document->id = $_GET['prev_document_id'];
		}
		Lingotek_Logger::info( 'Callback received', array( 'Callback Parameters' => $callback_parameters ) );
		$document->source_failed();
	}

	public function handleDocumentArchived( $document ) {
		$callback_parameters = array(
			// As this comes from Lingotek callback we cannot have a nonce.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,
			'Document ID' => isset( $_GET['document_id'] ) ? sanitize_key( $_GET['document_id'] ) : null,
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,
			'Type'        => isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : null,
		);
		Lingotek_Logger::info( 'Callback received', array( 'Callback Parameters' => $callback_parameters ) );
		// The removal of the lingotek hash term is needed as it could prevent further uploads.
		$wp_id = isset( $document->desc_array['lingotek']['source'] ) ? $document->desc_array['lingotek']['source'] : null;
		wp_remove_object_terms( $wp_id, "lingotek_hash_{$wp_id}", 'lingotek_hash' );

		if ( isset( $document->desc_array['lingotek']['translations'] ) && count( $document->desc_array['lingotek']['translations'] ) > 0 ) {
			foreach ( $document->desc_array['lingotek']['translations'] as $target_locale => $target_status ) {
				$document->desc_array['lingotek']['translations'][ $target_locale ] = 'archived';
			}
		}
		$document->desc_array['lingotek']['document_id'] = 'archived_' . $document->document_id;
		$document->desc_array['lingotek']['status']      = 'archived';

		$document->save();
	}

	public function handleDocumentDeleted( $document ) {
		$callback_parameters = array(
			// As this comes from Lingotek callback we cannot have a nonce.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,
			'Document ID' => isset( $_GET['document_id'] ) ? sanitize_key( $_GET['document_id'] ) : null,
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,
			'Type'        => isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : null,
		);
		Lingotek_Logger::info( 'Callback received', array( 'Callback Parameters' => $callback_parameters ) );

		if ( isset( $document->desc_array['lingotek']['translations'] ) && count( $document->desc_array['lingotek']['translations'] ) > 0 ) {
			foreach ( $document->desc_array['lingotek']['translations'] as $target_locale => $target_status ) {
				$document->desc_array['lingotek']['translations'][ $target_locale ] = 'deleted';
			}
		}

		// The removal of the lingotek hash term is needed as it could prevent further uploads.
		$wp_id = isset( $document->desc_array['lingotek']['source'] ) ? $document->desc_array['lingotek']['source'] : null;
		wp_remove_object_terms( $wp_id, "lingotek_hash_{$wp_id}", 'lingotek_hash' );

		$document->desc_array['lingotek']['document_id'] = 'deleted_' . $document->document_id;
		$document->desc_array['lingotek']['status']      = 'deleted';
		$document->save();
	}

	public function handleDocumentCancelled( $document ) {
		$callback_parameters = array(
			'Document ID' => isset( $_GET['document_id'] ) ? $_GET['document_id'] : null,
			'Type'        => isset( $_GET['type'] ) ? $_GET['type'] : null,
		);
		if ( isset( $document->desc_array['lingotek']['translations'] ) && count( $document->desc_array['lingotek']['translations'] ) > 0 ) {
			foreach ( $document->desc_array['lingotek']['translations'] as $target_locale => $target_status ) {
				$document->desc_array['lingotek']['translations'][ $target_locale ] = 'cancelled';
			}
		}
		$wp_id = isset( $document->desc_array['lingotek']['source'] ) ? $document->desc_array['lingotek']['source'] : null;
		wp_remove_object_terms( $wp_id, "lingotek_hash_{$wp_id}", 'lingotek_hash' );

		$document->desc_array['lingotek']['document_id'] = 'cancelled_' . $document->document_id;
		$document->desc_array['lingotek']['status']      = 'cancelled';
		$document->save();
		Lingotek_Logger::info( 'Callback Received', array( 'Callback Parameters' => $callback_parameters ) );
	}

	public function handleTargetCancelled( $document ) {
		$callback_parameters = array(
			'Document ID' => isset( $_GET['document_id'] ) ? $_GET['document_id'] : null,
			'Type'        => isset( $_GET['type'] ) ? $_GET['type'] : null,
			'Locale'      => isset( $_GET['locale'] ) ? $_GET['locale'] : null,
		);
		// Map to WordPress locale.
		$locale = Lingotek::map_to_wp_locale( $_GET['locale'] );
		if ( ! isset( $document->desc_array['lingotek']['translations'][ $locale ] ) ) {
			$document->desc_array['lingotek']['translations'][] = $locale;
			$document->save();
		}
		$document->update_translation_status( $locale, 'cancelled' );
		Lingotek_Logger::info( 'Callback received', array( 'Callback Parameters' => $callback_parameters ) );
	}

	public function handleTargetDeleted( $document ) {
		$callback_parameters = array(
			// As this comes from Lingotek callback we cannot have a nonce.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,
			'Document ID' => isset( $_GET['document_id'] ) ? sanitize_key( $_GET['document_id'] ) : null,
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,
			'Type'        => isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : null,
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,
			'Locale'      => isset( $_GET['locale'] ) ? sanitize_lingotek_locale( $_GET['locale'] ) : null,
		);
		// Map to WordPress locale.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,
		$locale = Lingotek::map_to_wp_locale( sanitize_lingotek_locale( $_GET['locale'] ) );
		if ( ! isset( $document->desc_array['lingotek']['translations'][ $locale ] ) ) {
			$document->desc_array['lingotek']['translations'][] = $locale;
			$document->save();
		}
		$document->update_translation_status( $locale, 'deleted' );
		Lingotek_Logger::info( 'Callback received', array( 'Callback Parameters' => $callback_parameters ) );
	}

}
