<?php

/*
 * A class to manage utilities
 *
 * @since 0.2
 */
class Lingotek_Utilities {
	// Polylang and Lingotek models.
	public $pllm, $lgtm;

	/*
	 * Constructor
	 *
	 * @since 0.2
	 */
	public function __construct() {
		$this->pllm = $GLOBALS['polylang']->model;
		$this->lgtm = $GLOBALS['wp_lingotek']->model;

		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_lingotek_progress_disassociate', array( &$this, 'ajax_utility_disassociate' ) );
		add_action( 'wp_ajax_lingotek_progress_disassociate_and_delete', array( &$this, 'ajax_utility_disassociate' ) );
		add_action( 'wp_ajax_lingotek_progress_cancel', array( &$this, 'ajax_utility_cancel' ) );
	}

	/*
	 * run an array of utilities
	 *
	 * @since 0.2
	 *
	 * @param array $utilities array of utility names to run
	 * @return array
	 */
	public function run_utilities( $utilities ) {
		$results = array();
		if ( ! empty( $utilities ) && is_array( $utilities ) ) {
			foreach ( $utilities as $utility_name ) {
				$results[] = $this->run_utility( $utility_name );
			}
		}
		return $results;
	}

	/*
	 * run a specified utility by name
	 *
	 * @since 0.2
	 *
	 * @param string $utilty_name
	 * @return boolean $result
	 */
	public function run_utility( $utility_name ) {
		$result = 0;
		switch ( $utility_name ) {
			case 'set_default_language':
			case 'utility_set_default_language':
				$result = $this->utility_set_default_language();
				break;
			default:
				break;
		}
		return $result;
	}

	// Utilities

	/*
	 * fills existing posts & terms with default language
	 *
	 * @since 0.2
	 */
	public function utility_set_default_language() {
		if ( $nolang = $this->pllm->get_objects_with_no_lang() ) {
			if ( ! empty( $nolang['posts'] ) ) {
				$this->pllm->set_language_in_mass( 'post', $nolang['posts'], $this->pllm->options['default_lang'] );
			}
			if ( ! empty( $nolang['terms'] ) ) {
				$this->pllm->set_language_in_mass( 'term', $nolang['terms'], $this->pllm->options['default_lang'] );
			}
		}
		add_settings_error( 'lingotek_utilities', 'utilities', __( 'The language update utility ran successfully.', 'lingotek-translation' ), 'updated' );
		return 0;
	}

	/*
	 * get all (associated) Lingotek document ids
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public static function get_all_document_ids() {
		$terms = get_terms( array( 'post_translations', 'term_translations' ) );
		foreach ( $terms as $term ) {
			$desc_arr = unserialize( $term->description );
			if ( ! empty( $desc_arr['lingotek'] ) ) {
				$ids[] = $term->slug;
			}
		}

		return empty( $ids ) ? array() : $ids;
	}

	/*
	 * outputs javascript data for progress.js
	 *
	 * @since 0.2
	 */
	public function admin_enqueue_scripts() {
		if ( ! empty( $_POST['utility_disassociate'] ) ) {
			$ids = self::get_all_document_ids();
			if ( ! empty( $ids ) ) {
				wp_localize_script(
					'lingotek_progress',
					'lingotek_data',
					array(
						'action'   => 'disassociate' . ( 'on' == $_POST['utility_delete_documents'] ? '_and_delete' : '' ),
						'taxonomy' => '',
						'sendback' => wp_get_referer(),
						// Lingotek document ids.
						'ids'      => $ids,
						'warning'  => '',
						'nonce'    => wp_create_nonce( 'lingotek_progress' ),
					)
				);
			}
		} elseif ( ! empty( $_POST['utility_cancel'] ) ) {
			$ids = self::get_all_document_ids();
			if ( ! empty( $ids ) ) {
				wp_localize_script(
					'lingotek_progress',
					'lingotek_data',
					array(
						'action'   => 'cancel' . ( $_POST['utility_cancel_documents'] ),
						'taxonomy' => '',
						'sendback' => wp_get_referer(),
						// Lingotek document ids.
						'ids'      => $ids,
						'warning'  => '',
						'nonce'    => wp_create_nonce( 'lingotek_progress' ),
					)
				);
			}
		}//end if
	}

	/*
	 * ajax response to disassociate documents and showing progress
	 *
	 * @since 0.2
	 */
	public function ajax_utility_disassociate() {
		check_ajax_referer( 'lingotek_progress', '_lingotek_nonce' );
		if ( $group = $this->lgtm->get_group_by_id( $_POST['id'] ) ) {
			$group->delete( 'lingotek_progress_disassociate_and_delete' == $_POST['action'] );
		}
		die();
	}

	public function ajax_utility_cancel() {
		check_ajax_referer( 'lingotek_progress', '_lingotek_nonce' );
		if ( $group = $this->lgtm->get_group_by_id( $_POST['id'] ) ) {
			$group->cancel( 'lingotek_progress_cancel' == $_POST['action'] );
		}
		die();
	}
}
