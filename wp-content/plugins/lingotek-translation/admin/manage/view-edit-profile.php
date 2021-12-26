<?php

$settings = $this->get_profiles_settings();

$defaults = get_option( 'lingotek_defaults', array() );
/* translators: %s: The global default value. */
$default = __( 'Use global default (%s)', 'lingotek-translation' );
foreach ( $settings as $key => $setting ) {
	if ( isset( $defaults[ $key ] ) && array_key_exists( $defaults[ $key ], $settings[ $key ]['options'] ) ) {
		$default_arr                 = array( 'default' => sprintf( $default, $settings[ $key ]['options'][ $defaults[ $key ] ] ) );
		$settings[ $key ]['options'] = array_merge( $default_arr, $settings[ $key ]['options'] );
	}
}

$target_settings = array(
	'default'  => __( 'Use default settings', 'lingotek-translation' ),
	'custom'   => __( 'Use custom settings', 'lingotek-translation' ),
	'copy'     => __( 'Copy source language', 'lingotek-translation' ),
	'disabled' => __( 'Disabled', 'lingotek-translation' ),
);

$profiles = $this->get_profiles_usage( get_option( 'lingotek_profiles' ) );

$profile  = isset( $_GET['profile'] ) && array_key_exists( sanitize_key( wp_unslash( $_GET['profile'] ) ), $profiles ) ? $profiles[ sanitize_key( wp_unslash( $_GET['profile'] ) ) ] : array();
$disabled = isset( $profile['profile'] ) && in_array( $profile['profile'], array( 'automatic', 'manual' ), true ) ? 'disabled="disabled"' : '';
// Code to determine which filter scenario will be displayed. (Not configured, defaults, custom filters)
$primary_filter_id     = array_search( 'okf_json@with-html-subfilter.fprm', $settings['primary_filter_id']['options'], true );
$secondary_filter_id   = array_search( 'okf_html@wordpress.fprm', $settings['secondary_filter_id']['options'], true );
$default_filters       = array(
	$primary_filter_id   => 'okf_json@with-html-subfilter.fprm',
	$secondary_filter_id => 'okf_html@wordpress.fprm',
);
$default_filters_exist = false;
$extra_filters_exist   = false;
$no_filters_exist      = false;

if ( array_key_exists( 'default', array_diff_assoc( $settings['primary_filter_id']['options'], $default_filters ) ) && 1 === count( array_diff_assoc( $settings['primary_filter_id']['options'], $default_filters ) ) ) {
	unset( $settings['primary_filter_id']['options']['default'] );
}
if ( $settings['primary_filter_id']['options'] === $default_filters ) {
	$default_filters_exist           = true;
	$defaults['primary_filter_id']   = $primary_filter_id;
	$defaults['secondary_filter_id'] = $secondary_filter_id;
	update_option( 'lingotek_defaults', $defaults, false );
} else {
	$num = count( array_diff_assoc( $settings['primary_filter_id']['options'], $default_filters ) );
	if ( $num > 0 ) {
		$extra_filters_exist = true;
	} else {
		$defaults['primary_filter_id']   = '';
		$defaults['secondary_filter_id'] = '';
		update_option( 'lingotek_defaults', $defaults, false );
		$no_filters_exist = true;
	}
}
unset( $settings['primary_filter_id']['options'][ $secondary_filter_id ] );
unset( $settings['secondary_filter_id']['options'][ $primary_filter_id ] );
?>

<form id="lingotek-edit-profile" method="post" action="admin.php?page=lingotek-translation_manage&sm=profiles" class="validate">
<?php wp_nonce_field( 'lingotek-edit-profile', '_wpnonce_lingotek-edit-profile' ); ?>
<input name="profile" type="hidden" value="<?php echo empty( $profile['profile'] ) ? '' : esc_attr( $profile['profile'] ); ?>">

<table class="form-table">
	<tr>
		<th scope="row"><?php printf( '<label for="%s">%s</label>', 'name', esc_attr__( 'Profile name', 'lingotek-translation' ) ); ?></th>
		<td>
		<?php
		if ( ! empty( $profile ) ) {
			// localize canned profile names
			$profile_name = $disabled ? __( 'Disabled', 'lingotek-translation' ) : $profile['name'];
		}
		printf(
			'<input name="name" id="name" type="text" value="%s" %s>',
			empty( $profile['name'] ) ? '' : esc_attr( $profile_name ),
			esc_html( $disabled )
		);
		?>
		</td>
	</tr>
</table>

<h3><?php esc_html_e( 'Default settings', 'lingotek-translation' ); ?></h3>

<table class="form-table">
<?php
foreach ( $settings as $key => $setting ) {
	?>
	<tr id="<?php echo $key . '_row'; ?>">
		<th scope="row"><?php printf( '<label for="%s">%s</label>', $key, $setting['label'] ); ?></th>
		<td>
		<?php
		printf( '<select name="%1$s" id="%1$s" %2$s>', $key, in_array( $key, array( 'upload', 'download' ) ) ? $disabled : '' );
		foreach ( $setting['options'] as $option_id => $option_title ) {
			$selected = isset( $profile[ $key ] ) && $profile[ $key ] == $option_id ? 'selected="selected"' : '';
			echo "\n\t<option value='" . esc_attr( $option_id ) . "' $selected>" . esc_html( $option_title ) . '</option>';
		}
		?>
			</select>
			<?php

			if ( 'project_id' == $key ) {
				?>
				<input type="checkbox" name="update_callback" id="update_callback"/>
				<label for="update_callback"><?php _e( 'Update the callback url for this project.', 'lingotek-translation' ); ?></label>
																								   <?php
			}

			if ( isset( $setting['description'] ) ) {
				printf( '<p class="description">%s</p>', $setting['description'] );
			}
			?>

			<!-- Code to handle displaying of Primary and Secondary Filters -->
			<?php if ( $no_filters_exist ) { ?>
						<script> document.getElementById("primary_filter_id_row").style.display = "none";</script>
						<script> document.getElementById("secondary_filter_id_row").style.display = "none";</script>
						<?php
						if ( 'primary_filter_id' == $key ) {
							?>
							<tr id="filters_row"><th><?php _e( 'Filters', 'lingotek-translation' ); ?></th><td><i><?php _e( 'Not configured', 'lingotek-translation' ); ?></i></td></tr>
							<?php
						}
			}
			if ( $default_filters_exist ) {
				?>
						<script> document.getElementById("primary_filter_id_row").style.display = "none";</script>
						<script> document.getElementById("secondary_filter_id_row").style.display = "none";</script>
				<?php } ?>
			<!-- End of filter code -->
		</td>
	</tr>
	<?php
}//end foreach

?>
</table>

<h3><?php _e( 'Target languages', 'lingotek-translation' ); ?></h3>

<table class="form-table target-table">
<?php
	// we don't want this for target languages
	unset( $settings['upload'] );
	// FIXME disable the possibility to have a different project per target language for now
	unset( $settings['project_id'] );
	// Filters not needed for target languages
	unset( $settings['primary_filter_id'] );
	unset( $settings['secondary_filter_id'] );
?>
	<?php
	$custom_profile_chosen = false;
	foreach ( $this->pllm->get_languages_list() as $language ) {
		?>
	<tr>
		<th scope="row"><?php printf( '<label for="%s">%s (%s)</label>', esc_attr( $language->slug ), esc_html( $language->name ), esc_attr( $language->locale ) ); ?>
			<?php

			printf(
				'<a id="%1$s_details_link" %2$s class="dashicons dashicons-arrow-right" onclick="%3$s">%4$s</a>',
				esc_attr( $language->slug ),
				isset( $profile['targets'][ $language->slug ] ) && 'custom' == $profile['targets'][ $language->slug ] ? '' : 'style="display:none;"',
				"
				d = document.getElementById('{$language->slug}_details');
				if ('none' == d.style.display) {
					d.style.display = '';
					this.className = 'dashicons dashicons-arrow-down';
				}
				else {
					d.style.display = 'none';
					this.className = 'dashicons dashicons-arrow-right';
				}",
				''
			);
			?>
			</th>
		<td class="target-table-td">
		<?php
			printf(
				'<select name="targets[%1$s]" id="targets[%1$s]" onchange="%2$s">',
				esc_attr( $language->slug ),
				"
				dl = document.getElementById('{$language->slug}_details_link');
				d = document.getElementById('{$language->slug}_details');
				var target_warning = document.getElementById('target_warning');
				if ('custom' == this.value) {
					d.style.display = dl.style.display = '';
					dl.className = 'dashicons dashicons-arrow-down';
					if (target_warning) {
						target_warning.style.display = 'block';
					}
				}
				else {
					d.style.display = dl.style.display = 'none';
					dl.className = 'dashicons dashicons-arrow-right';
				}
				var target_checkbox = document.getElementById('target_locales[{$language->slug}]');
				var label = document.getElementById('target_locales_label[{$language->slug}]');
				if ('disabled' === this.value || 'copy' === this.value) {
					if (target_checkbox) {
						target_checkbox.checked = false;
						target_checkbox.style.display = 'none';
					}
					if (label) {
						label.style.display = 'none';
					}
					return;
				}
				if (target_checkbox) {
					target_checkbox.style.display = 'inline-block';
				}
				if (label) {
					label.style.display = 'inline';
				}
				"
			);
		foreach ( $target_settings as $target_id => $target_title ) {
			$selected = ( empty( $profile['targets'][ $language->slug ] ) && 'default' === $target_id ) || ( isset( $profile['targets'][ $language->slug ] ) && $profile['targets'][ $language->slug ] === $target_id ) ? 'selected="selected"' : '';
			echo "\n\t<option value='" . esc_attr( $target_id ) . "' $selected>" . esc_html( $target_title ) . '</option>';
		}
		?>
			</select>

		</td>
		<?php
		if ( isset( $profile['targets'][ $language->slug ] ) && 'custom' === $profile['targets'][ $language->slug ] ) {
			$custom_profile_chosen = true;
		}
			$checked = empty( $profile['target_locales'][ $language->slug ] ) ? '' : 'checked="checked"';
			$display = '';
		if ( isset( $profile['targets'][ $language->slug ] ) && ( 'disabled' === $profile['targets'][ $language->slug ] || 'copy' === $profile['targets'][ $language->slug ] ) ) {
			$display = 'style="display:none;"';
		}
		?>
		<td><input
				<?php echo $checked; ?>
				<?php echo $display; ?>
				type="checkbox"
				class="ltk-target-checkbox"
				value="<?php echo esc_attr( $language->lingotek_locale ); ?>"
				id="target_locales[<?php echo esc_attr( $language->slug ); ?>]"
				name="target_locales[<?php echo esc_attr( $language->slug ); ?>]"
			>
			<label
				<?php echo $display; ?>
				for="target_locales[<?php echo esc_attr( $language->slug ); ?>]"
				id="target_locales_label[<?php echo esc_attr( $language->slug ); ?>]">Auto-request with document upload
			</label>
		</td>
	</tr>
	<tr id="<?php echo esc_attr( $language->slug ); ?>_details" style="display:none;">
	<td colspan="2" style="padding:0;">
		<table class="form-table" style="background: #ffffff; margin:0; padding: 10px;">
		<?php
		foreach ( $settings as $key => $setting ) {
			$custom_key = 'custom[' . $key . '][' . esc_attr( $language->slug ) . ']';
			?>
					<tr>
						<th scope="row"><?php printf( '<label for="%s">%s</label>', esc_attr( $custom_key ), esc_attr( $setting['label'] ) ); ?></th>
						<td>
						<?php
						printf( '<select name="%1$s" id="%1$s" %2$s>', $custom_key, in_array( $key, array( 'upload', 'download' ) ) ? $disabled : '' );
						foreach ( $setting['options'] as $option_id => $option_title ) {
							if ( 'default' === $option_id && 'workflow_id' === $key ) {
								$option_id = $defaults['workflow_id'];
							}
							$selected = isset( $profile['custom'][ $key ][ $language->slug ] ) && $profile['custom'][ $key ][ $language->slug ] == $option_id ? 'selected="selected"' : '';
							echo "\n\t<option value='" . esc_attr( $option_id ) . "' $selected>" . esc_html( $option_title ) . '</option>';
						}
						?>
							</select>
						</td>
					</tr>
					<?php
		}//end foreach
		?>
			</table>
	</td>
	</tr>
		<?php
	}//end foreach

	?>
	<tr>
	<td></td>
	<td class="target-table-td"></td>
	<td>
		<?php
			$select_all_on_change = "
										var elements = document.getElementsByClassName('ltk-target-checkbox');
										var checked = event.target && event.target.checked;
										for (var i = 0; i < elements.length; ++i) {
											if (elements[i].style.display !== 'none') {
												elements[i].checked = checked;
											}
										}
									";
		?>
		<input type="checkbox" name="target_select_all" id="target_select_all" onchange="<?php echo esc_attr( $select_all_on_change ); ?>">
		<label for="target_select_all"><?php esc_html_e( 'Select All', 'lingotek-translation' ); ?></label></td>
	</tr>
	<?php
	$target_warning = $custom_profile_chosen ? '' : 'style="display:none"';
	?>
	<tr>
		<td colspan="3"><span id='target_warning' <?php echo $target_warning; ?>><b>Note:</b> If any targets are selected to be uploaded and at the same time a custom workflow is selected
		for any of them, all targets to be uploaded must have a non-project-default workflow selected.</span></td>
	</tr>
</table>

<?php
$metadata = array(
	'author_email'   => 'Author Email',
	'author_name'    => 'Author Name',
	'division'       => 'Business Division',
	'unit'           => 'Business Unit',
	'campaign_id'    => 'Campaign ID',
	'channel'        => 'Channel',
	'contact_email'  => 'Contact Email',
	'contact_name'   => 'Contact Name',
	'description'    => 'Content Description',
	'domain'         => 'Domain',
	'style_id'       => 'External Style ID',
	'purchase_order' => 'Purchase Order',
	'reference_url'  => 'Reference URL',
	'region'         => 'Region',
	'require_review' => 'Require Review',
)
?>
<h3><?php esc_html_e( 'Document Metadata', 'lingotek-translation' ); ?></h3>
<table class="form-table">
<?php
foreach ( $metadata as $key => $data ) {
	$index = array_search( $key, array_keys( $metadata ), true );
	if ( 0 === $index ) {
		printf( '<tr>' );
	}
	if ( 0 === $index % 3 ) {
		printf( '</tr>' );
		printf( '<tr>' );
	}
	if ( isset( $profile[ $key ] ) ) {
		printf( '<th>&nbsp; %s <br><input type="text" name="%s" value="%s"></th>', esc_html( $data ), esc_attr( $key ), esc_attr( $profile[ $key ] ) );
	} else {
		printf( '<th>&nbsp; %s <br><input type="text" name="%s"></th>', esc_html( $data ), esc_attr( $key ) );
	}
}
?>
</table>

<?php submit_button( __( 'Save Changes', 'lingotek-translation' ), 'primary', 'submit', false ); ?>
<?php
if ( ! empty( $profile['profile'] ) && ! in_array( $profile['profile'], array( 'automatic', 'manual', 'disabled' ), true ) && empty( $profile['usage'] ) ) {
	printf(
		'<a href="%s" class="button" onclick = "return confirm(\'%s\');">%s</a>',
		esc_url( wp_nonce_url( 'admin.php?page=lingotek-translation_manage&sm=profiles&lingotek_action=delete-profile&noheader=true&profile=' . $profile['profile'], 'delete-profile' ) ),
		esc_html__( 'You are about to permanently delete this profile. Are you sure?', 'lingotek-translation' ),
		esc_html__( 'Delete', 'lingotek-translation' )
	);
}
?>
<a href="admin.php?page=lingotek-translation_manage&amp;sm=profiles" class="button"> <?php esc_html_e( 'Cancel', 'lingotek-translation' ); ?></a>
</form>

<?php Lingotek_Workflow_Factory::lingotek_translation_professional_translation_info_modals(); ?>
