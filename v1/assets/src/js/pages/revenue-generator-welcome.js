/* global revenueGeneratorGlobalOptions rgGlobal*/

/**
 * JS to handle plugin welcome screen interactions.
 */

/**
 * Internal dependencies.
 */
import '../utils';

( function( $ ) {
	$( function() {
		function revenueGeneratorWelcome() {
			// Welcome screen elements.
			const $o = {
				body: $( 'body' ),

				// Welcome screen wrapper.
				welcomeScreenWrapper: $( '.rev-gen-welcome' ),

				// Welcome Cards.
				isContribution: $( '#rg_Contribution' ),
				isPaywall: $( '#rg_Paywall' ),
				laterpayTrackingStatus: $( '#welcome-screen-tracking' ),

				snackBar: $( '#rg_js_SnackBar' ),
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
					storeWelcomePage( 'contribution', lpayTrackingStatus );

					rgGlobal.sendLPGAEvent(
						WelcomeEventAction,
						WelcomeEventCategory,
						'Contributions',
						0,
						true
					);
				} );

				/**
				 * Triggers Paywall card Selection.
				 */
				$o.isPaywall.on( 'click', function() {
					let lpayTrackingStatus = 0;
					if ( $o.laterpayTrackingStatus.is( ':checked' ) ) {
						lpayTrackingStatus = 1;
					}

					storeWelcomePage( 'paywall', lpayTrackingStatus );

					rgGlobal.sendLPGAEvent(
						WelcomeEventAction,
						WelcomeEventCategory,
						'Paywall',
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
			};

			/*
			 * Update and Store Welcome Page setup done.
			 *
			 * @param {string} is_welcome_done value.
			 * @param {int} lpayTrackingStatus status of Laterpay Tracking.
			 * @return {void}
			 */
			const storeWelcomePage = function(
				isWelcomeDone,
				lpayTrackingStatus
			) {
				const formData = {
					action: 'rg_update_global_config',
					config_key: 'is_welcome_done',
					config_value: isWelcomeDone,
					rg_ga_enabled_status: lpayTrackingStatus,
					security:
						revenueGeneratorGlobalOptions.rg_global_config_nonce,
				};
				updateGlobalConfig(
					revenueGeneratorGlobalOptions.ajaxUrl,
					formData
				);
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

		revenueGeneratorWelcome();
	} );
} )( jQuery ); // eslint-disable-line no-undef
