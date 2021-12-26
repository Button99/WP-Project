<div class="wrap">
  <h2><?php _e( 'Manage', 'lingotek-translation' ); ?></h2>

	<?php

	$menu_items = array(
		'content'       => __( 'Content Type Configuration', 'lingotek-translation' ),
		'profiles'      => __( 'Translation Profiles', 'lingotek-translation' ),
		'custom-fields' => __( 'Custom Fields', 'lingotek-translation' ),
		'string-groups' => __( 'String Groups', 'lingotek-translation' ),
		'strings'       => __( 'Strings', 'lingotek-translation' ),
	);

	?>

	<h3 class="nav-tab-wrapper">
	  <?php
		$menu_item_index = 0;
		foreach ( $menu_items as $menu_item_key => $menu_item_label ) {
			$use_as_default = ( $menu_item_index === 0 && ! isset( $_GET['sm'] ) ) ? true : false;
			?>

		<a class="nav-tab
			<?php
			if ( $use_as_default || ( isset( $_GET['sm'] ) && $_GET['sm'] == $menu_item_key ) ) :
				?>
			 nav-tab-active<?php endif; ?>"
		   href="admin.php?page=<?php echo $_GET['page']; ?>&amp;sm=<?php echo $menu_item_key; ?>"><?php echo $menu_item_label; ?></a>
			<?php
			 $menu_item_index++;
		}
		?>
	</h3>

	<?php
	settings_errors();
	$submenu  = isset( $_GET['sm'] ) ? sanitize_text_field( $_GET['sm'] ) : current( array_keys( $menu_items ) );
	$dir      = dirname( __FILE__ ) . '/manage/';
	$filename = $dir . 'view-' . $submenu . '.php';
	if ( file_exists( $filename ) ) {
		require $filename;
	} else {
		echo 'TO-DO: create <i>' . 'manage/view-' . $submenu . '.php</i>';
	}
	?>

</div>

<script>jQuery(document).ready(function($) { $('#wpfooter').remove(); } );</script>
