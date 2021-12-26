<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	// since WP 3.1.
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Lingotek Strings Table class.
 */
class Lingotek_Strings_Table extends WP_List_Table {
	/**
	 * Polylang model.
	 *
	 * @var object
	 */
	public $pllm;
	/**
	 * Lingotek model.
	 *
	 * @var object.
	 */
	public $lgtm;
	/**
	 * String actions.
	 *
	 * @var object
	 */
	public $string_actions;

	/**
	 * Constructor
	 *
	 * @since 0.2
	 * @param string $string_actions string actions.
	 */
	function __construct( $string_actions ) {
		parent::__construct(
			array(
				// Do not translate (used for css class).
				'plural' => 'lingotek-strings-translations',
				'ajax'   => false,
			)
		);
		$this->pllm           = $GLOBALS['polylang']->model;
		$this->lgtm           = $GLOBALS['wp_lingotek']->model;
		$this->string_actions = $string_actions;
	}

	/**
	 * Displays the item information in a column (default case)
	 *
	 * @since 0.2
	 *
	 * @param array  $item item.
	 * @param string $column_name column name.
	 */
	function column_default( $item, $column_name ) {
		// generic case (count).
		if ( false === strpos( $column_name, 'language_' ) ) {
			return $item[ $column_name ];
		}

		// language column.
		$language = $this->pllm->get_language( substr( $column_name, 9 ) );
		// FIXME.
		$document = $this->lgtm->get_group( 'string', $item['context'] );

		$workflow_id = Lingotek_Model::get_profile_option( 'workflow_id', 'string', $language );
		// TODO: put workflow_id here. It is currently not set up.
		$workflow = Lingotek_Workflow_Factory::get_workflow_instance( $workflow_id );
		$workflow->echo_strings_modal( $item['row'], $language->locale );

		$allowed_html = array(
			'a'   => array(
				'class' => array(),
				'title' => array(),
				'href'  => array(),
			),
			'img' => array(
				'src' => array(),
			),
			'div' => array(
				'title' => array(),
				'class' => array(),
			),
		);
		// post ready for upload.
		if ( $this->lgtm->can_upload( 'string', $item['context'] ) && $language->slug === $this->pllm->options['default_lang'] ) {
			echo wp_kses( $this->string_actions->upload_icon( $item['context'] ), $allowed_html );
		} //end if
		elseif ( isset( $document->source ) && $document->is_disabled_target( $language ) ) {
			echo '<div class="lingotek-color dashicons dashicons-no"></div>';
		} // source post is uploaded.
		elseif ( isset( $document->source ) && $document->source === $language->mo_id ) {
			echo wp_kses( 'importing' === $document->status ? Lingotek_Actions::importing_icon( $document ) : Lingotek_String_actions::uploaded_icon( $item['context'] ), $allowed_html );
		} // translations.
		elseif ( isset( $document->translations[ $language->locale ] ) || ( isset( $document->source ) && 'current' === $document->status ) && Lingotek::is_allowed_tms_locale( $language->lingotek_locale ) ) {
			echo wp_kses( Lingotek_Actions::translation_icon( $document, $language ), $allowed_html );
		} // no translation.
		else {
			echo '<div class="lingotek-color dashicons dashicons-no"></div>';
		}

		$language_only = 'language_' . $language->slug;
		$errors        = get_option( 'lingotek_log_errors' );
		if ( $language_only === $this->get_first_language_column() ) {
			if ( isset( $errors[ $item['context'] ] ) ) {
				$api_error = Lingotek_Actions::retrieve_api_error( $errors[ $item['context'] ] );
				echo Lingotek_Actions::display_error_icon( 'error', $api_error );
			}
		}
	}

	/**
	 * Displays the checkbox in first column
	 *
	 * @since 0.2
	 *
	 * @param array $item item.
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf( '<input id="string-select-%s" type="checkbox" name="strings[]" value="%d" />', esc_attr( $item['row'] ), esc_attr( $item['row'] ) );
	}

	/**
	 * Displays the item information in the column 'group'
	 * displays the row actions links
	 *
	 * @since 0.2
	 *
	 * @param object $item item.
	 * @return string
	 */
	function column_context( $item ) {
		return $item['context'] . $this->row_actions( $this->string_actions->row_actions( $item['context'] ) );
	}

	/**
	 * Gets the list of columns
	 *
	 * @since 0.2
	 *
	 * @return array the list of column titles
	 */
	function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />',
			'context' => __( 'Group', 'lingotek-translation' ),
			'count'   => __( 'Count', 'lingotek-translation' ),
		);

		foreach ( $GLOBALS['polylang']->model->get_languages_list() as $lang ) {
			if ( ! $lang->flag ) {
				$columns[ 'language_' . $lang->slug ] = $lang->slug;
			} else {
				$columns[ 'language_' . $lang->slug ] = $lang->flag;
			}
		}

		return $columns;
	}

	/**
	 * Gets the list of sortable columns
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	function get_sortable_columns() {
		return array(
			'context' => array( 'context', false ),
			'count'   => array( 'count', false ),
		);
	}

	/**
	 * Prepares the list of items ofr displaying
	 *
	 * @since 0.2
	 *
	 * @param array $data data.
	 */
	function prepare_items( $data = array() ) {
		$per_page              = $this->get_items_per_page( 'lingotek_strings_per_page' );
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		/**
		 * Custom sorting comparator.
		 *
		 * @param  array $a array of strings.
		 * @param  array $b array of strings.
		 * @return int sort direction.
		 */
		function usort_reorder( $a, $b ) {
			$order   = filter_input( INPUT_GET, 'order' );
			$orderby = filter_input( INPUT_GET, 'orderby' );
			// Determine sort order.
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			// Send final sort direction to usort.
			return ( empty( $order ) || 'asc' === $order ) ? $result : -$result;
		};

		// no sort by default.
		if ( ! empty( $orderby ) ) {
			usort( $data, 'usort_reorder' );
		}

		$total_items = count( $data );
		$this->items = array_slice( $data, ( $this->get_pagenum() - 1 ) * $per_page, $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Get the list of possible bulk actions
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	function get_bulk_actions() {
		foreach ( Lingotek_String_actions::$actions as $action => $strings ) {
			$arr[ 'bulk-lingotek-' . $action ] = $strings['action'];
		}
		return $arr;
	}

	/**
	 * Returns the first language column
	 *
	 * @since 1.2
	 *
	 * @return string first language column name
	 */
	protected function get_first_language_column() {
		foreach ( $this->pllm->get_languages_list() as $language ) {
			$columns[] = 'language_' . $language->slug;
		}

		return empty( $columns ) ? '' : reset( $columns );
	}
}
