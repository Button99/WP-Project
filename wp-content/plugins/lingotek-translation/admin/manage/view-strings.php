<h3 style="padding-top:10px; margin-top:10px; margin-bottom:-25px;"><?php _e( 'Strings', 'lingotek-translation' ); ?> <a href="admin.php?page=mlang_strings" title="<?php _e( 'Edit on Polylang Strings Translation page', 'lingotek-translation' ); ?>" class="dashicons dashicons-edit"></a></h3>

<?php
$listlanguages = $GLOBALS['polylang']->model->get_languages_list();

// FIXME now mainly copy paste of Polylang

// get the strings to translate
$data = PLL_Admin_Strings::get_strings();

$selected = empty( $_REQUEST['group'] ) ? -1 : $_REQUEST['group'];
foreach ( $data as $key => $row ) {
	// get the groups
	$groups[] = $row['context'];

	// filter for search string
	if ( ( $selected != -1 && $row['context'] != $selected ) || ( ! empty( $_REQUEST['s'] ) && stripos( $row['name'], $_REQUEST['s'] ) === false && stripos( $row['string'], $_REQUEST['s'] ) === false ) ) {
		unset( $data[ $key ] );
	}
}

$groups = array_unique( $groups );

// load translations
foreach ( $listlanguages as $language ) {
	// filters by language if requested
	if ( ( $lg = get_user_meta( get_current_user_id(), 'pll_filter_content', true ) ) && $language->slug != $lg ) {
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

// get an array with language slugs as keys, names as values
$languages = array_combine( wp_list_pluck( $listlanguages, 'slug' ), wp_list_pluck( $listlanguages, 'name' ) );

$string_table = new Lingotek_Table_String( compact( 'languages', 'groups', 'selected' ) );
$string_table->prepare_items( $data );
?>

<div class="form-wrap">
	<form id="string-translation" method="post" action="admin.php?page=mlang_strings&amp;noheader=true">
		<input type="hidden" name="pll_action" value="string-translation" />
		<?php
		$string_table->search_box( __( 'Search translations', 'lingotek-translation' ), 'translations' );
		wp_nonce_field( 'string-translation', '_wpnonce_string-translation' );
		$string_table->display();
		?>
	</form>
</div>
