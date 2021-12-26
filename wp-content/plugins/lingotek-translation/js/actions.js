/**
 * @file
 * Lingotek target actions JS code for lingotek target actions button.
 */
jQuery( document ).ready(
	function($) {
		'use strict';
		var $actionsElement = $( document ).find( '.lingotek-target-dropdown,.lingotek-source-dropdown' );
		// Attach event handlers to toggle button.
		$actionsElement.each(
			function () {
				var $this   = $( this );
				var $toggle = $this.find( '.lingotek-target-dropdown-toggle,.lingotek-source-dropdown-toggle' );
				$toggle.on(
					'click',
					function (e) {
						e.preventDefault();
						$this.toggleClass( 'open' );
					}
				);
				$this.on(
					'focusout',
					function (e) {
						setTimeout(
							function () {
								if ($this.has( document.activeElement ).length == 0) {
									// The focus left the action button group, hide actions.
									$this.removeClass( 'open' );
								}
							},
							1
						);
					}
				);
			}
		);
	}
);
