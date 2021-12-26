<?php
/**
 * I think this file is not used, and probably should be removed.
 *
 * @package lingotek-translation
 */

// phpcs:disable

/**
 * Sets all of the setting details so they can appropriately be presented.
 *
 * @author Unknown
 * @var array
 */
$setting_details = array(
	'import_post_status' => array(
		'type'        => 'dropdown',
		'label'       => __( 'Import documents as', 'wp-lingotek' ),
		'description' => __( 'The post status for newly imported documents', 'wp-lingotek' ),
		'values'      => array(
			'draft'   => __( 'Draft', 'wp-lingotek' ),
			'pending' => __( 'Pending Review', 'wp-lingotek' ),
			'publish' => __( 'Published', 'wp-lingotek' ),
			'private' => __( 'Privately Published', 'wp-lingotek' ),
		),
	),
	'import_type'        => array(
		'type'        => 'dropdown',
		'label'       => __( 'Format', 'wp-lingotek' ),
		'description' => __( 'In which format would you like your imports to be?', 'wp-lingotek' ),
		'values'      => array(
			'page' => __( 'Page', 'wp-lingotek' ),
			'post' => __( 'Post', 'wp-lingotek' ),
		),
	),
);


$page_key = $this->plugin_slug . '_import&sm=settings';

/**
 *Sets the options
 *
 *@author Unknown
 */
if ( ! empty( $_POST ) ) {
	$options = array();
	foreach ( $setting_details as $key => $setting ) {
		if ( isset( $_POST[ $key ] ) ) {
			$options[ $key ] = $_POST[ $key ];
		} else {
			$options[ $key ] = null;
		}
	}


	update_option( 'lingotek_import_prefs', $options, false );

	add_settings_error( 'lingotek_prefs', 'prefs', __( 'Your preferences were successfully updated.', 'wp-lingotek' ), 'updated' );
	settings_errors();
}
$selected_options = get_option( 'lingotek_import_prefs' );
?>

<h3><?php _e( 'Settings', 'wp-lingotek' ); ?></h3>

<form id="lingotek-settings" method="post" action="admin.php?page=<?php echo $page_key; ?>" class="validate">
<?php wp_nonce_field( $page_key, '_wpnonce_' . $page_key ); ?>

  <table class="form-table"><?php foreach ( $setting_details as $key => $setting ) { ?>

	  <tr>
		<th scope="row"><label for="<?php echo $key; ?>"><?php echo $setting['label']; ?></label></th>
		<td>
		  <?php if ( $setting['type'] == 'dropdown' ) { ?>
		  <select name="<?php echo $key; ?>" id="<?php echo $key; ?>">
				<?php
				foreach ( $setting['values'] as $id => $title ) {
					echo "\n\t" . '<option value="' . esc_attr( $id ) . '" ' . selected( $selected_options[ $key ], $id ) . '>' . $title . '</option>';
				}
				?>
			</select>
				<?php
		  } elseif ( $setting['type'] == 'checkboxes' ) {
			  echo '<ul class="pref-statuses">';
			  foreach ( $setting['values'] as $id => $title ) {
				  $cb_name = $key . '[' . esc_attr( $id ) . ']';
				  $checked = checked( '1', ( isset( $selected_options[ $key ][ $id ] ) && $selected_options[ $key ][ $id ] ), false );
				  echo '<li><input type="checkbox" id="' . $cb_name . '" name="' . $cb_name . '" value="1" ' . $checked . '><label for="' . $cb_name . '">' . $title . '</label></li>';
			  }
			  echo '</ul>';
		  }
			?>
		  <p class="description">
			<?php echo $setting['description']; ?>
		  </p>
	  </tr>
		<?php
							}//end foreach
							?>
  </table>

<?php submit_button( __( 'Save Changes', 'wp-lingotek' ), 'primary', 'submit', false ); ?>
</form>
