<?php

/**
 * Modifies Polylang filters
 * Manages automatic upload
 *
 * @since 0.2
 */
class Lingotek_Filters_Term extends PLL_Admin_Filters_Term {
	/**
	 * Lingotek model.
	 *
	 * @var object
	 */
	public $lgtm;
	/**
	 * Old term.
	 *
	 * @var string.
	 */
	protected $old_term;

	/**
	 * Constructor
	 *
	 * @since 0.2
	 * @param object $polylang polylang model.
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		$this->lgtm = &$GLOBALS['wp_lingotek']->model;

		add_action( 'edit_terms', array( &$this, 'save_old_term' ), 10, 2 );
		add_action( 'edited_term', array( &$this, 'edited_term' ), 10, 3 );
	}

	/**
	 * Controls whether to display the language metabox or not
	 *
	 * @since 0.2
	 * @param object $tag tag object.
	 */
	public function edit_term_form( $tag ) {
		if ( $this->model->is_translated_taxonomy( $tag->taxonomy ) ) {
			$document = $this->lgtm->get_group( 'term', $tag->term_id );
			if ( empty( $document->source ) ) {
				parent::edit_term_form( $tag );
			}
		}
	}

	/**
	 * Uploads a term when saved for the first time
	 *
	 * @since 0.2

	 * @param int    $term_id id.
	 * @param int    $tt_id term taxononomy id.
	 * @param string $taxonomy taxonomy.
	 */
	public function save_term( $term_id, $tt_id, $taxonomy ) {
		$document = $this->lgtm->get_group( 'term', $term_id );
		if ( $document ) {
			$document->pre_save_terms( $term_id, $taxonomy, PLL()->model->term->get_language( $term_id ) );
		}

		if ( ! $this->model->is_translated_taxonomy( $taxonomy ) ) {
			return;
		}

		$import_get  = filter_input( INPUT_GET, 'import' );
		$import_post = filter_input( INPUT_POST, 'import' );
		if ( empty( $import ) && empty( $import_post ) ) {
			parent::save_term( $term_id, $tt_id, $taxonomy );

			if ( 'automatic' === Lingotek_Model::get_profile_option( 'upload', $taxonomy, PLL()->model->term->get_language( $term_id ) ) && $this->lgtm->can_upload( 'term', $term_id ) ) {
				$this->lgtm->upload_term( $term_id, $taxonomy );
			} {
			}
		}
	}

	/**
	 * Saves the md5sum of a term before it is edited
	 *
	 * @since 0.2
	 *
	 * @param int    $term_id term id.
	 * @param string $taxonomy taxonomy.
	 */
	public function save_old_term( $term_id, $taxonomy ) {
		if ( pll_is_translated_taxonomy( $taxonomy ) ) {
			$this->old_term = md5( Lingotek_Group_Term::get_content( get_term( $term_id, $taxonomy ) ) );
		}
	}

	/**
	 * Marks the term as edited if needed
	 *
	 * @since 0.2
	 *
	 * @param int    $term_id term id.
	 * @param int    $tt_id not used.
	 * @param string $taxonomy taxonomy.
	 */
	public function edited_term( $term_id, $tt_id, $taxonomy ) {
		if ( pll_is_translated_taxonomy( $taxonomy ) ) {
			$document = $this->lgtm->get_group( 'term', $term_id );

			if ( $document && $term_id === $document->source && md5( Lingotek_Group_Term::get_content( get_term( $term_id, $taxonomy ) ) ) !== $this->old_term ) {
				$document->source_edited();

				if ( $document->is_automatic_upload() ) {
					$this->lgtm->upload_term( $term_id, $taxonomy );
				}
			}
		}
	}

	/**
	 * Get translations ids to sync for delete
	 * since we can't sync all translations as we get conflicts when attempting to act two times on the same
	 *
	 * @since 0.2
	 *
	 * @param int $term_id term id.
	 * @return array
	 */
	protected function get_translations_to_sync( $term_id ) {
		// don't synchronize disassociated terms.
		$group = $this->lgtm->get_group( 'term', $term_id );
		if ( empty( $group->source ) ) {
			return array();
		}

		$delete_tags      = null;
		$delete_tags_get  = filter_input( INPUT_GET, 'delete_tags' );
		$delete_tags_post = filter_input( INPUT_POST, 'delete_tags' );
		if ( ! empty( $delete_tags_get ) ) {
			$delete_tags = filter_input( INPUT_GET, 'delete_tags' );
		} elseif ( ! empty( $delete_tags_post ) ) {
			$delete_tags = filter_input( INPUT_POST, 'delete_tags' );
		}

		if ( isset( $delete_tags ) && is_array( $delete_tags ) ) {
			$term_ids = array_map( 'intval', $delete_tags );
		}

		$term_ids[] = $term_id;
		return array_diff( PLL()->model->term->get_translations( $term_id ), $term_ids );
	}

	/**
	 * Deletes the Lingotek document when a source document is deleted
	 *
	 * @since 0.2
	 *
	 * @param int $term_id term id.
	 */
	public function delete_term( $term_id ) {
		$taxonomy = substr( current_filter(), 7 );
		foreach ( $this->get_translations_to_sync( $term_id ) as $tr_id ) {
			// Forces deletion for the translations which are not already in the list.
			wp_delete_term( $tr_id, $taxonomy );
		}
		$this->lgtm->delete_term( $term_id );
	}

	public function cancel_term( $term_id ) {
		$taxonomy = substr( current_filter(), 7 );
		foreach ( $this->get_translations_to_sync( $term_id ) as $tr_id ) {
			wp_delete_term( $tr_id, $taxonomy );
		}
		$this->lgtm->cancel_term( $term_id );
	}
}
