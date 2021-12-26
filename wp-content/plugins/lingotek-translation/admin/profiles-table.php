<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	// since WP 3.1.
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Lingotek Profiles Table Class.
 */
class Lingotek_Profiles_Table extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct(
			array(
				// do not translate (used for css class).
				'plural' => 'lingotek-profiles',
				'ajax'   => false,
			)
		);
	}

	/**
	 * Displays the item information in a column (default case)
	 *
	 * @since 0.2
	 *
	 * @param array  $item item.
	 * @param string $column_name column.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
	}

	/**
	 * Displays the edit link in actions column
	 *
	 * @since 0.2
	 *
	 * @param array $item item.
	 * @return string
	 */
	protected function column_usage( $item ) {
		return empty( $item['usage'] ) ?
			__( 'No content types', 'lingotek-translation' ) :
			/* translators: %d: Number of content types. */
			sprintf( _n( '%d content type', '%d content types', $item['usage'], 'lingotek-translation' ), number_format_i18n( $item['usage'] ) );
	}

	/**
	 * Displays the edit link in actions column
	 *
	 * @since 0.2
	 *
	 * @param array $item item.
	 * @return string
	 */
	public function column_actions( $item ) {
		$actions = array();

		if ( 'disabled' !== $item['profile'] ) {
			$actions[] = sprintf(
				'<a href=%s>%s</a>',
				esc_url( admin_url( 'admin.php?page=lingotek-translation_manage&sm=edit-profile&profile=' . $item['profile'] ) ),
				__( 'Edit', 'lingotek-translation' )
			);
		}

		if ( ! in_array( $item['profile'], array( 'automatic', 'manual', 'disabled' ), true ) && empty( $item['usage'] ) ) {
			$actions[] = sprintf(
				'<a href="%s" onclick = "return confirm(\'%s\');">%s</a>',
				esc_url( wp_nonce_url( 'admin.php?page=lingotek-translation_manage&sm=profiles&lingotek_action=delete-profile&noheader=true&profile=' . $item['profile'], 'delete-profile' ) ),
				__( 'You are about to permanently delete this profile. Are you sure?', 'lingotek-translation' ),
				__( 'Delete', 'lingotek-translation' )
			);
		}

		return implode( ' | ', $actions );
	}

	/**
	 * Gets the list of columns
	 *
	 * @since 0.2
	 *
	 * @return array the list of column titles
	 */
	public function get_columns() {
		return array(
			'name'    => __( 'Profile name', 'lingotek-translation' ),
			'usage'   => __( 'Usage', 'lingotek-translation' ),
			'actions' => __( 'Actions', 'lingotek-translation' ),
		);
	}

	/**
	 * Gets the list of sortable columns
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'name' => array( 'name', false ),
		);
	}

	/**
	 * Prepares the list of items ofr displaying
	 *
	 * @since 0.2
	 *
	 * @param array $data data.
	 */
	public function prepare_items( $data = array() ) {
		$per_page              = $this->get_items_per_page( 'lingotek_profiles_per_page' );
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		/**
		 * Custom sort comparator.
		 *
		 * @param  array $a assoc. array representing a profile.
		 * @param  array $b assoc. array representing a profile.
		 * @return int  sort direction.
		 */
		function usort_reorder( $a, $b ) {
			$order   = filter_input( INPUT_GET, 'order' );
			$orderby = filter_input( INPUT_GET, 'orderby' );
			// Determine sort order.
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			// Send final sort direction to usort.
			return ( empty( $order ) || 'asc' === $order ) ? $result : -$result;
		};

		// No sort by default.
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
}
