/* global revenueGeneratorGlobalOptions, Shepherd, tippy, rgGlobal */
/**
 * JS to handle plugin paywall preview screen interactions.
 *
 */

/**
 * Internal dependencies.
 */

/* global Event */
import '../utils';
import { debounce } from '../helpers';
import { __, sprintf } from '@wordpress/i18n';
import { RevGenModal } from '../utils/rev-gen-modal';

( function( $ ) {
	$( function() {
		function revenueGeneratorPaywallPreview() {
			// Paywall screen elements.
			const $o = {
				body: $( 'body' ),

				requestSent: false,
				isPublish: false,

				// Preview wrapper and Contribution Wrapper.
				previewWrapper: $(
					'.rev-gen-preview-main, .rev-gen-contribution-main'
				),
				layoutWrapper: $( '.rev-gen-layout-wrapper' ),
				laterpayLoader: $( '.laterpay-loader-wrapper' ),
				noResultsWrapper: '.rev-gen-preview-main-no-result',

				// Contribution elements for account modal.
				contributionBox: $( '.rev-gen-contribution-main--box' ),

				// Search elements.
				searchContentWrapper: $( '.rev-gen-preview-main--search' ),
				searchContent: $( '#rg_js_searchContent' ),
				currentPaywall: $( '#rg_currentPaywall' ),
				searchResultWrapper: $(
					'.rev-gen-preview-main--search-results'
				),
				searchResultItem: '.rev-gen-preview-main--search-results-item',

				// Post elements.
				postPreviewWrapper: $( '#rg_js_postPreviewWrapper' ),
				postTitle: '.rev-gen-preview-main--post--title',
				postExcerpt: $( '#rg_js_postPreviewExcerpt' ),
				postContent: $( '#rg_js_postPreviewContent' ),

				// Overlay elements.
				purchaseOverlay: $( '#rg_js_purchaseOverlay' ),
				purchaseOverlayRemove: '.rg-purchase-overlay-remove',
				purchaseOptionItems: '.rg-purchase-overlay-purchase-options',
				purchaseOptionItem:
					'.rg-purchase-overlay-purchase-options-item',
				purchaseOptionItemInfo:
					'.rg-purchase-overlay-purchase-options-item-info',
				purchaseOptionItemTitle:
					'.rg-purchase-overlay-purchase-options-item-info-title',
				purchaseOptionItemDesc:
					'.rg-purchase-overlay-purchase-options-item-info-description',
				purchaseItemPriceIcon:
					'.rg-purchase-overlay-purchase-options-item-price-icon',
				purchaseOptionItemPrice:
					'.rg-purchase-overlay-purchase-options-item-price-span',
				purchaseOptionPriceSymbol:
					'.rg-purchase-overlay-purchase-options-item-price-symbol',
				purchaseOptionAction:
					'.rg-purchase-overlay-purchase-options-item-actions',
				optionArea: '.rg-purchase-overlay-option-area',
				addOptionArea: '.rg-purchase-overlay-option-area-add-option',
				previewSecondItem:
					'.rg-purchase-overlay-purchase-options .option-item-second',

				// Action buttons
				editOption: '.rg-purchase-overlay-option-edit',

				// Option manager.
				optionRemove: '.rg-purchase-overlay-option-remove',
				purchaseOptionType: '#rg_js_purchaseOptionType',
				optionManager: '.rg-purchase-overlay-option-manager',
				individualPricingWrapper:
					'.rg-purchase-overlay-option-manager-pricing',
				individualPricingSelection:
					'.rg-purchase-overlay-option-pricing-selection',
				purchaseRevenueWrapper:
					'.rg-purchase-overlay-option-manager-revenue',
				purchaseRevenueSelection:
					'.rg-purchase-overlay-option-revenue-selection',
				durationWrapper: '.rg-purchase-overlay-option-manager-duration',
				periodCountSelection:
					'.rg-purchase-overlay-option-manager-duration-count',
				periodSelection:
					'.rg-purchase-overlay-option-manager-duration-period',
				entitySelection: '.rg-purchase-overlay-option-manager-entity',

				// Paywall publish actions.
				addPaywall: '#rj_js_addNewPaywall',
				gotoDashboard: '#rg_js_gotoDashboard',
				actionsWrapper: $( '.rev-gen-preview-main--paywall-actions' ),
				actionButtons: $(
					'.rev-gen-preview-main--paywall-actions-update'
				),
				activatePaywall: $( '#rg_js_activatePaywall' ),
				savePaywall: $( '#rg_js_savePaywall' ),
				searchPaywallContent: $( '#rg_js_searchPaywallContent' ),
				searchPost: $( '#rg_js_searchPost' ),
				applyToSelect: $(
					'.rev-gen-preview-main--paywall-actions-apply select'
				),
				select2Multiple: $(
					'#rg_js_searchPaywallContent, #rg_js_searchPost'
				),
				searchPaywallWrapper: $(
					'.rev-gen-preview-main--paywall-actions-search'
				),
				searchPostWrapper: $(
					'.rev-gen-preview-main--paywall-actions-search-post'
				),
				paywallName: $( '.rev-gen-preview-main-paywall-name' ),

				// Keep orginal paywall name in record for GA edit event.
				paywallNameData: $( '.rev-gen-preview-main-paywall-name' )
					.text()
					.trim(),

				paywallTitle: '.rg-purchase-overlay-title',
				paywallDesc: '.rg-purchase-overlay-description',
				paywallAppliesTo: '.rev-gen-preview-main-paywall-applies-to',

				// Currency modal.
				currencyRadio: '[name=currency]',

				// Purchase options info modal.
				purchaseOptionInfoButton: '.rg-purchase-overlay-option-info',
				purchaseOptionInfoModal: '.rev-gen-preview-main-info-modal',

				// Account activation modal.
				accountActionId: '#rev-gen-merchant-id',
				accountActionKey: '#rev-gen-api-key',
				activateSignup: '#rg_js_activateSignup',
				warningSignup: '#rg_js_warningSignup',
				viewPost: '#rg_js_viewPost',
				viewDashboard: '#rg_js_viewDashboard',

				// Tour elements.
				exitTour: '.rev-gen-exit-tour',

				snackBar: $( '#rg_js_SnackBar' ),
				emailSupportButton: $( '.rev-gen-email-support' ),
			};

			/**
			 * Bind all element events.
			 */
			const bindEvents = function() {
				/**
				 * When the page has loaded, load the post content.
				 */
				$( document ).ready( function() {
					$o.postPreviewWrapper.fadeIn( 'slow' );

					$o.applyToSelect.select2( {
						width: 'auto',
						dropdownAutoWidth: true,
						dropdownCssClass: ':all:',
					} );

					// Highlight search bar and add tooltip, change background-color for wrapper.
					if ( $( $o.noResultsWrapper ).length ) {
						$o.layoutWrapper.css( {
							'background-color': 'rgba(0, 0, 0, 0.5)',
							'min-height': '800px',
						} );
						$o.searchContentWrapper.css( {
							'background-color': '#fff',
						} );
						const tippyInstance = tippy(
							document.querySelector(
								'.rev-gen-preview-main--search'
							),
							{
								arrow: tippy.roundArrow,
							}
						);
						tippyInstance.show();
					}

					$( $o.paywallAppliesTo ).trigger( 'change' );

					// Get all purchase options.
					const allPurchaseOptions = $( $o.purchaseOptionItems );
					if ( allPurchaseOptions.length ) {
						// Store individual pricing.
						const individualOption = allPurchaseOptions.find(
							"[data-purchase-type='individual']"
						);
						const pricingType = individualOption.attr(
							'data-pricing-type'
						);
						if ( 'dynamic' === pricingType ) {
							individualOption
								.find( $o.purchaseItemPriceIcon )
								.show();
							// Initialize tooltip for element.
							initializeTooltip(
								'.rg-purchase-overlay-purchase-options-item-price-icon'
							);
						}
					}

					// Start the welcome tour.
					if (
						( revenueGeneratorGlobalOptions.globalOptions
							.average_post_publish_count.length &&
							0 ===
								parseInt(
									revenueGeneratorGlobalOptions.globalOptions
										.paywall_tutorial_done
								) &&
							allPurchaseOptions &&
							allPurchaseOptions.length > 0 ) ||
						0 ===
							parseInt(
								revenueGeneratorGlobalOptions.has_paywalls
							)
					) {
						const tour = initializeTour();
						addTourSteps( tour );
						startWelcomeTour( tour );
					}

					// Check if there are already preselected option on mutltiselect and trigger click event twice to reset placeholder.
					const preSelectedOptions = $o.select2Multiple.val();
					if ( preSelectedOptions && preSelectedOptions.length > 0 ) {
						const mutipleSelect2 = $(
							'.select2-selection--multiple .select2-search.select2-search--inline'
						);
						// Open up search.
						mutipleSelect2.trigger( 'click' );
						// Close Search.
						mutipleSelect2.trigger( 'click' );
					}
				} );

				/**
				 * Adds data attribute if custom title or description is added to purchase option.
				 */
				$o.body.on( 'click', $o.purchaseOptionItemTitle, function() {
					this.addEventListener( 'input', function() {
						$( this )
							.closest( $o.purchaseOptionItem )
							.attr( 'data-custom-title', '1' );
					} );
				} );

				$o.body.on( 'click', $o.purchaseOptionItemDesc, function() {
					this.addEventListener( 'input', function() {
						$( this )
							.closest( $o.purchaseOptionItem )
							.attr( 'data-custom-desc', '1' );
					} );
				} );

				/**
				 * Handle the next button events of the tour and update preview accordingly.
				 */
				$o.body.on( 'click', '.shepherd-button', function() {
					const currentStep = Shepherd.activeTour.getCurrentStep();
					const stepId = currentStep.id;
					$( $o.purchaseOptionItem ).css( {
						'background-color': 'darkgray',
					} );

					$( $o.purchaseOptionItemInfo ).css( {
						'border-right': '1px solid #928d8d',
					} );

					if ( 'rg-purchase-option-item' === stepId ) {
						$( $o.previewSecondItem ).trigger( 'mouseenter' );
						$o.searchContentWrapper.css( {
							'background-color': '#a9a9a9',
						} );
						$( $o.previewSecondItem ).css( {
							'background-color': '#fff',
						} );
						$( $o.previewSecondItem )
							.find( $o.purchaseOptionItemInfo )
							.css( {
								'border-right': '1px solid #e3e4e6',
							} );
					} else if ( 'rg-purchase-option-item-price' === stepId ) {
						$( $o.previewSecondItem ).trigger( 'mouseleave' );
					} else if ( 'rg-purchase-option-paywall-name' === stepId ) {
						$( $o.paywallAppliesTo ).val( 'category' );
						$( $o.paywallAppliesTo ).trigger( 'change' );
					} else if (
						'rg-purchase-option-paywall-publish' === stepId
					) {
						$o.activatePaywall.css( 'background-color', '#000' );
					}
				} );

				/**
				 * Complete the tour when exit tour is clicked.
				 */
				$o.body.on( 'click', $o.exitTour, function() {
					if (
						typeof Shepherd !== 'undefined' &&
						typeof Shepherd.activeTour !== 'undefined'
					) {
						Shepherd.activeTour.complete();
					}
				} );

				/**
				 * Hide the post content search if not in focus and revert back to original title.
				 */
				$o.postPreviewWrapper.on( 'click', function() {
					// Hide result wrapper and revert search box text if no action was taken.
					$o.searchContentWrapper.removeClass( 'searching' );
					$o.searchResultWrapper.hide();
					$o.searchContentWrapper.find( 'label' ).show();
					const searchText = $o.searchContent.val().trim();
					const postTitle = $( $o.postTitle )
						.text()
						.trim();
					if ( searchText !== postTitle ) {
						$o.searchContent.val( postTitle );
					}
				} );

				/**
				 * Handle the event when merchant has clicked a post for preview.
				 */
				$o.body.on( 'click', $o.searchResultItem, function() {
					const searchItem = $( this );
					const searchPostID = searchItem.attr( 'data-id' );
					showPreviewContent( searchPostID );
				} );

				/**
				 * When merchant starts to type in the search box blur out the rest of the area.
				 */
				$o.searchContent.on( 'focus', function() {
					$o.postPreviewWrapper.addClass( 'blury' );
					$( 'html, body' ).animate( { scrollTop: 0 }, 'slow' );
					$o.body.css( {
						overflow: 'hidden',
						height: '100%',
					} );
				} );

				/**
				 * When merchant click in the search box blur out the rest of the area and prompt if existing.
				 */
				$o.searchContent.on( 'click', function() {
					// Check for existing paywall.
					const paywallId = $o.currentPaywall.val();
					if ( ! paywallId ) {
						return false;
					}

					new RevGenModal( {
						id: 'rg-modal-search-paywall-warning',
						onConfirm: async () => {
							$o.searchContent.focus();
						},
					} );
				} );

				/**
				 * Revert back to original state once the focus is no more on search box.
				 */
				$o.searchContent.on( 'focusout', function() {
					$o.body.css( {
						overflow: 'auto',
						height: 'auto',
					} );
					$o.postPreviewWrapper.removeClass( 'blury' );
				} );

				/**
				 * Handle preview content search input.
				 */
				$o.searchContent.on(
					'input change',
					debounce( function() {
						$o.searchContentWrapper.find( 'label' ).hide();
						const searchPostTerm = $( this )
							.val()
							.trim();
						const postTitle = $( $o.postTitle )
							.text()
							.trim();
						if (
							searchPostTerm.length &&
							searchPostTerm !== postTitle
						) {
							searchPreviewContent( searchPostTerm );
						}
					}, 500 )
				);

				/**
				 * Add combobox with search for categories.
				 */
				$o.searchPaywallContent.select2( {
					ajax: {
						url: revenueGeneratorGlobalOptions.ajaxUrl,
						dataType: 'json',
						delay: 500,
						type: 'POST',
						data( params ) {
							return {
								term: params.term,
								action: 'rg_search_term',
								security:
									revenueGeneratorGlobalOptions.rg_paywall_nonce,
							};
						},
						processResults( data ) {
							const options = [];
							if ( data ) {
								$.each( data.categories, function( index ) {
									const term = data.categories[ index ];
									options.push( {
										id: term.term_id,
										text: term.name,
									} );
								} );
							}
							return {
								results: options,
							};
						},
						cache: true,
					},
					width: 'auto',
					dropdownAutoWidth: true,
					dropdownCssClass: ':all:',
					placeholder: __( 'Search', 'revenue-generator' ),
					language: {
						inputTooShort() {
							return __(
								'Please enter 1 or more characters.',
								'revenue-generator'
							);
						},
						noResults() {
							return __(
								'No results found.',
								'revenue-generator'
							);
						},
					},
					minimumInputLength: 1,
					closeOnSelect: false,
				} );

				/**
				 * Handle change of current category to clear our meta data.
				 */
				$o.searchPaywallContent.on( 'change', function() {
					const categoryIds = $( this ).val();
					const jsonCategoryIds = JSON.stringify( categoryIds );

					// Remove current meta.
					if ( jsonCategoryIds ) {
						removeCurrentCategoryMeta( jsonCategoryIds );
					}

					if ( jsonCategoryIds ) {
						$o.postPreviewWrapper.attr(
							'data-access-id',
							jsonCategoryIds
						);
						$o.savePaywall.removeAttr( 'disabled' );
						$o.activatePaywall.removeAttr( 'disabled' );
					}
				} );

				$o.searchPost.select2( {
					ajax: {
						url: revenueGeneratorGlobalOptions.ajaxUrl,
						dataType: 'json',
						delay: 500,
						type: 'POST',
						data( params ) {
							return {
								term: params.term,
								action: 'rg_search_post',
								security:
									revenueGeneratorGlobalOptions.rg_paywall_nonce,
							};
						},
						processResults( data ) {
							const options = [];
							if ( data ) {
								$.each( data.posts, function( index ) {
									const post = data.posts[ index ];
									options.push( {
										id: post.ID,
										text: post.post_title,
									} );
								} );
							}
							return {
								results: options,
							};
						},
						cache: true,
					},
					dropdownAutoWidth: true,
					dropdownCssClass: ':all:',
					placeholder: __( 'search', 'revenue-generator' ),
					language: {
						inputTooShort() {
							return __(
								'Please enter 1 or more characters.',
								'revenue-generator'
							);
						},
						noResults() {
							return __(
								'No results found.',
								'revenue-generator'
							);
						},
					},
					minimumInputLength: 1,
					closeOnSelect: false,
				} );

				/*
				 * Adds Placeholder in select2 on close and hide options.
				 */
				$o.select2Multiple.on( 'select2:close', function() {
					const parentMutiplediv = $( this )
						.siblings( 'span.select2' )
						.find( '.select2-selection--multiple' );
					const count = $( this ).select2( 'data' ).length;
					const select2Counter = parentMutiplediv.find(
						'.select2-selection__rendered .select2-search--inline input'
					);
					select2Counter.attr(
						'placeholder',
						count + ' items selected'
					);

					// Setting width dynamically as it has to overwrite default dynamic width.
					select2Counter.css( 'width', '100%' );
				} );

				/**
				 * Handle change of current post.
				 */
				$o.searchPost.on( 'change', function() {
					const specificPosts = $( this ).val();
					const jsonSpecificPosts = JSON.stringify( specificPosts );
					if ( specificPosts ) {
						$o.searchPostWrapper.attr(
							'data-access-id',
							jsonSpecificPosts
						);
						$o.savePaywall.removeAttr( 'disabled' );
						$o.activatePaywall.removeAttr( 'disabled' );
					}
				} );

				/**
				 * Hide the existing option manager if open by any chance.
				 */
				$o.previewWrapper.on( 'click', function( e ) {
					const currentTarget = $( e.target );
					const isEditButton = currentTarget.parent(
						'.rg-purchase-overlay-option-edit'
					).length;

					/**
					 * Hide other existing option manager in the view if parent is not the manager element
					 * and no modal is show currently.
					 */
					if (
						! currentTarget.parents(
							'.rg-purchase-overlay-option-manager'
						).length &&
						! $( '.rev-gen-modal' ).length &&
						! isEditButton
					) {
						e.preventDefault();
						const opendOptionManagers = $o.body.find(
							'.rg-purchase-overlay-option-manager'
						);

						$.each( opendOptionManagers, function(
							key,
							opendOptionManager
						) {
							// Don't trigger when there is no manager open.
							if (
								'none' !==
								$( opendOptionManager ).css( 'display' )
							) {
								$( opendOptionManager ).trigger(
									'option-manager-close'
								);
							}
						} );
					}

					if (
						! currentTarget.parents(
							'.rg-purchase-overlay-option-manager'
						).length
					) {
						if ( $( '.pricing-info-modal' ).length ) {
							$o.body
								.find( '#pricing-info-modal' )
								.trigger( 'click' );
						}

						if ( $( '.revenue-info-modal' ).length ) {
							$o.body
								.find( '#revenue-info-modal' )
								.trigger( 'click' );
						}
					}
				} );

				/**
				 * Add action items on purchase item hover.
				 */
				$o.body.on( 'mouseenter', $o.purchaseOptionItem, function() {
					// Hide the paywall border.
					$o.purchaseOverlay
						.children( '.rg-purchase-overlay-highlight' )
						.hide();
					$( $o.purchaseOverlayRemove ).hide();

					const actionOptions = $( this ).find(
						'.rg-purchase-overlay-purchase-options-item-actions'
					);
					const actionsExist = actionOptions.length;
					const optionHighlight = $( this ).children(
						'.rg-purchase-overlay-purchase-options-item-highlight'
					);

					// Show action options if it already exist, else add it.
					if ( actionsExist ) {
						actionOptions.show();
						$( optionHighlight ).show();
					} else {
						// Get the template for purchase overlay action.
						const actionTemplate = wp.template(
							'revgen-purchase-overlay-actions'
						);

						const overlayMarkup = actionTemplate();

						// Highlight the current option being edited.
						$( optionHighlight ).show();

						// Add purchase option actions to the highlighted item.
						$( this ).prepend( overlayMarkup );
					}
				} );

				/**
				 * Remove action items when purchase item is not being edited.
				 */
				$o.body.on( 'mouseleave', $o.purchaseOptionItem, function() {
					const optionHighlight = $( this ).children(
						'.rg-purchase-overlay-purchase-options-item-highlight'
					);
					optionHighlight.hide();

					if (
						! $o.body.find(
							'.rg-purchase-overlay-option-manager:visible'
						).length
					) {
						$( this )
							.find(
								'.rg-purchase-overlay-purchase-options-item-actions'
							)
							.hide();
					}
				} );

				/**
				 * Handle purchase option edit operations.
				 */
				$o.body.on( 'click', $o.editOption, function() {
					const $this = $( this );

					const optionItem = $( this ).parents(
						'.rg-purchase-overlay-purchase-options-item'
					);
					let actionManager = optionItem.find(
						'.rg-purchase-overlay-option-manager'
					);

					const optionHighlight = $( this ).children(
						'.rg-purchase-overlay-purchase-options-item-highlight'
					);

					// Get all purchase options.
					const allPurchaseOptions = $( $o.purchaseOptionItems );
					let doesIndividualOptionExist = false;

					// Check if an individual option exist.
					allPurchaseOptions
						.children( $o.purchaseOptionItem )
						.each( function() {
							if (
								'individual' ===
								$( this ).attr( 'data-purchase-type' )
							) {
								doesIndividualOptionExist = true;
							}
						} );

					if ( ! actionManager.length ) {
						const entityType = optionItem.attr(
							'data-purchase-type'
						);

						// Send the data to our new template function, get the HTML markup back.
						const data = {
							entityType,
						};

						// Get the template for purchase overlay action.
						const actionTemplate = wp.template(
							'revgen-purchase-overlay-item-manager'
						);

						const actionMarkup = actionTemplate( data );

						// Add purchase option manager to the selected item.
						optionItem.prepend( actionMarkup );

						actionManager = optionItem.find(
							'.rg-purchase-overlay-option-manager'
						);
						const pricingManager = actionManager.find(
							'.rg-purchase-overlay-option-manager-entity'
						);

						$( '.rg-purchase-overlay-option-manager div' ).css( {
							'border-bottom-color': '#e3e4e6',
						} );
						$( '.rg-purchase-overlay-option-manager select' ).css( {
							'border-color': '#e3e4e6',
						} );

						// Duration selection.
						const periodSelection = actionManager.find(
							$o.durationWrapper
						);

						if ( 'individual' !== entityType ) {
							// hide pricing type selection if not individual.
							const dynamicPricing = actionManager.find(
								$o.individualPricingWrapper
							);
							dynamicPricing.hide();

							// show period selection if not individual.
							periodSelection
								.find( $o.periodSelection )
								.val(
									optionItem.attr( 'data-expiry-duration' )
								);
							periodSelection
								.find( $o.periodCountSelection )
								.val( optionItem.attr( 'data-expiry-period' ) );
							periodSelection.show();

							if ( doesIndividualOptionExist ) {
								const individualOption = pricingManager
									.find( 'option' )
									.filter( '[value=individual]' );
								individualOption.attr( 'disabled', true );
							}
						} else {
							periodSelection.hide();
							// Set pricing model for selected option.
							const pricingType = optionItem.attr(
								'data-pricing-type'
							);
							const pricingWrapper = actionManager.find(
								$o.individualPricingWrapper
							);
							if ( 'dynamic' === pricingType ) {
								pricingWrapper
									.find( $o.individualPricingSelection )
									.prop( 'checked', true );
								pricingWrapper
									.find( '.static-pricing' )
									.addClass( 'unchecked' );
							} else {
								pricingWrapper
									.find( $o.individualPricingSelection )
									.prop( 'checked', false );
								pricingWrapper
									.find( '.dynamic-pricing' )
									.addClass( 'unchecked' );
							}
						}

						const revenueWrapper = actionManager.find(
							$o.purchaseRevenueWrapper
						);
						if ( 'subscription' === entityType ) {
							// Add extra height to get proper styling.
							actionManager.find( 'div' ).css( {
								height: ' 55px',
							} );
							revenueWrapper.hide();
						} else {
							// Set revenue model for selected option.
							const priceItem = optionItem.find(
								$o.purchaseOptionItemPrice
							);
							const revenueModel = priceItem.attr(
								'data-pay-model'
							);
							if ( 'ppu' === revenueModel ) {
								revenueWrapper
									.find( $o.purchaseRevenueSelection )
									.prop( 'checked', true );
								revenueWrapper
									.find( '.pay-now' )
									.addClass( 'unchecked' );
							} else {
								revenueWrapper
									.find( $o.purchaseRevenueSelection )
									.prop( 'checked', false );
								revenueWrapper
									.find( '.pay-later' )
									.addClass( 'unchecked' );
							}
							revenueWrapper.show();
						}
					} else {
						if ( doesIndividualOptionExist ) {
							const pricingManager = actionManager.find(
								'.rg-purchase-overlay-option-manager-entity'
							);
							const individualOption = pricingManager
								.find( 'option' )
								.filter( '[value=individual]' );
							individualOption.attr( 'disabled', true );
						}

						actionManager.toggle();
						optionHighlight.show();
					}

					$( '.rev-gen__select2', actionManager ).each( function() {
						$( this ).select2( {
							width: 'auto',
							dropdownAutoWidth: true,
							dropdownParent: $( this ).parent(),
							dropdownCssClass: ':all:',
							minimumResultsForSearch: -1,
						} );
					} );

					/**
					 * This is done to keep current state of triggered action manger
					 * not be changed by the hiding of all others.
					 */
					const actionManagerCurrentState = actionManager.css(
						'display'
					);
					const actionOptions = optionItem.find(
						'.rg-purchase-overlay-purchase-options-item-actions'
					);

					// Hide other open option managers.
					$o.body
						.find( '.rg-purchase-overlay-option-manager' )
						.hide();
					$o.body
						.find(
							'.rg-purchase-overlay-purchase-options-item-actions'
						)
						.hide();
					actionOptions.show();

					// The following code checks if title and descirption is customized.
					if ( 'none' === actionManagerCurrentState ) {
						const isCustomTitle = optionItem.attr(
							'data-custom-title'
						);
						const isCustomDesc = optionItem.attr(
							'data-custom-desc'
						);

						// Generate Dynamic Title and Description.
						dynamicTitleDescription(
							$this,
							isCustomTitle,
							isCustomDesc
						);
					}
					// Reset current action manager back to original state.
					actionManager.css( {
						display: actionManagerCurrentState,
					} );
				} );

				/**
				 * Custom Event to tigger close option mangaer and generate dynamic title and description.
				 */
				$o.body.on(
					'option-manager-close',
					$o.optionManager,
					function() {
						const $this = $( this );

						$this.hide();

						const purchaseOptionItem = $this.closest(
							$o.purchaseOptionItem
						);

						const isCustomTitle = purchaseOptionItem.attr(
							'data-custom-title'
						);
						const isCustomDesc = purchaseOptionItem.attr(
							'data-custom-desc'
						);

						// Generate Dynamic Title and Description.
						dynamicTitleDescription(
							$this,
							isCustomTitle,
							isCustomDesc
						);
					}
				);

				/**
				 * Remove purchase option.
				 */
				$o.body.on( 'click', $o.optionRemove, function() {
					const purchaseItem = $( this ).parents(
						'.rg-purchase-overlay-purchase-options-item'
					);
					const type = purchaseItem.attr( 'data-purchase-type' );
					let entityId;

					switch ( type ) {
						case 'individual':
							entityId = purchaseItem.attr( 'data-paywall-id' );
							break;

						case 'timepass':
							entityId = purchaseItem.attr( 'data-tlp-id' );
							break;

						case 'subscription':
							entityId = purchaseItem.attr( 'data-sub-id' );
							break;
					}

					if ( 'individual' !== type && entityId ) {
						new RevGenModal( {
							id: 'rg-modal-purchase-option-update',
							templateData: {
								optionType: type,
							},
							onConfirm: async () => {
								purchaseItem.remove();
								reorderPurchaseItems();
								removePurchaseOption( type, entityId );
								$o.isPublish = true;
								$o.savePaywall.trigger( 'click' );
							},
						} );
					} else {
						purchaseItem.remove();
						reorderPurchaseItems();
						removePurchaseOption( type, entityId );
						$o.isPublish = true;
						$o.savePaywall.trigger( 'click' );
					}
				} );

				/**
				 * Handle change of purchase option type.
				 */
				$o.body.on( 'change', $o.purchaseOptionType, function() {
					const purchaseManager = $( this ).parents(
						'.rg-purchase-overlay-option-manager'
					);
					const pricingManager = purchaseManager.find(
						'.rg-purchase-overlay-option-manager-entity'
					);
					const staticPricingOptions = purchaseManager.find(
						$o.individualPricingWrapper
					);
					const revenueWrapper = purchaseManager.find(
						$o.purchaseRevenueWrapper
					);
					const durationWrapper = purchaseManager.find(
						$o.durationWrapper
					);

					// Hide dynamic pricing selection options if not Individual type.
					if ( 'individual' === pricingManager.val() ) {
						staticPricingOptions.show();
						durationWrapper.hide();
					} else {
						staticPricingOptions.hide();
						durationWrapper.show();
					}

					// Hide revenue mode selection options if not Subscription type.
					if ( 'subscription' === pricingManager.val() ) {
						revenueWrapper.hide();
					} else {
						revenueWrapper.show();
					}
				} );

				/**
				 * Handle revenue model change.
				 */
				$o.body.on(
					'change',
					$o.individualPricingSelection,
					function() {
						const optionItem = $( this ).parents(
							$o.purchaseOptionItem
						);
						const purchaseManager = $( this ).parents(
							'.rg-purchase-overlay-option-manager'
						);
						const pricingSelection = purchaseManager.find(
							$o.individualPricingSelection
						);
						const pricingWrapper = purchaseManager.find(
							$o.individualPricingWrapper
						);

						if ( pricingSelection.prop( 'checked' ) ) {
							const allPurchaseOptions = $(
								$o.purchaseOptionItems
							);
							const dynamicPrice = allPurchaseOptions.attr(
								'data-dynamic-price'
							);
							const dynamicRevenue = allPurchaseOptions.attr(
								'data-dynamic-revenue'
							);
							const priceItem = optionItem.find(
								$o.purchaseOptionItemPrice
							);
							priceItem.attr( 'data-pay-model', dynamicRevenue );
							const dynamicStar = $( $o.purchaseItemPriceIcon );
							const validatedPrice = validatePrice(
								dynamicPrice
							);
							dynamicStar.css( {
								display: 'none',
							} );
							priceItem.text( validatedPrice );
							optionItem.attr( 'data-pricing-type', 'dynamic' );
							optionItem.find( $o.purchaseItemPriceIcon ).show();
							pricingSelection.val( 1 );
							pricingWrapper
								.find( '.static-pricing' )
								.addClass( 'unchecked' );
							pricingWrapper
								.find( '.dynamic-pricing' )
								.removeClass( 'unchecked' );
						} else {
							optionItem.attr( 'data-pricing-type', 'static' );
							optionItem.find( $o.purchaseItemPriceIcon ).hide();
							pricingSelection.val( 0 );
							pricingWrapper
								.find( '.static-pricing' )
								.removeClass( 'unchecked' );
							pricingWrapper
								.find( '.dynamic-pricing' )
								.addClass( 'unchecked' );
						}
					}
				);

				/**
				 * Handle pricing type change for individual type.
				 */
				$o.body.on( 'change', $o.purchaseRevenueSelection, function() {
					const optionItem = $( this ).parents(
						$o.purchaseOptionItem
					);
					const purchaseManager = $( this ).parents(
						'.rg-purchase-overlay-option-manager'
					);
					const revenueSelection = purchaseManager.find(
						$o.purchaseRevenueSelection
					);
					const priceItem = optionItem.find(
						$o.purchaseOptionItemPrice
					);
					const currentRevenue = priceItem.attr( 'data-pay-model' );
					const currentValue = revenueSelection.val();
					const optionType = optionItem.attr( 'data-purchase-type' );
					const revenueWrapper = purchaseManager.find(
						$o.purchaseRevenueWrapper
					);

					let entityId;
					switch ( optionType ) {
						case 'individual':
							entityId = optionItem.attr( 'data-paywall-id' );
							break;
						case 'timepass':
							entityId = optionItem.attr( 'data-tlp-id' );
							break;
						case 'subscription':
							entityId = optionItem.attr( 'data-sub-id' );
							break;
					}

					// If a saved option is being edited, get confirmation.
					if ( 'individual' !== optionType && entityId ) {
						new RevGenModal( {
							id: 'rg-modal-purchase-option-update',
							templateData: {
								optionType,
							},
							onConfirm: async () => {
								if ( revenueSelection.prop( 'checked' ) ) {
									priceItem.attr( 'data-pay-model', 'ppu' );
									revenueSelection.val( 1 );
									validatePricingRevenue( optionItem, true );
									revenueWrapper
										.find( '.pay-later' )
										.removeClass( 'unchecked' );
									revenueWrapper
										.find( '.pay-now' )
										.addClass( 'unchecked' );
								} else {
									priceItem.attr( 'data-pay-model', 'sis' );
									revenueSelection.val( 0 );
									validatePricingRevenue( optionItem, false );
									revenueWrapper
										.find( '.pay-later' )
										.addClass( 'unchecked' );
									revenueWrapper
										.find( '.pay-now' )
										.removeClass( 'unchecked' );
								}
							},
							onCancel: async () => {
								priceItem.attr(
									'data-pay-model',
									currentRevenue
								);
								revenueSelection.val( currentValue );
								revenueSelection.attr(
									'checked',
									1 === parseInt( currentValue )
								);
								validatePricingRevenue(
									optionItem,
									1 === parseInt( currentValue )
								);
								if ( 1 === parseInt( currentValue ) ) {
									revenueWrapper
										.find( '.pay-later' )
										.removeClass( 'unchecked' );
									revenueWrapper
										.find( '.pay-now' )
										.addClass( 'unchecked' );
								} else {
									revenueWrapper
										.find( '.pay-later' )
										.addClass( 'unchecked' );
									revenueWrapper
										.find( '.pay-now' )
										.removeClass( 'unchecked' );
								}
							},
						} );
					}

					if ( revenueSelection.prop( 'checked' ) ) {
						priceItem.attr( 'data-pay-model', 'ppu' );
						revenueSelection.val( 1 );
						const newmsg = __(
							"You'll only be charged once you've reached $5.",
							'revenue-generator'
						);
						$( optionItem )
							.find( $o.purchaseOptionItemDesc )
							.text( newmsg );
						validatePricingRevenue( optionItem, true );
						revenueWrapper
							.find( '.pay-later' )
							.removeClass( 'unchecked' );
						revenueWrapper
							.find( '.pay-now' )
							.addClass( 'unchecked' );
					} else {
						priceItem.attr( 'data-pay-model', 'sis' );
						revenueSelection.val( 0 );
						const previewPostTitle = $o.searchContent.val();
						const newmsg = sprintf(
							/* translators: %1$s post title */
							__(
								'Get lifetime access to %1$s now!',
								'revenue-generator'
							),
							previewPostTitle
						);
						$( optionItem )
							.find( $o.purchaseOptionItemDesc )
							.text( newmsg );

						validatePricingRevenue( optionItem, false );
						revenueWrapper
							.find( '.pay-later' )
							.addClass( 'unchecked' );
						revenueWrapper
							.find( '.pay-now' )
							.removeClass( 'unchecked' );
					}
				} );

				/**
				 * Period selection change handler.
				 */
				$o.body.on( 'change', $o.periodSelection, function() {
					const purchaseManager = $( this ).parents(
						'.rg-purchase-overlay-option-manager'
					);
					const periodSelection = purchaseManager.find(
						$o.periodSelection
					);
					const periodCountSelection = purchaseManager.find(
						$o.periodCountSelection
					);
					changeDurationOptions(
						periodSelection.val(),
						periodCountSelection
					);
					const optionItem = $( this ).parents(
						$o.purchaseOptionItem
					);
					optionItem.attr(
						'data-expiry-duration',
						periodSelection.val()
					);
				} );

				/**
				 * Period count selection change handler.
				 */
				$o.body.on( 'change', $o.periodCountSelection, function() {
					const purchaseManager = $( this ).parents(
						'.rg-purchase-overlay-option-manager'
					);
					const periodCountSelection = purchaseManager.find(
						$o.periodCountSelection
					);
					const optionItem = $( this ).parents(
						$o.purchaseOptionItem
					);
					optionItem.attr(
						'data-expiry-period',
						periodCountSelection.val()
					);
				} );

				/**
				 * Handle price input and change.
				 */
				$o.body
					.on( 'focus', $o.purchaseOptionItemPrice, function() {
						const currentPrice = $( this )
							.text()
							.trim();
						$( this ).attr( 'data-current-value', currentPrice );
					} )
					.on(
						'input change',
						$o.purchaseOptionItemPrice,
						debounce( function() {
							const optionItem = $( this ).parents(
								$o.purchaseOptionItem
							);
							const priceItem = optionItem.find(
								$o.purchaseOptionItemPrice
							);
							const currentPrice = $( this ).attr(
								'data-current-value'
							);
							const newPrice = priceItem.text().trim();
							const priceSymbol = optionItem.find(
								$o.purchaseOptionPriceSymbol
							);
							const optionType = optionItem.attr(
								'data-purchase-type'
							);
							const currentRevenue = priceItem.attr(
								'data-pay-model'
							);

							const symbol = priceSymbol.text().trim();
							if (
								! symbol.length &&
								! revenueGeneratorGlobalOptions.globalOptions
									.merchant_currency.length
							) {
								showCurrencySelectionModal();
							}

							let entityId;
							switch ( optionType ) {
								case 'individual':
									entityId = optionItem.attr(
										'data-paywall-id'
									);
									break;
								case 'timepass':
									entityId = optionItem.attr( 'data-tlp-id' );
									break;
								case 'subscription':
									entityId = optionItem.attr( 'data-sub-id' );
									break;
							}

							// If a saved item is being updated, display warning.
							if ( 'individual' !== optionType && entityId ) {
								new RevGenModal( {
									id: 'rg-modal-purchase-option-update',
									templateData: {
										optionType,
									},
									onConfirm: async () => {
										const validatedPrice = validatePrice(
											newPrice,
											'subscription' === optionType
										);
										$( this )
											.empty()
											.text( validatedPrice );
										validateRevenue(
											validatedPrice,
											optionItem
										);
									},
									onCancel: async () => {
										const validatedPrice = validatePrice(
											currentPrice,
											'subscription' === optionType
										);
										$( this )
											.empty()
											.text( validatedPrice );
										validateRevenue(
											validatedPrice,
											optionItem
										);
									},
								} );
							} else {
								const validatedPrice = validatePrice(
									newPrice,
									'subscription' === optionType
								);
								const dynamicStar = $(
									$o.purchaseItemPriceIcon
								);
								dynamicStar.css( {
									display: 'none',
								} );
								$( this ).text( validatedPrice );
								validateRevenue( validatedPrice, optionItem );
							}

							// Display message when revenue chagnes based on price.
							if (
								currentRevenue !==
								priceItem.attr( 'data-pay-model' )
							) {
								if (
									'ppu' === priceItem.attr( 'data-pay-model' )
								) {
									$o.snackBar.showSnackbar(
										__(
											'Pay Now is only available for prices set to $1.99 or higher.',
											'revenue-generator'
										),
										2500
									);
								} else {
									$o.snackBar.showSnackbar(
										__(
											'Pay Later is only available for prices set less than $5.',
											'revenue-generator'
										),
										2500
									);
								}
							}

							reorderPurchaseItems();
						}, 1000 )
					);

				/**
				 * Handle paywall applicable dropdown.
				 */
				$o.body.on( 'change', $o.paywallAppliesTo, function() {
					if (
						'exclude_category' === $( this ).val() ||
						'category' === $( this ).val()
					) {
						$o.searchPostWrapper.hide();
						$o.searchPaywallWrapper.show();
						if (
							$o.searchPaywallContent.length &&
							null === $o.searchPaywallContent.val()
						) {
							$o.savePaywall.attr( 'disabled', true );
							$o.activatePaywall.attr( 'disabled', true );
						}
						const jsonCategoriesID = JSON.stringify(
							$o.searchPaywallContent.val()
						);

						$o.postPreviewWrapper.attr(
							'data-access-id',
							jsonCategoriesID
						);
					} else if ( 'specific_post' === $( this ).val() ) {
						$o.searchPaywallWrapper.hide();
						$o.searchPostWrapper.show();
						if (
							$o.searchPost.length &&
							null === $o.searchPost.val()
						) {
							$o.savePaywall.attr( 'disabled', true );
							$o.activatePaywall.attr( 'disabled', true );
						}
					} else {
						$o.savePaywall.removeAttr( 'disabled' );
						$o.activatePaywall.removeAttr( 'disabled' );
						$o.searchPaywallWrapper.hide();
						$o.searchPostWrapper.hide();
						$o.postPreviewWrapper.attr(
							'data-access-id',
							$o.postPreviewWrapper.attr( 'data-preview-id' )
						);
					}
				} );

				/**
				 * Add new option handler.
				 */
				$o.body.on( 'click', $o.addOptionArea, function() {
					// Only add if total count limit doesn't exceed.
					const currentOptionCount = $( $o.purchaseOptionItems ).find(
						$o.purchaseOptionItem
					).length;
					if ( currentOptionCount < 5 ) {
						// Get the template for default option.
						const optionItem = wp.template(
							'revgen-default-purchase-option-item'
						);
						$( $o.purchaseOptionItems ).append( optionItem );
					}

					// hide if we have added 5 options.
					if ( 5 <= currentOptionCount + parseInt( 1 ) ) {
						$( $o.optionArea ).hide();
					}

					reorderPurchaseItems();
				} );

				/**
				 * Handle tooltip button events for info modals.
				 */
				$o.body.on( 'click', $o.purchaseOptionInfoButton, function(
					e
				) {
					e.stopPropagation();

					const infoButton = $( this );
					const modalType = infoButton.attr( 'data-info-for' );

					$( '.rev-gen-modal, .rev-gen-modal-overlay' ).remove();

					new RevGenModal( {
						id: `rg-modal-info-${ modalType }`,
						closeOutside: true,
					} );

					let eventLabel = '';

					if ( 'revenue' === modalType ) {
						eventLabel = 'Pay Now v Pay Later';
					} else if ( 'pricing' === modalType ) {
						eventLabel = 'Static Pricing v Dynamic Pricing';
					}

					// Send GA Event.
					const eventCategory = 'LP RevGen Configure Paywall';
					const eventAction = 'Help';
					rgGlobal.sendLPGAEvent(
						eventAction,
						eventCategory,
						eventLabel,
						0,
						true
					);
				} );

				/**
				 * Add paywall border if hovering over the saved paywall area.
				 */
				$o.purchaseOverlay.on( 'mouseenter', function() {
					const paywall = $( $o.purchaseOptionItems );
					const paywallId = paywall.attr( 'data-paywall-id' );
					if ( paywallId.length ) {
						$o.purchaseOverlay
							.children( '.rg-purchase-overlay-highlight' )
							.show();
						$( $o.purchaseOverlayRemove ).show();
					}
				} );

				/**
				 * Hide the border and paywall remove button when not in focus.
				 */
				$o.purchaseOverlay.on( 'mouseleave', function() {
					$o.purchaseOverlay
						.children( '.rg-purchase-overlay-highlight' )
						.hide();
					$( $o.purchaseOverlayRemove ).hide();
				} );

				/**
				 * Remove the paywall after merchant confirmation.
				 */
				$o.body.on( 'click', $o.purchaseOverlayRemove, function() {
					new RevGenModal( {
						id: 'rg-modal-remove-paywall',
						onConfirm: async () => {
							const paywall = $( $o.purchaseOptionItems );
							const paywallId = paywall.attr( 'data-paywall-id' );

							// Create form data.
							const formData = {
								action: 'rg_remove_paywall',
								id: paywallId,
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
								const eventLabel = $o.paywallName.text().trim();

								// Show message and remove the overlay.
								$o.snackBar.showSnackbar( r.msg, 1500 );
								$o.purchaseOverlay.remove();

								// Update paywall actions bar with new button.
								$o.actionsWrapper.css( {
									width: '75%',
									'background-color':
										'rgba(239, 239, 239, 0.3)',
									'backdrop-filter': 'blur(10px)',
								} );

								// Get the template for confirmation popup and add it.
								const template = wp.template(
									'revgen-add-paywall'
								);
								$o.actionsWrapper.empty().append( template );

								// Add preview url for new paywall.
								if ( r.preview_id ) {
									$( $o.addPaywall ).attr(
										'data-preview-id',
										r.preview_id
									);
								}

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
							} );
						},
					} );
				} );

				/**
				 * Add new paywall from paywall publish action.
				 */
				$o.body.on( 'click', $o.addPaywall, function() {
					const postPreviewId = $( $o.addPaywall ).attr(
						'data-preview-id'
					);
					if ( postPreviewId ) {
						showPreviewContent( postPreviewId );
					}
				} );

				/**
				 * Handle the dashboard redirection on paywall deletion.
				 */
				$o.body.on( 'click', $o.gotoDashboard, function() {
					const dashboardURL = $( this ).attr( 'data-dashboard-url' );

					if ( dashboardURL ) {
						window.location.href = dashboardURL;
					}
				} );

				/**
				 * Limit paywall name to 20 characters.
				 */
				$o.paywallName.on( 'keydown', function( e ) {
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
				 * Send Google Anayltics Event on Paywall Name edit.
				 */
				$o.paywallName.on( 'focusout', function() {
					if (
						$o.paywallNameData !==
						$( this )
							.text()
							.trim()
					) {
						// Send GA Event.
						const eventCategory = 'LP RevGen Configure Paywall';
						const eventAction = 'Edit Title';
						const eventLabel =
							$( this )
								.text()
								.trim() +
							' - ' +
							$( $o.paywallTitle )
								.text()
								.trim();
						rgGlobal.sendLPGAEvent(
							eventAction,
							eventCategory,
							eventLabel,
							0,
							true
						);

						$o.paywallNameData = $( this )
							.text()
							.trim();
					}
				} );

				/**
				 * Handle the change of entity type i.e Individual, TimePass, Subscription.
				 */
				$o.body.on( 'change', $o.entitySelection, function() {
					const optionItem = $( this ).parents(
						$o.purchaseOptionItem
					);
					const currentType = optionItem.attr( 'data-purchase-type' );
					const selectedEntityType = $( this ).val();
					const optionManager = optionItem.find(
						'.rg-purchase-overlay-option-manager'
					);

					// We are using all div to add styling.
					const childsOfOptionManager = optionManager.find( 'div' );

					const editActionbutton = $( optionItem ).find(
						$o.editOption
					);

					let entityId;

					if ( currentType !== selectedEntityType ) {
						// Set the id based on current type.
						if ( 'subscription' === currentType ) {
							entityId = optionItem.attr( 'data-sub-id' );
						} else if ( 'timepass' === currentType ) {
							entityId = optionItem.attr( 'data-tlp-id' );
						} else if ( 'individual' === currentType ) {
							entityId = optionItem.attr( 'data-paywall-id' );
						}

						if ( 'individual' !== currentType && entityId ) {
							new RevGenModal( {
								id: 'rg-modal-purchase-option-update',
								templateData: {
									optionType: currentType,
								},
								onConfirm: async () => {
									removePurchaseOption(
										currentType,
										entityId,
										false
									);
									$o.isPublish = true;
									$o.savePaywall.trigger( 'click' );
								},
								onCancel: async () => {
									optionItem.attr(
										'data-purchase-type',
										currentType
									);
									$( this ).val( currentType );
									return false;
								},
							} );
						}

						// Remove all current attributes.
						optionItem.removeAttr( 'data-purchase-type' );
						optionItem.removeAttr( 'data-expiry-duration' );
						optionItem.removeAttr( 'data-expiry-period' );
						optionItem.removeAttr( 'data-pricing-type' );
						optionItem.removeAttr( 'data-paywall-id' );
						optionItem.removeAttr( 'data-tlp-id' );
						optionItem.removeAttr( 'data-sub-id' );
						optionItem.removeAttr( 'data-uid' );
						optionItem.removeAttr( 'data-order' );

						// Add empty options for a fresh option.
						optionItem.attr( 'data-order', '' );
						optionItem.attr( 'data-uid', '' );
						optionItem.attr(
							'data-purchase-type',
							selectedEntityType
						);
						$( this ).val( selectedEntityType );

						// Add type specific options.
						if ( 'individual' !== selectedEntityType ) {
							// Set default 1 Month period for changed option.

							const optionPrice = optionItem.find(
								$o.purchaseOptionItemPrice
							);
							optionPrice.removeAttr( 'data-pay-model' );

							if ( 'timepass' === selectedEntityType ) {
								const timePassDefaultValues =
									revenueGeneratorGlobalOptions.defaultConfig
										.timepass;

								// Default value for new time pass.
								optionItem.attr( 'data-tlp-id', '' );
								optionItem.attr(
									'data-expiry-duration',
									timePassDefaultValues.duration
								);
								optionItem.attr(
									'data-expiry-period',
									timePassDefaultValues.period
								);
								optionPrice.attr(
									'data-pay-model',
									timePassDefaultValues.revenue
								);
								optionPrice.text( timePassDefaultValues.price );

								// Set option item info.
								optionItem
									.find( $o.purchaseOptionItemTitle )
									.text( timePassDefaultValues.title );
								optionItem
									.find( $o.purchaseOptionItemDesc )
									.text( timePassDefaultValues.description );

								childsOfOptionManager.removeClass(
									'rg-purchase-overlay-option-manager-subscription'
								);
								optionManager
									.find( $o.periodCountSelection )
									.val( timePassDefaultValues.period );
								optionManager
									.find( $o.periodSelection )
									.val( timePassDefaultValues.duration );
							} else if (
								'subscription' === selectedEntityType
							) {
								const subscriptionDefaultValues =
									revenueGeneratorGlobalOptions.defaultConfig
										.subscription;

								// Default value for new subscription.
								optionItem.attr( 'data-sub-id', '' );
								optionItem.attr(
									'data-expiry-duration',
									subscriptionDefaultValues.duration
								);
								optionItem.attr(
									'data-expiry-period',
									subscriptionDefaultValues.period
								);
								optionPrice.attr(
									'data-pay-model',
									subscriptionDefaultValues.revenue
								);
								optionPrice.text(
									subscriptionDefaultValues.price
								);

								// Set option item info.
								optionItem
									.find( $o.purchaseOptionItemTitle )
									.text( subscriptionDefaultValues.title );
								optionItem
									.find( $o.purchaseOptionItemDesc )
									.text(
										subscriptionDefaultValues.description
									);

								childsOfOptionManager.addClass(
									'rg-purchase-overlay-option-manager-subscription'
								);
								optionManager
									.find( $o.periodCountSelection )
									.val( subscriptionDefaultValues.period );
								optionManager
									.find( $o.periodSelection )
									.val( subscriptionDefaultValues.duration );
							}
						} else {
							// Set static pricing by default if individual.
							optionItem.attr( 'data-pricing-type', 'static' );
							optionItem.attr( 'data-paywall-id', '' );
							childsOfOptionManager.removeClass(
								'rg-purchase-overlay-option-manager-subscription'
							);
						}
					}

					// Trigger click to update purchase options.
					editActionbutton.trigger( 'click' );
				} );

				/**
				 * Save Paywall and its purchase options.
				 */
				$o.savePaywall.on( 'click', function() {
					showLoader();
					reorderPurchaseItems();

					// Get all purchase options.
					const purchaseOptions = $( $o.purchaseOptionItems );

					/**
					 * Loop through Time Passes and Subscriptions and add unique ids
					 * so that created id can be added accordingly.
					 */
					purchaseOptions
						.children( $o.purchaseOptionItem )
						.each( function() {
							const uid = $( this ).attr( 'data-uid' );
							if ( ! uid ) {
								// To add appropriate ids after saving.
								$( this ).attr( 'data-uid', createUniqueID() );
							}
						} );

					// Store individual pricing.
					const individualOption = purchaseOptions.find(
						"[data-purchase-type='individual']"
					);
					let individualObj;

					/**
					 * Create individual purchase option data.
					 */
					if ( individualOption.length ) {
						individualObj = {
							title: individualOption
								.find( $o.purchaseOptionItemTitle )
								.text()
								.trim(),
							desc: individualOption
								.find( $o.purchaseOptionItemDesc )
								.text()
								.trim(),
							price: individualOption
								.find( $o.purchaseOptionItemPrice )
								.text()
								.trim(),
							revenue: individualOption
								.find( $o.purchaseOptionItemPrice )
								.attr( 'data-pay-model' ),
							type: individualOption.attr( 'data-pricing-type' ),
							order: individualOption.attr( 'data-order' ),
							custom_title: individualOption.attr(
								'data-custom-title'
							),
							custom_desc: individualOption.attr(
								'data-custom-desc'
							),
						};
					}

					// Store time pass pricing.
					const timePassOptions = purchaseOptions.find(
						"[data-purchase-type='timepass']"
					);
					const timePasses = [];

					/**
					 * Create time passes data array.
					 */
					timePassOptions.each( function() {
						const timePass = $( this );
						const timePassObj = {
							title: timePass
								.find( $o.purchaseOptionItemTitle )
								.text()
								.trim(),
							desc: timePass
								.find( $o.purchaseOptionItemDesc )
								.text()
								.trim(),
							price: timePass
								.find( $o.purchaseOptionItemPrice )
								.text()
								.trim(),
							revenue: $(
								timePass.find( $o.purchaseOptionItemPrice )
							).attr( 'data-pay-model' ),
							duration: $( timePass ).attr(
								'data-expiry-duration'
							),
							period: $( timePass ).attr( 'data-expiry-period' ),
							tlp_id: $( timePass ).attr( 'data-tlp-id' ),
							uid: $( timePass ).attr( 'data-uid' ),
							order: $( timePass ).attr( 'data-order' ),
							custom_title: $( timePass ).attr(
								'data-custom-title'
							),
							custom_desc: $( timePass ).attr(
								'data-custom-desc'
							),
						};
						timePasses.push( timePassObj );
					} );

					// Store subscription pricing.
					const subscriptionOptions = purchaseOptions.find(
						"[data-purchase-type='subscription']"
					);
					const subscriptions = [];

					/**
					 * Create subscriptions data array.
					 */
					subscriptionOptions.each( function() {
						const subscription = $( this );
						const subscriptionObj = {
							title: subscription
								.find( $o.purchaseOptionItemTitle )
								.text()
								.trim(),
							desc: subscription
								.find( $o.purchaseOptionItemDesc )
								.text()
								.trim(),
							price: subscription
								.find( $o.purchaseOptionItemPrice )
								.text()
								.trim(),
							revenue: $(
								subscription.find( $o.purchaseOptionItemPrice )
							).attr( 'data-pay-model' ),
							duration: $( subscription ).attr(
								'data-expiry-duration'
							),
							period: $( subscription ).attr(
								'data-expiry-period'
							),
							sub_id: $( subscription ).attr( 'data-sub-id' ),
							uid: $( subscription ).attr( 'data-uid' ),
							order: $( subscription ).attr( 'data-order' ),
							custom_title: $( subscription ).attr(
								'data-custom-title'
							),
							custom_desc: $( subscription ).attr(
								'data-custom-desc'
							),
						};
						subscriptions.push( subscriptionObj );
					} );

					const specificPosts = $o.searchPost.val();
					let jsonSpecificPosts = '';
					if ( specificPosts ) {
						jsonSpecificPosts = JSON.stringify( specificPosts );
					}

					/**
					 * Paywall data.
					 */
					const paywall = {
						id: purchaseOptions.attr( 'data-paywall-id' ),
						title: $o.purchaseOverlay
							.find( $o.paywallTitle )
							.text()
							.trim(),
						desc: $o.purchaseOverlay
							.find( $o.paywallDesc )
							.text()
							.trim(),
						name: $o.paywallName.text().trim(),
						applies: $( $o.paywallAppliesTo ).val(),
						specific_posts: jsonSpecificPosts,
						preview_id: $o.postPreviewWrapper.attr(
							'data-preview-id'
						),
					};

					/**
					 * Final data of paywall.
					 */
					const data = {
						action: 'rg_update_paywall',
						post_id: $o.postPreviewWrapper.attr( 'data-access-id' ),
						paywall,
						individual: individualObj,
						time_passes: timePasses,
						subscriptions,
						security:
							revenueGeneratorGlobalOptions.rg_paywall_nonce,
					};

					// Update paywall data.
					updatePaywall(
						revenueGeneratorGlobalOptions.ajaxUrl,
						data
					);
				} );

				/**
				 * Handle paywall activation.
				 */
				$o.activatePaywall.on( 'click', function() {
					if (
						0 ===
						parseInt(
							revenueGeneratorGlobalOptions.globalOptions
								.is_merchant_verified
						)
					) {
						showAccountActivationModal();
					} else {
						let entityId;
						const purchaseOptionItems = $( $o.purchaseOptionItem );
						let isNewItem = false;

						$.each( purchaseOptionItems, function(
							key,
							purchaseOptionItem
						) {
							const optionItem = $( purchaseOptionItem );
							const purchaseType = optionItem.attr(
								'data-purchase-type'
							);

							switch ( purchaseType ) {
								case 'individual':
									entityId = optionItem.attr(
										'data-paywall-id'
									);
									break;
								case 'timepass':
									entityId = optionItem.attr( 'data-tlp-id' );
									break;
								case 'subscription':
									entityId = optionItem.attr( 'data-sub-id' );
									break;
							}

							// If its new item set flag.
							if (
								! isNewItem &&
								! entityId &&
								'individual' !== purchaseType
							) {
								isNewItem = true;
							}
						} );

						if ( isNewItem ) {
							new RevGenModal( {
								id: 'rg-modal-purchase-option-update',
								templateData: {
									optionType: 'newPurchaseOption',
								},
								onConfirm: async () => {
									/**
									 * Save paywall data to get new paywall id and wait for some time to publish the paywall.
									 */
									$o.isPublish = true;
									$o.savePaywall.trigger( 'click' );
									$o.savePaywall.addClass( 'hide' );
									showLoader();
									setTimeout( function() {
										publishPaywall();
										hideLoader();
										$o.isPublish = false;
									}, 1500 );
								},
							} );
						} else {
							/**
							 * Save paywall data to get new paywall id and wait for some time to publish the paywall.
							 */
							$o.isPublish = true;
							$o.savePaywall.trigger( 'click' );
							$o.savePaywall.addClass( 'hide' );
							showLoader();
							setTimeout( function() {
								publishPaywall();
								hideLoader();
								$o.isPublish = false;
							}, 1500 );
						}
					}
				} );

				/**
				 * Handle signup link event for activate signup button.
				 */
				$o.body.on( 'click', $o.activateSignup, function() {
					if (
						revenueGeneratorGlobalOptions.globalOptions
							.merchant_region.length
					) {
						const currentRegion =
							revenueGeneratorGlobalOptions.globalOptions
								.merchant_region;
						const signUpURL =
							revenueGeneratorGlobalOptions.signupURL;
						if ( 'US' === currentRegion ) {
							window.open( signUpURL.US, '_blank' );
						} else {
							window.open( signUpURL.EU, '_blank' );
						}
					}
				} );

				/**
				 * Handle signup link event for warning signup button.
				 */
				$o.body.on( 'click', $o.warningSignup, function() {
					if (
						revenueGeneratorGlobalOptions.globalOptions
							.merchant_region.length
					) {
						const currentRegion =
							revenueGeneratorGlobalOptions.globalOptions
								.merchant_region;
						const signUpURL =
							revenueGeneratorGlobalOptions.signupURL;
						if ( 'US' === currentRegion ) {
							window.open( signUpURL.US, '_blank' );
						} else {
							window.open( signUpURL.EU, '_blank' );
						}
					}
				} );

				/**
				 * Handle merchant id input.
				 */
				$o.body.on( 'input change', $o.accountActionId, function() {
					if ( areCredentialsFilled() ) {
						$( 'body #rg_js_modal_confirm' ).removeAttr(
							'disabled'
						);
					}
				} );

				/**
				 * Handle merchant key input.
				 */
				$o.body.on( 'input change', $o.accountActionKey, function() {
					if ( areCredentialsFilled() ) {
						$( 'body #rg_js_modal_confirm' ).removeAttr(
							'disabled'
						);
					}
				} );

				/**
				 * Handle currency change radio.
				 */
				$o.body.on( 'input change', $o.currencyRadio, function() {
					if ( $o.currencyRadio ) {
						$( '#rg_js_modal_confirm', $o.body ).removeAttr(
							'disabled'
						);
					}
				} );

				/**
				 * Open paywall target post.
				 */
				$o.body.on( 'click', $o.viewPost, function() {
					const targetPostId = $( this ).attr( 'data-target-id' );
					const selectedCategoryId = $o.searchPaywallContent.val();
					const appliesTo = $( $o.paywallAppliesTo ).val();
					const specificPostIDs = $o.searchPost.val();

					if ( targetPostId ) {
						viewPost(
							targetPostId,
							selectedCategoryId,
							appliesTo,
							specificPostIDs
						);
					}
				} );

				/**
				 * View Dashboard on click.
				 */
				$o.body.on( 'click', $o.viewDashboard, function() {
					const dashboardURL = $( this ).attr( 'data-dashboard-url' );

					if ( dashboardURL ) {
						window.location.href = dashboardURL;
					}
				} );
			};

			/**
			 * Generates Dynamic title and Description for purchase option.
			 *
			 * @param {Object} $this Dom Element.
			 * @param {string} isCustomTitle
			 * @param {string} isCustomDesc
			 * @return {void}
			 */
			const dynamicTitleDescription = function(
				$this,
				isCustomTitle,
				isCustomDesc
			) {
				const OptionItem = $this.closest( $o.purchaseOptionItem );
				//Prevent user actions after dropdown change.
				showLoader();

				// Timeout is added to wait for changeDurationOptions to perform operations on change first.
				setTimeout( function() {
					// Get selection values.
					const periodCount = parseInt(
						OptionItem.find( $o.periodCountSelection ).val()
					);
					const periodSelection = OptionItem.find(
						$o.periodSelection
					).val();
					const currentPurchaseType = OptionItem.attr(
						'data-purchase-type'
					);

					let newTitle, newDescriptionTitle;
					newTitle = periodCount + ' ';
					newDescriptionTitle = periodCount + ' ';
					switch ( periodSelection ) {
						case 'h':
							newTitle += __( 'Hour', 'revenue-generator' );
							newDescriptionTitle += __(
								'Hours',
								'revenue-generator'
							);
							break;
						case 'd':
							newTitle += __( 'Day', 'revenue-generator' );
							newDescriptionTitle += __(
								'Days',
								'revenue-generator'
							);
							break;
						case 'w':
							newTitle += __( 'Week', 'revenue-generator' );
							newDescriptionTitle += __(
								'Weeks',
								'revenue-generator'
							);
							break;
						case 'm':
							newTitle += __( 'Month', 'revenue-generator' );
							newDescriptionTitle += __(
								'Months',
								'revenue-generator'
							);
							break;
						case 'y':
							newTitle += __( 'Year', 'revenue-generator' );
							newDescriptionTitle += __(
								'Years',
								'revenue-generator'
							);
							break;
					}

					let newDescription = sprintf(
						/* translators: %1$s auto generated period string */
						__(
							'Enjoy unlimited access to all our content for %1$s'
						),
						newTitle
					);

					if ( periodCount && periodCount > 1 ) {
						newDescription = sprintf(
							/* translators: %1$s auto generated period string. */
							__(
								'Enjoy unlimited access to all our content for %1$s'
							),
							newDescriptionTitle
						);
					}

					switch ( currentPurchaseType ) {
						case 'subscription':
							newTitle +=
								' ' + __( 'Subscription', 'revenue-generator' );
							break;
						case 'timepass':
							newTitle += ' ' + __( 'Pass', 'revenue-generator' );
							break;
					}

					// if its custom title and description display modal.
					if (
						( isCustomTitle && '1' === isCustomTitle ) ||
						( isCustomDesc && '1' === isCustomDesc )
					) {
						new RevGenModal( {
							id: 'rg-modal-dynamic-title-desc',
							onConfirm: async () => {
								// User reset to default remove custom flag.
								OptionItem.attr( 'data-custom-desc', '' );
								OptionItem.attr( 'data-custom-title', '' );

								OptionItem.find(
									$o.purchaseOptionItemTitle
								).text( newTitle );
								OptionItem.find(
									$o.purchaseOptionItemDesc
								).text( newDescription );
							},
							onCancel: () => {
								// do nothing.
							},
						} );
					} else {
						OptionItem.find( $o.purchaseOptionItemTitle ).text(
							newTitle
						);
						OptionItem.find( $o.purchaseOptionItemDesc ).text(
							newDescription
						);
					}
					hideLoader();
				}, 1000 );
			};

			const areCredentialsFilled = function() {
				const merchantID = $o.body
					.find( $o.accountActionId )
					.val()
					.trim();
				const merchantKey = $o.body
					.find( $o.accountActionKey )
					.val()
					.trim();

				if ( merchantID && merchantKey ) {
					return true;
				}

				return false;
			};

			/**
			 * Get permalink for preview post id and take merchant to the post.
			 *
			 * @param {number} previewPostId Post Preview ID.
			 * @param {number} selectedCategoryId Selected Category ID.
			 * @param {string} appliesTo Applies to Type.
			 * @param {Array} specificPostIDs Array of Specific Post ID's.
			 */
			const viewPost = function(
				previewPostId,
				selectedCategoryId = 0,
				appliesTo = '',
				specificPostIDs = []
			) {
				// Create form data.
				const formData = {
					action: 'rg_post_permalink',
					preview_post_id: previewPostId,
					category_id: selectedCategoryId,
					applies_to: appliesTo,
					specific_posts_ids: specificPostIDs,
					security: revenueGeneratorGlobalOptions.rg_paywall_nonce,
				};

				// Delete the option.
				$.ajax( {
					url: revenueGeneratorGlobalOptions.ajaxUrl,
					method: 'POST',
					data: formData,
					dataType: 'json',
				} ).done( function( r ) {
					if ( r.redirect_to ) {
						window.open( r.redirect_to, '_blank' );
					}
				} );
			};

			/**
			 * Publish the paywall.
			 */
			const publishPaywall = function() {
				if ( ! $o.requestSent ) {
					$o.requestSent = true;
					showLoader();

					// Get paywall id.
					const paywall = $( $o.purchaseOptionItems );
					const paywallId = paywall.attr( 'data-paywall-id' );

					// Create form data for activating paywall.
					const formData = {
						action: 'rg_activate_paywall',
						paywall_id: paywallId,
						security:
							revenueGeneratorGlobalOptions.rg_paywall_nonce,
					};

					// Delete the option.
					$.ajax( {
						url: revenueGeneratorGlobalOptions.ajaxUrl,
						method: 'POST',
						data: formData,
						dataType: 'json',
					} ).done( function() {
						$o.requestSent = false;
						hideLoader();

						// Get required info for success message.
						const postPreviewID = $o.postPreviewWrapper.attr(
							'data-preview-id'
						);
						const categoryName = $o.searchPaywallContent
							.text()
							.trim();
						const paywallName = $o.paywallName.text().trim();
						const appliedTo = $( $o.paywallAppliesTo ).val();
						const selectedCategoryID = $o.searchPaywallContent.val();
						const specificPostIDs = $o.searchPost.val();

						new RevGenModal( {
							id: 'rg-modal-paywall-activation',
							templateData: {
								paywallID: paywallId,
								paywallName,
								appliedTo,
								categoryName,
								postTitle: $( $o.postTitle )
									.text()
									.trim(),
							},
							onConfirm: async () => {
								if ( postPreviewID ) {
									viewPost(
										postPreviewID,
										selectedCategoryID,
										appliedTo,
										specificPostIDs
									);
								}
							},
							onCancel: async ( e ) => {
								window.location.href =
									e.target.dataset.dashboard_url;
							},
						} );
					} );
				}
			};

			const showAccountActivationModal = function() {
				const closeEvent = new Event( 'rev-gen-modal-close' );

				new RevGenModal( {
					id: 'rg-modal-account-activation',
					keepOpen: true,
					templateData: {},
					onConfirm: async ( e, el ) => {
						el.dispatchEvent( closeEvent );

						showAccountModal();
					},
					onCancel: async ( e, el ) => {
						if (
							revenueGeneratorGlobalOptions.globalOptions
								.merchant_region.length
						) {
							const currentRegion =
								revenueGeneratorGlobalOptions.globalOptions
									.merchant_region;
							const signUpURL =
								revenueGeneratorGlobalOptions.signupURL;
							if ( 'US' === currentRegion ) {
								window.open( signUpURL.US, '_blank' );
							} else {
								window.open( signUpURL.EU, '_blank' );
							}
							el.dispatchEvent( closeEvent );
							showAccountModal();
						}
						// Send GA Event.
						const eventCategory = 'LP RevGen Account';
						const eventLabel = 'Signup';
						const eventAction = 'Connect Account';
						rgGlobal.sendLPGAEvent(
							eventAction,
							eventCategory,
							eventLabel,
							0,
							true
						);
					},
				} );
			};

			const showAccountModal = function() {
				new RevGenModal( {
					id: 'rg-modal-connect-account',
					keepOpen: true,
					templateData: {},
					onConfirm: async ( e, el ) => {
						const closeEvent = new Event( 'rev-gen-modal-close' );
						const $el = $( el );
						const merchantID = $(
							'#rev-gen-merchant-id',
							$el
						).val();
						const merchantKey = $( '#rev-gen-api-key', $el ).val();
						const $tryAgain = $(
							'#rg_js_restartVerification',
							$el
						);

						$el.addClass( 'loading' );

						const verify = await verifyAccountCredentials(
							merchantID,
							merchantKey
						);

						$tryAgain.on( 'click', function() {
							el.dispatchEvent( closeEvent );
							showAccountModal();
						} );

						if ( verify ) {
							$el.removeClass( 'loading' );
							el.dispatchEvent( closeEvent );
						} else {
							$el.removeClass( 'loading' ).addClass(
								'modal-error'
							);
						}
					},
				} );
			};

			/**
			 * Verify merchant credentials and allow paywall publishing.
			 *
			 * @param {string}  merchantId  Merchant ID.
			 * @param {string}  merchantKey Merchant Key.
			 */
			const verifyAccountCredentials = function(
				merchantId,
				merchantKey
			) {
				return new Promise( ( resolve ) => {
					let success = false;

					if ( ! $o.requestSent ) {
						$o.requestSent = true;

						// Create form data.
						const formData = {
							action: 'rg_verify_account_credentials',
							merchant_id: merchantId,
							merchant_key: merchantKey,
							security:
								revenueGeneratorGlobalOptions.rg_paywall_nonce,
						};

						let eventLabel = '';

						// Validate merchant details.
						$.ajax( {
							url: revenueGeneratorGlobalOptions.ajaxUrl,
							method: 'POST',
							async: false,
							data: formData,
							dataType: 'json',
						} ).done( function( r ) {
							$o.requestSent = false;

							// Get all purchase options and check paywall id.
							const allPurchaseOptions = $(
								$o.purchaseOptionItems
							);

							// set connecting merchant ID.
							revenueGeneratorGlobalOptions.merchant_id =
								r.merchant_id;

							if ( true === r.success ) {
								const paywallId = allPurchaseOptions.attr(
									'data-paywall-id'
								);

								/**
								 * If paywall id exists, then publish directly, else wait to save paywall data to
								 * get new paywall id and wait for some time to publish the paywall.
								 */
								if ( paywallId.length ) {
									publishPaywall();
								} else {
									// Save the paywall as well, so that we don't miss any new changes if merchant as done any.
									$o.isPublish = true;
									$o.savePaywall.trigger( 'click' );
									showLoader();
									setTimeout( function() {
										// Explicitly change loclized data.
										revenueGeneratorGlobalOptions.globalOptions.is_merchant_verified =
											'1';
										publishPaywall();
										hideLoader();
									}, 2000 );
								}
								eventLabel = 'Success';

								success = true;
							} else {
								// Save the paywall as well, so that we don't miss any new changes if merchant as done any.
								$o.isPublish = true;
								$o.savePaywall.trigger( 'click' );
								eventLabel = 'Failure - ' + r.msg;

								success = false;
							}

							// Send GA Event.
							const eventCategory = 'LP RevGen Account';
							const eventAction = 'Connect Account';
							rgGlobal.sendLPGAEvent(
								eventAction,
								eventCategory,
								eventLabel,
								0,
								true
							);

							resolve( success );
						} );

						return success;
					}
				} );
			};

			/**
			 * Initialized the tooltip on given element.
			 *
			 * @param {string} elementIdentifier Selector matching elements on the document
			 */
			const initializeTooltip = function( elementIdentifier ) {
				tippy( elementIdentifier, {
					arrow: tippy.roundArrow,
				} );
			};

			/**
			 * Initialize the tour object.
			 *
			 * @return {Shepherd.Tour} Shepherd tour object.
			 */
			const initializeTour = function() {
				return new Shepherd.Tour( {
					defaultStepOptions: {
						classes: 'rev-gen-tutorial-card',
						scrollTo: true,
						scrollToHandler: ( e ) => {
							$( 'html, body' ).animate(
								{
									scrollTop:
										$( e ).offset().top -
										$( window ).height() / 2 -
										$( e ).height(),
								},
								1000
							);
						},
					},
				} );
			};

			/**
			 * Add required info steps for the merchant.
			 *
			 * @param {Shepherd.Tour} tour Tour object.
			 */
			const addTourSteps = function( tour ) {
				const skipTourButton = {
					text: __( 'Skip Tour', 'revenue-generator' ),
					action: tour.complete,
					classes: 'shepherd-content-skip-tour',
				};

				const nextButton = {
					text: __( 'Next >', 'revenue-generator' ),
					action: tour.next,
					classes: 'shepherd-content-next-tour-element',
				};

				const tutorialEventCategory = 'LP RevGen Paywall Tutorial';
				const tutorialEventLabelContinue = 'Continue';
				const tutorialEventLabelComplete = 'Complete';

				// Add tutorial step for main search.
				const step1 = tour
					.addStep( {
						id: 'rg-main-search-input',
						text: __(
							"Search for the page or post you'd like to preview with Revenue Generator here.",
							'revenue-generator'
						),
						attachTo: {
							element: '.rev-gen-preview-main--search',
							on: 'bottom',
						},
						arrow: true,
						classes: 'shepherd-content-add-space-top fade-in',
						buttons: [ skipTourButton, nextButton ],
						when: {
							hide() {
								rgGlobal.sendLPGAEvent(
									'1 - Article Search',
									tutorialEventCategory,
									tutorialEventLabelContinue,
									0,
									true
								);
							},
						},
					} )
					.on( 'before-hide', () => {
						const optionClasses = step1.options.classes;
						step1.options.classes = optionClasses.replace(
							'fade-in',
							'fade-out'
						);
						step1.updateStepOptions( step1.options );
					} )
					.on( 'hide', () => {
						$( step1.el ).removeAttr( 'hidden' );
						setTimeout( function() {
							$( step1.el ).attr( 'hidden', '' );
						}, 700 );
					} );

				// Add tutorial step for editing header title
				const step2 = tour
					.addStep( {
						id: 'rg-purchase-overlay-header',
						text: __( 'Click to Edit', 'revenue-generator' ),
						attachTo: {
							element: '.rg-purchase-overlay-title',
							on: 'bottom',
						},
						arrow: true,
						classes: 'rev-gen-tutorial-title fade-in',
						buttons: [ nextButton ],
						when: {
							hide() {
								rgGlobal.sendLPGAEvent(
									'2 - Name Paywall',
									tutorialEventCategory,
									tutorialEventLabelContinue,
									0,
									true
								);
							},
						},
					} )
					.on( 'before-hide', () => {
						const optionClasses = step2.options.classes;
						step2.options.classes = optionClasses.replace(
							'fade-in',
							'fade-out'
						);
						step2.updateStepOptions( step2.options );
					} )
					.on( 'hide', () => {
						$( step2.el ).removeAttr( 'hidden' );
						setTimeout( function() {
							$( step2.el ).attr( 'hidden', '' );
						}, 700 );
					} );

				// Add tutorial step for option item.
				const step3 = tour
					.addStep( {
						id: 'rg-purchase-option-item',
						text: __(
							'Hover over each element to see the available options.',
							'revenue-generator'
						),
						attachTo: {
							element:
								'.rg-purchase-overlay-purchase-options .option-item-second',
							on: 'top',
						},
						arrow: true,
						classes: 'shepherd-content-add-space-bottom fade-in',
						buttons: [ nextButton ],
						when: {
							hide() {
								rgGlobal.sendLPGAEvent(
									'3 - Element Hover',
									tutorialEventCategory,
									tutorialEventLabelContinue,
									0,
									true
								);
							},
						},
					} )
					.on( 'before-hide', () => {
						const optionClasses = step3.options.classes;
						step3.options.classes = optionClasses.replace(
							'fade-in',
							'fade-out'
						);
						step3.updateStepOptions( step3.options );
					} )
					.on( 'hide', () => {
						$( step3.el ).removeAttr( 'hidden' );
						setTimeout( function() {
							$( step3.el ).attr( 'hidden', '' );
						}, 700 );
					} );

				// Add tutorial step for option item edit button.
				const step4 = tour
					.addStep( {
						id: 'rg-purchase-option-item-edit',
						text: __(
							'Click on the ‘more options’ icon to set the product type (single item purchase, time pass, or subscription).',
							'revenue-generator'
						),
						attachTo: {
							element:
								'.rg-purchase-overlay-purchase-options .option-item-second .rg-purchase-overlay-option-edit',
							on: 'left',
						},
						arrow: true,
						buttons: [ nextButton ],
						classes: 'fade-in',
						when: {
							hide() {
								rgGlobal.sendLPGAEvent(
									'4 - More Options',
									tutorialEventCategory,
									tutorialEventLabelContinue,
									0,
									true
								);
							},
						},
					} )
					.on( 'before-hide', () => {
						const optionClasses = step4.options.classes;
						step4.options.classes = optionClasses.replace(
							'fade-in',
							'fade-out'
						);
						step4.updateStepOptions( step4.options );
					} )
					.on( 'hide', () => {
						$( step4.el ).removeAttr( 'hidden' );
						setTimeout( function() {
							$( step4.el ).attr( 'hidden', '' );
						}, 700 );
					} );

				// Add tutorial step for option item title area.
				const step5 = tour
					.addStep( {
						id: 'rg-purchase-option-item-title',
						text: __(
							'Click on any text element to edit it.',
							'revenue-generator'
						),
						attachTo: {
							element:
								'.rg-purchase-overlay-purchase-options .option-item-second .rg-purchase-overlay-purchase-options-item-info .rg-purchase-overlay-purchase-options-item-info-title',
							on: 'top',
						},
						arrow: true,
						classes: 'shepherd-content-add-space-bottom fade-in',
						buttons: [ nextButton ],
						when: {
							hide() {
								rgGlobal.sendLPGAEvent(
									'5 - Text Edit',
									tutorialEventCategory,
									tutorialEventLabelContinue,
									0,
									true
								);
							},
						},
					} )
					.on( 'before-hide', () => {
						const optionClasses = step5.options.classes;
						step5.options.classes = optionClasses.replace(
							'fade-in',
							'fade-out'
						);
						step5.updateStepOptions( step5.options );
					} )
					.on( 'hide', () => {
						$( step5.el ).removeAttr( 'hidden' );
						setTimeout( function() {
							$( step5.el ).attr( 'hidden', '' );
						}, 700 );
					} );

				// Add tutorial step for option item price area.
				const step6 = tour
					.addStep( {
						id: 'rg-purchase-option-item-price',
						text: sprintf(
							/* translators: %1$s line break tag, %2$s laterpay.net info link opening,  %3$s laterpay.net info link closing */
							__(
								'These are our recommended prices. %1$s%1$sClick to edit; prices lower than 5.00 will default to %2$spay later%3$s.',
								'revenue-generator'
							),
							'<br/>',
							'<a class="info-link" href="https://www.laterpay.net/academy/getting-started-with-laterpay-the-difference-between-pay-now-pay-later" target="_blank" rel="noopener noreferrer">',
							'</a>'
						),
						attachTo: {
							element:
								'.rg-purchase-overlay-purchase-options .option-item-second .rg-purchase-overlay-purchase-options-item-price',
							on: 'top',
						},
						arrow: true,
						classes: 'shepherd-content-add-space-bottom fade-in',
						buttons: [ nextButton ],
						when: {
							hide() {
								rgGlobal.sendLPGAEvent(
									'6 - Pricing',
									tutorialEventCategory,
									tutorialEventLabelContinue,
									0,
									true
								);
							},
						},
					} )
					.on( 'before-hide', () => {
						const optionClasses = step6.options.classes;
						step6.options.classes = optionClasses.replace(
							'fade-in',
							'fade-out'
						);
						step6.updateStepOptions( step6.options );
					} )
					.on( 'hide', () => {
						$( step6.el ).removeAttr( 'hidden' );
						setTimeout( function() {
							$( step6.el ).attr( 'hidden', '' );
						}, 700 );
					} );

				// Add tutorial step for option item add.
				const step7 = tour
					.addStep( {
						id: 'rg-purchase-option-item-add',
						text: __(
							'Hover below the paywall to get the option to add another purchase button.',
							'revenue-generator'
						),
						attachTo: {
							element:
								'.rg-purchase-overlay-option-area-add-option',
							on: 'top',
						},
						arrow: true,
						classes: 'shepherd-content-add-space-bottom fade-in',
						buttons: [ nextButton ],
						when: {
							hide() {
								rgGlobal.sendLPGAEvent(
									'7 - Add Purchase Option',
									tutorialEventCategory,
									tutorialEventLabelContinue,
									0,
									true
								);
							},
						},
					} )
					.on( 'before-hide', () => {
						const optionClasses = step7.options.classes;
						step7.options.classes = optionClasses.replace(
							'fade-in',
							'fade-out'
						);
						step7.updateStepOptions( step7.options );
					} )
					.on( 'hide', () => {
						$( step7.el ).removeAttr( 'hidden' );
						setTimeout( function() {
							$( step7.el ).attr( 'hidden', '' );
						}, 700 );
					} );

				// Add tutorial step for paywall name.
				const step8 = tour
					.addStep( {
						id: 'rg-purchase-option-paywall-name',
						text: __(
							'Click here to change the name of your paywall.',
							'revenue-generator'
						),
						attachTo: {
							element: '.rev-gen-preview-main-paywall-name',
							on: 'bottom',
						},
						arrow: true,
						classes: 'shepherd-content-add-space-bottom fade-in',
						buttons: [ nextButton ],
						when: {
							hide() {
								rgGlobal.sendLPGAEvent(
									'8 - Name Paywall',
									tutorialEventCategory,
									tutorialEventLabelContinue,
									0,
									true
								);
							},
						},
					} )
					.on( 'before-hide', () => {
						const optionClasses = step8.options.classes;
						step8.options.classes = optionClasses.replace(
							'fade-in',
							'fade-out'
						);
						step8.updateStepOptions( step8.options );
					} )
					.on( 'hide', () => {
						$( step8.el ).removeAttr( 'hidden' );
						setTimeout( function() {
							$( step8.el ).attr( 'hidden', '' );
						}, 700 );
					} );

				// Add tutorial step for paywall actions search.
				const step9 = tour
					.addStep( {
						id: 'rg-purchase-option-paywall-actions-search',
						text: __(
							'Select the content that should display this paywall.',
							'revenue-generator'
						),
						attachTo: {
							element:
								'.rev-gen-preview-main--paywall-actions-search',
							on: 'bottom',
						},
						arrow: true,
						classes: 'shepherd-content-add-space-bottom fade-in',
						buttons: [ nextButton ],
						when: {
							hide() {
								rgGlobal.sendLPGAEvent(
									'9 - Select Content',
									tutorialEventCategory,
									tutorialEventLabelContinue,
									0,
									true
								);
							},
						},
					} )
					.on( 'before-hide', () => {
						const optionClasses = step9.options.classes;
						step9.options.classes = optionClasses.replace(
							'fade-in',
							'fade-out'
						);
						step9.updateStepOptions( step9.options );
					} )
					.on( 'hide', () => {
						$( step9.el ).removeAttr( 'hidden' );
						setTimeout( function() {
							$( step9.el ).attr( 'hidden', '' );
						}, 700 );
					} );

				// Add tutorial step for paywall actions publish.
				const step10 = tour
					.addStep( {
						id: 'rg-purchase-option-paywall-publish',
						text: __(
							'When you’re ready to activate your paywall, connect your account.',
							'revenue-generator'
						),
						attachTo: {
							element: '#rg_js_activatePaywall',
							on: 'bottom',
						},
						arrow: true,
						classes: 'shepherd-content-add-space-bottom fade-in',
						buttons: [
							{
								text: __( 'Complete', 'revenue-generator' ),
								action: tour.next,
								classes:
									'shepherd-content-complete-tour-element',
							},
						],
						when: {
							hide() {
								rgGlobal.sendLPGAEvent(
									'10 - Publish',
									tutorialEventCategory,
									tutorialEventLabelComplete,
									0,
									true
								);
							},
						},
					} )
					.on( 'before-hide', () => {
						const optionClasses = step10.options.classes;
						step10.options.classes = optionClasses.replace(
							'fade-in',
							'fade-out'
						);
						step10.updateStepOptions( step10.options );
					} )
					.on( 'hide', () => {
						$( step10.el ).removeAttr( 'hidden' );
						setTimeout( function() {
							$( step10.el ).attr( 'hidden', '' );
						}, 700 );
					} );
			};

			/**
			 * Handle the tour of the paywall elements.
			 *
			 * @param {Shepherd.Tour} tour Tour object.
			 */
			const startWelcomeTour = function( tour ) {
				// Show exit tour button.
				$( $o.exitTour ).css( {
					visibility: 'visible',
					'pointer-events': 'all',
					cursor: 'pointer',
				} );

				// Blur out the wrapper and disable events, to highlight the tour elements.
				$o.body.addClass( 'modal-blur' );
				$o.layoutWrapper.css( {
					'pointer-events': 'none',
				} );
				$( $o.purchaseOptionItem ).css( {
					'background-color': 'darkgray',
				} );
				$( $o.purchaseOptionItemInfo ).css( {
					'border-right': '1px solid #928d8d',
				} );

				$o.emailSupportButton.hide();

				const directionalKeys = [
					'ArrowUp',
					'ArrowDown',
					'ArrowRight',
					'ArrowLeft',
				];
				const disableArrowKeys = function( e ) {
					if ( directionalKeys.includes( e.key ) ) {
						e.preventDefault();
						return false;
					}
				};

				// Disable arrow events.
				$( document ).keydown( disableArrowKeys );

				// Remove the blurry class and allow click events.
				Shepherd.on( 'complete', function() {
					// Revert to original state.
					$o.body.removeClass( 'modal-blur' );

					$o.layoutWrapper.css( {
						'pointer-events': 'unset',
					} );

					// Removed background from search bar.
					$o.searchContentWrapper.css( {
						'background-color': '#fff',
					} );

					// Revert to original theme.
					$( $o.purchaseOptionItem ).css( {
						'background-color': '#fff',
					} );
					$( $o.purchaseOptionItemInfo ).css( {
						'border-right': '1px solid #e3e4e6',
					} );

					// Hide exit tour button.
					$( $o.exitTour ).remove();

					$o.emailSupportButton.show();

					// Enable arrow events.
					$( document ).unbind( 'keydown', disableArrowKeys );

					const currentStep = Shepherd.activeTour.getCurrentStep();
					let tutorialEventAction = '';
					let tutorialEventLabel = 'Exit Tour';

					switch ( currentStep.id ) {
						case 'rg-main-search-input':
							tutorialEventAction = '1 - Article Search';
							break;
						case 'rg-purchase-overlay-header':
							tutorialEventAction = '2 - Name Paywall';
							break;
						case 'rg-purchase-option-item':
							tutorialEventAction = '3 - Element Hover';
							break;
						case 'rg-purchase-option-item-edit':
							tutorialEventAction = '4 - More Options';
							break;
						case 'rg-purchase-option-item-title':
							tutorialEventAction = '5 - Text Edit';
							break;
						case 'rg-purchase-option-item-price':
							tutorialEventAction = '6 - Pricing';
							break;
						case 'rg-purchase-option-item-add':
							tutorialEventAction = '7 - Add Purchase Option';
							break;
						case 'rg-purchase-option-paywall-name':
							tutorialEventAction = '8 - Name Paywall';
							break;
						case 'rg-purchase-option-paywall-actions-search':
							tutorialEventAction = '9 - Select Content';
							break;
						case 'rg-purchase-option-paywall-publish':
							tutorialEventAction = '10 - Publish';
							tutorialEventLabel = 'Complete';
							break;
					}

					const tutorialEventCategory = 'LP RevGen Paywall Tutorial';

					// Send GA exit event.
					rgGlobal.sendLPGAEvent(
						tutorialEventAction,
						tutorialEventCategory,
						tutorialEventLabel,
						0,
						true
					);

					setTimeout( function() {
						// Complete the tour, and update plugin option.
						completeTheTour();
					}, 500 );
				} );

				// Start the tour.
				tour.start();
			};

			/**
			 * Complete the tour.
			 */
			const completeTheTour = function() {
				// Create form data.
				const formData = {
					action: 'rg_complete_tour',
					config_key: 'paywall_tutorial_done',
					config_value: 1,
					security: revenueGeneratorGlobalOptions.rg_paywall_nonce,
				};

				// Delete the option.
				$.ajax( {
					url: revenueGeneratorGlobalOptions.ajaxUrl,
					method: 'POST',
					data: formData,
					dataType: 'json',
				} );
			};

			/**
			 * Clear category meta on change.
			 *
			 * @param {number} categoryId Category Id.
			 */
			const removeCurrentCategoryMeta = function( categoryId ) {
				// prevent duplicate requests.
				if ( ! $o.requestSent ) {
					$o.requestSent = true;

					// Create form data.
					const formData = {
						action: 'rg_clear_category_meta',
						rg_category_id: categoryId,
						security:
							revenueGeneratorGlobalOptions.rg_paywall_nonce,
					};

					$.ajax( {
						url: revenueGeneratorGlobalOptions.ajaxUrl,
						method: 'POST',
						data: formData,
						dataType: 'json',
					} ).done( function() {
						$o.requestSent = false;
					} );
				}
			};

			/**
			 * Update the post preview based on selected preview content.
			 *
			 * @param {number} postId Post ID.
			 */
			const showPreviewContent = function( postId ) {
				// prevent duplicate requests.
				if ( ! $o.requestSent ) {
					$o.requestSent = true;

					// Create form data.
					const formData = {
						action: 'rg_select_preview_content',
						post_preview_id: postId,
						security:
							revenueGeneratorGlobalOptions.rg_paywall_nonce,
					};

					$.ajax( {
						url: revenueGeneratorGlobalOptions.ajaxUrl,
						method: 'POST',
						data: formData,
						dataType: 'json',
					} ).done( function( r ) {
						$o.requestSent = false;
						if ( r.redirect_to ) {
							window.location.href = r.redirect_to;
						}
					} );
				}
			};

			/**
			 * Search post content for preview based on merchants search term.
			 *
			 * @param {string} searchTerm The part of title being searched for preview.
			 */
			const searchPreviewContent = function( searchTerm ) {
				if ( searchTerm.length ) {
					// prevent duplicate requests.
					if ( ! $o.requestSent ) {
						$o.requestSent = true;

						// Create form data.
						const formData = {
							action: 'rg_search_preview_content',
							search_term: searchTerm,
							security:
								revenueGeneratorGlobalOptions.rg_paywall_nonce,
						};

						$.ajax( {
							url: revenueGeneratorGlobalOptions.ajaxUrl,
							method: 'POST',
							data: formData,
							dataType: 'json',
						} ).done( function( r ) {
							$o.requestSent = false;
							if ( true === r.success ) {
								$o.searchContentWrapper.addClass( 'searching' );
								$o.searchResultWrapper.empty();
								const postPreviews = r.preview_posts;
								postPreviews.forEach( function( item ) {
									const itemType =
										item.type === 'post'
											? 'dashicons-admin-post'
											: 'dashicons-admin-page';
									const itemTitle = item.title;
									const searchRegExp = new RegExp(
										searchTerm,
										'i'
									);
									const highlightedTitle = itemTitle.replace(
										searchRegExp,
										`<b>${ searchTerm }</b>`
									);
									const searchItem = $( '<span/>', {
										'data-id': item.id,
										class:
											'rev-gen-preview-main--search-results-item dashicons-before',
									} ).append( highlightedTitle );
									searchItem.addClass( itemType );
									$o.searchResultWrapper.append( searchItem );
									$o.searchResultWrapper.css( {
										display: 'flex',
									} );
								} );

								// Send GA Event.
								const eventCategory =
									'LP RevGen Paywall Preview';
								const eventLabel = '';
								rgGlobal.sendLPGAEvent(
									'Article Search',
									eventCategory,
									eventLabel,
									0,
									true
								);
							} else {
								$o.snackBar.showSnackbar( r.msg, 1500 );
							}
						} );
					}
				}
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
			 * Hide the loader.
			 */
			const hideLoader = function() {
				$o.laterpayLoader.hide();
			};

			/**
			 * Remove purchase option from DB.
			 *
			 * @param {string}  type        Type of purchase option being removed.
			 * @param {number}  id          Id of the purchase option.
			 * @param {boolean} showMessage Should the message be shown for removal..
			 */
			const removePurchaseOption = function(
				type,
				id,
				showMessage = true
			) {
				const paywall = $( $o.purchaseOptionItems );
				const paywallId = paywall.attr( 'data-paywall-id' );

				// Create form data.
				const formData = {
					action: 'rg_remove_purchase_option',
					type,
					id,
					paywall_id: paywallId,
					security: revenueGeneratorGlobalOptions.rg_paywall_nonce,
				};

				// Delete the option.
				$.ajax( {
					url: revenueGeneratorGlobalOptions.ajaxUrl,
					method: 'POST',
					data: formData,
					dataType: 'json',
				} ).done( function( r ) {
					if ( true === showMessage ) {
						$o.snackBar.showSnackbar( r.msg, 1500 );
					}
				} );
			};

			/**
			 * Update purchase options order.
			 */
			const reorderPurchaseItems = function() {
				// Get all purchase options.
				const purchaseOptions = $( $o.purchaseOptionItems );
				// Sort options by order, single purchase, time passes, subscriptions.
				if ( purchaseOptions.length ) {
					// Add temporary lpc pricing attribute, for sorting.
					purchaseOptions
						.children( $o.purchaseOptionItem )
						.each( function() {
							const priceItem = $( this ).find(
								$o.purchaseOptionItemPrice
							);
							const currentPrice = priceItem.text().trim();
							const connectorPricing = (
								currentPrice * 100
							).toFixed( 0 );
							$( this ).attr(
								'data-lpc-pricing',
								connectorPricing
							);
						} );

					// Get all options, if available.
					const individualOption = purchaseOptions.find(
						"[data-purchase-type='individual']"
					);
					const timePassOptions = purchaseOptions.find(
						"[data-purchase-type='timepass']"
					);
					const subscriptionOptions = purchaseOptions.find(
						"[data-purchase-type='subscription']"
					);

					// Move individual option to the top of the list.
					if ( individualOption.length ) {
						purchaseOptions.prepend( individualOption );
					}

					// Add time passes after individual option else add to top.
					if ( timePassOptions.length ) {
						// Sort the time passes internally.
						timePassOptions.sort( function( a, b ) {
							return (
								+$( a ).attr( 'data-lpc-pricing' ) -
								+$( b ).attr( 'data-lpc-pricing' )
							);
						} );

						// Remove options and add sorted options.
						purchaseOptions
							.find( "[data-purchase-type='timepass']" )
							.remove();

						if ( individualOption.length ) {
							purchaseOptions
								.find( individualOption )
								.after( timePassOptions );
						} else {
							purchaseOptions.prepend( timePassOptions );
						}
					}

					// Add subscriptions to the end.
					if ( subscriptionOptions.length ) {
						// Sort the subscriptions internally.
						subscriptionOptions.sort( function( a, b ) {
							return (
								+$( a ).data( 'lpc-pricing' ) -
								+$( b ).data( 'lpc-pricing' )
							);
						} );

						// Remove options and add sorted options.
						purchaseOptions
							.find( "[data-purchase-type='subscription']" )
							.remove();

						// Append sorted subscriptions to the purchase options.
						purchaseOptions.append( subscriptionOptions );
					}

					/**
					 * Loop through all purchase options and update the order.
					 */
					purchaseOptions
						.children( $o.purchaseOptionItem )
						.each( function( i ) {
							const order = i + 1;
							$( this ).attr( 'data-order', order );
						} );
				}
			};

			/**
			 * Validate the purchase item revenue model.
			 *
			 * @param {string} price         Price of the item.
			 * @param {Object} purchaseItem  Purchase option.
			 */
			const validateRevenue = function( price, purchaseItem ) {
				const purchaseManager = $( purchaseItem ).find(
					'.rg-purchase-overlay-option-manager'
				);
				const revenueWrapper = purchaseManager.find(
					$o.purchaseRevenueWrapper
				);
				const pricingTypeWrapper = purchaseManager.find(
					$o.individualPricingWrapper
				);
				const entityType = $( purchaseItem ).attr(
					'data-purchase-type'
				);

				// Set pricing type to static for manual change of price.
				$( purchaseItem ).attr( 'data-pricing-type', 'static' );
				pricingTypeWrapper
					.find( $o.individualPricingSelection )
					.prop( 'checked', false );

				// Set pricing type based on value, and update manager UI accordingly.
				if (
					price >=
					revenueGeneratorGlobalOptions.currency.sis_only_limit
				) {
					$( purchaseItem )
						.find( $o.purchaseOptionItemPrice )
						.attr( 'data-pay-model', 'sis' );
					revenueWrapper
						.find( $o.purchaseRevenueSelection )
						.prop( 'checked', false );
					if ( 'subscription' !== entityType ) {
						$( purchaseItem ).trigger( 'mouseenter' );
						$( purchaseItem )
							.find( $o.editOption )
							.trigger( 'click' );
					}
				} else if (
					( price <= revenueGeneratorGlobalOptions.currency.ppu_min ||
						price >
							revenueGeneratorGlobalOptions.currency.ppu_min ) &&
					price < revenueGeneratorGlobalOptions.currency.sis_min
				) {
					$( purchaseItem )
						.find( $o.purchaseOptionItemPrice )
						.attr( 'data-pay-model', 'ppu' );
					revenueWrapper
						.find( $o.purchaseRevenueSelection )
						.prop( 'checked', true );
					if ( 'subscription' !== entityType ) {
						$( purchaseItem ).trigger( 'mouseenter' );
						$( purchaseItem )
							.find( $o.editOption )
							.trigger( 'click' );
					}
				}
			};

			/**
			 * Validate the purchase item revenue model on revenue change.
			 *
			 * @param {Object} purchaseItem  Purchase option.
			 * @param {boolean} revenueType  Purchase option revenue type.
			 */
			const validatePricingRevenue = function(
				purchaseItem,
				revenueType
			) {
				const priceItem = $( purchaseItem ).find(
					$o.purchaseOptionItemPrice
				);
				const price = parseFloat( priceItem.text().trim() );
				const optionType = $( purchaseItem ).attr(
					'data-purchase-type'
				);
				const currencyLimits = revenueGeneratorGlobalOptions.currency;
				let validatedPrice = validatePrice( price );

				// If merchant selected pay now.
				if (
					false === revenueType &&
					price <= parseFloat( currencyLimits.ppu_only_limit )
				) {
					validatedPrice = validatePrice(
						currencyLimits.sis_min,
						'subscription' === optionType
					);
					$o.snackBar.showSnackbar(
						__(
							'Pay Now is only available for prices set to $1.99 or higher.',
							'revenue-generator'
						),
						2500
					);

					if ( 'individual' === optionType ) {
						const dynamicStar = $( $o.purchaseItemPriceIcon );
						dynamicStar.css( {
							display: 'none',
						} );
						priceItem.text( validatedPrice );
					} else {
						priceItem.empty().text( validatedPrice );
					}
				} else if (
					true === revenueType &&
					price > parseFloat( currencyLimits.ppu_max )
				) {
					validatedPrice = validatePrice(
						currencyLimits.ppu_max,
						'subscription' === optionType
					);
					$o.snackBar.showSnackbar(
						__(
							'Pay Later is only available for prices set less than $5.',
							'revenue-generator'
						),
						2500
					);

					if ( 'individual' === optionType ) {
						const dynamicStar = $( $o.purchaseItemPriceIcon );
						dynamicStar.css( {
							display: 'none',
						} );
						priceItem.text( validatedPrice );
					} else {
						priceItem.empty().text( validatedPrice );
					}
				}
			};

			/**
			 * Get validated price.
			 *
			 * @param {string}  price                   Purchase option price.
			 * @param {boolean} subscriptionValidation  Is current item subscription.
			 *
			 * @return {string} return validated price.
			 */
			const validatePrice = function( price, subscriptionValidation ) {
				if ( typeof price !== 'number' ) {
					// strip non-number characters
					price = price.replace( /[^0-9\,\.]/g, '' );

					// convert price to proper float value
					price = parseFloat( price.replace( ',', '.' ) ).toFixed(
						2
					);
				}

				// prevent non-number prices
				if ( isNaN( price ) ) {
					price = 0;
				}

				// prevent negative prices
				price = Math.abs( price );

				if ( subscriptionValidation ) {
					if (
						price < revenueGeneratorGlobalOptions.currency.sis_min
					) {
						price = revenueGeneratorGlobalOptions.currency.sis_min;
					} else if (
						price > revenueGeneratorGlobalOptions.currency.sis_max
					) {
						price = revenueGeneratorGlobalOptions.currency.sis_max;
					}
				} else {
					// correct prices outside the allowed range of 0.05 - 149.99
					if (
						price > revenueGeneratorGlobalOptions.currency.sis_max
					) {
						price = revenueGeneratorGlobalOptions.currency.sis_max;
					}

					if (
						price > 0 &&
						price < revenueGeneratorGlobalOptions.currency.ppu_min
					) {
						price = revenueGeneratorGlobalOptions.currency.ppu_min;
					}
				}

				// format price with two digits
				price = price.toFixed( 2 );

				// localize price
				if (
					revenueGeneratorGlobalOptions.locale.indexOf( 'de_DE' ) !==
					-1
				) {
					price = price.replace( '.', ',' );
				}

				return price;
			};

			/**
			 * Add currency modal.
			 */
			const showCurrencySelectionModal = function() {
				new RevGenModal( {
					id: 'rg-modal-choose-currency',
					onConfirm: async ( e, el ) => {
						const closeEvent = new Event( 'rev-gen-modal-close' );

						const formData = {
							action: 'rg_update_currency_selection',
							config_key: 'merchant_currency',
							config_value: $(
								'input:radio[name=currency]:checked'
							).val(),
							security:
								revenueGeneratorGlobalOptions.rg_paywall_nonce,
						};

						$.ajax( {
							url: revenueGeneratorGlobalOptions.ajaxUrl,
							method: 'POST',
							data: formData,
							dataType: 'json',
						} ).done( function( r ) {
							$o.snackBar.showSnackbar( r.msg, 1500 );

							el.dispatchEvent( closeEvent );

							const purchaseOptions = $( $o.purchaseOptionItems );
							purchaseOptions
								.children( $o.purchaseOptionItem )
								.each( function() {
									const priceSymbol = $( this ).find(
										$o.purchaseOptionPriceSymbol
									);
									const symbol =
										'USD' === formData.config_value
											? '$'
											: '€';
									priceSymbol.empty().text( symbol );
								} );
						} );
					},
				} );
			};

			/**
			 * Update the paywall.
			 *
			 * @param {string} ajaxURL  AJAX URL.
			 * @param {Object} formData Form data to be submitted.
			 */
			const updatePaywall = function( ajaxURL, formData ) {
				$.ajax( {
					url: ajaxURL,
					method: 'POST',
					data: formData,
					dataType: 'json',
				} ).done( function( r ) {
					hideLoader();
					$o.snackBar.showSnackbar( r.msg, 1500 );

					const currentOptionCount = $( $o.purchaseOptionItems ).find(
						$o.purchaseOptionItem
					).length;

					// show if we have less than 5 options.
					if ( currentOptionCount < 5 ) {
						$( $o.optionArea ).show();
					}

					// @todo Add Events here.
					let eventLabel = '';
					let eventAction = '';
					if ( $o.isPublish ) {
						eventAction = 'Publish';
					} else {
						eventAction = 'Save';
					}
					const eventCategory = 'LP RevGen Paywall Publish';
					const paywallName = formData.paywall.name;
					let merchantId = revenueGeneratorGlobalOptions.merchant_id;
					if ( ! merchantId && $( $o.accountActionId ).val() ) {
						merchantId = $( $o.accountActionId ).val();
					}

					const appliesTo = formData.paywall.applies;
					const countPostId = formData.post_id;
					const subscriptionsCount = formData.subscriptions.length;
					const timePassesCount = formData.time_passes.length;
					const countPurcahseOption =
						parseInt( subscriptionsCount ) +
						parseInt( timePassesCount );

					eventLabel =
						merchantId +
						' - ' +
						r.paywall_id +
						' - ' +
						paywallName +
						' - ' +
						appliesTo +
						' - ' +
						countPostId +
						' - ' +
						countPurcahseOption;

					// Send GA Event.
					rgGlobal.sendLPGAEvent(
						eventAction,
						eventCategory,
						eventLabel,
						0,
						true
					);

					const purchaseOptions = $( $o.purchaseOptionItems );

					eventAction = 'Paywall Details';

					// Set main paywall id.
					purchaseOptions.attr( 'data-paywall-id', r.paywall_id );

					const individualOption = purchaseOptions.find(
						"[data-purchase-type='individual']"
					);
					if ( individualOption.length ) {
						individualOption.attr(
							'data-paywall-id',
							r.paywall_id
						);

						let typeEvent = formData.individual.type;
						if ( 'dynamic' !== typeEvent ) {
							const revenueType = formData.individual.revenue;
							if ( 'ppu' === revenueType ) {
								typeEvent = 'Pay Later';
							} else if ( 'sis' === revenueType ) {
								typeEvent = 'Pay now';
							}
						}
						// Send Single GA Event.
						const price = formData.individual.price;
						eventLabel =
							revenueGeneratorGlobalOptions.merchant_id +
							' - ' +
							r.paywall_id +
							' - Single Article - ' +
							typeEvent +
							' - ' +
							price;
						rgGlobal.sendLPGAEvent(
							eventAction,
							eventCategory,
							eventLabel,
							0,
							true
						);
					}

					const timePassOptions = purchaseOptions.find(
						"[data-purchase-type='timepass']"
					);
					if ( timePassOptions.length ) {
						// Add returned ids to appropriate purchase option.
						timePassOptions.each( function( i ) {
							const timePassUID = $( this ).attr( 'data-uid' );
							$( this ).attr(
								'data-tlp-id',
								r.time_passes[ timePassUID ]
							);
							const revenueType =
								formData.time_passes[ i ].revenue;
							let typeEvent = '';
							if ( 'ppu' === revenueType ) {
								typeEvent = 'Pay Later';
							} else if ( 'sis' === revenueType ) {
								typeEvent = 'Pay now';
							}
							const price = formData.time_passes[ i ].price;
							const durtion =
								formData.time_passes[ i ].period +
								formData.time_passes[ i ].duration;
							eventLabel =
								revenueGeneratorGlobalOptions.merchant_id +
								' - ' +
								r.paywall_id +
								' - Time Pass - ' +
								typeEvent +
								' - ' +
								durtion +
								' - ' +
								price;
							rgGlobal.sendLPGAEvent(
								eventAction,
								eventCategory,
								eventLabel,
								0,
								true
							);
						} );
					}

					const subscriptionOptions = purchaseOptions.find(
						"[data-purchase-type='subscription']"
					);
					if ( subscriptionOptions.length ) {
						// Add returned ids to appropriate purchase option.
						subscriptionOptions.each( function( i ) {
							const subscriptionUID = $( this ).attr(
								'data-uid'
							);
							$( this ).attr(
								'data-sub-id',
								r.subscriptions[ subscriptionUID ]
							);

							const revenueType =
								formData.subscriptions[ i ].revenue;
							let typeEvent = '';
							if ( 'ppu' === revenueType ) {
								typeEvent = 'Pay Later';
							} else if ( 'sis' === revenueType ) {
								typeEvent = 'Pay now';
							}
							const price = formData.subscriptions[ i ].price;
							const durtion =
								formData.subscriptions[ i ].period +
								formData.subscriptions[ i ].duration;
							eventLabel =
								revenueGeneratorGlobalOptions.merchant_id +
								' - ' +
								r.paywall_id +
								' - Subscription - ' +
								typeEvent +
								' - ' +
								durtion +
								' - ' +
								price;
							rgGlobal.sendLPGAEvent(
								eventAction,
								eventCategory,
								eventLabel,
								0,
								true
							);
						} );
					}

					if ( r.redirect_to && false === $o.isPublish ) {
						window.location.href = r.redirect_to;
					}
				} );
			};

			/**
			 * Create a unique identifier.
			 *
			 * From https://stackoverflow.com/a/2117523/4368718 - uuidv4().
			 */
			const createUniqueID = function() {
				return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
					/[xy]/g,
					function( c ) {
						const r = ( Math.random() * 16 ) | 0, // eslint-disable-line no-bitwise
							v = c === 'x' ? r : ( r & 0x3 ) | 0x8; // eslint-disable-line no-bitwise
						return v.toString( 16 );
					}
				);
			};

			/**
			 * Create options markup based on period selection.
			 *
			 * @param {string} period   Type of period, i.e Year, Month, Day, Hour.
			 * @param {Object} $wrapper Select wrapper.
			 */
			const changeDurationOptions = function( period, $wrapper ) {
				const options = [];
				let limit = 24;

				// change duration options.
				switch ( period ) {
					case 'y':
						limit = 1;
						break;

					case 'm':
						limit = 12;
						break;

					case 'd':
						limit = 24;
						break;

					default:
						limit = 24;
						break;
				}

				for ( let i = 1; i <= limit; i++ ) {
					const option = $( '<option/>', {
						value: i,
					} );
					option.text( i );
					options.push( option );
				}

				$( $wrapper )
					.find( 'option' )
					.remove()
					.end()
					.append( options );
			};

			/**
			 * Adds paywall.
			 */
			const addPaywall = function() {
				if ( $o.postContent ) {
					// Blur the paid content out.
					$o.postContent.addClass( 'blur-content' );

					// Get the template for purchase overlay along with data.
					const template = wp.template( 'revgen-purchase-overlay' );

					// Send the data to our new template function, get the HTML markup back.
					$o.purchaseOverlay.append( template );
					$o.purchaseOverlay.show();
				}
			};

			/**
			 * Displays Welcome modal when visiting screen for the first time.
			 */
			const handleWelcomeModal = function() {
				if (
					! revenueGeneratorGlobalOptions.welcome.isPaywallWelcomeDone
				) {
					const PostCoutEventAction = 'Paywall Landing Page';
					const PostCountEventCategory = 'LP RevGen Paywall Tutorial';

					new RevGenModal( {
						id: 'rg-modal-publishing-frequency',
						// onConfirm handles 'Fewer than 10 posts per month' option
						onConfirm: () => {
							storePostPublishCount( 'low' );

							rgGlobal.sendLPGAEvent(
								PostCoutEventAction,
								PostCountEventCategory,
								'Fewer than 10',
								0,
								true
							);
						},
						// onCancel handles 'More than 10 posts per month' option
						onCancel: () => {
							storePostPublishCount( 'high' );

							rgGlobal.sendLPGAEvent(
								PostCoutEventAction,
								PostCountEventCategory,
								'10+',
								0,
								true
							);
						},
					} );
				}

				/**
				 * Saves publish count selected in Welcome modal to database.
				 *
				 * @param {string} count
				 */
				const storePostPublishCount = function( count ) {
					const formData = {
						action: 'rg_update_global_config',
						config_key: 'average_post_publish_count',
						config_value: count,
						security:
							revenueGeneratorGlobalOptions.rg_global_config_nonce,
					};

					$.ajax( {
						url: revenueGeneratorGlobalOptions.ajaxUrl,
						method: 'POST',
						data: formData,
						dataType: 'json',
					} );
				};
			};

			// Initialize all required events.
			const initializePage = function() {
				handleWelcomeModal();
				bindEvents();
				addPaywall();
			};

			if ( $( '.rev-gen-preview-main' ).length ) {
				initializePage();
			}
		}

		revenueGeneratorPaywallPreview();
	} );
} )( jQuery ); // eslint-disable-line no-undef
