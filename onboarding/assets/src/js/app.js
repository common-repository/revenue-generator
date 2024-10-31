/* global rgOptions, rgGlobal */

/**
 * JS to handle plugin welcome screen interactions.
 */

/**
 * Internal dependencies.
 */
import '../../../../v2/assets/src/js/utils';
import { addQueryArgs } from '@wordpress/url';

( function( $, options ) {
	$( function() {
		function rgv2Welcome() {
			// Welcome screen elements.
			const $o = {
				body: $( 'body' ),
				snackBar: $( '#rgv2_js_snackBar' ),

				// Welcome screen wrapper.
				welcomeScreenWrapper: $( '.rgv2-welcome' ),

				// Welcome Cards.
				isContribution: $( '#rgv2_Contribution' ),
				laterpayTrackingStatus: $( '#welcome-screen-tracking' ),

				// Connect account.
				connectWelcomeButtons: $( '#rgv2_js_connect_welcome_buttons' ),
				connectTrigger: $( '#rgv2_js_connect_account' ),
				connectForm: $( '#rgv2_js_connect_form' ),
			};

			/**
			 * Bind all element events.
			 */
			const bindEvents = function() {
				const WelcomeEventCategory = 'LP RevGen';
				const WelcomeEventAction = 'Welcome Landing Page';

				/**
				 * Triggers Contribution card Selection.
				 */
				$o.isContribution.on( 'click', function() {
					let lpayTrackingStatus = 0;
					if ( $o.laterpayTrackingStatus.is( ':checked' ) ) {
						lpayTrackingStatus = 1;
					}

					rgGlobal.sendLPGAEvent(
						WelcomeEventAction,
						WelcomeEventCategory,
						'Contributions',
						0,
						true
					);
				} );

				/**
				 * Toggles checked attribute on click event.
				 */
				$o.laterpayTrackingStatus.on( 'click', function() {
					if ( 'checked' === $( this ).attr( 'checked' ) ) {
						$( this ).attr( 'checked', 'checked' );
					} else {
						$( this ).removeAttr( 'checked' );
					}
				} );

				$o.connectTrigger.on( 'click', function( e ) {
					e.preventDefault();

					$o.connectWelcomeButtons.hide();
					$o.connectForm.addClass( 'show' );
				} );

				$( 'input', $o.connectForm ).on( 'keyup', function() {
					const $clientID = $( '#rgv2-client-id' );
					const $clientSecret = $( '#rgv2-client-secret' );

					if ( $clientID.val() && $clientSecret.val() ) {
						$( 'button', $o.connectForm ).removeAttr( 'disabled' );
					} else {
						$( 'button', $o.connectForm ).attr(
							'disabled',
							'disabled'
						);
					}
				} );

				$o.connectForm.on( 'submit', function( e ) {
					e.preventDefault();

					const $form = $( e.target );
					const $button = $( 'button', $form );
					$form.addClass( 'loading' );

					let lpayTrackingStatus = 0;

					if ( $o.laterpayTrackingStatus.is( ':checked' ) ) {
						lpayTrackingStatus = 1;
					}

					$button
						.attr( 'disabled', 'disabled' )
						.text( $button.data( 'loading-text' ) );

					$.ajax( {
						url: options.ajaxUrl,
						method: 'POST',
						data: $form.serialize(),
						dataType: 'json',
					} ).done( function( r ) {
						let eventLabel = 'Success';

						if ( true === r.success ) {
							$o.snackBar.showSnackbar( r.msg, 1500 );

							let redirectURI = options.contributionDashboardURL;

							redirectURI = addQueryArgs( redirectURI, {
								revGenWelcome: 1,
							} );

							setTimeout( function() {
								window.location.href = redirectURI;
							}, 1000 );
						} else {
							eventLabel = 'Failure - ' + r.msg;

							$o.snackBar.showSnackbar( r.msg, 1500 );

							$button
								.removeAttr( 'disabled' )
								.text( $button.data( 'default-text' ) );
						}

						const eventCategory = 'LP RevGen Account';
						const eventAction = 'Connect Account';

						rgGlobal.sendLPGAEvent(
							eventAction,
							eventCategory,
							eventLabel,
							0,
							true
						);
					} );
				} );
			};

			/**
			 * Update the global config with provided value.
			 *
			 * @param {string} ajaxURL  AJAX URL.
			 * @param {Object} formData Form data to be submitted.
			 */
			const updateGlobalConfig = function( ajaxURL, formData ) {
				$.ajax( {
					url: ajaxURL,
					method: 'POST',
					data: formData,
					dataType: 'json',
				} ).done( function( r ) {
					$o.snackBar.showSnackbar( r.msg, 500 );
					$o.welcomeScreenWrapper.fadeOut( 500, function() {
						window.location.reload();
					} );
				} );
			};

			// Initialize all required events.
			const initializePage = function() {
				bindEvents();
			};
			initializePage();
		}

		rgv2Welcome();
	} );
} )( jQuery, rgOptions ); // eslint-disable-line no-undef
