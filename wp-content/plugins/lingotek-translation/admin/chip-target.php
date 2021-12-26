<?php

/**
 * Chip class for representing a target locale and its actions.
 *
 * @since 1.5.0
 */
class Lingotek_Chip_Target extends Lingotek_Chip_Base {

	/**
	 * The type of the chip.
	 *
	 * @var string
	 */
	protected $type = 'target';

	/**
	 * {@inheritdoc}
	 */
	public function get_action_url( $language, $status ) {
		$url          = null;
		$uri          = null;
		$title        = null;
		$post_actions = $GLOBALS['wp_lingotek']->post_actions;
		$document_id  = $this->document->document_id;
		if ( $document_id ) {
			if ( in_array( $status, array( 'request', 'untracked' ), true ) ) {
				$uri = $post_actions->request_translation_url( $this->id, $this->document, $language->locale );
				/* translators: %s: The lingotek locale. */
				$title = sprintf( __( 'Request translation to %s', 'lingotek-translation' ), $language->lingotek_locale );
			}
			if ( 'deleted' === $status ) {
				$uri           = $post_actions->request_translation_url( $this->id, $this->document, $language->locale );
				$canonical_url = $this->get_canonical_url( $language );
				if ( $canonical_url ) {
					$title = sprintf( __( 'This target has been deleted and the translation does exist.', 'lingotek-translation' ) );
				} else {
					$title = sprintf( __( 'This target has been deleted and the translation does not exist.', 'lingotek-translation' ) );
				}
			}
			if ( 'archived' === $status ) {
				$uri           = $post_actions->request_translation_url( $this->id, $this->document, $language->locale );
				$canonical_url = $this->get_canonical_url( $language );
				if ( $canonical_url ) {
					$title = sprintf( __( 'This target has been archived and the translation does exist.', 'lingotek-translation' ) );
				} else {
					$title = sprintf( __( 'This target has been archived and the translation does not exist.', 'lingotek-translation' ) );
				}
			}
			if ( 'pending' === $status ) {
				$uri = $post_actions->check_translation_url( $this->id, $this->document, $language->locale );
				/* translators: %s: The lingotek locale. */
				$title = sprintf( __( 'Check translation status for %s', 'lingotek-translation' ), $language->lingotek_locale );
			}
			if ( in_array( $status, array( 'ready', 'error', 'failed' ), true ) ) {
				$uri = $post_actions->download_translation_url( $this->id, $this->document, $language->locale );
				/* translators: %s: The lingotek locale. */
				$title = sprintf( __( 'Download translation for %s', 'lingotek-translation' ), $language->lingotek_locale );
			}
			if ( in_array( $status, array( 'current', 'interim', 'intermediate', 'edited' ), true ) ) {
				$uri   = $post_actions->workbench_url( $this->id, $this->document, $language->lingotek_locale );
				$title = __( 'Open in Lingotek workbench', 'lingotek-translation' );
			}
			if ( 'disabled' === $status ) {
				$url = null;
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
		$document_id  = $this->document->document_id;

		$canonical_url = $this->get_canonical_url( $language );
		if ( $canonical_url ) {
			$urls[] = new Lingotek_Action_Url(
				$canonical_url,
				'source' === $this->type ? __( 'View', 'lingotek-translation' ) : __( 'View translation', 'lingotek-translation' )
			);
		}

		if ( $document_id ) {
			if ( in_array( $status, array( 'request', 'deleted', 'untracked' ), true ) ) {
				$urls[] = new Lingotek_Action_Url(
					$post_actions->request_translation_url( $this->id, $this->document, $language->locale ),
					sprintf( __( 'Request translation', 'lingotek-translation' ), $language->locale )
				);
			}
			if ( 'pending' === $status ) {
				$urls[] = new Lingotek_Action_Url(
					$post_actions->check_translation_url( $this->id, $this->document, $language->locale ),
					sprintf( __( 'Check translation status', 'lingotek-translation' ), $language->locale )
				);
				$urls[] = new Lingotek_Action_Url(
					$post_actions->cancel_translation_url( $this->id, $this->document, $language->locale ),
					sprintf( __( 'Cancel translation', 'lingotek-translation' ), $language->locale )
				);
			}
			if ( in_array( $status, array( 'ready'), true ) ) {
				$urls[] = new Lingotek_Action_Url(
					$post_actions->download_translation_url( $this->id, $this->document, $language->locale ),
					sprintf( __( 'Download translation', 'lingotek-translation' ), $language->locale )
				);
			}
			if ( in_array( $status, array('error', 'failed' ), true ) ) {
				$urls[] = new Lingotek_Action_Url(
					$post_actions->download_translation_url( $this->id, $this->document, $language->locale ),
					sprintf( __( 'Download translation', 'lingotek-translation' ), $language->locale )
				);
				$urls[] = new Lingotek_Action_Url(
					$post_actions->cancel_translation_url( $this->id, $this->document, $language->locale ),
					sprintf( __( 'Cancel translation', 'lingotek-translation' ), $language->locale )
				);
			}
			// I'm not sure if the status is interim or intermediate, so we consider both for now.
			if ( in_array( $status, array( 'current', 'interim', 'intermediate' ), true ) ) {
				$urls[] = new Lingotek_Action_Url(
					$post_actions->download_translation_url( $this->id, $this->document, $language->locale ),
					sprintf( __( 'Re-Download translation', 'lingotek-translation' ), $language->locale )
				);
			}
			// I'm not sure if the status is error or failed, so we consider both for now.
			if ( in_array( $status, array( 'pending', 'ready', 'error', 'failed', 'current', 'interim', 'intermediate', 'edited' ), true ) ) {
				$urls[] = new Lingotek_Action_Url(
					$post_actions->workbench_url( $this->id, $this->document, $language->lingotek_locale ),
					__( 'Open in Lingotek workbench', 'lingotek-translation' )
				);
			}
			if ( 'disabled' === $status ) {
				$url = null;
			}
		}//end if
		return $urls;
	}

}
