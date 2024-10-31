/* global revenueGeneratorGlobalOptions rgGlobal */
/**
 * JS to handle plugin dashboard screen interactions.
 */

/**
 * Internal dependencies.
 */
import '../utils';
import { RevGenModal } from '../utils/rev-gen-modal';
import { copyToClipboard } from '../helpers/index';

( function( $ ) {
	$( function() {
		function revenueGeneratorDashboard() {
			// Dashboard screen elements.
			const $o = {
				body: $( 'body' ),
				requestSent: false,

				// Paywall listing area elements.
				paywallContent: '.rev-gen-dashboard-content',
				paywallPreview: '.rev-gen-dashboard-content-paywall-preview',
				paywallContentWrapper: '.rev-gen-dashboard-content-paywall',

				// Delete Paywall link.
				removePaywallDashboard: '.rev-gen-dashboard-remove-paywall',

				newPaywall: $( '#rg_js_newPaywall' ),
				newContribution: $( '#rg_js_newContribution' ),
				sortPaywalls: $( '#rg_js_filterPaywalls' ),
				searchPaywall: $( '#rg_js_searchPaywall' ),
				editPayWallName: $( '.rev-gen-dashboard-paywall-name' ),
				paywallSearchIcon: $( '.rev-gen-dashboard-bar--search-icon' ),
				laterpayLoader: $( '.laterpay-loader-wrapper' ),
				contributionDelete: $(
					'.rev-gen-dashboard__contribution-delete'
				),
				contributionCopyShortcode: $(
					'.rev-gen-dashboard__link--copy-shortcode'
				),

				// Dashboard footer area.
				restartTour: $( '#rg_js_RestartTutorial' ),
				restartTourContribution: $(
					'#rg_js_RestartTutorial_Contribution'
				),
				snackBar: $( '#rg_js_SnackBar' ),
			};

			/**
			 * Bind all element events.
			 */
			const bindEvents = function() {
				/**
				 * Handle the next button events of the tour and update preview accordingly.
				 */
				$o.body.on( 'click', $o.paywallPreview, function() {
					const paywallId = $( this )
						.closest( $o.paywallContentWrapper )
						.attr( 'data-paywall-id' );
					if ( paywallId ) {
						window.location.href =
							revenueGeneratorGlobalOptions.paywallPageBase +
							'&current_paywall=' +
							paywallId;
					}
				} );

				/**
				 * Restart the Payall tour from Payall dashboard.
				 */
				$o.restartTour.on( 'click', function() {
					// Create form data.
					const formData = {
						action: 'rg_restart_tour',
						tour_type: 'paywall_tutorial_done',
						restart_tour: '1',
						security:
							revenueGeneratorGlobalOptions.rg_paywall_nonce,
					};

					// Delete the option.
					$.ajax( {
						url: revenueGeneratorGlobalOptions.ajaxUrl,
						method: 'POST',
						data: formData,
						dataType: 'json',
					} ).done( function( r ) {
						if ( true === r.success ) {
							window.location = $o.newPaywall.attr( 'href' );
						}
					} );
				} );

				/**
				 * Restart the contribution tour from Contribution Dashboard.
				 */
				$o.restartTourContribution.on( 'click', function() {
					// Create form Data.
					const formData = {
						action: 'rg_restart_tour',
						tour_type: 'is_contribution_tutorial_completed',
						restart_tour: '1',
						security:
							revenueGeneratorGlobalOptions.rg_paywall_nonce,
					};

					// Delete the option.
					$.ajax( {
						url: revenueGeneratorGlobalOptions.ajaxUrl,
						method: 'POST',
						data: formData,
						dataType: 'json',
					} ).done( function( r ) {
						if ( true === r.success ) {
							window.location = $o.newContribution.attr( 'href' );
						}
					} );
				} );

				/**
				 * Handle paywall sorting dropdown.
				 */
				$o.sortPaywalls.on( 'change', function() {
					const sortBy = $( this ).val();
					// Create form data.
					const formData = {
						action: 'rg_set_paywall_order',
						rg_current_url: window.location.href,
						rg_sort_order: sortBy,
						security:
							revenueGeneratorGlobalOptions.rg_paywall_nonce,
					};

					// Delete the option.
					$.ajax( {
						url: revenueGeneratorGlobalOptions.ajaxUrl,
						method: 'POST',
						data: formData,
						dataType: 'json',
					} ).done( function( r ) {
						if ( true === r.success && r.redirect_to ) {
							window.location.href = r.redirect_to;
						}
					} );
				} );

				/**
				 * When merchant starts to type in the search box blur out the paywall list area.
				 */
				$o.searchPaywall.on( 'focus', function() {
					$( $o.paywallContent ).addClass( 'blury' );
					$o.body.css( {
						height: '100%',
					} );
					const searchPaywallTerm = $( this )
						.val()
						.trim();
					if ( searchPaywallTerm.length ) {
						$o.searchPaywall.trigger( 'change' );
					}
				} );

				/**
				 * Revert back to original state once the focus is no more on search box.
				 */
				$o.searchPaywall.on( 'focusout', function() {
					$o.body.css( {
						height: 'auto',
					} );
					$( $o.paywallContent ).removeClass( 'blury' );
				} );

				/**
				 * Revert back to original state if merchant clicks on paywall list area.
				 */
				$( $o.paywallContent ).on( 'click', function() {
					$o.body.css( {
						overflow: 'auto',
						height: 'auto',
					} );
					$( $o.paywallContent ).removeClass( 'blury' );
				} );

				/**
				 * Handle dashboard search icon click event.
				 */
				$o.paywallSearchIcon.on( 'click', function() {
					showLoader();
					const searchPaywallTerm = $o.searchPaywall.val().trim();
					searchPaywall( searchPaywallTerm );
				} );

				/**
				 * Handle Search input on enter.
				 */
				$o.searchPaywall.on( 'keyup', function( event ) {
					// Check for enter key.
					if ( event.keyCode === 13 ) {
						$o.paywallSearchIcon.trigger( 'click' );
					}
				} );

				/**
				 * Handle the paywall title update.
				 */
				$o.editPayWallName.on( 'focusout', function() {
					if ( ! $o.requestSent ) {
						// Prevent duplicate requests.
						$o.requestSent = true;

						$( $o.paywallContent ).addClass( 'blury' );
						$o.body.css( {
							overflow: 'hidden',
							height: '100%',
						} );

						// Create form data.
						const formData = {
							action: 'rg_set_paywall_name',
							new_paywall_name: $( this )
								.text()
								.trim(),
							paywall_id: $( this )
								.closest( $o.paywallContentWrapper )
								.attr( 'data-paywall-id' ),
							security:
								revenueGeneratorGlobalOptions.rg_paywall_nonce,
						};

						// Update the title.
						$.ajax( {
							url: revenueGeneratorGlobalOptions.ajaxUrl,
							method: 'POST',
							data: formData,
							dataType: 'json',
						} ).done( function( r ) {
							$o.snackBar.showSnackbar( r.msg, 1500 );

							$o.body.css( {
								overflow: 'auto',
								height: 'auto',
							} );

							$( $o.paywallContent ).removeClass( 'blury' );

							// Release request lock.
							$o.requestSent = false;
						} );
					}
				} );

				/**
				 * Limit paywall name to 20 characters.
				 */
				$o.editPayWallName.on( 'keydown', function( e ) {
					const textlen = $( this )
						.text()
						.trim().length;
					if ( 20 <= textlen ) {
						// if more than 20 prevent allow following keys execept default case.
						switch ( e.keyCode ) {
							case 8: // Backspace
							case 9: // Tab
							case 13: // Enter
							case 37: // Left
							case 38: // Up
							case 39: // Right
							case 40: // Down
								break;
							default:
								const regex = new RegExp(
									'^[a-zA-Z0-9.,/ $@()]+$'
								);
								const key = e.key;
								// Block All Characters, Numbers and Special Characters.
								if ( regex.test( key ) ) {
									e.preventDefault();
									return false;
								}
								break;
						}
					}
				} );

				/**
				 * Handles Contribution deletion on confirmation.
				 */
				$o.contributionDelete.on( 'click', function( e ) {
					e.preventDefault();

					const $this = $( e.target );
					const contributionID = $this.data( 'id' );
					const nonce = $this.data( 'nonce' );

					new RevGenModal( {
						id: 'rg-modal-remove-contribution',
						templateData: {
							isEditable: parseInt(
								$this.data( 'editable' ),
								10
							),
						},
						onConfirm: async () => {
							$.ajax( {
								url: revenueGeneratorGlobalOptions.ajaxUrl,
								data: {
									action: 'rg_contribution_delete',
									security: nonce,
									id: contributionID,
								},
								success: ( r ) => {
									$o.snackBar.showSnackbar(
										r.data.msg,
										1500
									);

									setTimeout( () => {
										window.location.reload();
									}, 1500 );
								},
							} );
						},
						onCancel: () => {
							// noop
						},
					} );
				} );

				$o.contributionCopyShortcode.on( 'click', function( e ) {
					const code = $( e.target ).data( 'shortcode' );
					copyToClipboard( code );

					$o.snackBar.showSnackbar(
						revenueGeneratorGlobalOptions.rg_code_copy_msg,
						1500
					);
				} );

				/**
				 * Remove the paywall after merchant confirmation.
				 */
				$o.body.on( 'click', $o.removePaywallDashboard, function() {
					const paywallId = $( this ).attr( 'data-paywall-id' );
					const eventLabel = $( this )
						.closest( '.rev-gen-dashboard-content-paywall-info' )
						.find( '.rev-gen-dashboard-paywall-name' )
						.text()
						.trim();

					new RevGenModal( {
						id: 'rg-modal-remove-paywall',
						onConfirm: async () => {
							$.ajax( {
								url: revenueGeneratorGlobalOptions.ajaxUrl,
								method: 'POST',
								data: {
									action: 'rg_remove_paywall',
									id: paywallId,
									security:
										revenueGeneratorGlobalOptions.rg_paywall_nonce,
								},
								success: ( r ) => {
									// Show message and remove the overlay.
									$o.snackBar.showSnackbar( r.msg, 1500 );
									// Send GA Event.
									const eventCategory =
										'LP RevGen Configure Paywall';
									const eventAction = 'Paywall Deleted';
									rgGlobal.sendLPGAEvent(
										eventAction,
										eventCategory,
										eventLabel,
										0,
										true
									);

									// waits until snackbar is shown and delete event is sent.
									setTimeout( function() {
										window.location.reload();
									}, 1500 );
								},
							} );
						},
						onCancel: () => {
							// do nothing.
						},
					} );
				} );
			};

			/**
			 * Show the loader.
			 */
			const showLoader = function() {
				$o.laterpayLoader.css( {
					display: 'flex',
				} );
			};

			/**
			 * Search paywall based on merchants search term.
			 *
			 * @param {string} searchTerm The part of name being searched for.
			 */
			const searchPaywall = function( searchTerm ) {
				// prevent duplicate requests.
				if ( ! $o.requestSent ) {
					$o.requestSent = true;

					// Has something input.
					if ( searchTerm.length ) {
						// Search and display resultset.
						// Create form data.
						const formData = {
							action: 'rg_search_paywall',
							rg_current_url: window.location.href,
							search_term: searchTerm,
							security:
								revenueGeneratorGlobalOptions.rg_paywall_nonce,
						};

						// Delete the option.
						$.ajax( {
							url: revenueGeneratorGlobalOptions.ajaxUrl,
							method: 'POST',
							data: formData,
							dataType: 'json',
						} ).done( function( r ) {
							$o.requestSent = false;
							if ( true === r.success && r.redirect_to ) {
								window.location.href = r.redirect_to;
							}
						} );
					} else {
						const url = new URL( window.location.href );
						const params = url.searchParams;

						// Delete the search_term parameter.
						params.delete( 'search_term' );
						url.search = params.toString();
						const dashboardURL = url.toString();

						// redirects to dashboard url.
						window.location.href = dashboardURL;
					}
				}
			};

			// Initialize all required events.
			const initializePage = function() {
				bindEvents();
			};
			initializePage();
		}

		revenueGeneratorDashboard();
	} );
} )( jQuery ); // eslint-disable-line no-undef
