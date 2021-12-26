<?php

global $polylang;

$items                 = array();
$default_setting       = array(
	'ignore'    => 'Ignore',
	'translate' => 'Translate',
	'copy'      => 'Copy',
);
$default_custom_fields = '';

if ( ! empty( $_POST ) ) {
	check_admin_referer( 'lingotek-custom-fields', '_wpnonce_lingotek-custom-fields' );

	if ( ! empty( $_POST['submit'] ) ) {
		$arr = empty( $_POST['settings'] ) ? array() : $_POST['settings'];

		if ( isset( $_POST['default_custom_fields'] ) ) {
			$default_custom_fields = $_POST['default_custom_fields'];
			update_option( 'lingotek_default_custom_fields', $default_custom_fields, false );
		}
		update_option( 'lingotek_custom_fields', $arr, false );
		$post_types = get_post_types();
		foreach ( $post_types as $post_type ) {
			$cache_key = 'content_type_fields_' . $post_type;
			wp_cache_delete( $cache_key, 'lingotek' );
		}
		add_settings_error( 'lingotek_custom_fields_save', 'custom_fields', __( 'Your <i>Custom Fields</i> were sucessfully saved.', 'lingotek-translation' ), 'updated' );
	}

	// Bulk Change.
	if ( ! empty( $_POST['submit2'] ) ) {
		$arr = empty( $_POST['settings'] ) ? array() : $_POST['settings'];

		if ( isset( $_POST['custom_fields'] ) ) {
			$custom_fields = $_POST['custom_fields'];
		}
		foreach ( $_POST as $post => $value ) {
			if ( $value == 'value1' ) {
				$temp = explode( '_', $post );
				$temp = implode( ' ', $temp );

				foreach ( $arr as $item => $val ) {
					$temp2 = explode( '_', $item );
					$temp2 = implode( ' ', $temp2 );
					if ( $temp == $temp2 ) {
						$arr[ $item ] = $custom_fields;
					}
				}
			}
		}
		update_option( 'lingotek_custom_fields', $arr, false );
	}//end if

	if ( ! empty( $_POST['refresh'] ) ) {
		Lingotek_Group_Post::get_updated_meta_values();
		add_settings_error( 'lingotek_custom_fields_refresh', 'custom_fields', __( 'Your <i>Custom Fields</i> were sucessfully identified.', 'lingotek-translation' ), 'updated' );
	}
	settings_errors();
}//end if

$items                 = Lingotek_Group_Post::get_cached_meta_values();
$default_custom_fields = get_option( 'lingotek_default_custom_fields' );

?>

<h3><?php _e( 'Custom Field Configuration', 'lingotek-translation' ); ?></h3>
<p class="description">
	<?php _e( 'Custom Fields can be translated, copied, or ignored. Click "Refresh Custom Fields" to identify and enable your custom fields.', 'lingotek-translation' ); ?>
</p>

<form id="lingotek-custom-fields" method="post" action="admin.php?page=lingotek-translation_manage&amp;sm=custom-fields"
	class="validate">
	<?php
	wp_nonce_field( 'lingotek-custom-fields', '_wpnonce_lingotek-custom-fields' );
	?>

	<br>
	<label for="default_custom_fields">Default configuration for new Custom Fields</label>
	<select name="default_custom_fields">
	<?php
	foreach ( $default_setting as $key => $title ) {
		$selected = $key == $default_custom_fields ? 'selected="selected"' : '';
		echo "\n\t<option value='" . $key . "' $selected>" . $title . '</option>';
	}
	?>
	</select>
	</br>
	<div id="d1" style="display: none;">
		<select name="custom_fields">
		<?php
		foreach ( $default_setting as $key => $title ) {
			$selected = $key == $default_custom_fields ? 'selected="selected"' : '';
			echo "\n\t<option value='" . $key . "' $selected>" . $title . '</option>';
		}
		?>
		</select>
		<?php
		submit_button( __( 'Bulk Change', 'lingotek-translation' ), 'primary', 'submit2', false );
		echo '</div>';
		$table = new Lingotek_Custom_Fields_Table();
		$table->prepare_items( $items );
		$table->display();
		?>

		<p>
			<?php submit_button( __( 'Save Changes', 'lingotek-translation' ), 'primary', 'submit', false ); ?>
			<?php submit_button( __( 'Refresh Custom Fields', 'lingotek-translation' ), 'secondary', 'refresh', false ); ?>
		</p>
</form>


<?php
echo "
<script>

function show(source){
  var ids = document.getElementsByClassName('boxes');
  for ( var i in ids) {
      if (ids[i].checked == true || source.checked == true ) {
        document.getElementById('d1').style.display='block';   
        break;    
      }
      else{
        document.getElementById('d1').style.display='none';     
  }  
}
 
}
var selectedIds = [];
function toggle(source) {
  document.getElementById('d1').style.display='block';
  if(source.checked == false){
    document.getElementById('d1').style.display='none';
  }


    checkboxes = document.getElementsByClassName('boxes');
    for ( var i in checkboxes)
        checkboxes[i].checked = source.checked;
}

</script>" ?>
