<?php

require_once 'http.php';

/**
 * manages communication with Lingotek TMS
 * uses Lingotek API V5
 *
 * @since 0.1
 */
class Lingotek_API extends Lingotek_HTTP {
	protected $base_url;
	protected $api_url;
	protected $client_id;
	private $auth_temp;

	const PRODUCTION_URL = 'https://myaccount.lingotek.com';
	// Lingotek App ID.
	const CLIENT_ID = '780966c9-f9c8-4691-96e2-c0aaf47f62ff';


	private $bridge_url = '';

	/**
	 * constructor
	 *
	 * @since 0.1
	 */
	public function __construct() {
		$base_url        = get_option( 'lingotek_base_url' );
		$this->base_url  = $base_url ? $base_url : self::PRODUCTION_URL;
		$this->api_url   = $this->base_url . '/api';
		$this->client_id = self::CLIENT_ID;
		$token_details   = get_option( 'lingotek_token', array() );
		if ( isset( $token_details['access_token'] ) ) {
			$this->headers['Authorization'] = 'bearer ' . $token_details['access_token'];
		}
		$this->defaults   = get_option( 'lingotek_defaults', array() );
		$this->bridge_url = BRIDGE_URL . '/api/v2/transaction/translation/';
	}

	public function get_token_details( $access_token ) {
		$url = "{$this->base_url}/auth/oauth2/access_token_info";
		Lingotek_Logger::debug(
			'GET',
			array(
				'url'    => $url,
				'method' => __METHOD__,
			)
		);
		$args          = array(
			'headers' => array(
				'Authorization' => "Bearer $access_token",
			),
		);
		$response      = wp_remote_get( $url, $args );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == 200 ) {
			$response_body = json_decode( wp_remote_retrieve_body( $response ) );
			$token_details = $response_body;
		} else {
			$token_details = false;
		}
		$this->log_error_on_response_failure( $response, 'GetTokenDetails: Error occured' );
		return $token_details;
	}

	public function get_api_url() {
		return $this->api_url;
	}

	/**
	 * updates the projet callback
	 *
	 * @since 0.2
	 *
	 * @param string $project_id
	 */
	public function update_callback_url( $project_id ) {
		$args     = array( 'callback_url' => add_query_arg( 'lingotek', 1, site_url() ) );
		$response = $this->patch( $this->api_url . '/project/' . $project_id, $args );
		Lingotek_Logger::info( 'Request to update callback url', array( 'project_id' => $project_id ) );
		$this->log_error_on_response_failure( $response, 'UpdateCallbackUrl: Error occured', array( 'project_id' => $project_id ) );
		return ! is_wp_error( $response ) && 204 == wp_remote_retrieve_response_code( $response );
	}

	/**
	 * creates a new project
	 *
	 * @since 0.2
	 *
	 * @param string $title
	 */
	public function create_project( $title, $community_id ) {
		$args = array(
			'title'        => $title,
			'community_id' => $community_id,
			'workflow_id'  => $this->get_workflow_id(),
			'callback_url' => add_query_arg( 'lingotek', 1, site_url() ),
		);

		$response = $this->post( $this->api_url . '/project', $args );
		if ( ! is_wp_error( $response ) && 201 == wp_remote_retrieve_response_code( $response ) ) {
			$new_id = json_decode( wp_remote_retrieve_body( $response ) );
			Lingotek_Logger::info(
				'Project created',
				array(
					'id'    => $new_id->properties->id,
					'title' => $title,
				)
			);
			return $new_id->properties->id;
		}

		$this->log_error_on_response_failure(
			$response,
			'CreateProject: Error occured',
			array(
				'title'        => $title,
				'community_id' => $community_id,
			)
		);
		return false;
	}

	/**
	 * uploads a document
	 *
	 * @since 0.1
	 *
	 * @param array $args expects array with title, content and locale_code
	 * @returns bool|string document_id, false if something got wrong
	 */
	public function upload_document( $args, $wp_id = null ) {
		Lingotek::log( debug_backtrace() );
		Lingotek::log( 'uploading document' );
		$args = wp_parse_args(
			$args,
			array(
				'format'      => 'JSON',
				'project_id'  => $this->defaults['project_id'],
				'workflow_id' => $this->get_workflow_id(),
			)
		);
		$this->format_as_multipart( $args );
		$response = $this->post( $this->api_url . '/document', $args );
		$this->update_lingotek_error_option( $response, $wp_id, 'upload_document', sprintf( __( 'There was an error uploading WordPress item %1$s', 'lingotek-translation' ), $wp_id ) );
		$code = wp_remote_retrieve_response_code( $response );
		if ( ! is_wp_error( $response ) && 202 == $code ) {
			$b = json_decode( wp_remote_retrieve_body( $response ) );
			Lingotek_Logger::info(
				'Document uploaded',
				array(
					'document_id' => $b->properties->id,
					'wp_id'       => $wp_id,
				)
			);
			return $b->properties->id;
		} elseif ( $code == 402 ) {
			$lingotek_log_errors                         = get_option( 'lingotek_log_errors' );
			$error_message                               = $this->get_error_message_from_response( $response ) !== false ?
			$this->get_error_message_from_response( $response ) : 'No error message set by Lingotek';
			$lingotek_log_errors['patch_document_error'] = __( "Payment required. Message: $error_message", 'lingotek-translation' );
			update_option( 'lingotek_log_errors', $lingotek_log_errors, false );
		}
		return false;
	}

	/**
	 * modifies a document
	 *
	 * @since 0.1
	 *
	 * @param string $id document id
	 * @param array  $args expects array with content
	 * @return bool|string false if something got wrong
	 */
	public function patch_document( $id, $args, $wp_id = null ) {
		$lgtm     = $GLOBALS['wp_lingotek']->model;
		$document = $lgtm->get_group_by_id( $id );
		$args     = $unformatted_args = wp_parse_args( $args, array( 'format' => 'JSON' ) );
		$title    = isset( $args['title'] ) ? $args['title'] : $id;
		$this->format_as_multipart( $args );
		$response    = $this->patch( $this->api_url . '/document/' . $id, $args );
		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ) );
		if ( $status_code == 400 && strpos( $body->messages[0], 'has previously been cancelled' ) ) {
			// Document has been cancelled, re-upload the post
			// Slug can't be empty or duplicated, so we prefix `disassociated` to show the document id is no longer associated with this post
			$document->document_id = 'disassociated_' . $document->document_id;
			unset( $document->desc_array['lingotek'] );
			$document->save();
			return $lgtm->upload_post( $wp_id );
		}
		if ( $status_code == 423 ) {
			$document->document_id = $body->next_document_id;
			$document->save();
			return $this->patch_document( $body->next_document_id, $unformatted_args, $wp_id );
		}

		// We will handle patch errors separately. All we want to do is return the result of the current patch request to the user if payment is required
		// (although in theory this will never happen, because the WP model is document count based, and it will not allow them to upload documents until
		// they choose to buy more documents)
		$this->update_patch_error_message( $response, $status_code, $title );
		// The current behavior sends no body, so we will just check to see if the body is empty for now in order to maintain current behavior
		if ( empty( $body ) ) {
			$success = ! is_wp_error( $response ) && 202 == wp_remote_retrieve_response_code( $response );
			if ( $success ) {
				Lingotek_Logger::info(
					'Document updated',
					array(
						'document_id' => $id,
						'wp_id'       => $wp_id,
					)
				);
			} else {
				Lingotek_Logger::error(
					'Document failed to update',
					array(
						'document_id' => $id,
						'wp_id'       => $wp_id,
					)
				);
			}
			return $success;
		}//end if

		if ( $status_code == 410 || $status_code == 404 ) {
			$document_status_string = $status_code == 410 ? 'archived' : 'not found';
			Lingotek_Logger::info(
				'Document ID was ' . $document_status_string . ', reuploading document',
				array(
					'old_document_id' => $id,
					'wp_id'           => $wp_id,
					'args'            => $args,
				)
			);
			$targets          = array_keys( $document->translations );
			$lingotek_locales = array();
			foreach ( $targets as $target ) {
				// Targets stored in the document translations property are the polylang targets, so we need to get the lingotek locales instead
				$document->translations[ $target ] = 'pending';
				$lingotek_locales[]                = Lingotek::map_to_lingotek_locale( $target );
			}
			$unformatted_args['translation_locale_code'] = $lingotek_locales;
			$this->format_args_for_upload( $unformatted_args, $wp_id );
			$document->document_id = 'disassociated_' . $document->document_id;
			unset( $document->desc_array['lingotek'] );
			$document->save();
			return $this->upload_document( $unformatted_args, $wp_id );
		}//end if
		if ( $status_code == 402 ) {
			Lingotek_Logger::error( 'There was an error updating WordPress item', array( 'error' => $this->get_error_message_from_response( $response ) ) );
			$document->source_failed();
			return false;
		}
		if ( $status_code == 202 && ! is_wp_error( $response ) ) {
			Lingotek_Logger::info(
				'Document updated',
				array(
					'old_document_id' => $id,
					'new_document_id' => $body->next_document_id,
					'wp_id'           => $wp_id,
				)
			);
			$document->document_id = $body->next_document_id;
			$document->save();
			return $body->next_document_id;
		}
		return false;
	}

	private function format_args_for_upload( &$args, $wp_id ) {
		$lgtm        = $GLOBALS['wp_lingotek']->model;
		$post        = get_post( $wp_id );
		$language    = PLL()->model->post->get_language( $wp_id );
		$project_id  = $lgtm->get_profile_option( 'project_id', $post->post_type, $language, false, $wp_id );
		$workflow_id = $lgtm->get_profile_option( 'workflow_id', $post->post_type, $language, false, $wp_id );
		$locale_code = $language->lingotek_locale;
		unset( $args['format'] );
		$args['locale_code'] = $locale_code;
		$args['project_id']  = $project_id;
		$args['workflow_id'] = $workflow_id;
	}

	private function update_patch_error_message( $response, $status_code, $title ) {
		// Do not inform user if call was successful.
		if ( $status_code == 202 || $status_code == 410 || $status_code == 404 ) {
			return;
		}
		$lingotek_log_errors = get_option( 'lingotek_log_errors', array() );
		if ( ! is_array( $lingotek_log_errors ) ) {
			$lingotek_log_errors = array();
		}
		$error_message = $this->get_error_message_from_response( $response ) !== false ?
			$this->get_error_message_from_response( $response ) : 'No error message set by Lingotek.';

		if ( $status_code == 402 ) {
			$lingotek_log_errors['patch_document_error'] = __( "Payment required. Message: $error_message", 'lingotek-translation' );
		} else {
			$lingotek_log_errors['patch_document_error'] = __( "Error occurred while updating document $title. Message: $error_message", 'lingotek-translation' );
		}
		update_option( 'lingotek_log_errors', $lingotek_log_errors );
	}

	/**
	 * cancels a document
	 *
	 * @since 1.4.2
	 *
	 * @param string $id document id
	 */
	public function cancel_document( &$id, $wp_id = null ) {
		$args     = wp_parse_args( array( 'cancelled_reason' => 'CANCELLED_BY_AUTHOR' ) );
		$response = $this->post( "$this->api_url/document/$id/cancel", $args );
		if ( $wp_id ) {
			$arr = get_option( 'lingotek_log_errors' );
			if ( isset( $arr[ $wp_id ] ) ) {
				unset( $arr[ $wp_id ] );
				update_option( 'lingotek_log_errors', $arr, false );
			}
		}
		$this->log_error_on_response_failure(
			$response,
			'CancelDocument: Error occurred',
			array(
				'id'           => $id,
				'wordpress_id' => $wp_id,
			)
		);
		$is_success       = ! is_wp_error( $response ) && ( 204 == wp_remote_retrieve_response_code( $response ) || 202 == wp_remote_retrieve_response_code( $response ) );
		$lgtm             = $GLOBALS['wp_lingotek']->model;
		$document         = $lgtm->get_group_by_id( $id );
		$current_status   = $document->status;
		$document->status = 'cancelled';
		if ( $is_success ) {
			wp_remove_object_terms( $wp_id, "lingotek_hash_{$wp_id}", 'lingotek_hash' );
			Lingotek_Logger::info(
				'Document cancelled',
				array(
					'document_id' => $id,
					'wp_id'       => $wp_id,
				)
			);
			$document->save();
			return $is_success;
		} elseif ( 403 != wp_remote_retrieve_response_code( $response ) ) {
			wp_remove_object_terms( $wp_id, "lingotek_hash_{$wp_id}", 'lingotek_hash' );
			Lingotek_Logger::warning(
				'Document was not cancelled in TMS',
				array(
					'document' => $id,
					'wp_id'    => $wp_id,
				)
			);
			// Slug can't be empty or duplicated, so we prefix `disassociated` to show the document id is no longer associated with this post
			$document->document_id = $id = 'disassociated_' . $document->document_id;
			$document->save();
			return true;
		}//end if
		$document->status    = $current_status;
		$lingotek_log_errors = get_option( 'lingotek_log_errors', array() );
		if ( ! is_array( $lingotek_log_errors ) ) {
			$lingotek_log_errors = array();
		}
		$error_message                                      = $this->get_error_message_from_response( $response ) !== false ?
			$this->get_error_message_from_response( $response ) : 'No error message set by Lingotek.';
		$lingotek_log_errors['disassociate_document_error'] = $error_message;
		update_option( 'lingotek_log_errors', $lingotek_log_errors, false );
		return $is_success;
	}


	/**
	 * get all documents ids
	 *
	 * @since 0.1
	 */
	public function get_document_ids( $args = array() ) {
		$response = $this->get( add_query_arg( $args, $this->api_url . '/document' ) );
		$ids      = array();

		if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
			$documents = json_decode( wp_remote_retrieve_body( $response ) );
			foreach ( $documents->entities as $doc ) {
				$ids[] = $doc->properties->id;
			}
		}

		$this->log_error_on_response_failure( $response, 'GetDocumentIds: Error occured' );
		return $ids;
	}

	public function get_document_count( $args = array() ) {
		$response = $this->get( add_query_arg( $args, $this->api_url . '/document' ) );
		$docs     = array();

		if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
			$response = json_decode( wp_remote_retrieve_body( $response ) );
			return $response->properties->total;
		}

		$this->log_error_on_response_failure( $response, 'GetDocumentCount: Error occured' );
		return null;
	}

	/**
	 * get all documents
	 *
	 * @since 0.1
	 */
	public function get_documents( $args = array() ) {
		$response = $this->get( add_query_arg( $args, $this->api_url . '/document' ) );
		$docs     = array();

		if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
			$documents = json_decode( wp_remote_retrieve_body( $response ) );
			foreach ( $documents->entities as $doc ) {
				$docs[] = $doc;
			}
		}

		$this->log_error_on_response_failure( $response, 'GetDocuments: Error occured' );
		return $docs;
	}

	/**
	 * get document by id
	 *
	 * @since 0.1
	 */
	public function get_document( $doc_id ) {
		$response = $this->get( $this->api_url . '/document/' . $doc_id );

		if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
			$document = json_decode( wp_remote_retrieve_body( $response ) );
		}

		$this->log_error_on_response_failure( $response, 'GetDocument: Error occured' );
		return $document;
	}

	public function get_document_status( $doc_id ) {
		$imported = false;
		$response = $this->get( $this->api_url . '/document/' . $doc_id . '/status' );

		if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ) );
			// Cancelled docs return with a 200 status code,
			$imported = 'cancelled' !== strtolower( $body->properties->status ) ? 'current' : 'cancelled';
		} elseif ( 410 == wp_remote_retrieve_response_code( $response ) ) {
			$imported = 'archived';
		} elseif ( 404 == wp_remote_retrieve_response_code( $response ) ) {
			$imported = 'deleted';
		}

		$this->log_error_on_response_failure( $response, 'GetDocumentStatus: Error occured', array( 'document_id' => $doc_id ) );
		return $imported;
	}


	/**
	 * get specific document content
	 *
	 * @since 0.1
	 *
	 * @param string $id document id
	 * @return string
	 */
	public function get_document_content( $doc_id ) {
		$response = $this->get( $this->api_url . "/document/$doc_id/content" );

		if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
			$content = wp_remote_retrieve_body( $response );
		}

		$this->log_error_on_response_failure( $response, 'GetDocumentContent: Error occured', array( 'document_id' => $doc_id ) );
		return $content;
	}

	/**
	 * check translations status of a specific locale for a document
	 *
	 * @since 0.1
	 *
	 * @param string $doc_id document id
	 * @param string $locale locale
	 * @return int with locale percent_complete
	 */
	public function get_translation_status( $doc_id, $locale ) {
		$locale   = Lingotek::map_to_lingotek_locale( $locale );
		$status   = -1;
		$response = $this->get( $this->api_url . "/document/$doc_id/translation/$locale" );
		if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
			$b      = json_decode( wp_remote_retrieve_body( $response ) );
			$status = $b->properties->percent_complete;
		}

		$this->log_error_on_response_failure(
			$response,
			'GetTranslationStatus: Error occurred',
			array(
				'document_id' => $doc_id,
				'locale'      => $locale,
			)
		);
		return $status;
	}

	/**
	 * check translations status of a document
	 *
	 * @since 0.1
	 *
	 * @param string $id document id
	 * @return array with locale as key and status as value
	 */
	public function get_translations_status( $doc_id, $wp_id = null ) {
		$response = $this->get( $this->api_url . '/document/' . $doc_id . '/translation' );
		if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
			$b = json_decode( wp_remote_retrieve_body( $response ) );
			foreach ( $b->entities as $e ) {
				if ( $e->properties->status != 'CANCELLED' ) {
					// cancelled is in a completed state, but it does not mean the translation is 100% complete
					$translations[ $e->properties->locale_code ] = array(
						'progress'         => $e->properties->status,
						'percent_complete' => $e->properties->percent_complete,
					);
				}
			}
		}

		Lingotek_Logger::info(
			'Translation status requested',
			array(
				'document_id'  => $doc_id,
				'wp_id'        => $wp_id,
				'translations' => isset( $translations ) ? $translations : '',
			)
		);

		$this->update_lingotek_error_option(
			$response,
			$wp_id,
			'get_translations_status',
			sprintf( __( 'There was an error updating the translations status for WordPress item %1$s', 'lingotek-translation' ), $wp_id ),
			array( 'document_id' => $doc_id )
		);

		return empty( $translations ) ? array() : $translations;
	}


	public function get_language_mappings() {
		$url      = 'https://gmc.lingotek.com/v1/locales';
		$response = $this->get( $url );
		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Makes an API call to bridge to get the estimated cost for translating a particular document professionally.
	 *
	 * @param string $lingotek_auth the auth token to make an API call to bridge.
	 * @param string $document_id the id of the specific document.
	 * @param string $locale the locale of the document being requested.
	 */
	public function get_cost_estimate( $lingotek_auth, $document_id, $locale ) {
		$this->start_bridge_call();

		$args     = array(
			'document_id' => $document_id,
			'locale'      => $locale,
		);
		$response = $this->get( $this->bridge_url . 'estimate', $args );

		$success = 200 === wp_remote_retrieve_response_code( $response );

		$this->end_bridge_call();

		$this->log_error_on_response_failure(
			$response,
			'GetCostEstimate: Error occured',
			array(
				'document_id' => $document_id,
				'locale'      => $locale,
			)
		);
		return array(
			'success' => $success,
			'data'    => $this->get_response_body_from_bridge( $response ),
		);
	}

	/**
	 * Makes an API call to bridge request translation for a document using the professional workflow.
	 *
	 * @param string $document_id the id of the specific document.
	 * @param string $locale the locale of the document being requested.
	 * @param string $workflow_id the id used to process the document.
	 */
	public function request_professional_translation( $document_id, $locale, $workflow_id ) {
		$this->start_bridge_call();

		$args     = array(
			'document_id' => $document_id,
			'locale'      => $locale,
			'workflow_id' => $workflow_id,
		);
		$response = $this->post( $this->bridge_url . 'request', $args );
		$success  = 200 === wp_remote_retrieve_response_code( $response );

		$this->end_bridge_call();

		if ( $success ) {
			Lingotek_Logger::info(
				'Professional translation requested',
				array(
					'document_id' => $document_id,
					'locale'      => $locale,
					'wordflow_id' => $workflow_id,
				)
			); }
		$this->log_error_on_response_failure(
			$response,
			'RequestProfessionalTranslation: Error occured',
			array(
				'document_id' => $document_id,
				'locale'      => $locale,
				'workflow_id' => $workflow_id,
			)
		);

		return array(
			'success' => $success,
			'data'    => $this->get_response_body_from_bridge( $response ),
		);
	}

	/**
	 * Makes an API call to bridge request bulk translations for a document using the professional workflow.
	 *
	 * @param string $document_id the id of the specific document.
	 * @param string $locale the locale of the document being requested.
	 * @param string $workflow_id the id used to process the document.
	 */
	public function request_professional_translation_bulk( $workflow_id, $translations, $total_estimate, $summary ) {
		$this->start_bridge_call();

		$args     = array(
			'workflow_id'    => $workflow_id,
			'translations'   => $translations,
			'total_estimate' => $total_estimate,
			'summary'        => $summary,
		);
		$response = $this->post( $this->bridge_url . 'request/bulk', $args, 60 );
		$success  = 200 === wp_remote_retrieve_response_code( $response );

		$this->end_bridge_call();

		if ( $success ) {
			Lingotek_Logger::info(
				'Professional translation (bulk) requested',
				array(
					'translations' => $translations,
					'wordflow_id'  => $workflow_id,
				)
			); }
		$this->log_error_on_response_failure(
			$response,
			'RequestProfessionalTranslationBulk: Error occured',
			array(
				'translations'   => $translations,
				'total_estimate' => $total_estimate,
				'workflow_id'    => $workflow_id,
			)
		);

		return array( 'data' => $this->get_response_body_from_bridge( $response ) );
	}


	public function get_lingotek_terms_and_conditions() {
		$this->start_bridge_call();
		$response = $this->get( BRIDGE_URL . '/api/v2/transaction/terms/' );
		$success  = 200 === wp_remote_retrieve_response_code( $response );
		$this->end_bridge_call();

		$this->log_error_on_response_failure( $response, 'GetLingotekTermsAndConditions: Error occured' );
		return array(
			'success' => $success,
			'data'    => $this->get_response_body_from_bridge( $response ),
		);
	}

	/**
	 * Makes an API call to bridge to get the payment information about the user.
	 */
	public function get_user_payment_information() {
		$this->start_bridge_call();

		$response = $this->get( BRIDGE_URL . '/api/v2/transaction/payment' );
		$success  = 200 === wp_remote_retrieve_response_code( $response );

		$this->end_bridge_call();

		$this->log_error_on_response_failure( $response, 'GetUserPaymentInformation: Error occured' );
		return array(
			'success'      => $success,
			'payment_info' => $this->get_response_body_from_bridge( $response ),
		);
	}

	/**
	 * Helper function to retrieve the response body from bridge.
	 *
	 * @param array $response the response from bridge.
	 */
	private function get_response_body_from_bridge( $response ) {
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return isset( $body ) ? $body : wp_remote_retrieve_body( $response );
	}

	/**
	 * requests a new translation of a document
	 *
	 * @since 0.1
	 *
	 * @param string &$document_id document id.
	 * @param string $locale Lingotek locale.
	 * @param array  $args optional arguments (only workflow_id at the moment).
	 * @param object $document the translation term for the post.
	 * @return bool true if the request succeeded.
	 */
	public function request_translation( &$document_id, $locale, $args = array(), $wp_id = null, $document = null ) {
		$lgtm            = $GLOBALS['wp_lingotek']->model;
		$document        = isset( $document ) ? $document : $lgtm->get_group_by_id( $document_id );
		$lingotek_locale = Lingotek::map_to_lingotek_locale( $locale );
		$args            = $unformatted_args = wp_parse_args( $args, array( 'workflow_id' => $this->get_workflow_id() ) );
		$args            = array_merge( array( 'locale_code' => $lingotek_locale ), $args );
		$response        = $this->post( $this->api_url . '/document/' . $document_id . '/translation', $args );
		$title           = isset( $args['title'] ) ? $args['title'] : $document_id;
		if ( $wp_id ) {
			$arr         = get_option( 'lingotek_log_errors', array() );
			$status_code = wp_remote_retrieve_response_code( $response );
			$body        = json_decode( wp_remote_retrieve_body( $response ) );
			$this->update_patch_error_message( $response, $status_code, $title );
			if ( 423 === $status_code ) {
				$document->document_id = $body->next_document_id;
				$document->save();
				return $this->request_translation( $body->next_document_id, $lingotek_locale, $unformatted_args, $wp_id );
			}
			if ( 402 === $status_code ) {
				Lingotek_Logger::error( 'There was an error updating WordPress item', array( 'error' => $this->get_error_message_from_response( $response ) ) );
				$document->source_failed();
				return false;
			}
			if ( 410 === $status_code || 404 === $status_code ) {
				// WP hooks automatically check source status so this might not get called
				$polylang_targets = array_keys( $document->translations );
				if ( ! in_array( $locale, $polylang_targets ) ) {
					$polylang_targets[] = $locale;
				}
				$params           = $lgtm->reupload_build_params( $wp_id );
				$unformatted_args = array_merge( $unformatted_args, $params );
				unset( $document->desc_array['lingotek'] );
				$document->save();
				$lingotek_locales = array();
				foreach ( $polylang_targets as $target ) {
					$document->translations[ $target ] = 'pending';
					// Targets stored in the document translations property are the polylang targets, so we need to get the lingotek locales instead
					$lingotek_locales[] = Lingotek::map_to_lingotek_locale( $target );
				}
				$unformatted_args['translation_locale_code'] = $lingotek_locales;
				$this->format_args_for_upload( $unformatted_args, $wp_id );
				$upload_response = $this->upload_document( $unformatted_args, $wp_id );
				if ( $upload_response ) {
					$document_id = $upload_response;
				}
				return $upload_response;
			}//end if
			if ( 400 === $status_code &&
				! empty( $body->messages ) &&
				strpos( $body->messages[0], 'already exists' ) ) {
				// Translation has already been requested, treat it as a 201 response
				$status_code = 201;
			}
			if ( 201 === $status_code ) {
				if ( isset( $arr[ $wp_id ] ) ) {
					unset( $arr[ $wp_id ]['wp_error'] );
					unset( $arr[ $wp_id ]['request_translation'][ $lingotek_locale ] );
					if ( empty( $arr[ $wp_id ] ) ) {
						unset( $arr[ $wp_id ] );
					}
				}
			} elseif ( is_wp_error( $response ) ) {
				$arr[ $wp_id ]['wp_error'] = __( 'Make sure you have internet connectivity', 'lingotek-translation' );
			} elseif ( 400 == $status_code ) {
				$arr[ $wp_id ]['request_translation'][ $lingotek_locale ] = sprintf(
					__( 'There was an error requesting translation %1$s for WordPress item %2$s', 'lingotek-translation' ),
					$lingotek_locale,
					$wp_id
				);
			}
			update_option( 'lingotek_log_errors', $arr, false );
		}//end if
		if ( 201 !== $status_code ) {
			$this->log_error_on_response_failure(
				$response,
				'RequestTranslation: Error Occurred',
				array(
					'document_id' => $document_id,
					'locale'      => $lingotek_locale,
					'args'        => $args,
				)
			);
		}
		return ! is_wp_error( $response ) && 201 === $status_code;
	}

	/**
	 * get a translation
	 *
	 * @since 0.1
	 *
	 * @param string $id document id
	 * @param string $locale Lingotek locale
	 * @return string|bool the translation, false if there is none
	 */
	public function get_translation( $doc_id, $locale, $wp_id = null ) {
		$locale = Lingotek::map_to_lingotek_locale( $locale );

		$response = $this->get(
			add_query_arg(
				array(
					'locale_code' => $locale,
					'auto_format' => 'true',
				),
				$this->api_url . '/document/' . $doc_id . '/content'
			)
		);

		if ( $wp_id ) {
			$arr         = get_option( 'lingotek_log_errors' );
			$status_code = wp_remote_retrieve_response_code( $response );
			if ( 410 == $status_code ) {
				$lgtm     = $GLOBALS['wp_lingotek']->model;
				$document = $lgtm->get_group_by_id( $doc_id );
				$document->source_failed();
				//TODO IS THIS SETTING ERROR PROPERLY?
				$arr[ $wp_id ]['wp_error'] = __( "Document $doc_id has been archived. Please re-upload source", 'lingotek-translation' );
				return false;
			} elseif ( 200 == $status_code ) {
				if ( isset( $arr[ $wp_id ] ) ) {
					unset( $arr[ $wp_id ]['wp_error'] );
					unset( $arr[ $wp_id ]['get_translation'][ $locale ] );
					if ( empty( $arr[ $wp_id ] ) ) {
						unset( $arr[ $wp_id ] );
					}
				}
			} elseif ( is_wp_error( $response ) ) {
				$arr[ $wp_id ]['wp_error'] = __( 'Make sure you have internet connectivity', 'lingotek-translation' );
			} elseif ( 400 == $status_code || 404 == $status_code ) {
				$arr[ $wp_id ]['get_translation'][ $locale ] = sprintf(
					__( 'There was an error downloading translation %1$s for WordPress item %2$s' ),
					$locale,
					$wp_id
				);
			}//end if
			update_option( 'lingotek_log_errors', $arr, false );
		}//end if
		$this->log_error_on_response_failure(
			$response,
			'GetTranslation: Error Occurred',
			array(
				'document_id' => $doc_id,
				'locale'      => $locale,
			)
		);
		return ! is_wp_error( $response ) && 200 == $status_code ? wp_remote_retrieve_body( $response ) : false;
	}

	/**
	 * cancels a translation
	 *
	 * @since 1.4.2
	 * @since 1.5.0 Changed signature, breaking change.
	 *
	 * @param string $document_id document id
	 * @param string $locale Lingotek locale
	 */
	public function cancel_translation( $document_id, $locale, $wp_id = null ) {
		$args            = wp_parse_args(
			array(
				'cancelled_reason' => 'CANCELLED_BY_AUTHOR',
				'mark_invoiceable' => true,
			)
		);
		$lingotek_locale = Lingotek::map_to_lingotek_locale( $locale );
		$response        = $this->post( "$this->api_url/document/$document_id/translation/$lingotek_locale/cancel", $args );

		if ( $wp_id ) {
			$arr = get_option( 'lingotek_log_errors' );
			if ( isset( $arr[ $wp_id ] ) ) {
				unset( $arr[ $wp_id ] );
				update_option( 'lingotek_log_errors', $arr, false );
			}
		}
		$this->log_error_on_response_failure(
			$response,
			'CancelTranslation: Error occurred',
			array(
				'id'           => $document_id,
				'wordpress_id' => $wp_id,
			)
		);
		$is_success = ! is_wp_error( $response ) && ( 204 == wp_remote_retrieve_response_code( $response ) || 202 == wp_remote_retrieve_response_code( $response ) );
		if ( $is_success ) {
			Lingotek_Logger::info(
				'Target cancelled',
				array(
					'document_id' => $document_id,
					'wp_id'       => $wp_id,
				)
			);
			return $is_success;
		}
		$lingotek_log_errors = get_option( 'lingotek_log_errors', array() );
		if ( ! is_array( $lingotek_log_errors ) ) {
			$lingotek_log_errors = array();
		}
		// Use the response message if it's an authorization error
		$response_error_message                             = 403 == wp_remote_retrieve_response_code( $response ) && $this->get_error_message_from_response( $response ) !== false ?
			$this->get_error_message_from_response( $response ) : false;
		if ( $response_error_message ) {
			$error_message = $response_error_message;
		}
		$lingotek_log_errors['disassociate_document_error'] = $error_message;
		update_option( 'lingotek_log_errors', $lingotek_log_errors, false );

		return $is_success;
	}

	/**
	 * get connect account url
	 *
	 * @param string $redirect_uri the location where to redirect to after account has been connected
	 * @return string the complete url for the connect account link
	 */
	public function get_connect_url( $redirect_uri, $env = null ) {
		$base_url  = $this->base_url;
		$client_id = $this->client_id;
		if ( ! is_null( $env ) ) {
			$base_url = self::PRODUCTION_URL;
		}
		return "$base_url/auth/authorize.html?client_id=$client_id&redirect_uri=" . urlencode( $redirect_uri ) . '&response_type=token';
	}

	public function get_new_url( $redirect_uri ) {
		$base_url  = self::PRODUCTION_URL;
		$client_id = $this->client_id;
		return "$base_url/lingopoint/portal/requestAccount.action?client_id=$client_id&app=" . urlencode( $redirect_uri ) . '&response_type=token';
	}

	public function get_communities() {
		$response = $this->get( add_query_arg( array( 'limit' => 1000 ), $this->api_url . '/community' ) );
		return ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ? json_decode( wp_remote_retrieve_body( $response ) ) : false;
	}

	public function get_projects( $community_id ) {
		$response = $this->get(
			add_query_arg(
				array(
					'community_id' => $community_id,
					'limit'        => 1000,
				),
				$this->api_url . '/project'
			)
		);
		if ( wp_remote_retrieve_response_code( $response ) == 204 ) {
			// There are currently no projects.
			return array();
		}
		return ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ? json_decode( wp_remote_retrieve_body( $response ) ) : false;
	}

	public function get_vaults( $community_id ) {
		$response = $this->get(
			add_query_arg(
				array(
					'community_id' => $community_id,
					'limit'        => 1000,
				),
				$this->api_url . '/vault'
			)
		);
		return ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ? json_decode( wp_remote_retrieve_body( $response ) ) : false;
	}

	public function get_workflows( $community_id ) {
		$response = $this->get(
			add_query_arg(
				array(
					'community_id' => $community_id,
					'limit'        => 1000,
				),
				$this->api_url . '/workflow'
			)
		);
		return ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ? json_decode( wp_remote_retrieve_body( $response ) ) : false;
	}

	public function get_filters() {
		$response = $this->get( add_query_arg( array( 'limit' => 1000 ), $this->api_url . '/filter' ) );
		return ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ? json_decode( wp_remote_retrieve_body( $response ) ) : false;
	}

	public function upload_filter( $name, $type, $content ) {
		$args     = array(
			'name'    => $name,
			'type'    => $type,
			'content' => $content,
		);
		$response = $this->post( "$this->api_url/filter", $args );
	}

	private function start_bridge_call() {
		$this->auth_temp = $this->headers['Authorization'];
		unset( $this->headers['Authorization'] );
		$option                                  = get_option( 'lingotek_token' );
		$this->headers['Authorization-Lingotek'] = $option['access_token'];
	}

	private function end_bridge_call() {
		unset( $this->headers['Authorization-Lingotek'] );
		$this->headers['Authorization'] = $this->auth_temp;
	}

	private function get_workflow_id() {
		return 'project-default' === $this->defaults['workflow_id'] ? null : $this->defaults['workflow_id'];
	}

	/**
	 * Helper function to update lingotek errors as 'lingotek_log_errors' option in case of translation actions (get / request).
	 *
	 * @param $response the response from the action (wp_remote_*).
	 * @param $wp_id id that represents the object in the WP world (post_id / term_id / etc)
	 * @param $action the name of the action performed (for ex 'reqeust_translation')
	 * @param $error_message the message to write in case the response indicates failure
	 * @param $locale the locale the translation action was performed on
	 * @param $extra_data (optional) array of key/value pairs that will be sent as part of the error
	 */
	private function update_lingotek_error_option_for_translation( $response, $wp_id, $action, $error_message, $locale, $extra_data = array() ) {
		if ( $wp_id ) {
			$arr                   = get_option( 'lingotek_log_errors' );
			$response_message_code = wp_remote_retrieve_response_code( $response );
			if ( 200 == $response_message_code || 201 == $response_message_code ) {
				if ( isset( $arr[ $wp_id ] ) ) {
					unset( $arr[ $wp_id ]['wp_error'] );
					unset( $arr[ $wp_id ][ $action ][ $locale ] );
					if ( empty( $arr[ $wp_id ] ) ) {
						unset( $arr[ $wp_id ] );
					}
				}
			} elseif ( is_wp_error( $response ) ) {
				$arr[ $wp_id ]['wp_error'] = __( 'Make sure you have internet connectivity', 'lingotek-translation' );
				Lingotek_Logger::error(
					$action . ': WordPress error occured, please make sure you have internet connectivity',
					array_merge(
						array(
							'http_status'  => $response_message_code,
							'wordpress_id' => $wp_id,
						),
						$extra_data
					)
				);
			} elseif ( 400 == $response_message_code || 404 == $response_message_code ) {
				$arr[ $wp_id ][ $action ][ $locale ] = $error_message;
				Lingotek_Logger::error(
					$action . ': Error occured',
					array_merge(
						array(
							'response_message_code' => $response_message_code,
							'wordpress_id'          => $wp_id,
							'response_message'      => $this->get_error_message_from_response( $response ),
						),
						$extra_data
					)
				);
			}//end if
			update_option( 'lingotek_log_errors', $arr, false );
		}//end if
	}

	/**
	 * Helper function to update lingotek errors as 'lingotek_log_errors' option.
	 *
	 * @param $response the response from the action (wp_remote_*).
	 * @param $wp_id id that represents the object in the WP world (post_id / term_id / etc)
	 * @param $action the name of the action performed (for ex 'reqeust_translation')
	 * @param $error_message the message to write in case the response indicates failure
	 * @param $extra_data (optional) array of key/value pairs that will be sent as part of the error
	 */
	private function update_lingotek_error_option( $response, $wp_id, $action, $error_message, $extra_data = array() ) {
		if ( $wp_id ) {
			$arr                   = get_option( 'lingotek_log_errors' );
			$response_message_code = wp_remote_retrieve_response_code( $response );
			if ( empty( $arr ) ) {
				return;
			}

			if ( 200 == $response_message_code || 202 == $response_message_code ) {
				if ( isset( $arr[ $wp_id ] ) ) {
					unset( $arr[ $wp_id ] );
				}
			} elseif ( is_wp_error( $response ) ) {
				$arr[ $wp_id ]['wp_error'] = __( 'Make sure you have internet connectivity', 'lingotek-translation' );
				Lingotek_Logger::error(
					"$action: WordPress error occured, please make sure you have internet connectivity",
					array_merge(
						array(
							'http_status'  => $response_message_code,
							'wordpress_id' => $wp_id,
						),
						$extra_data
					)
				);
			} elseif ( 400 == $response_message_code || 404 == $response_message_code ) {
				$arr[ $wp_id ][ $action ] = $error_message;
				Lingotek_Logger::error(
					"$action: Error occured",
					array_merge(
						array(
							'http_status'      => $response_message_code,
							'wordpress_id'     => $wp_id,
							'response_message' => $this->get_error_message_from_response( $response ),
						),
						$extra_data
					)
				);
			}//end if
			update_option( 'lingotek_log_errors', $arr, false );
		}//end if
	}

	/**
	 * Helper function to send error log entry to Lingotek_Logger in case the response indicates failure.
	 * Failure response has http status different than 200/201/202/204 and is not wp_error response
	 *
	 * @param $response the response from the action (wp_remote_*).
	 * @param $error_message the message to write in case the response indicates failure
	 * @param $extra_data (optional) array of key/value pairs that will be sent as part of the error
	 */
	private function log_error_on_response_failure( $response, $error_message, $extra_data = array() ) {
		$http_code = wp_remote_retrieve_response_code( $response );
		$success   = 200 === $http_code || 201 === $http_code || 202 === $http_code || 204 === $http_code;
		if ( ! $success || is_wp_error( $response ) ) {
			Lingotek_Logger::error(
				$error_message,
				array_merge(
					array(
						'http_status' => $http_code,
						$extra_data,
					)
				)
			);
		}
	}

	private function get_error_message_from_response( $response ) {
		$responseBody = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $responseBody ) ) {
			return false;
		}
		return property_exists( $responseBody, 'messages' ) && is_array( $responseBody->messages ) ? implode( ' ', $responseBody->messages ) : false;
	}
}
