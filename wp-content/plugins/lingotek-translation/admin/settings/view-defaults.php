<?php

$page_key = $this->plugin_slug . '_settings&sm=defaults';

wp_enqueue_script( 'defaults', LINGOTEK_URL . '/js/defaults.js', array(), LINGOTEK_VERSION, false );

if ( ! empty( $_POST ) ) {
	check_admin_referer( $page_key, '_wpnonce_' . $page_key );
	$refresh = filter_input( INPUT_POST, 'refresh' );
	if ( ! empty( $refresh ) ) {
			$refresh_success = $this->set_community_resources( $community_id );
		if ( true === $refresh_success['projects'] && true === $refresh_success['workflows'] ) {
			add_settings_error( 'lingotek_community_resources', 'options', __( 'Resources from Lingotek were successfully updated for projects and workflows.', 'lingotek-translation' ), 'updated' );
		} elseif ( true === $refresh_success['projects'] ) {
			add_settings_error( 'lingotek_community_resources', 'error', __( 'Resources from Lingotek were successfully updated for projects.', 'lingotek-translation' ), 'updated' );
		} elseif ( true === $refresh_success['workflows'] ) {
			add_settings_error( 'lingotek_community_resources', 'error', __( 'Resources from Lingotek were successfully updated for workflows.', 'lingotek-translation' ), 'updated' );
		} elseif ( false === $refresh_success['workflows'] || false === $refresh_success['projects'] ) {
			add_settings_error( 'lingotek_community_resources', 'error', __( 'The Lingotek TMS is currently unavailable. Please try again later. If the problem persists, contact Lingotek Support.', 'lingotek-translation' ), 'error' );
		}
	} else {
			$options  = array();
			$settings = $this->get_profiles_settings( true );
		foreach ( $settings as $key => $setting ) {
			$key_input = filter_input( INPUT_POST, $key );
			if ( ! empty( $key_input ) ) {
				$options[ $key ] = $key_input;
			}
		}
			update_option( 'lingotek_defaults', $options );
			add_settings_error( 'lingotek_defaults', 'defaultgs', __( 'Your <i>Defaults</i> were sucessfully saved.', 'lingotek-translation' ), 'updated' );

		$update_callback = filter_input( INPUT_POST, 'update_callback' );
		if ( ! empty( $update_callback ) ) {
			$client = new Lingotek_API();
			if ( $client->update_callback_url( $options['project_id'] ) ) {
				add_settings_error( 'lingotek_defaults', 'defaultgs', __( 'Your callback url was successfully updated.', 'lingotek-translation' ), 'updated' );
			}
		}

			// adds new project if text box is filled out.
		$new_project = filter_input( INPUT_POST, 'new_project' );
		if ( ! empty( $new_project ) ) {
			$client        = new Lingotek_API();
			$project_title = stripslashes( filter_input( INPUT_POST, 'new_project' ) );

			$new_id = $client->create_project( $project_title, $community_id );
			if ( $new_id ) {
				add_settings_error( 'lingotek_defaults', 'defaultgs', __( 'Your new project was successfully created.', 'lingotek-translation' ), 'updated' );
				// Updates the cache to include the newly created project.
				$this->set_community_resources( $community_id );
				$options['project_id'] = $new_id;
				update_option( 'lingotek_defaults', $options );
			}
		}
	}//end if
	settings_errors();
}//end if
$settings = $this->get_profiles_settings( true );
$options  = get_option( 'lingotek_defaults', array() );

// Code to determine which filter scenario will be displayed (Not configured, defaults, custom filters).
$primary_filter_id     = array_search( 'okf_json@with-html-subfilter.fprm', $settings['primary_filter_id']['options'], true );
$secondary_filter_id   = array_search( 'okf_html@wordpress.fprm', $settings['secondary_filter_id']['options'], true );
$default_filters       = array(
	$primary_filter_id   => 'okf_json@with-html-subfilter.fprm',
	$secondary_filter_id => 'okf_html@wordpress.fprm',
);
$default_filters_exist = false;
$extra_filters_exist   = false;
$no_filters_exist      = false;

if ( $settings['primary_filter_id']['options'] === $default_filters ) {
	$default_filters_exist          = true;
	$options['primary_filter_id']   = $primary_filter_id;
	$options['secondary_filter_id'] = $secondary_filter_id;
	update_option( 'lingotek_defaults', $options );
} else {
	$num = count( array_diff_assoc( $settings['primary_filter_id']['options'], $default_filters ) );
	if ( $num > 0 ) {
		$extra_filters_exist = true;
	} else {
		$options['primary_filter_id']   = '';
		$options['secondary_filter_id'] = '';
		update_option( 'lingotek_defaults', $options );
		$no_filters_exist = true;
	}
}
unset( $settings['primary_filter_id']['options'][ $secondary_filter_id ] );
unset( $settings['secondary_filter_id']['options'][ $primary_filter_id ] );
?>

<h3><?php esc_html_e( 'Defaults', 'lingotek-translation' ); ?></h3>
<p class="description"><?php esc_html_e( 'The default automation settings and resources that should be used for this site.  These settings can be overriden using translation profiles and content type configuration.', 'lingotek-translation' ); ?></p>


<form id="lingotek-settings" method="post" action="admin.php?page=<?php echo esc_html( $page_key ); ?>" class="validate">
	<?php wp_nonce_field( $page_key, '_wpnonce_' . $page_key ); ?>

	<table class="form-table">
		<?php foreach ( $settings as $key => $setting ) { ?>
		<tr id="<?php echo esc_html( $key ) . '_row'; ?>">
			<th scope="row"><label for="<?php echo esc_html( $key ); ?>"><?php echo esc_html( $setting['label'] ); ?></label></th>
			<td>
				<select name="<?php echo esc_html( $key ); ?>" id="<?php echo esc_html( $key ); ?>">
					<?php
					foreach ( $setting['options'] as $option_id => $option_title ) {
						$selected = array_key_exists( $key, $options ) && ( $options[ $key ] === $option_id ) ? 'selected="selected"' : '';
						echo "\n\t<option value='" . esc_attr( $option_id ) . "' " . esc_html( $selected ) . '>' . esc_html( $option_title ) . '</option>';
					}
					?>
				</select>
				<?php
				if ( 'project_id' === $key ) {
					?>
									<?php

									if ( empty( $setting['options'] ) ) {
										?>
										<script> document.getElementById('project_id').style.display = 'none';</script>
										<input type="text" name="new_project" id="new_project" placeholder="<?php esc_html_e( 'Enter new project name', 'lingotek-translation' ); ?>" />
									<?php } else { ?>

										<input type="text" style="display:none" name="new_project" id="new_project" placeholder="<?php esc_html_e( 'Enter new project name', 'lingotek-translation' ); ?>" />
										<input type="checkbox" name="update_callback" id="update_callback"/>
										<label for="update_callback" id="callback_label"><?php esc_html_e( 'Update the callback url for this project.', 'lingotek-translation' ); ?></label>

										<br/><a href="#" id="create" onclick="toggleTextbox()" style="padding-left:3px; color:#999; font-size:80%; text-decoration:none"><b>+</b> <?php echo esc_html_e( 'Create New Project', 'lingotek-translation' ); ?></a>
									<?php } ?>
								<?php } ?>
			<!-- Code to handle displaying of Primary and Secondary Filters. -->
			<?php if ( $no_filters_exist ) { ?>
						<script> document.getElementById("primary_filter_id_row").style.display = "none";</script>
						<script> document.getElementById("secondary_filter_id_row").style.display = "none";</script>
						<?php
						if ( 'primary_filter_id' === $key ) {
							?>
							<tr id="filters_row"><th><?php esc_html_e( 'Filters', 'lingotek-translation' ); ?></th><td><i><?php esc_html_e( 'Not configured', 'lingotek-translation' ); ?></i></td></tr>
							<?php
						}
			}
			if ( $default_filters_exist ) {
				?>
						<script> document.getElementById("primary_filter_id_row").style.display = "none";</script>
						<script> document.getElementById("secondary_filter_id_row").style.display = "none";</script>
								<?php } ?>
			<!-- End of filter code. -->
		</tr>
			<?php
		}//end foreach
		?>
	</table>

	<p>
	<?php submit_button( __( 'Save Changes', 'lingotek-translation' ), 'primary', 'submit', false ); ?>
	<?php submit_button( __( 'Refresh Resources', 'lingotek-translation' ), 'secondary', 'refresh', false ); ?>
	</p>
</form>

<?php Lingotek_Workflow_Factory::lingotek_translation_professional_translation_info_modals(); ?>
