<?php

/*
 * Translations groups for categories, tags and custom taxonomies
 *
 * @since 0.2
 */
class Lingotek_Group_Term extends Lingotek_Group {

	/*
	 * set a translation term for an object
	 *
	 * @since 0.2
	 *
	 * @param int $object_id term id
	 * @param string $tax taxonomy name
	 * @param object $language
	 * @param string $document_id translation term name (Lingotek document id)
	 */
	public static function create( $object_id, $tax, $language, $document_id ) {
		$data = array(
			'lingotek'      => array(
				'type'         => $tax,
				'source'       => $object_id,
				'status'       => 'importing',
				'translations' => array(),
			),
			// for Polylang
			$language->slug => $object_id,
		);

		self::_create( $object_id, $document_id, $data, 'term_translations' );
	}

	/*
	 * returns content type fields
	 *
	 * @since 0.2
	 *
	 * @param string $taxonomy
	 * @return array
	 */
	public static function get_content_type_fields( $taxonomy ) {
		$arr = array(
			'name' => __( 'Name', 'lingotek-translation' ),
			'args' => array(
				'slug'        => __( 'Slug', 'lingotek-translation' ),
				'description' => __( 'Description', 'lingotek-translation' ),
			),
		);

		return apply_filters( 'lingotek_term_content_type_fields', $arr, $taxonomy );
	}

	/*
	 * returns the content to translate
	 *
	 * @since 0.2
	 *
	 * @param object $term
	 * @return string json encoded content to translate
	 */
	public static function get_content( $term ) {
		$fields        = self::get_content_type_fields( $term->taxonomy );
		$content_types = get_option( 'lingotek_content_type' );

		foreach ( array_keys( $fields ) as $key ) {
			if ( 'args' == $key ) {
				foreach ( array_keys( $fields['args'] ) as $arg ) {
					if ( empty( $content_types[ $term->taxonomy ]['fields']['args'][ $arg ] ) ) {
						$arr['args'][ $arg ] = $term->$arg;
					}
				}
			} elseif ( empty( $content_types[ $term->taxonomy ]['fields'][ $key ] ) ) {
				$arr[ $key ] = $term->$key;
			}
		}

		return json_encode( $arr );
	}

	/*
	 * requests translations to Lingotek TMS
	 *
	 * @since 0.2
	 */
	public function request_translations() {
		if ( isset( $this->source ) ) {
			$language = PLL()->model->term->get_language( (int) $this->source );
			$this->_request_translations( $language );
		}
	}

	/*
	 * create a translation downloaded from Lingotek TMS
	 *
	 * @since 0.2
	 * @uses Lingotek_Group::safe_translation_status_update() as the status can be automatically set by the TMS callback
	 *
	 * @param string $locale
	 */
	public function create_translation( $locale ) {
		$client = new Lingotek_API();

		if ( false === ( $translation = $client->get_translation( $this->document_id, $locale, $this->source ) ) ) {
			return;
		}

		self::$creating_translation = true;

		// wp_insert_post expects array
		$translation = json_decode( $translation, true );
		$args        = $translation['args'];

		// update existing translation
		if ( $tr_id = PLL()->model->term->get( $this->source, $locale ) ) {
			$args['name'] = $translation['name'];
			wp_update_term( $tr_id, $this->type, $args );

			$this->safe_translation_status_update( $locale, 'current' );
		}

		// create new translation
		else {
			$content_type_options = get_option( 'lingotek_content_type' );
			$tr_lang              = $this->pllm->get_language( $locale );

			// translate parent
			$term           = get_term( $this->source, $this->type );
			$args['parent'] = ( $term->parent && $tr_parent = PLL()->model->term->get_translation( $term->parent, $locale ) ) ? $tr_parent : 0;

			if ( class_exists( 'PLL_Share_Term_Slug', true ) && isset( $content_type_options[ $this->type ]['fields']['args']['slug'] ) ) {
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
				$_POST['term_lang_choice'] = $tr_lang->slug;
				$args['slug']              = $term->slug;
			} else {
				// attempt to get a unique slug in case it already exists in another language
				if ( isset( $args['slug'] ) && term_exists( $args['slug'] ) ) {
					$args['slug'] .= '-' . $tr_lang->slug;
				}
			}

			$tr = wp_insert_term( $translation['name'], $this->type, $args );

			if ( ! is_wp_error( $tr ) ) {
				PLL()->model->term->set_language( $tr['term_id'], $tr_lang );
				$this->safe_translation_status_update( $locale, 'current', array( $tr_lang->slug => $tr['term_id'] ) );
				wp_set_object_terms( $tr['term_id'], $this->term_id, 'term_translations' );
			}
		}

		self::$creating_translation = false;
	}

	/*
	 * checks if content should be automatically uploaded
	 *
	 * @since 0.2
	 *
	 * @return bool
	 */
	public function is_automatic_upload() {
		return 'automatic' == Lingotek_Model::get_profile_option( 'upload', $this->type, $this->get_source_language() ) && parent::is_automatic_upload();
	}

	/*
	 * get the the language of the source term
	 *
	 * @since 0.2
	 *
	 * @return object
	 */
	public function get_source_language() {
		return PLL()->model->term->get_language( $this->source );
	}
}
