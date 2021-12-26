<?php

/**
 * Manages interactions with database
 * Factory for Lingotek_Group objects
 *
 * @since 0.1
 */
class Lingotek_Model {

	/**
	 * Polylang model.
	 *
	 * @var obj
	 */
	public $pllm;

	public static $copying_post;
	public static $copying_term;

	/**
	 * constructor
	 *
	 * @since 0.1
	 */
	public function __construct() {
		$this->pllm = $GLOBALS['polylang']->model;

		register_taxonomy(
			'lingotek_profile',
			null,
			array(
				'label'     => false,
				'public'    => false,
				'query_var' => false,
				'rewrite'   => false,
			)
		);
		register_taxonomy(
			'lingotek_hash',
			null,
			array(
				'label'     => false,
				'public'    => false,
				'query_var' => false,
				'rewrite'   => false,
			)
		);
	}

	/**
	 * get the strings groups as well as their count
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public static function get_strings() {
		static $strings = array();
		if ( empty( $strings ) ) {
			// Enables sanitization filter.
			PLL_Admin_Strings::init();

			foreach ( PLL_Admin_Strings::get_strings() as $string ) {
				$strings[ $string['context'] ]['context'] = $string['context'];
				$strings[ $string['context'] ]['count']   = empty( $strings[ $string['context'] ]['count'] ) ? 1 : $strings[ $string['context'] ]['count'] + 1;
			}
			$strings = array_values( $strings );
		}
		return $strings;
	}

	/**
	 * create a translation group object from a translation term
	 *
	 * @since 0.2
	 *
	 * @param object $term
	 * @return object
	 */
	protected function convert_term( $term ) {
		switch ( $term->taxonomy ) {
			case 'term_translations':
				return new Lingotek_Group_Term( $term, $this->pllm );

			case 'post_translations':
				$class = $term->name === $term->slug ? 'Lingotek_Group_Post' : 'Lingotek_Group_String';
				return new $class( $term, $this->pllm );
		}
	}

	/**
	 * get the translation term of an object
	 *
	 * @since 0.2
	 *
	 * @param string     $type Either 'post' or 'term' or 'string'.
	 * @param int|string $id Post id or term id or strings translations group name.
	 * @return object Translation term.
	 */
	public function get_group( $type, $id ) {
		switch ( $type ) {
			case 'post':
				return ( $post = PLL()->model->post->get_object_term( (int) $id, $type . '_translations' ) ) && ! empty( $post ) ? $this->convert_term( $post ) : false;
			case 'term':
				return ( $term = PLL()->model->term->get_object_term( (int) $id, $type . '_translations' ) ) && ! empty( $term ) ? $this->convert_term( $term ) : false;
			case 'string':
				if ( is_numeric( $id ) ) {
					$strings = self::get_strings();
					$id      = $strings[ $id ]['context'];
				}
				return ( $term = get_term_by( 'name', $id, 'post_translations' ) ) && ! empty( $term ) ? $this->convert_term( $term ) : false;
			default:
				return false;
		}
	}

	/**
	 * get document id of an object, false if it wasn't uploaded yet.
	 *
	 * @since 1.4.11
	 *
	 * @param string     $type either 'post' or 'term' or 'string'
	 * @param int|string $id post id or term id or strings translations group name
	 * @return string|bool document id or false
	 */
	public function get_document_id( $type, $id ) {
		$document_term = $this->get_group( $type, $id );
		$document_id   = $document_term != false ? $document_term->document_id : false;
		return $document_id;
	}

	/**
	 * get the translation term of an object by its Lingotek document id
	 *
	 * @since 0.2
	 *
	 * @param string|object $document_id
	 * @return object translation term
	 */
	public function get_group_by_id( $document_id ) {
		// we already passed a translation group object
		if ( is_object( $document_id ) ) {
			return $document_id;
		}

		$terms = get_terms( array( 'post_translations', 'term_translations' ), array( 'slug' => $document_id ) );
		return is_wp_error( $terms ) || empty( $terms ) ? false : $this->convert_term( reset( $terms ) );
	}

	/**
	 * get a translation profile
	 *
	 * @since 0.2
	 *
	 * @param string $type post type or taxonomy
	 * @param object $language
	 * @return array
	 */
	public static function get_profile( $type, $language, $post_id = null ) {
		global $profileInformation, $ltk_profiles, $ltk_contentTypes;

		if ( is_null( $ltk_profiles ) ) {
			$ltk_profiles = get_option( 'lingotek_profiles' );
		}
		if ( $post_id && $profileInformation && isset( $profileInformation[ $type ] ) && isset( $profileInformation[ $type ][ $language->locale ] ) && isset( $profileInformation[ $type ][ $language->locale ][ $post_id ] ) ) {
			return $ltk_profiles[ $profileInformation[ $type ][ $language->locale ][ $post_id ] ];
		}

		if ( is_null( $ltk_contentTypes ) ) {
			$ltk_contentTypes = get_option( 'lingotek_content_type' );
		}

		// If a profile is set for a specific post/page get that first
		if ( $post_id ) {
			$profile_override = get_term_by( 'name', 'lingotek_profile_' . $post_id, 'lingotek_profile' );
			if ( $profile_override ) {
				if ( ! isset( $profileInformation ) ) {
					$profileInformation = array(); }
				if ( ! isset( $profileInformation[ $type ] ) ) {
					$profileInformation[ $type ] = array(); }
				if ( ! isset( $profileInformation[ $type ][ $language->locale ] ) ) {
					$profileInformation[ $type ][ $language->locale ] = array(); }
				$profileInformation[ $type ][ $language->locale ][ $post_id ] = $profile_override->description;
				return $ltk_profiles[ $profile_override->description ];
			}
		}

		// Default profile is manual for posts and pages, custom types are set to disabled by default.
		$default = 'post' === $type || 'page' === $type ? 'manual' : 'disabled';

		$profile = is_object( $language ) && isset( $content_types[ $type ]['sources'][ $language->slug ] ) ?
			$ltk_contentTypes[ $type ]['sources'][ $language->slug ] :
			( isset( $ltk_contentTypes[ $type ]['profile'] ) ? $ltk_contentTypes[ $type ]['profile'] : $default );

		if ( $post_id ) {
			if ( ! isset( $profileInformation ) ) {
				$profileInformation = array(); }
			if ( ! isset( $profileInformation[ $type ] ) ) {
				$profileInformation[ $type ] = array(); }
			if ( ! isset( $profileInformation[ $type ][ $language->locale ] ) ) {
				$profileInformation[ $type ][ $language->locale ] = array(); }
			$profileInformation[ $type ][ $language->locale ][ $post_id ] = $profile;
		}

		return $ltk_profiles[ $profile ];
	}

	public static function get_prefs() {
		$default = array(
			'download_post_status'      => Lingotek_Group_Post::SAME_AS_SOURCE,
			'auto_upload_post_statuses' => array(
				// ignore auto-upload
				'draft'   => 0,
				// auto-upload
				'pending' => 1,
				'publish' => 1,
				'future'  => 1,
				'private' => 0,
			),
			'trash_linked_content'      => array(),
			'auto_update_status'        => '10',
			'enable_lingotek_logs'      => 0,
		);
		// Ensure defaults are set for missing keys.
		$prefs = array_merge( $default, get_option( 'lingotek_prefs', $default ) );
		return $prefs;
	}

	/**
	 * get a profile option
	 *
	 * @since 0.2
	 *
	 * @param string $item 'project_id' | 'workflow_id' | 'upload' | 'download'.
	 * @param string $type Post type or taxonomy.
	 * @param object $source_language
	 * @param object $target_language Optional, needed to get custom target informations 'workflow_id' | 'download'.
	 * @return string | bool either the option or false if the translation is disabled.
	 */
	public static function get_profile_option( $item, $type, $source_language, $target_language = false, $post_id = null ) {
		$profile = self::get_profile( $type, $source_language, $post_id );
		if ( 'disabled' === $profile['profile'] || is_object( $target_language ) && isset( $profile['targets'][ $target_language->slug ] ) && 'disabled' === $profile['targets'][ $target_language->slug ] ) {
			return false;
		}

		if ( ! empty( $target_language ) && isset( $profile['targets'][ $target_language->slug ] ) && ! empty( $profile['custom'][ $item ][ $target_language->slug ] ) ) {
			return $profile['custom'][ $item ][ $target_language->slug ];
		}

		if ( ! empty( $profile[ $item ] ) ) {
			return $profile[ $item ];
		}

		$defaults = get_option( 'lingotek_defaults', array() );
		return isset( $defaults[ $item ] ) ? $defaults[ $item ] : null;
	}

	/**
	 * find targets that are set to copy in a profile
	 *
	 * @since 1.1.1
	 *
	 * @param array $profile (use get_profile to retrieve)
	 * @return array of targets that should be copied. if none exist returns empty array
	 */
	public function targets_to_be_copied( $profile ) {
		if ( isset( $profile['targets'] ) && in_array( 'copy', $profile['targets'] ) ) {
			$targets_to_copy = array_keys( $profile['targets'], 'copy' );
			return $targets_to_copy;
		} else {
			return array();
		}
	}

	/**
	 * copy a post from the source language to a target language
	 *
	 * @since 1.1.1
	 *
	 * @param object $post
	 * @param string $target polylang language slug (ex: en, de, fr, etc)
	 * @return $new_post_id if copy of post is successful, false otherwise
	 */
	public function copy_post( $post, $target ) {
		self::$copying_post     = true;
		$document               = $this->get_group( 'post', $post->ID );
		$prefs                  = self::get_prefs();
		$cp_lang                = $this->pllm->get_language( $target );
		$cp_post                = (array) $post;
		$cp_post['post_status'] = ( $prefs['download_post_status'] === 'SAME_AS_SOURCE' ) ? $post->post_status : $prefs['download_post_status'];
		$slug                   = $cp_post['post_name'];
		unset( $cp_post['ID'] );
		unset( $cp_post['post_name'] );
		if ( ! isset( $document->desc_array[ $target ] ) ) {
			$new_post_id = wp_insert_post( $cp_post, true );
			if ( ! is_wp_error( $new_post_id ) ) {
				PLL()->model->post->set_language( $new_post_id, $cp_lang );
				wp_set_object_terms( $new_post_id, $document->term_id, 'post_translations' );
				$GLOBALS['polylang']->sync->taxonomies->copy( $document->source, $new_post_id, $cp_lang->slug );
				$GLOBALS['polylang']->sync->post_metas->copy( $document->source, $new_post_id, $cp_lang->slug );
				Lingotek_Group_Post::copy_or_ignore_metas( $post->ID, $new_post_id );
				$document->desc_array[ $target ] = $new_post_id;
				$document->save();
				if ( class_exists( 'PLL_Share_Post_Slug', true ) ) {
					wp_update_post(
						array(
							'ID'        => $new_post_id,
							'post_name' => $slug,
						)
					);
				}
			}
		}
		self::$copying_post = false;
	}

	public function copy_term( $term, $target, $taxonomy ) {
		self::$copying_term = true;
		$document           = $this->get_group( 'term', $term->term_id );
		$cp_lang            = $this->pllm->get_language( $target );
		$cp_term            = (array) $term;

		if ( class_exists( 'PLL_Share_Term_Slug', true ) ) {
			remove_action( 'create_term', array( PLL()->filters_term, 'save_term' ), 999, 3 );
			remove_action( 'edit_term', array( PLL()->filters_term, 'save_term' ), 999, 3 );
			remove_action( 'pre_post_update', array( PLL()->filters_term, 'pre_post_update' ) );
			remove_filter( 'pre_term_name', array( PLL()->filters_term, 'pre_term_name' ) );
			remove_filter( 'pre_term_slug', array( PLL()->filters_term, 'pre_term_slug' ), 10, 2 );
			add_action( 'pre_post_update', array( PLL()->share_term_slug, 'pre_post_update' ) );
			add_filter( 'pre_term_name', array( PLL()->share_term_slug, 'pre_term_name' ) );
			add_filter( 'pre_term_slug', array( PLL()->share_term_slug, 'pre_term_slug' ), 10, 2 );
			add_action( 'create_term', array( PLL()->share_term_slug, 'save_term' ), 1, 3 );
			add_action( 'edit_term', array( PLL()->share_term_slug, 'save_term' ), 1, 3 );
			$_POST['term_lang_choice'] = $cp_lang->slug;
		} else {
			if ( isset( $cp_term['slug'] ) && term_exists( $cp_term['slug'] ) ) {
				$cp_term['slug'] .= '-' . $cp_lang->slug;
			}
		}

		$new_term = wp_insert_term( $cp_term['name'], $taxonomy, $cp_term );

		if ( ! is_wp_error( $new_term ) ) {
			PLL()->model->term->set_language( $new_term['term_id'], $cp_lang );
			wp_set_object_terms( $new_term['term_id'], $document->term_id, 'term_translations' );
			$document->desc_array[ $target ] = $new_term['term_id'];
			$document->save();
		}
		self::$copying_term = false;
	}

	private function format_patch_params( $params, $profile, $source_lang ) {
		$params['translation_locale_code'] = array();
		$params['translation_workflow_id'] = array();

		foreach ( $this->pllm->get_languages_list() as $lang ) {
			if ( $lang->lingotek_locale == $source_lang->lingotek_locale
			|| ( isset( $profile['targets'][ $lang->slug ] ) && 'disabled' === $profile['targets'][ $lang->slug ] ) ) {
				continue;
			}
			$params['translation_locale_code'][] = $lang->lingotek_locale;
			if ( isset( $profile['custom']['workflow_id'][ $lang->slug ], $profile['targets'][ $lang->lingotek_locale ] ) ) {
				$params['translation_workflow_id'][] = $profile['custom']['workflow_id'][ $lang->slug ];
			} elseif ( ! isset( $profile['targets'][ $lang->lingotek_locale ] ) && isset( $profile['workflow_id'] ) && $profile['workflow_id'] !== 'project-default' ) {
				// Target is not using a custom workflow so we use the default workflow.
				$params['translation_workflow_id'][] = $profile['workflow_id'];
			}
		}
		if ( empty( $params['translation_workflow_id'] ) ) {
			unset( $params['translation_workflow_id'] );
		}
		return $params;
	}

	/**
	 * uploads a new post to Lingotek TMS and requests the targets specified
	 *
	 * @since 0.1
	 *
	 * @param int $post_id
	 * @return boolean|string false if post failed to upload
	 */
	public function upload_post( $post_id ) {
		$post     = get_post( $post_id );
		$language = PLL()->model->post->get_language( $post_id );
		$profile  = self::get_profile( $post->post_type, $language, $post_id );
		if ( 'disabled' === $profile['profile'] || empty( $post ) || empty( $language ) ) {
			return;
		}
		// Returns type string or boolean
		$document_id = self::get_document_id( 'post', $post_id );
		// Slug can't be empty or duplicated, so we prefix `disassociated` to show the document id is no longer associated with this post
		if ( is_string( $document_id ) && $this->is_disassociated_deleted_cancelled_or_archived( $document_id ) ) {
			$document_id = false;
		}
		$content = null;
		// If we already uploaded this doc, check if it changed and prevent the upload if it didn't.
		$client = new Lingotek_API();
		if ( $document_id ) {
			$content    = Lingotek_Group_Post::get_content( $post );
			$hash_terms = wp_get_object_terms( $post->ID, 'lingotek_hash' );
			$hash_term  = array_pop( $hash_terms );
			$new_hash   = md5( $content );
			if ( ! empty( $hash_term ) ) {
				// If the document hasn't changed and it exists in TMS, don't upload
				$status = $client->get_document_status( $document_id );
				if ( $hash_term->description === $new_hash && 'current' === $status ) {
					return;
				}
			}
		}
		$document = $this->get_group( 'post', $post_id );
		// Customized workflows have the option to do any sort of pre-processing before a document
		// is uploaded to lingotek.
		if ( $document ) {
			$document->pre_upload_to_lingotek( $post_id, $post->post_type, $language, 'post' );
		}

		$external_url = get_page_link( $post_id );
		if ( ! $content ) {
			$content = Lingotek_Group_Post::get_content( $post );
		}
		$params     = $this->build_params( $external_url, $post->post_title, $post->post_type, $content, $language, $profile, $post_id, $wp_target_locales );
		$filter_ids = $this->get_filter_ids( $post->post_type, $language, $post_id );
		$params     = array_merge( $params, $filter_ids );
		if ( $document && in_array( $document->status, array( 'edited', 'importing', 'current' ) ) && $document_id ) {
			$response = $document->patch( $this->format_patch_params( $params, $profile, $language ) );
			if ( $response ) {
				// Only save the hash if the patch function is successful
				// Re-establish hash relation if needed
				if ( ! isset( $hash_term->term_id ) ) {
					$hash_terms   = wp_set_post_terms( $post->ID, 'lingotek_hash_' . $post->ID, 'lingotek_hash' );
					$hash_term_id = array_pop( $hash_terms );
				} else {
					$hash_term_id = $hash_term->term_id;
				}
				wp_update_term( $hash_term_id, 'lingotek_hash', array( 'description' => $new_hash ) );
			}
			return $response;
		} elseif ( ! Lingotek_Group::$creating_translation && ! self::$copying_post && ( ! $document || in_array( $document->status, array( 'deleted', 'disassociated', 'archived', 'cancelled', null ), true ) ) ) {
			$document_id = $client->upload_document( $params, $post->ID );
			if ( $document_id ) {
				Lingotek_Group_Post::create( $post->ID, $language, $document_id );
				$document = $this->get_group_by_id( $document_id );
				if ( isset( $wp_target_locales ) ) {
					foreach ( $wp_target_locales as $locale ) {
						$document->translations[ $locale ] = 'pending';
					}
				}
				$document->save();
				// If a translation profile has targets set to copy then copy them
				$targets_to_copy = $this->targets_to_be_copied( $profile );
				$upload          = self::get_profile_option( 'upload', $post->post_type, $language, false, $post_id );
				if ( ! empty( $targets_to_copy ) && $upload === 'automatic' ) {
					foreach ( $targets_to_copy as $target ) {
						$this->copy_post( $post, $target );
					}
				}
				return $document_id;
			}
			return false;
		}//end if
	}

	/**
	 * uploads a new term to Lingotek TMS
	 *
	 * @since 0.2
	 *
	 * @param int    $term_id
	 * @param string $taxonomy
	 */
	public function upload_term( $term_id, $taxonomy ) {
		$term     = get_term( $term_id, $taxonomy );
		$language = PLL()->model->term->get_language( $term_id );
		if ( empty( $term ) || empty( $language ) ) {
			return;
		}

		$profile = self::get_profile( $taxonomy, $language );
		if ( 'disabled' === $profile['profile'] ) {
			return;
		}

		/**
		* Customized workflows have the option to do any sort of pre-processing before a document is uploaded to lingotek.
		*/
		$document = $this->get_group( 'term', $term_id );
		if ( $document ) {
			$document->pre_upload_to_lingotek( $term_id, $taxonomy, $language, 'term' );
		}

		$client     = new Lingotek_API();
		$content    = Lingotek_Group_Term::get_content( $term );
		$params     = $this->build_params( get_term_link( $term_id, $taxonomy ), $term->name, $taxonomy, $content, $language, $profile, $term_id, $wp_target_locales );
		$filter_ids = $this->get_filter_ids( $taxonomy, $language, $term_id );
		$params     = array_merge( $params, $filter_ids );

		if ( ( $document = $this->get_group( 'term', $term_id ) ) && 'edited' === $document->status ) {
			$document->patch( $this->format_patch_params( $params, $profile, $language ), $term->name, $term );
		} elseif ( ! Lingotek_Group::$creating_translation && ! self::$copying_term ) {
			$document_id = $client->upload_document( $params, $term_id );

			if ( $document_id ) {
				Lingotek_Group_Term::create( $term_id, $taxonomy, $language, $document_id );
				$document = $this->get_group_by_id( $document_id );
				foreach ( $wp_target_locales as $locale ) {
					$document->translations[ $locale ] = 'pending';
				}
				$document->save();

				// If a translation profile has targets set to copy then copy them
				$targets_to_copy = $this->targets_to_be_copied( $profile );
				if ( ! empty( $targets_to_copy ) && 'automatic' === $profile['upload'] ) {
					foreach ( $targets_to_copy as $target ) {
						$this->copy_term( $term, $target, $taxonomy );
					}
				}
			}
		}//end if
	}

	/**
	 * uploads a strings group to Lingotek TMS
	 *
	 * @since 0.2
	 *
	 * @param string $group
	 */
	public function upload_strings( $group ) {
		$type     = 'string';
		$language = $this->pllm->get_language( $this->pllm->options['default_lang'] );
		$profile  = self::get_profile( $type, $language );

		if ( 'disabled' === $profile['profile'] ) {
			return;
		}

		if ( is_numeric( $group ) ) {
			$strings = self::get_strings();
			$group   = $strings[ $group ]['context'];
		}

		// check that we have a valid string group
		if ( ! in_array( $group, wp_list_pluck( self::get_strings(), 'context' ) ) ) {
			return;
		}

		$client     = new Lingotek_API();
		$content    = Lingotek_Group_String::get_content( $group );
		$params     = $this->build_params( '', $group, $type, $content, $language, $profile, null );
		$filter_ids = $this->get_filter_ids( $type, $language, null );
		$params     = array_merge( $params, $filter_ids );

		if ( ( $document = $this->get_group( $type, $group ) ) && 'edited' === $document->status ) {
			$document->patch( $this->format_patch_params( $params, $profile, $language ) );
		} else {
			$document_id = $client->upload_document( $params, $group );

			if ( $document_id ) {
				Lingotek_Group_String::create( $group, $language, $document_id );
			}
		}
	}

	/**
	 * checks if the document can be upload to Lingotek
	 *
	 * @since 0.1
	 *
	 * @param string $type either 'post' or 'term'
	 * @param int    $object_id post id or term id
	 * @return bool
	 */
	public function can_upload( $type, $object_id ) {
		// FIXME should I check for disabled profile here?
		$document = $this->get_group( $type, $object_id );
		if ( $document && 'failed' === $document->status ) {
			return false;
		}
		switch ( $type ) {
			case 'string':
				if ( empty( $document ) ) {
					return true;
				}
				// Check if source strings have not been modified.
				elseif ( $document->md5 !== md5( Lingotek_Group_String::get_content( $object_id ) ) ) {
					$document->source_edited();
					return true;
				}

				return false;

			case 'post':
				$language     = PLL()->model->post->get_language( $object_id );
				$allow_status = $document && 'edited' === $document->status ? true : ( $document && 'cancelled' === $document->status ? true : false );
				return ! empty( $language ) && ( empty( $document ) ||
					( isset( $document ) && $allow_status && $document->source == $object_id ) );
			case 'term':
				// first check that a language is associated to the object
				$language = PLL()->model->term->get_language( $object_id );

				// FIXME how to get profile to check if disabled?

				return ! empty( $language ) && ( empty( $document ) ||
					// Specific for terms as document is never empty.
					( empty( $document->translations ) && empty( $document->source ) ) ||
					( isset( $document ) && 'edited' == $document->status && $document->source == $object_id ) );
		}//end switch
	}

	/**
	 * deletes a post
	 *
	 * @since 0.1
	 *
	 * @param int $object_id post id
	 */
	public function delete_post( $object_id ) {
		if ( $document = $this->get_group( 'post', $object_id ) ) {
			$client = new Lingotek_API();

			if ( $document->source == $object_id ) {
				$client->cancel_document( $document->document_id, $object_id );
			} else {
				PLL()->model->post->delete_translation( $object_id );
				$lang = PLL()->model->post->get_language( $object_id );
				$client->cancel_translation( $document->document_id, $lang->lingotek_locale, $object_id );
			}
		}
	}

	public function cancel_post( $object_id ) {
		if ( $document = $this->get_group( 'post', $object_id ) ) {
			$client = new Lingotek_API();

			if ( $document->source == $object_id ) {
				$client->cancel_document( $document->document_id, $object_id );
			} else {
				$lang   = PLL()->model->post->get_language( $object_id );
				$locale = $lang->locale;
				if ( isset( $document->desc_array['lingotek']['translations'][ $locale ] ) ) {
					$document->desc_array['lingotek']['translations'][ $locale ] = 'cancelled';
				}
				$document->save();
				$client->cancel_translation( $document->document_id, $lang->lingotek_locale, $object_id );
			}
		}
	}

	/**
	 * deletes a term
	 *
	 * @since 0.2
	 *
	 * @param int $object_id term id
	 */
	public function delete_term( $object_id ) {
		if ( $document = $this->get_group( 'term', $object_id ) ) {
			$client = new Lingotek_API();
			if ( $document->source == $object_id ) {
				$client->cancel_document( $document->document_id, $object_id );
			} else {
				$lang = PLL()->model->term->get_language( $object_id );
				PLL()->model->term->delete_language( $object_id );
				PLL()->model->term->delete_translation( $object_id );
				$client->cancel_translation( $document->document_id, $lang->lingotek_locale, $object_id );
			}
		}
	}

	public function cancel_term( $object_id ) {
		if ( $document = $this->get_group( 'term', $object_id ) ) {
			$client = new Lingotek_API();
			if ( $document->source == $object_id ) {
				$client->cancel_document( $document->document_id, $object_id );
			} else {
				$lang = PLL()->model->term->get_language( $object_id );
				PLL()->model->term->cancel_translation( $document->document_id, $lang->lingotek_locale );
				PLL()->model->term->delete_translation( $object_id );
				$client->cancel_translation( $document->document_id, $lang->lingotek_locale, $object_id );
			}
		}
	}

	/**
	 * counts the number of targets per language
	 *
	 * @since 0.2
	 *
	 * @param array $groups Array of serialized 'post_translations' or 'term_translations' description.
	 * @return array number of targets per language
	 */
	protected function get_target_count( $groups ) {
		$targets = array_fill_keys( $this->pllm->get_languages_list( array( 'fields' => 'slug' ) ), 0 );

		foreach ( $groups as $group ) {
			$group = unserialize( $group );
			if ( isset( $group['lingotek']['translations'] ) ) {
				foreach ( $group['lingotek']['translations'] as $locale => $status ) {
					$language = $this->pllm->get_language( $locale );
					if ( 'current' === $status && $language ) {
						$targets[ $language->slug ]++;
					}
				}
			}
		}
		return $targets;
	}

	/**
	 * counts the number of sources and targets per language for a certain post type
	 *
	 * @since 0.2
	 *
	 * @param string $post_type
	 * @return array
	 */
	public function count_posts( $post_type ) {
		global $wpdb;

		static $r = array();
		if ( ! empty( $r[ $post_type ] ) ) {
			return $r[ $post_type ];
		}

		if ( ! post_type_exists( $post_type ) ) {
			return;
		}

		// gets all translations groups for the post type
		$groups = $wpdb->get_col(
			$wpdb->prepare(
				"
			SELECT DISTINCT tt.description FROM $wpdb->term_taxonomy AS tt
			INNER JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN $wpdb->posts AS p ON p.ID = tr.object_id
			WHERE tt.taxonomy = %s
			AND p.post_type = %s
			AND p.post_status NOT IN ('trash', 'auto-draft')",
				'post_translations',
				$post_type
			)
		);

		$targets = $this->get_target_count( $groups );

		$group_ids = array();
		$disabled  = array();

		foreach ( $this->pllm->get_languages_list() as $language ) {
			// counts all the posts in one language
			$n = $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*) FROM $wpdb->term_relationships AS tr
				INNER JOIN $wpdb->posts AS p ON p.ID = tr.object_id
				WHERE tr.term_taxonomy_id = %d
				AND p.post_type = %s
				AND p.post_status NOT IN ('trash', 'auto-draft')",
					$language->term_taxonomy_id,
					$post_type
				)
			);

			$objects = $wpdb->get_col(
				$wpdb->prepare(
					"
				SELECT object_id FROM $wpdb->term_relationships AS tr
				INNER JOIN $wpdb->posts AS p ON p.ID = tr.object_id
				WHERE tr.term_taxonomy_id = %d
				AND p.post_type = %s
				AND p.post_status NOT IN ('trash', 'auto-draft')",
					$language->term_taxonomy_id,
					$post_type
				)
			);

			foreach ( $groups as $group ) {
				$group = unserialize( $group );
				if ( array_key_exists( $language->slug, $group ) ) {
					$group_ids[] = $group[ $language->slug ];
				}
			}

			$count = 0;
			foreach ( $objects as $object ) {
				$id = $object;
				if ( ! in_array( $id, $group_ids, true ) ) {
					$profile = self::get_profile( $post_type, $language, $id );
					if ( 'disabled' === $profile['profile'] && in_array( $id, $objects, true ) ) {
						++$count;
					}
				}
			}
			$disabled[ $language->slug ] = $count;

			// if a post is not a target, then it is source
			$sources[ $language->slug ] = $n - $targets[ $language->slug ];
			// $sources[$language->slug] -= $disabled[$language->slug];
		}//end foreach

		// untranslated posts have no associated translation group in DB
		// so let's count them indirectly

		// counts the number of translated posts
		$n_translated = $wpdb->get_var(
			$wpdb->prepare(
				"
			SELECT COUNT(*) FROM $wpdb->term_relationships AS tr
			INNER JOIN $wpdb->posts AS p ON p.ID = tr.object_id
			INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			WHERE tt.taxonomy = %s
			AND p.post_type = %s
			AND p.post_status NOT IN ('trash', 'auto-draft')",
				'post_translations',
				$post_type
			)
		);

		// untranslated = total - translated
		// total of posts translations groups = untranslated + number of translation groups stored in DB
		$count_posts = (array) wp_count_posts( $post_type );
		// Don't count trash and auto-draft.
		unset( $count_posts['trash'], $count_posts['auto-draft'] );
		$total = array_sum( $count_posts ) - $n_translated + count( $groups );

		return $r[ $post_type ] = compact( 'sources', 'targets', 'total' );
	}

	/**
	 * counts the number of sources and targets per language for a certain taxonomy
	 *
	 * @since 0.2
	 *
	 * @param string $taxonomy
	 * @return array
	 */
	public function count_terms( $taxonomy ) {
		global $wpdb;

		static $r = array();
		if ( ! empty( $r[ $taxonomy ] ) ) {
			return $r[ $taxonomy ];
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		// gets all translations groups for the taxonomy
		$groups = $wpdb->get_col(
			$wpdb->prepare(
				"
			SELECT DISTINCT tt1.description FROM $wpdb->term_taxonomy AS tt1
			INNER JOIN $wpdb->term_relationships AS tr ON tt1.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN $wpdb->term_taxonomy AS tt2 ON tt2.term_id = tr.object_id
			WHERE tt1.taxonomy = %s
			AND tt2.taxonomy = %s",
				'term_translations',
				$taxonomy
			)
		);

		$targets = $this->get_target_count( $groups );

		$group_ids = array();
		$disabled  = array();

		foreach ( $this->pllm->get_languages_list() as $language ) {
			// counts all the terms in one language
			$n = $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*) FROM $wpdb->term_relationships AS tr
				INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = tr.object_id
				WHERE tr.term_taxonomy_id = %d
				AND tt.taxonomy = %s",
					$language->tl_term_taxonomy_id,
					$taxonomy
				)
			);

			$objects = $wpdb->get_col(
				$wpdb->prepare(
					"
				SELECT object_id FROM $wpdb->term_relationships AS tr
				INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = tr.object_id
				WHERE tr.term_taxonomy_id = %d
				AND tt.taxonomy = %s",
					$language->tl_term_taxonomy_id,
					$taxonomy
				)
			);

			$count = 0;
			foreach ( $groups as $group ) {
				$group = unserialize( $group );
				if ( array_key_exists( $language->slug, $group ) ) {
					$group_ids[] = $group[ $language->slug ];
					$profile     = self::get_profile( $taxonomy, $language, $group[ $language->slug ] );
					if ( 'disabled' === $profile['profile'] && ! isset( $group['lingotek'] ) ) {
						++$count;
					}
				}
			}

			$disabled[ $language->slug ] = $count;

			// if a term is not a target, then it is a source
			$sources[ $language->slug ] = $n - $targets[ $language->slug ];
			// $sources[$language->slug] -= $disabled[$language->slug];
		}//end foreach

		$total = count( $groups );

		// default categories are created by Polylang in all languages
		// don't count them as sources if they are not associated to the TMS
		if ( 'category' === $taxonomy ) {
			$term_id = get_option( 'default_category' );
			$group   = $this->get_group( 'term', $term_id );
			foreach ( $this->pllm->get_languages_list() as $language ) {
				if ( empty( $group->source ) || ( $group->get_source_language()->slug !== $language->slug && empty( $group->translations[ $language->locale ] ) ) ) {
					if ( $language->slug !== $this->pllm->options['default_lang'] ) {
						$sources[ $language->slug ]--;
					}
				}
			}
			// Remove category targets from being counted until they are downloaded. Fixed target categories being counted as source languages.
			foreach ( $groups as $group ) {
				$group = unserialize( $group );
				if ( isset( $group['lingotek']['translations'] ) ) {
					foreach ( $group['lingotek']['translations'] as $locale => $status ) {
						$language = $this->pllm->get_language( $locale );
						if ( ( 'pending' === $status || 'ready' === $status ) && $language ) {
							if ( $sources[ $language->slug ] > 0 ) {
								$sources[ $language->slug ]--;
							}
						}
					}
				}
			}
			if ( 1 === count( $sources ) && $total !== $sources[ $this->pllm->options['default_lang'] ] ) {
				$total = $sources[ $this->pllm->options['default_lang'] ];
			}
		}//end if
		$r[ $taxonomy ] = compact( 'sources', 'targets', 'total' );
		return $r[ $taxonomy ];
	}

	private function build_params( $external_url, $title, $type, $content, $language, $profile, $wp_id, &$wp_target_locales = array() ) {
		$translation_workflow_id = self::get_profile_option( 'workflow_id', $type, $language, false, $wp_id );
		if ( 'project-default' === $translation_workflow_id || false === $translation_workflow_id ) {
			$translation_workflow_id = null;
		}

		$params = array(
			'title'        => $title,
			'content'      => $content,
			'locale_code'  => $language->lingotek_locale,
			'project_id'   => self::get_profile_option( 'project_id', $type, $language, false, $wp_id ),
			'external_url' => $external_url,
		);

		$lingotek_metadata_keys = array(
			'author_email',
			'author_name',
			'division',
			'unit',
			'campaign_id',
			'channel',
			'contact_email',
			'contact_name',
			'description',
			'domain',
			'style_id',
			'purchase_order',
			'reference_url',
			'region',
			'require_review',
		);

		foreach ( $lingotek_metadata_keys as $key ) {
			if ( isset( $profile[ $key ] ) ) {
				$params[ $key ] = $profile[ $key ];
			}
		}

		if ( null !== $translation_workflow_id ) {
			$params['translation_workflow_id'] = $translation_workflow_id;
		}

		// Get target locales to send up from profile
		$target_locales = array();
		if ( ! empty( $profile['target_locales'] ) ) {
			if ( isset( $profile['target_locales'][ $language->slug ] ) ) {
				unset( $profile['target_locales'][ $language->slug ] );
			}
			if ( isset( $profile['custom']['workflow_id'][ $language->slug ] ) ) {
				unset( $profile['custom']['workflow_id'][ $language->slug ] );
			}
			$target_locales['translation_locale_code'] = array_values( $profile['target_locales'] );
			$wp_target_locales                         = array_keys( $profile['target_locales'] );

			// Add workflows
			if ( ! empty( $profile['custom']['workflow_id'] ) ) {
				$temp_workflow_ids = array();
				$workflow_id       = null;
				foreach ( $wp_target_locales as $language_slug ) {
					if ( isset( $profile['custom']['workflow_id'][ $language_slug ] ) ) {
						$profile_override = $profile['custom']['workflow_id'][ $language_slug ];
						$workflow_id      = 'project-default' === $profile_override ? $translation_workflow_id : $profile_override;
					} else {
						$workflow_id = $translation_workflow_id;
					}
					if ( $workflow_id ) {
						$temp_workflow_ids[] = $workflow_id;
					}
				}
				$params['translation_workflow_id'] = $temp_workflow_ids;
			}
		} else {
			// No targets being sent up to TMS, unset the workflow
			unset( $params['translation_workflow_id'] );
		}//end if

		$params = array_merge( $params, $target_locales );

		return $params;
	}

	/**
	 * Builds the array of parameters for reuploads by getting only the post id.
	 * Primarily for when handling 410 and 404 response codes
	 * Return empty array if profile is disabled, or post/language are empty
	 *
	 * @since 1.5.0
	 *
	 * @param integer $post_id
	 * @return array
	 */
	public function reupload_build_params( $post_id ) {
		$post     = get_post( $post_id );
		$language = PLL()->model->post->get_language( $post_id );
		$profile  = self::get_profile( $post->post_type, $language, $post_id );
		if ( 'disabled' === $profile['profile'] || empty( $post ) || empty( $language ) ) {
			Lingotek_Logger::info(
				'Document cannot be uploaded',
				array(
					'profile'           => $profile['profile'],
					'post is empty'     => empty( $post ),
					'language is empty' => empty( $language ),
				)
			);
			return array();
		}
		$external_url = get_page_link( $post_id );
		$content      = Lingotek_Group_Post::get_content( $post );
		return $this->build_params( $external_url, $post->post_title, $post->post_type, $content, $language, $profile, $post_id );
	}

	private function get_filter_ids( $type, $language, $wp_id ) {
		$filter_ids = array();
		if ( self::get_profile_option( 'primary_filter_id', $type, $language, false, $wp_id ) ) {
			$filter_ids['fprm_id'] = self::get_profile_option( 'primary_filter_id', $type, $language, false, $wp_id );
		}
		if ( self::get_profile_option( 'secondary_filter_id', $type, $language, false, $wp_id ) ) {
			$filter_ids['fprm_subfilter_id'] = self::get_profile_option( 'secondary_filter_id', $type, $language, false, $wp_id );
		}
		return $filter_ids;
	}

	/**
	 * Checks if a document id is disassociated, cancelled or archived.
	 *
	 * @param string $document_id
	 *
	 * @return bool
	 */
	protected function is_disassociated_deleted_cancelled_or_archived( $document_id ) {
		return strpos( $document_id, 'disassociated_' ) === 0 ||
				strpos( $document_id, 'deleted_' ) === 0 ||
				strpos( $document_id, 'cancelled_' ) === 0 ||
				strpos( $document_id, 'archived_' ) === 0;
	}
}
