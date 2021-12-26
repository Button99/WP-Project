<div class="wrap">
	<h2><?php esc_html_e( 'Settings', 'lingotek-translation' ); ?></h2>

	<?php
	if ( strlen( $access_token ) ) {
		$sm = filter_input( INPUT_GET, 'sm' );
		?>

		<?php

		$menu_items = array(
			'account' => __( 'Account', 'lingotek-translation' ),
		);

		$community_required_menu_items = array(
			'defaults'    => __( 'Defaults', 'lingotek-translation' ),
			'preferences' => __( 'Preferences', 'lingotek-translation' ),
			'utilities'   => __( 'Utilities', 'lingotek-translation' ),
		);

		if ( false !== $community_id ) {
			$menu_items = array_merge( $menu_items, $community_required_menu_items );
		}

		?>

		<h3 class="nav-tab-wrapper">
		<?php
		$menu_item_index = 0;
		foreach ( $menu_items as $menu_item_key => $menu_item_label ) {
			$use_as_default = ( 0 === $menu_item_index && empty( $sm ) ) ? true : false;
			$alias          = null;
			// custom sub sub-menus.
			if ( ! empty( $sm ) && 'edit-profile' === $sm ) {
				$alias = 'profiles';
			}
			?>
			<a class="nav-tab
			<?php
			if ( $use_as_default || ( ! empty( $sm ) && $sm === $menu_item_key ) || $alias === $menu_item_key ) :
				?>
				nav-tab-active<?php endif; ?>"
				href="admin.php?page=<?php echo filter_input( INPUT_GET, 'page' ); ?>&amp;sm=<?php echo esc_html( $menu_item_key ); ?>"><?php echo esc_html( $menu_item_label ); ?></a>
			<?php
			$menu_item_index++;
		}
		?>
		</h3>

		<?php
		settings_errors();
		$submenu  = ! empty( $sm ) ? sanitize_text_field( $sm ) : 'account';
		$dir      = dirname( __FILE__ ) . '/settings/';
		$filename = $dir . 'view-' . $submenu . '.php';
		if ( file_exists( $filename ) ) {
			include $filename;
		} else {
			echo 'TO-DO: create <i>settings/view-' . esc_html( $submenu ) . '.php</i>';
		}
		?>

		<?php
	}//end if
	?>
</div>
<script>jQuery(document).ready(function($) { $('#wpfooter').remove(); } );</script>
