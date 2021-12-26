<!-- Redirect Access Token -->
<script>
jQuery(document).ready(function ($){
	var hash = window.location.hash;
	if (hash.length && hash.indexOf("access_token") !== -1) {
		$('.content').toggle();
		var regex = /^access_token=(\w{8}-\w{4}-\w{4}-\w{4}-\w{12})/;
		var access_token = regex.exec(hash.substr(1));
		var redirect_url = window.location.origin + window.location.pathname + window.location.search;
		if (access_token && access_token[1]) {
			var request_data = {
				'action': 'lingotek_authorization_action',
			}
			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				data: request_data,
				headers: {
					'Token': access_token[1],
				},
				success: function (response) {
					console.log('success')
				}
				,
				error: function (xhr, status, error) {
					console.log('failed', status, error)
				}
			}).then( function (data) {
				window.location.href = redirect_url;
			})
		}
	} else if (window.location.search.includes("connect")) {
		window.location.href = "<?php echo esc_url_raw( $connect_url ); ?>";
	}
});
</script>
<!-- Connect Your Account Button -->
<div class="loader content" hidden></div>
<div class="wrap content">
	<h2><?php esc_html_e( 'Connect Your Account', 'lingotek-translation' ); ?></h2>
	<div>
	<p class="description">
	<?php esc_html_e( 'Get started by clicking the button below to connect your Lingotek account to this WordPress installation.', 'lingotek-translation' ); ?>
	</p>
	<hr/>
	<p>
	<a class="button button-large button-hero" href="<?php echo esc_url_raw( $connect_account_cloak_url_new ); ?>">
		<img src="<?php echo esc_url_raw( LINGOTEK_URL ); ?>/img/lingotek-icon.png" style="padding: 0 4px 2px 0;" align="absmiddle"/> <?php esc_html_e( 'Connect New Account', 'lingotek-translation' ); ?>
	</a>
	</p>
	<hr/>
	<p class="description">
	<?php
		$allowed_html = array(
			'a' => array(
				'href' => array(),
			),
		);
		/* translators: %s: Connect to Lingotek url. */
		echo sprintf( wp_kses( __( 'Do you already have a Lingotek account? <a href="%s">Connect Lingotek Account</a>', 'lingotek-translation' ), $allowed_html ), esc_attr( $connect_account_cloak_url_prod ) )
		?>
	</p>
	</div>
</div>
<style>
.loader {
	border: 16px solid #dedede;
	border-top: 16px solid #3498db;
	border-radius: 50%;
	width: 100px;
	height: 100px;
	animation: spin 1.5s linear infinite;
	animation-direction: reverse;
	margin: 7em;
}

@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}
</style>
