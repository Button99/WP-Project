<?php

/**
 * Chip base class for representing a locale (source or target).
 *
 * @since 1.5.0
 */
abstract class Lingotek_Chip_Base {

	/**
	 * Id of the document.
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * The document.
	 *
	 * @var object
	 */
	protected $document;

	/**
	 * Construct the chip.
	 *
	 * @param int    $id Id of the document.
	 * @param object $document The document.
	 */
	public function __construct( $id, $document ) {
		$this->id       = $id;
		$this->document = $document;
	}

	/**
	 * Get the url for an action
	 *
	 * @param object $language
	 * @param string $status
	 *
	 * @return Lingotek_Action_Url|null
	 *
	 * @since 1.5.0
	 */
	abstract public function get_action_url( $language, $status );

	/**
	 * Get the secondary urls for an action menu.
	 *
	 * @param object $language
	 * @param string $status
	 *
	 * @return Lingotek_Action_Url[]
	 *
	 * @since 1.5.0
	 */
	abstract public function get_secondary_action_urls( $language, $status );

	/**
	 * Renders a chip with their primary action and a secondary actions menu.
	 *
	 * @param object $language
	 * @param string $status
	 *
	 * @return string
	 *
	 * @since 1.5.0
	 */
	public function render( $language, $status ) {
		$primary_action_url    = $this->get_action_url( $language, $status );
		$secondary_action_urls = $this->get_secondary_action_urls( $language, $status );

		$output_html = sprintf(
			'<div class="lingotek-%s-dropdown">' .
			'<a href="%s" class="language-icon %s-%s" title="%s">%s</a>',
			$this->type,
			null !== $primary_action_url ? $primary_action_url->getUri() : '#',
			$this->type,
			$status,
			null !== $primary_action_url ? $primary_action_url->getTitle() : '',
			$language->lingotek_locale
		);

		$secondary_actions_code = '';
		foreach ( $secondary_action_urls as $source_secondary_action ) {
			$secondary_actions_code .= $source_secondary_action->render();
		}

		if ( count( $secondary_action_urls ) > 0 ) {
			$output_html .= sprintf(
				'
			      <button class="language-icon lingotek-%s-dropdown-toggle %s-%s"><span class="visually-hidden">Toggle Actions</span></button>
			      <ul class="lingotek-%s-actions">
			         %s
		          </ul>',
				$this->type,
				$this->type,
				$status,
				$this->type,
				$secondary_actions_code
			);
		}

		$output_html .= '</div>';

		return $output_html;
	}

	public function get_canonical_url( $language ) {
		if ( is_object( $this->document ) ) {
			if ( post_type_exists( $this->document->type ) ) {
				$id = PLL()->model->post->get( $this->document->source, $language->locale );
				if ( $id ) {
					return get_permalink( $id );
				}
			} elseif ( taxonomy_exists( $this->document->type ) ) {
				$id = $this->document->pllm->get_term( $this->document->source, $language->locale );
				if ( $id ) {
					return get_term_link( $id, $this->document->type );
				}
			}
		}
		return false;
	}

}
