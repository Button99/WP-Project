<?php
/**
 * I think this file is not used, and probably should be removed.
 *
 * @package lingotek-translation
 */

// phpcs:disable
/**
 * This file actually prepares the content to be viewed and calls the other files
 * as needed so that everything appears as expected.
 *
 * @author Unknown
 */


require_once 'import-table.php';

$profile = Lingotek_Model::get_profile( 'string', $this->pllm->get_language( $this->pllm->options['default_lang'] ) );

/**
 *Tests to see if the profile is disabled or not.
 *
 *@author Unknown
 */
if ( 'disabled' == $profile['profile'] ) {
	printf(
		'<div class="error" style="border-left: 4px solid #ffba00;"><p>%s</p></div>',
		sprintf(
			__( 'The strings translation is disabled in %1$sContent Type Configuration%2$s.', 'wp-lingotek' ),
			'<a href="' . admin_url( 'admin.php?page=wp-lingotek_settings&sm=content' ) . '">',
			'</a>'
		)
	);
} else {
	$table = new Lingotek_Import_Table();

	$table->prepare_items();
	//Gets the import type (post or page)
	$post_type          = get_option( 'lingotek_import_prefs' )['import_type'];
	$plural_or_singular = '';

	/**
	 *This tests to see if the import was successful. If it was it runs the subsequent
	 *code.
   *
	 *@author Unknown
	 */
	if ( $table->get_import_status() == 'success' ) {
		$importedCount = 0;

		/**
		 *Gets the import count based on if it is POST (Bulk import) or GET (indiviudal import)
	 *
		 *@author Unknown
		 */
		// Used for bulk import.
		if ( isset( $_POST['post'] ) ) {
			$importedCount = count( $_POST['post'] );
		}
		// Used for single import.
		elseif ( isset( $_GET['post'] ) ) {
			$importedCount = count( $_GET['post'] );
		}

		/**
		*This is to show the correct syntax in the message saying the import was Successful
		*or not. It was always showing post even if it was imported as a page. Now it
		*will show page if imported as page and post if imported as a post.
		*
		*@author TJ Murphy
		*@uses Lingotek_Import_Table::$import_success_count
		*/
		if ( $post_type == 'page' ) {
			$plural_or_singular = sprintf( _n( 'Successfully imported %1$s of %2$s page', 'Successfully imported %1$s of %2$s pages', $table->import_success_count, 'wp-lingotek' ), $table->import_success_count, $importedCount );
		} else {
			$plural_or_singular = sprintf( _n( 'Successfully imported %1$s of %2$s post', 'Successfully imported %1$s of %2$s posts', $table->import_success_count, 'wp-lingotek' ), $table->import_success_count, $importedCount );
		}

		add_settings_error( $this->plugin_slug . '_import', '', $plural_or_singular, 'updated' );

		/**
		*If not all the imports were successful this if statement adds a settings error
		*that lists the ids of the documents that did not import
		*
		*@author TJ Murphy
		*@uses Lingotek_Import_Table::$import_success_count
		*@uses Lingotek_Import_Table::$import_failure_count
		*@uses Lingotek_Import_Table::to_string_unsuccessful_imports()
		*/
		if ( $table->import_success_count != $importedCount ) {
			$document_plurality = sprintf( _n( 'The following document did not import: %s', 'The following documents did not import: %s', $table->import_failure_count, 'wp-lingotek' ), $table->to_string_unsuccessful_imports() );
			add_settings_error( $this->plugin_slug . '_import', '', $document_plurality, 'error' );
		}
	} elseif ( $table->get_import_status() == 'failure' ) {
		/**
		*This determines if any files were attempted to be imported. If Zero were even
		*attempted then it shows a message relating that. If >0 were attempted and 0
		*passed then it displays an error message saying all imports failed.
		*
		*@author TJ Murphy
		*@uses Lingotek_Import_Table::import_success_count
		*@uses Lingotek_Import_Table::import_failure_count
		*/
		if ( $table->import_success_count == $importedCount && $table->import_failure_count == $importedCount && $importedCount == 0 ) {
			add_settings_error(
				$this->plugin_slug . '_import',
				'',
				__( 'No files were selected to import. Please check the desired documents to import.', 'wp-lingotek' ),
				'error'
			);
		} else {
			$file_plurality = sprintf(
				_n(
					'There was an error importing your file. We currently only support WordPress, Drupal, HTML, and Text files.',
					'There was an error importing your files. We currently only support WordPress, Drupal, HTML, and Text files.',
					$table->import_failure_count,
					'wp-lingotek'
				),
				$table->import_failure_count
			);
			add_settings_error(
				$this->plugin_slug . '_import',
				'',
				$file_plurality,
				'error'
			);
		}//end if
	}//end if
	settings_errors();

	$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';
	$orderby = ( ! empty( $_GET['orderby'] ) ) ? '&orderby=' . $_GET['orderby'] . '&order=' . $order : '';

	?>

	<?php
	/**
	 * This creates the starting form tag for the import page and sets the page and
	 * action parameters.
	 *
	 * @author Unknown
	 */
	echo sprintf( '<form id="lingotek-import" method="post" action="admin.php?page=lingotek-translation_import&action=bulk_import' . (string) $orderby . '"' );
	/**
	 *@see wp-admin/includes/class-wp-list-table.php/WP_List_Table::display()
	 */
	$table->display();
	?>
	</form>
	<?php
}//end if
