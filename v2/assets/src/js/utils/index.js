/**
 * jQuery plugins used inside the plugin.
 */
( function( $ ) {
	/**
	 * Show snackbar with message.
	 *
	 * @param {string} message  Message to be displayed in snackbar.
	 * @param {number} duration Duration for setTimeout.
	 */
	$.fn.showSnackbar = function( message, duration ) {
		const $container = $( this );
		$container.text( message );
		$container.addClass( 'rev-gen-snackbar--show' );
		setTimeout( function() {
			$container.removeClass( 'rev-gen-snackbar--show' );
		}, duration );
	};
} )( jQuery ); // eslint-disable-line no-undef
