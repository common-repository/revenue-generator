/* global rgOptions */
/**
 * JS to handle plugin settings screen interactions.
 */

/**
 * Internal dependencies.
 */
import '../utils';
import { debounce } from '../helpers';

( function( $ ) {
	function revGenSettings() {
		// Settings screen elements.
		const $o = {
			body: $( 'body' ),
			requestSent: false,
			somethingChanged: false,

			// Settings Elements
			settingsClientID: '.rev-gen-settings-main-client-id',
			settingsClientSecret: '.rev-gen-settings-main-client-secret',
			settingsModalClose: '.rev-gen-settings-main-info-modal-cross',

			rgDashboard: '.rev-gen-dashboard-main',

			// HelpModel
			helpGAButton: '.rev-gen-settings-main-option-info',
			helpGAModal: '.rev-gen-settings-main-info-modal',

			// The hightlight rows
			rgGALaterpayRow: '.rev-gen-laterpay-row',
			rgGAUserRow: '.rev-gen-user-row',

			// Settings Action items.
			laterpayLoader: $( '.laterpay-loader-wrapper' ),
			rgLayoutWrapper: $( '.rev-gen-layout-wrapper' ),
			settingsWrapper: $( '.rev-gen-settings-main' ),
			settingsPerMonth: $( '.rev-gen-settings-main-post-per-month' ),
			settingsGAUserID: $( '.rev-gen-settings-main-ga-code-user' ),
			settingsSaveButton: $( '.rev-gen-settings-main-save-settings' ),
			gaUserStatus: $( '#gaUserStatus' ),
			gaLaterPayStatus: $( '#gaLaterpayStatus' ),
			// Popup.
			snackBar: $( '#rg_js_snackBar' ),
			everyInput: $( 'table.rev-gen-settings-main-table input' ),
			triggerPopup: $( '.triggerPopup' ),
		};

		/**
		 * Bind all element events.
		 */
		const bindEvents = function() {
			$o.settingsSaveButton.on(
				'click',
				debounce( function() {
					// check Lock
					if ( ! $o.requestSent ) {
						// Add lock.
						$o.requestSent = true;
						let UserStatus = 0;
						let LaterPayStatus = 0;

						const clientID = $( $o.settingsClientID ).val();
						const clientSecret = $( $o.settingsClientSecret ).val();
						const gaUserId = $o.settingsGAUserID.val();

						if ( $o.gaUserStatus.is( ':checked' ) ) {
							UserStatus = $o.gaUserStatus.val();
						}

						if ( $o.gaLaterPayStatus.is( ':checked' ) ) {
							LaterPayStatus = $o.gaLaterPayStatus.val();
						}

						// Create form Data.
						const formData = {
							action: 'rgv2_update_settings',
							client_id: clientID,
							client_secret: clientSecret,
							personal_ga_ua_id: gaUserId,
							ga_personal_enabled_status: UserStatus,
							ga_enabled_status: LaterPayStatus,
							security: rgOptions.rg_global_config_nonce,
						};

						// Display Loader.
						showLoader();
						// Update Global Configurations.
						updateGlobalConfig( formData );
						// Release request lock.
						$o.requestSent = false;
						$o.somethingChanged = false;
					}
				}, 500 )
			);

			/**
			 * Toggles checked attribute on click event.
			 */
			$o.settingsPerMonth.on( 'click', function() {
				$o.settingsPerMonth.removeAttr( 'checked' );
				$( this ).prop( 'checked', true );
			} );

			/**
			 * Check if anything changed.
			 */
			$o.everyInput.on( 'change', function() {
				$o.somethingChanged = true;
			} );

			/**
			 * Prevent User from leaving if there is unsaved changes.
			 */
			$( window ).on( 'beforeunload', function( e ) {
				if ( $o.somethingChanged ) {
					e.preventDefault();
					return false;
				}
			} );

			/**
			 * Hide Help Modal on click of wrapper.
			 */
			$o.rgLayoutWrapper.on( 'click', function() {
				if ( $o.helpGAModal && $o.helpGAModal.length > 0 ) {
					$( $o.helpGAModal ).remove();
					$o.body.removeClass( 'modal-blur' );
					$( $o.rgGAUserRow ).css( 'background-color', 'inherit' );
					$( $o.rgGALaterpayRow ).css(
						'background-color',
						'inherit'
					);
					$o.body.find( 'input' ).removeClass( 'input-blur' );
				}
			} );

			/**
			 * Handle tooltip button events for info modals.
			 */
			$o.body.on( 'click', $o.helpGAButton, function() {
				const infoButton = $( this );
				const modalType = infoButton.attr( 'data-info-for' );
				const existingModal = $o.settingsWrapper.find( $o.helpGAModal );

				// Remove any existing modal.
				if ( existingModal.length ) {
					$o.body.removeClass( 'modal-blur' );
					$o.body.find( 'input' ).removeClass( 'input-blur' );
					existingModal.remove();
				} else {
					const template = wp.template(
						`rev-gen-info-${ modalType }`
					);
					$o.settingsWrapper.append( template() );

					// Change background color and highlight the clicked parent.
					$o.body.addClass( 'modal-blur' );
					$o.body.find( 'input' ).addClass( 'input-blur' );
					// Highlight selected info modal parent based on type.
					if ( 'user' === modalType ) {
						$( $o.rgGAUserRow )
							.find( 'input' )
							.removeClass( 'input-blur' );
						$( $o.rgGALaterpayRow ).removeAttr( 'style' );
						$( $o.rgGAUserRow ).css( 'background-color', '#fff' );
					} else {
						$( $o.rgGALaterpayRow )
							.find( 'input' )
							.removeClass( 'input-blur' );
						$( $o.rgGAUserRow ).removeAttr( 'style' );
						$( $o.rgGALaterpayRow ).css(
							'background-color',
							'#fff'
						);
					}
				}
			} );

			/**
			 * Hide the existing help popup.
			 */
			$o.body.on( 'click', $o.settingsModalClose, function() {
				$( $o.helpGAModal ).remove();
				$o.body.removeClass( 'modal-blur' );
				$( $o.rgGAUserRow ).css( 'background-color', 'inherit' );
				$( $o.rgGALaterpayRow ).css( 'background-color', 'inherit' );
				$o.body.find( 'input' ).removeClass( 'input-blur' );
			} );
		};

		/**
		 * Show the loader.
		 */
		const showLoader = function() {
			$o.laterpayLoader.css( 'display', 'flex' );
		};

		/**
		 * Hide the loader.
		 */
		const hideLoader = function() {
			$o.laterpayLoader.hide();
		};

		/**
		 * Updates global configuration and display message popup.
		 *
		 * @param {Object} formData Form Data.
		 * @return {void}
		 */
		const updateGlobalConfig = function( formData ) {
			// Update the title.
			$.ajax( {
				url: rgOptions.ajaxUrl,
				method: 'POST',
				data: formData,
				dataType: 'json',
			} ).done( function( r ) {
				hideLoader();
				$o.snackBar.showSnackbar( r.data.msg, 1500 );

				if ( r.data.merchant ) {
					validBorder( $o.settingsMerchantID );
					validBorder( $o.settingsMerchantKey );
				} else {
					invalidBorder( $o.settingsMerchantID );
					invalidBorder( $o.settingsMerchantKey );
				}
				// Release request lock.
				$o.requestSent = false;
			} );
		};

		/**
		 * Adds valid Border.
		 *
		 * @param {string} element
		 * @return {void}
		 */
		const validBorder = function( element ) {
			$( element ).css( 'border-color', '#19e4ac' );
			setTimeout( function() {
				$( element ).removeAttr( 'style' );
			}, 5000 );
		};

		/**
		 * Adds Invalid Border.
		 *
		 * @param {string} element
		 * @return {void}
		 */
		const invalidBorder = function( element ) {
			$( element ).css( 'border-color', '#ff1939' );
			setTimeout( function() {
				$( element ).removeAttr( 'style' );
			}, 5000 );
		};

		// Initialize all required events.
		const initializePage = function() {
			bindEvents();
		};

		/**
		 * Inject GA Script.
		 *
		 * @param {boolean} injectNow
		 * @return {Window} returns script loaded.
		 */
		const injectGAScript = function( injectNow ) {
			if ( true === injectNow ) {
				// This injector script is for GA have made minor modifications to fix linting issue.
				( function( i, s, o, g, r, a, m ) {
					i.GoogleAnalyticsObject = r;
					i[ r ] =
						i[ r ] ||
						function() {
							( i[ r ].q = i[ r ].q || [] ).push( arguments );
						};
					i[ r ].l = 1 * new Date();
					a = s.createElement( o );
					m = s.getElementsByTagName( o )[ 0 ];
					a.async = 1;
					a.src = g;
					m.parentNode.insertBefore( a, m );
				} )(
					window,
					document,
					'script',
					'https://www.google-analytics.com/analytics.js',
					'rgga'
				);
				return window[ window.GoogleAnalyticsObject || 'rgga' ];
			}
		};

		/**
		 * Send Event to Laterpay.
		 *
		 * @param {boolean} injectNow
		 * @param {string} eventlabel
		 * @param {string} eventAction
		 * @param {string} eventCategory
		 * @param {string} eventValue
		 * @param {string} eventInteraction
		 * @return {void}
		 */
		const sendParentEvent = function(
			injectNow,
			eventlabel,
			eventAction,
			eventCategory,
			eventValue,
			eventInteraction
		) {
			const rgga = injectGAScript( injectNow );
			if ( typeof rgga === 'function' ) {
				rgga(
					'create',
					rgOptions.rg_tracking_id,
					'auto',
					'rgParentTracker'
				);
				rgga( 'rgParentTracker.send', 'event', {
					eventCategory,
					eventAction,
					eventLabel: eventlabel,
					eventValue,
					nonInteraction: eventInteraction,
				} );
			}
		};

		/**
		 * Send event to User GA.
		 *
		 * @param {boolean} injectNow
		 * @param {string} eventlabel
		 * @param {string} eventAction
		 * @param {string} eventCategory
		 * @param {string} eventValue
		 * @param {string} eventInteraction
		 * @return {void}
		 */
		const sendUserEvent = function(
			injectNow,
			eventlabel,
			eventAction,
			eventCategory,
			eventValue,
			eventInteraction
		) {
			const rgga = injectGAScript( injectNow );
			if ( typeof rgga === 'function' ) {
				rgga(
					'create',
					rgOptions.rg_user_tracking_id,
					'auto',
					'rgUserTracker'
				);
				rgga( 'rgUserTracker.send', 'event', {
					eventCategory,
					eventAction,
					eventLabel: eventlabel,
					eventValue,
					nonInteraction: eventInteraction,
				} );
			}
		};

		/**
		 * Create a tracker and send event to GA.
		 *
		 * @param {string} gaTracker
		 * @param {string} trackingId
		 * @param {string} trackerName
		 * @param {string} eventAction
		 * @param {string} eventLabel
		 * @param {string} eventCategory
		 * @param {string} eventValue
		 * @param {string} eventInteraction
		 * @return {void}
		 */
		const createTrackerAndSendEvent = function(
			gaTracker,
			trackingId,
			trackerName,
			eventAction,
			eventLabel,
			eventCategory,
			eventValue,
			eventInteraction
		) {
			gaTracker( 'create', trackingId, 'auto', trackerName );
			gaTracker( trackerName + '.send', 'event', {
				eventCategory,
				eventAction,
				eventLabel,
				eventValue,
				nonInteraction: eventInteraction,
			} );
		};

		// Detect if GA is Enabled by MonsterInsights Plugin.
		const detectMonsterInsightsGA = function() {
			if (
				typeof window.mi_track_user === 'boolean' &&
				true === window.mi_trac_user
			) {
				return window[ window.GoogleAnalyticsObject || '__gaTracker' ];
			}
		};

		window.rgGlobal = {
			// Send GA Event conditionally.
			sendLPGAEvent(
				eventAction,
				eventCategory,
				eventLabel,
				eventValue,
				eventInteraction
			) {
				if ( 'undefined' === typeof eventInteraction ) {
					eventInteraction = false;
				}

				let sentUserEvent = false;
				const __gaTracker = detectMonsterInsightsGA();
				let trackers = '';
				const userUAID = rgOptions.rg_tracking_id;
				const rgUAID = rgOptions.rg_tracking_id;

				if ( userUAID.length > 0 && rgUAID.length > 0 ) {
					if ( typeof __gaTracker === 'function' ) {
						trackers = __gaTracker.getAll();
						trackers.forEach( function( tracker ) {
							if ( userUAID === tracker.get( 'trackingId' ) ) {
								sentUserEvent = true;
								const trackerName = tracker.get( 'name' );
								__gaTracker( trackerName + '.send', 'event', {
									eventCategory,
									eventAction,
									eventLabel,
									eventValue,
									nonInteraction: eventInteraction,
								} );
							}
						} );

						if ( true === sentUserEvent ) {
							createTrackerAndSendEvent(
								rgUAID,
								'rgParentTracker',
								eventAction,
								eventLabel,
								eventCategory,
								eventValue,
								eventInteraction
							);
						} else {
							createTrackerAndSendEvent(
								__gaTracker,
								rgUAID,
								'rgParentTracker',
								eventAction,
								eventLabel,
								eventCategory,
								eventValue,
								eventInteraction
							);
							createTrackerAndSendEvent(
								__gaTracker,
								userUAID,
								'rgUserTracker',
								eventAction,
								eventLabel,
								eventCategory,
								eventValue,
								eventInteraction
							);
						}
					} else {
						sendParentEvent(
							true,
							eventLabel,
							eventAction,
							eventCategory,
							eventValue,
							eventInteraction
						);
						sendUserEvent(
							true,
							eventLabel,
							eventAction,
							eventCategory,
							eventValue,
							eventInteraction
						);
					}
				} else if ( userUAID.length > 0 && rgUAID.length === 0 ) {
					if ( typeof __gaTracker === 'function' ) {
						trackers = __gaTracker.getAll();
						trackers.forEach( function( tracker ) {
							if ( userUAID === tracker.get( 'trackingId' ) ) {
								sentUserEvent = true;
								const trackerName = tracker.get( 'name' );
								__gaTracker( trackerName + '.send', 'event', {
									eventCategory,
									eventAction,
									eventLabel,
									eventValue,
									nonInteraction: eventInteraction,
								} );
							}
						} );

						if ( true !== sentUserEvent ) {
							sendUserEvent(
								true,
								eventLabel,
								eventAction,
								eventCategory,
								eventValue,
								eventInteraction
							);
						}
					} else {
						sendUserEvent(
							true,
							eventLabel,
							eventAction,
							eventCategory,
							eventValue,
							eventInteraction
						);
					}
				} else if ( userUAID.length === 0 && rgUAID.length > 0 ) {
					if ( typeof __gaTracker === 'function' ) {
						createTrackerAndSendEvent(
							__gaTracker,
							rgUAID,
							'rgParentTracker',
							eventAction,
							eventLabel,
							eventCategory,
							eventValue,
							eventInteraction
						);
					} else {
						sendParentEvent(
							true,
							eventLabel,
							eventAction,
							eventCategory,
							eventValue,
							eventInteraction
						);
					}
				}
			},
		};

		initializePage();
	}

	$( document ).ready( function() {
		revGenSettings();
	} );
} )( jQuery ); // eslint-disable-line no-undef
