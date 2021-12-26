<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	// Since WP 3.1.
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Lingotek Content Table class.
 */
class Lingotek_Content_Table extends WP_List_Table {
	/**
	 * Profiles.
	 *
	 * @var array
	 */
	protected $profiles;
	/**
	 * Content types.
	 *
	 * @var array
	 */
	protected $content_types;

	/**
	 * Constructor
	 *
	 * @param array $content_types array of content types.
	 * @since 0.2
	 */
	public function __construct( $content_types ) {
		parent::__construct(
			array(
				// Do not translate (used for css class).
				'plural' => 'lingotek-content',
				'ajax'   => false,
			)
		);

		$this->profiles      = Lingotek::get_profiles();
		$this->content_types = $content_types;
	}

	/**
	 * Displays the item information in a column (default case)
	 *
	 * @since 0.2
	 *
	 * @param array $item items.
	 */
	protected function column_name( $item ) {
		global $polylang;

		printf( '<span class="content-type-name">%s</span>', esc_html( $item['name'] ) );

		// the source language for strings is always the default language.
		if ( 'string' !== $item['type'] ) {
			printf(
				'<a id="id[%s]" class="dashicons dashicons-arrow-right" onclick="%s" href="#"></a>',
				esc_attr( $item['type'] ),
				"
				d1 = document.getElementById('sources-name[" . esc_html( $item['type'] ) . "]');
				d2 = document.getElementById('sources-profile[" . esc_html( $item['type'] ) . "]');
				c = 'dashicons dashicons-arrow-';
				if (c+'right' == this.className) {
					this.className = c+'down';
					d1.style.display = d2.style.display = '';
				}
				else {
					this.className = c+'right';
					d1.style.display = d2.style.display = 'none';
				}
				return false;
				"
			);

			printf( '<ul class="sources-name" id="sources-name[%s]" style="display:none;">', esc_html( $item['type'] ) );
			foreach ( $polylang->model->get_languages_list() as $language ) {
				/* translators: %s: The language name. */
				printf( '<li>%s</li>', sprintf( esc_html( __( '%s source', 'lingotek-translation' ) ), esc_html( $language->name ) ) );
			}
			echo '</ul>';
		}//end if
	}

	/**
	 * Displays the item profile dropdown list
	 *
	 * @since 0.2
	 *
	 * @param array $item item.
	 */
	protected function column_profile( $item ) {
		global $polylang;

		printf( '<select class="content-type-profile" name="%1$s" id="%1$s">', esc_html( $item['type'] ) . '[profile]' );
		foreach ( $this->profiles as $key => $profile ) {
			$selected = ( isset( $item['profile'] ) && $key === $item['profile'] ) ? 'selected="selected"' : '';
			echo "\n\t<option value='" . esc_attr( $key ) . "' " . esc_html( $selected ) . '>' . esc_html( $profile['name'] ) . '</option>';
		}
		echo '</select>';

		$options = array_merge( array( 'default' => array( 'name' => __( 'Use content type default', 'lingotek-translation' ) ) ), $this->profiles );

		// the source language for strings is always the default language.
		if ( 'string' !== $item['type'] ) {
			printf( '<ul class="sources-profile" id="sources-profile[%s]" style="display:none;">', esc_html( $item['type'] ) );
			foreach ( $polylang->model->get_languages_list() as $language ) {
				printf( '<li><select name="%1$s" id="%1$s">', esc_html( $item['type'] ) . '[sources][' . esc_html( $language->slug ) . ']' );
				foreach ( $options as $key => $profile ) {
					$selected = ( isset( $item['sources'][ $language->slug ] ) && $key === $item['sources'][ $language->slug ] ) ? 'selected="selected"' : '';
					echo "\n\t<option value='" . esc_attr( $key ) . "' " . esc_html( $selected ) . '>' . esc_html( $profile['name'] ) . '</option>';
				}
				echo '</select></li>';
			}
			echo '</ul>';
		}
	}

	/**
	 * Displays checkboxes in fields columns
	 * can handle fields with one or two keys
	 *
	 * @since 0.2
	 *
	 * @param array  $labels labels.
	 * @param array  $values values.
	 * @param string $name name.
	 */
	protected function display_fields( $labels, $values, $name ) {
		foreach ( $labels as $key => $str ) {
			if ( is_array( $str ) ) {
				if ( 'metas' === $key ) {
					continue;
				}
				$this->display_fields( $str, isset( $values[ $key ] ) ? $values[ $key ] : array(), $name . "[$key]" );
			} else {
				printf(
					'<li><label><input name="%s" type="checkbox" value="1" %s /> %s</label></li>',
					esc_attr( $name . "[$key]" ),
					empty( $values[ $key ] ) ? 'checked="checked"' : '',
					esc_html( $str )
				);
			}
		}
	}

	/**
	 * Displays the item fields checkboxes
	 *
	 * @since 0.2
	 *
	 * @param array $item item.
	 */
	protected function column_fields( $item ) {
		if ( ! empty( $item['fields'] ) ) {
			echo '<ul class="content-type-fields">';
			$this->display_fields( $item['fields']['label'], isset( $item['fields']['value'] ) ? $item['fields']['value'] : array(), $item['type'] . '[fields]' );
			echo '</ul>';
		}
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
			'name'    => __( 'Content Type', 'lingotek-translation' ),
			'profile' => __( 'Profile', 'lingotek-translation' ),
			'fields'  => __( 'Fields', 'lingotek-translation' ),
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
		$per_page              = $this->get_items_per_page( 'lingotek_content_per_page' );
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
