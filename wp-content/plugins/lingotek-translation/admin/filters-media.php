<?php

/**
 * Manages automatic upload
 *
 * @since 0.2
 */
class Lingotek_Filters_Media extends PLL_Admin_Filters_Media {
	/**
	 * Lingotek model.
	 *
	 * @var object
	 */
	public $lgtm;

	/**
	 * Constructor
	 *
	 * @since 0.2
	 * @param model $polylang polylang.
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		$this->lgtm = &$GLOBALS['wp_lingotek']->model;

		add_action( 'edit_attachment', array( &$this, 'edit_attachment' ) );
		// After Polylang.
		add_action( 'add_attachment', array( &$this, 'add_attachment' ), 11 );
	}

	/**
	 * Marks the attachment as edited if needed
	 *
	 * @since 0.2
	 *
	 * @param int $post_id post id.
	 */
	public function edit_attachment( $post_id ) {
		$document = $this->lgtm->get_group( 'post', $post_id );

		// FIXME how to check if the attachment is really modified?
		if ( $document && $post_id === $document->source ) {
			$document->source_edited();

			if ( $document->is_automatic_upload() ) {
				$this->lgtm->upload_post( $post_id );
			}
		}
	}

	/**
	 * Uploads an attachment when saved for the first time
	 *
	 * @since 0.2

	 * @param int $post_id post id.
	 */
	public function add_attachment( $post_id ) {
		if ( $this->model->is_translated_post_type( 'attachment' ) && 'automatic' === Lingotek_Model::get_profile_option( 'upload', 'attachment', PLL()->model->post->get_language( $post_id ) ) && $this->lgtm->can_upload( 'post', $post_id ) ) {
			$this->lgtm->upload_post( $post_id );
		}
	}
}
