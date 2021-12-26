<?php

/**
 * Extends the Polylang class to disable the input fields
 *
 * @since 0.3
 */
class Lingotek_Table_String extends PLL_Table_String {
	/**
	 * Displays the translations to edit (disabled).
	 *
	 * @since 0.3
	 *
	 * @param array $item item.
	 * @return string
	 */
	function column_translations( $item ) {
		$out = '';
		foreach ( $item['translations'] as $key => $translation ) {
			$input_type = $item['multiline'] ?
				'<textarea name="translation[%1$s][%2$s]" id="%1$s-%2$s" disabled="disabled">%4$s</textarea>' :
				'<input type="text" name="translation[%1$s][%2$s]" id="%1$s-%2$s" value="%4$s" disabled="disabled" />';
			$out       .= sprintf(
				'<div class="translation"><label for="%1$s-%2$s">%3$s</label>' . $input_type . '</div>' . "\n",
				esc_attr( $key ),
				esc_attr( $item['row'] ),
				esc_html( $this->languages['languages'][ $key ] ),
				// Don't interpret special chars.
				format_to_edit( $translation )
			);
		}
		return $out;
	}

	/**
	 * Prepares items for display.
	 *
	 * @param  array $data data.
	 */
	function prepare_items( $data = null ) {
		$listlanguages = $GLOBALS['polylang']->model->get_languages_list();

		// Filter for search string.
		$s = filter_input( INPUT_GET, 's' );
		$s = empty( $s ) ? '' : wp_unslash( $s );
		foreach ( $data as $key => $row ) {
			if ( ( -1 !== $this->selected_group && $row['context'] !== $this->selected_group ) || ( ! empty( $s ) && stripos( $row['name'], $s ) === false && stripos( $row['string'], $s ) === false ) ) {
				unset( $data[ $key ] );
			}
		}

		// Load translations.
		foreach ( $listlanguages as $language ) {
			// filters by language if requested.
			if ( ( $lg = get_user_meta( get_current_user_id(), 'pll_filter_content', true ) ) && $language->slug !== $lg ) {
				continue;
			}

			$mo = new PLL_MO();
			$mo->import_from_db( $language );
			foreach ( $data as $key => $row ) {
				$data[ $key ]['translations'][ $language->slug ] = $mo->translate( $row['string'] );
				// Store the row number for convenience.
				$data[ $key ]['row'] = $key;
			}
		}

		$per_page              = $this->get_items_per_page( 'pll_strings_per_page' );
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$orderby = filter_input( INPUT_GET, 'orderby' );
		// No sort by default.
		if ( ! empty( $orderby ) ) {
			usort( $data, array( $this, 'usort_reorder' ) );
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
