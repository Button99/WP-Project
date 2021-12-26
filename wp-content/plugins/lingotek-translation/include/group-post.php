<?php

/*
 * Translations groups for posts, pages and custom post types
 *
 * @since 0.2
 */
class Lingotek_Group_Post extends Lingotek_Group {

	// Preference constant used for downloaded translations.
	const SAME_AS_SOURCE = 'SAME_AS_SOURCE';
	/*
	 * set a translation term for an object
	 *
	 * @since 0.2
	 *
	 * @param int $object_id post id
	 * @param object $language
	 * @param string $document_id translation term name (Lingotek document id)
	 */
	public static function create( $object_id, $language, $document_id ) {
		$data = array(
			'lingotek'      => array(
				'type'         => get_post_type( $object_id ),
				'source'       => $object_id,
				'status'       => 'importing',
				'translations' => array(),
			),
			// For Polylang.
			$language->slug => $object_id,
		);
		self::save_hash_on_upload( $object_id );
		self::_create( $object_id, $document_id, $data, 'post_translations' );
	}

	/*
	 * returns content type fields
	 *
	 * @since 0.2
	 *
	 * @param string $post_type
	 * @return array
	 */
	public static function get_content_type_fields( $post_type, $post_ID = null ) {
		$cache_key = 'content_type_fields_' . $post_type;
		$arr       = wp_cache_get( $cache_key, 'lingotek' );
		if ( ! $arr ) {
			$arr = 'attachment' == $post_type ?
			array(
				'post_title'   => __( 'Title', 'lingotek-translation' ),
				'post_excerpt' => __( 'Caption', 'lingotek-translation' ),
				'metas'        => array( '_wp_attachment_image_alt' => __( 'Alternative Text', 'lingotek-translation' ) ),
				'post_content' => __( 'Description', 'lingotek-translation' ),
			) : array(
				'post_title'   => __( 'Title', 'lingotek-translation' ),
				'post_name'    => __( 'Slug', 'lingotek-translation' ),
				'post_content' => __( 'Content', 'lingotek-translation' ),
				'post_excerpt' => __( 'Excerpt', 'lingotek-translation' ),
			);

			// if the user hasn't visited the custom fields tab, and hasn't saved actions for custom
			// fields, and uploaded a post, check the wpml file for settings
			if ( $post_ID ) {
				self::get_updated_meta_values( $post_ID );
			}
			// add the custom fields from the lingotek_custom_fields option
			$custom_fields = get_option( 'lingotek_custom_fields', array() );

			if ( is_array( $custom_fields ) && ! empty( $custom_fields ) ) {
				foreach ( $custom_fields as $cf => $setting ) {
					if ( 'translate' == $setting ) {
						$arr['metas'][ $cf ] = $cf;
					}
				}
			}
			wp_cache_set( $cache_key, $arr, 'lingotek', 300 );
		}//end if
		// allow plugins to modify the fields to translate
		return apply_filters( 'lingotek_post_content_type_fields', $arr, $post_type );
	}

	/*
	* returns custom fields from the wpml-config.xml file
	*
	* @since 0.2
	*
	* @param string $post_type
	* @return array
	*/
	public static function get_custom_fields_from_wpml() {
		$wpml_config = PLL_WPML_Config::instance();
		$arr         = array();

		if ( isset( $wpml_config->tags['custom-fields'] ) ) {
			foreach ( $wpml_config->tags['custom-fields'] as $context ) {
				foreach ( $context['custom-field'] as $cf ) {
					$arr[ $cf['value'] ] = $cf['attributes']['action'];
				}
			}
		}

		// allow plugins to modify the fields to translate
		return apply_filters( 'lingotek_post_content_type_fields_from_wpml', $arr );
	}

	/*
	 * returns meta (custom) fields from the wp-postmeta database table
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public static function get_custom_fields_from_wp_postmeta( $post_ID = null ) {
		$all_acf_fields  = array();
		$custom_fields   = get_option( 'lingotek_custom_fields', array() );
		$meta_black_list = array( '_encloseme', '_edit_last', '_edit_lock', '_wp_trash_meta_status', '_wp_trash_meta_time' );
		$arr             = array();
		$keys            = array();

		if ( $post_ID ) {
			$p       = get_post( $post_ID );
			$posts[] = $p;
		} else {
			$posts = get_posts(
				array(
					'posts_per_page' => -1,
					'post_type'      => 'post',
				)
			);
			$pages = get_posts(
				array(
					'posts_per_page' => -1,
					'post_type'      => 'page',
				)
			);

			$posts = array_merge( $posts, $pages );
		}
		//Only perform if ACF is installed and these functions are active
		if ( function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) ) {
			$groups       = acf_get_field_groups();
			$field_holder = array();
			foreach ( $groups as $group ) {
				if ( isset( $group['key'] ) ) {
					$field_holder = acf_get_fields( $group['key'] );
					foreach ( $field_holder as $field ) {
						$field_data               = array();
						$field_data['meta_key']   = $field['name'];
						$field_data['meta_value'] = $field['key'];
						$all_acf_fields[]         = $field_data;
					}
				}
			}
		}

		foreach ( $posts as $post ) {
			$metadata = has_meta( $post->ID );
			foreach ( $metadata as $key => $meta ) {
				if ( in_array( $meta['meta_key'], $meta_black_list ) || in_array( $meta['meta_key'], $keys ) ) {
					unset( $metadata[ $key ] );
				}
				$keys[] = $meta['meta_key'];
			}
			$arr = array_merge( $arr, $metadata );
		}
		$arr = array_merge( $arr, $all_acf_fields );
		// allow plugins to modify the fields to translate
		return apply_filters( 'lingotek_post_custom_fields', $arr );
	}

	/*
	 * tests whether a meta belongs to the Advanced Custom Fields plugin
	 *
	 * @since 0.2
	 *
	 * @return bool
	 */
	protected static function is_advanced_custom_fields_meta( $key, $value ) {
		return ( ( substr( $key, 0, strlen( '_' ) ) === '_' ) && ( substr( $value, 0, strlen( 'field_' ) ) === 'field_' ) ) ? true : false;
	}

	/*
	 * updates meta (custom) fields values in the lingotek_custom_fields option
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public static function get_updated_meta_values( $post_ID = null ) {
		$custom_fields_from_wpml     = self::get_custom_fields_from_wpml();
		$custom_fields_from_postmeta = self::get_custom_fields_from_wp_postmeta( $post_ID );
		$custom_fields_from_lingotek = get_option( 'lingotek_custom_fields', array() );
		if ( ! is_array( $custom_fields_from_lingotek ) ) {
			$custom_fields_from_lingotek = array();
		}
		$default_custom_fields = get_option( 'lingotek_default_custom_fields' ) ? get_option( 'lingotek_default_custom_fields' ) : 'ignore';
		$custom_fields         = array();
		$items                 = array();

		foreach ( $custom_fields_from_postmeta as $cf ) {
			// hide-copy means hide from user, and always copy to translations (Advanced Custom Fields plugin)
			if ( self::is_advanced_custom_fields_meta( $cf['meta_key'], $cf['meta_value'] ) ) {
				$custom_fields[ $cf['meta_key'] ] = 'hide-copy';
				continue;
			}

			// no lingotek setting
			if ( ! array_key_exists( $cf['meta_key'], $custom_fields_from_lingotek ) ) {
				// no lingotek setting, but there's a wpml setting
				if ( array_key_exists( $cf['meta_key'], $custom_fields_from_wpml ) ) {
					$custom_fields[ $cf['meta_key'] ] = $custom_fields_from_wpml[ $cf['meta_key'] ];
				}
				// no lingotek setting, no wpml setting, so save default setting of ignore
				else {
					$custom_fields[ $cf['meta_key'] ] = $default_custom_fields;
				}
			}
			// lingotek already has this field setting saved
			else {
				$custom_fields[ $cf['meta_key'] ] = $custom_fields_from_lingotek[ $cf['meta_key'] ];
			}
		}//end foreach

		if ( $post_ID ) {
			// get_option sometimes returns empty string for `lingotek_custom_fields`
			$custom_fields = is_array( $custom_fields_from_lingotek ) ? array_merge( $custom_fields_from_lingotek, $custom_fields ) : $custom_fields;
		}
		update_option( 'lingotek_custom_fields', $custom_fields, false );
	}

	/*
	 * returns cached meta (custom) fields values in the lingotek_custom_fields option
	 *
	 * @since 0.2
	 *
	 * @return array
	 */

	public static function get_cached_meta_values() {
		$custom_fields_from_lingotek = get_option( 'lingotek_custom_fields', array() );
		$items                       = array();

		if ( is_array( $custom_fields_from_lingotek ) ) {
			foreach ( $custom_fields_from_lingotek as $key => $setting ) {
				if ( $setting === 'hide-copy' ) {
					continue;
				}
				$arr     = array(
					'meta_key' => $key,
					'setting'  => $setting,
				);
				$items[] = $arr;
			}
		}
		return $items;
	}

	/*
	 * returns the content to translate
	 *
	 * @since 0.2
	 *
	 * @param object $post
	 * @return string json encoded content to translate
	 */
	public static function get_content( $post ) {
		$arr                = array();
		$fields             = self::get_content_type_fields( $post->post_type, $post->ID );
		$content_types      = get_option( 'lingotek_content_type' );
		$post_custom_fields = get_post_meta( $post->ID );
		$meta_black_list    = array( '_encloseme', '_edit_last', '_edit_lock', '_wp_trash_meta_status', '_wp_trash_meta_time' );
		foreach ( array_keys( $fields ) as $key ) {
			if ( 'metas' == $key ) {
				foreach ( $post_custom_fields as $meta => $value ) {
					// check for advanced custom fields meta expects a string
					// so if array, check first item
					$value = is_array( $value ) ? current( $value ) : $value;
					if ( self::is_advanced_custom_fields_meta( $meta, $value ) || in_array( $meta, $meta_black_list ) ) {
						continue;
					}
					// Get all metas in array format
					$meta_value = get_post_meta( $post->ID, $meta, true );
					if ( $meta_value && isset( $fields['metas'][ $meta ] ) ) {
						$arr['metas'][ $meta ] = $meta_value;
					}
				}
				// Check if acf is active and using these functions
				$acf_empty_string = '';
				if ( isset( $fields['metas'][ $acf_empty_string ] ) && function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) ) {
					$arr['metas'][ $acf_empty_string ] = get_post_meta( $post->ID, $acf_empty_string, true );
				}
			}
			// send slug for translation only if it has been modified
			elseif ( 'post_name' == $key && empty( $content_types[ $post->post_type ]['fields'][ $key ] ) ) {
				// Default slug created by WordPress.
				$default_slug = sanitize_title( $post->post_title );
				// if ($default_slug != $post->post_name)
				$arr['post'][ $key ] = $post->$key;
			} elseif ( empty( $content_types[ $post->post_type ]['fields'][ $key ] ) ) {
				$arr['post'][ $key ] = $post->$key;
			}
		}//end foreach
		return json_encode( $arr );
	}

	public static function is_valid_auto_upload_post_status( $post_status ) {
		$prefs          = Lingotek_Model::get_prefs();
		$valid_statuses = $prefs['auto_upload_post_statuses'];
		$valid          = isset( $valid_statuses[ $post_status ] ) && $valid_statuses[ $post_status ];
		return $valid;
	}

	/*
	 * requests translations to Lingotek TMS
	 *
	 * @since 0.1
	 */
	public function request_translations() {
		if ( isset( $this->source ) ) {
			$language = PLL()->model->post->get_language( (int) $this->source );
			$this->_request_translations( $language );
		}
	}

	/*
	 * create a translation downloaded from Lingotek TMS
	 *
	 * @since 0.1
	 * @uses Lingotek_Group::safe_translation_status_update() as the status can be automatically set by the TMS callback
	 *
	 * @param string $locale
	 */
	public function create_translation( $locale, $automatic = false, $callback_type = null ) {
		// Removes content sanitization so YouTube videos, links, etc don't get removed when inserting translations
		remove_filter( 'content_save_pre', 'wp_filter_post_kses' );
		remove_filter( 'content_filtered_save_pre', 'wp_filter_post_kses' );
		$client = new Lingotek_API();
		$status = $client->get_translation_status( $this->document_id, $locale );

		if ( $status === -1 ) {
			return;
		}

		$translation = $client->get_translation( $this->document_id, $locale, $this->source );
		// If the request failed.
		if ( ! $translation || $this->translation_not_ready( json_decode( $translation, true ) ) ) {
			return;
		}
		// wp_insert_post expects array
		$translation = json_decode( $translation, true );

		self::$creating_translation = true;
		// Need an array by default.
		$prefs = Lingotek_Model::get_prefs();

		$tr_post = $translation['post'];
		if ( isset( $tr_post['post_name'] ) ) {
			$tr_post['post_name'] = sanitize_title( $tr_post['post_name'] );
		}

		// source post
		$post = get_post( $this->source );
		// status
		$tr_post['post_status'] = ( $prefs['download_post_status'] === self::SAME_AS_SOURCE ) ? $post->post_status : $prefs['download_post_status'];

		// update existing translation
		if ( $tr_id = PLL()->model->post->get( $this->source, $locale ) ) {
			$tr_post['ID'] = $tr_id;

			// translate metas
			if ( isset( $translation['metas'] ) ) {
				self::copy_translated_metas( $translation['metas'], $tr_id );
			}

			// copy or ignore metas
			self::copy_or_ignore_metas( $post->ID, $tr_id );

			wp_update_post( $tr_post );
			if ( $status !== 100 ) {
				$this->safe_translation_status_update( $locale, 'interim' );
			} else {
				$this->safe_translation_status_update( $locale, 'current' );
			}
		}

		// create new translation
		elseif ( ( $this->translations[ $locale ] == 'ready' || $this->translations[ $locale ] == 'pending' ) || $automatic ) {
			$content_type_options = get_option( 'lingotek_content_type' );
			if ( ! isset( $content_type_options[ $post->post_type ]['fields']['post_name'] ) ) {
				// Forces the creation of a new default slug if not translated by Lingotek.
				unset( $post->post_name );
			}
			// Copy all untranslated fields from the original post.
			$tr_post = array_merge( (array) $post, $tr_post );
			// Will force the creation of a new post.
			$tr_post['ID'] = null;

			// translate parent
			$tr_post['post_parent'] = ( $post->post_parent && $tr_parent = $this->pllm->post->get_translation( $post->post_parent, $locale ) ) ? $tr_parent : 0;

			if ( 'attachment' == $post->post_type ) {
				$tr_id = wp_insert_attachment( $tr_post );
				add_post_meta( $tr_id, '_wp_attachment_metadata', get_post_meta( $this->source, '_wp_attachment_metadata', true ) );
				add_post_meta( $tr_id, '_wp_attached_file', get_post_meta( $this->source, '_wp_attached_file', true ) );
			} else {
				$tr_id = wp_insert_post( $tr_post );
			}

			if ( $tr_id ) {
				$tr_lang = $this->pllm->get_language( $locale );
				PLL()->model->post->set_language( $tr_id, $tr_lang );
				$this->safe_translation_status_update( $locale, 'current', array( $tr_lang->slug => $tr_id ) );
				if ( $status !== 100 ) {
					$this->safe_translation_status_update( $locale, 'interim' );
				}
				wp_set_object_terms( $tr_id, $this->term_id, 'post_translations' );

				// Copies categories and tags
				$GLOBALS['polylang']->sync->taxonomies->copy( $this->source, $tr_id, $tr_lang->slug );

				// assign terms and metas
				$GLOBALS['polylang']->sync->post_metas->copy( $this->source, $tr_id, $tr_lang->slug );

				// translate metas
				if ( isset( $translation['metas'] ) ) {
					self::copy_translated_metas( $translation['metas'], $tr_id );
				}

				// copy or ignore metas
				self::copy_or_ignore_metas( $post->ID, $tr_id );

				if ( class_exists( 'PLL_Share_Post_Slug', true ) && $content_type_options[ $post->post_type ]['fields']['post_name'] == 1 ) {
					wp_update_post(
						array(
							'ID'        => $tr_id,
							'post_name' => $post->post_name,
						)
					);
				}
			}//end if
		}

		self::$creating_translation = false;

		// Adds content sanitization back in after Lingotek saves the translation
		add_filter( 'content_save_pre', 'wp_filter_post_kses' );
		add_filter( 'content_filtered_save_pre', 'wp_filter_post_kses' );
	}

	/**
	 * TMS will return an associative array with empty fields if the translation is not ready.
	 *
	 * @param array $translation the array returned from TMS.
	 */
	private function translation_not_ready( $translation ) {
		$trimmed_title   = trim( $translation['post']['post_title'] );
		$trimmed_content = trim( $translation['post']['post_content'] );
		$trimmed_excerpt = trim( $translation['post']['post_excerpt'] );
		return empty( $trimmed_title ) &&
			empty( $trimmed_content ) &&
			empty( $trimmed_excerpt );
	}

	/*
	 * copies source meta strings to translations or deletes meta if set to ignore
	 *
	 * @since 1.0.9
	 */

	public static function copy_or_ignore_metas( $post_id, $tr_id ) {
		// copy or ignore metas
		$custom_fields      = get_option( 'lingotek_custom_fields', array() );
		$post_custom_fields = get_post_meta( $post_id );
		foreach ( $post_custom_fields as $key => $source_meta ) {
			// Set to blank string to ignore if no lingotek setting has been set.
			$setting = isset( $custom_fields[ $key ] ) ? $custom_fields[ $key ] : '';
			if ( 'copy' === $setting || 'hide-copy' === $setting ) {
				$source_meta = current( get_post_meta( $post_id, $key ) );
				update_post_meta( $tr_id, $key, $source_meta );
			} elseif ( 'ignore' === $setting ) {
				delete_post_meta( $tr_id, $key );
			}
		}
	}

	/*
	 * inserts translated meta values into translations
	 *
	 * @since 1.0.9
	 */
	protected static function copy_translated_metas( $translation_metas, $tr_id ) {
		if ( ! empty( $translation_metas ) ) {
			foreach ( $translation_metas as $key => $meta ) {
				update_post_meta( $tr_id, $key, $meta );
			}
		}
	}

	/*
	 * checks if content should be automatically uploaded
	 *
	 * @since 0.2
	 *
	 * @return bool
	 */
	public function is_automatic_upload() {
		 return 'automatic' == Lingotek_Model::get_profile_option( 'upload', get_post_type( $this->source ), $this->get_source_language(), false, $this->source ) && parent::is_automatic_upload();
	}

	/*
	 * get the the language of the source post
	 *
	 * @since 0.2
	 *
	 * @return object
	 */
	public function get_source_language() {
		 return PLL()->model->post->get_language( $this->source );
	}

	/*
	 * creates hash for lingotek_hash term
	 *
	 * @since 1.0.9
	 */
	protected static function save_hash_on_upload( $object_id ) {
		$post        = get_post( $object_id );
		$document_id = 'lingotek_hash_' . $post->ID;
		$new_hash    = md5( self::get_content( $post ) );

		wp_insert_term( $document_id, 'lingotek_hash', array( 'description' => $new_hash ) );
		wp_set_object_terms( $post->ID, $document_id, 'lingotek_hash' );
	}
}
