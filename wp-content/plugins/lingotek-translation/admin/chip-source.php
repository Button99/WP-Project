<?php

/**
 * Chip class for representing a source locale and its actions.
 *
 * @since 1.5.0
 */
class Lingotek_Chip_Source extends Lingotek_Chip_Base {

	/**
	 * The type of the chip.
	 *
	 * @var string
	 */
	protected $type = 'source';

	/**
	 * {@inheritdoc}
	 */
	public function get_action_url( $language, $status ) {
		$url          = null;
		$uri          = null;
		$title        = null;
		$post_actions = $GLOBALS['wp_lingotek']->post_actions;
		$document_id  = $this->document ? $this->document->document_id : false;
		if ( $document_id ) {
			if ( in_array( $status, array( 'request', 'deleted', 'untracked' ), true ) ) {
				$uri   = $post_actions->upload_url( $this->id );
				$title = __( 'Upload', 'lingotek-translation' );
			}
			if ( 'deleted' === $status ) {
				$uri   = $post_actions->upload_url( $this->id );
				$title = sprintf( __( 'This document has been deleted. Reupload the source for translation.', 'lingotek-translation' ) );
			}
			if ( 'archived' === $status ) {
				$uri   = $post_actions->upload_url( $this->id );
				$title = sprintf( __( 'This document has been archived. Reupload the source for translation.', 'lingotek-translation' ) );
			}
			if ( 'importing' === $status ) {
				$uri   = $post_actions->check_source_url( $this->id, $this->document, $language->locale );
				$title = __( 'Check source status', 'lingotek-translation' );
			}
			if ( in_array( $status, array( 'current', 'edited', 'failed', 'error' ), true ) ) {
				$uri   = $post_actions->upload_url( $this->id );
				$title = __( 'Re-Upload', 'lingotek-translation' );
			}
			if ( 'disabled' === $status ) {
				$url = null;
			}
		} else {
			if ( 'deleted' === $status ) {
				$uri   = $post_actions->upload_url( $this->id );
				$title = sprintf( __( 'This document has been deleted. Reupload the source for translation.', 'lingotek-translation' ) );
			} elseif ( 'archived' === $status ) {
				$uri   = $post_actions->upload_url( $this->id );
				$title = sprintf( __( 'This document has been archived. Reupload the source for translation.', 'lingotek-translation' ) );
			} else {
				$uri   = $post_actions->upload_url( $this->id );
				$title = __( 'Upload', 'lingotek-translation' );
			}
		}//end if
		if ( null !== $uri && null !== $title ) {
			$url = new Lingotek_Action_Url( $uri, $title );
		}
		return $url;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_secondary_action_urls( $language, $status ) {
		$urls         = array();
		$post_actions = $GLOBALS['wp_lingotek']->post_actions;
		$document_id  = $this->document ? $this->document->document_id : false;

		$canonical_url = $this->get_canonical_url( $language );
		if ( $canonical_url ) {
			$urls[] = new Lingotek_Action_Url(
				$canonical_url,
				'source' === $this->type ? __( 'View', 'lingotek-translation' ) : __( 'View translation', 'lingotek-translation' )
			);
		}

		if ( $document_id ) {
			if ( in_array( $status, array( 'request', 'deleted', 'untracked', 'archived', 'cancelled' ), true ) ) {
				$urls[] = new Lingotek_Action_Url(
					$post_actions->upload_url( $this->id ),
					__( 'Upload', 'lingotek-translation' )
				);
			}
			if ( 'importing' === $status ) {
				$urls[] = new Lingotek_Action_Url(
					$post_actions->check_source_url( $this->id, $this->document, $language->locale ),
					__( 'Check source status', 'lingotek-translation' )
				);
			}
			if ( in_array( $status, array( 'current', 'edited', 'failed', 'error' ), true ) ) {
				$urls[] = new Lingotek_Action_Url(
					$post_actions->upload_url( $this->id ),
					__( 'Re-Upload', 'lingotek-translation' )
				);
			}
			if ( 'disabled' === $status ) {
				$url = null;
			}
		} else {
			$urls[] = new Lingotek_Action_Url(
				$post_actions->upload_url( $this->id ),
				__( 'Upload', 'lingotek-translation' )
			);
		}//end if
		return $urls;
	}

}
