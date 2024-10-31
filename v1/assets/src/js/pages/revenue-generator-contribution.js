/* global revenueGeneratorGlobalOptions, rgGlobal, Backbone, _, RevGenContributionData */
/**
 * JS to handle plugin settings screen interactions.
 */

/**
 * Internal dependencies.
 */
import '../utils';
import { RevGenModal } from '../utils/rev-gen-modal';
import { isURL, addQueryArgs } from '@wordpress/url';
import { RevGenTour, tourSettings } from '../utils/tour';
import { copyToClipboard } from '../helpers/index';

const options = revenueGeneratorGlobalOptions;

window.RevGenApp = {
	vars: {},
	views: {},
};

window.handlePreviewUpdate = ( attr, value ) => {
	window.RevGenApp.views.app.trigger( 'preview-update', {
		attr,
		value,
	} );
};

window.handleIframeLoad = ( iframe ) => {
	iframe.classList.remove( 'loading' );
};

window.trackTourStep = ( step ) => {
	const trackingProps = step.options.tracking;

	if ( step.options.cancelled ) {
		if ( 'Continue' === trackingProps.action ) {
			trackingProps.action = 'Exit Tour';
		}
	}

	rgGlobal.sendLPGAEvent(
		trackingProps.event,
		trackingProps.category,
		trackingProps.action,
		0,
		true
	);
};

window.updateTourProgress = () => {
	const tourProgress = document.getElementById( 'rg-tour-progress' );
	tourProgress.style.display = 'block';

	const activeStep = tourProgress.querySelector( '.active' );
	let nextStep = '';

	if ( activeStep ) {
		nextStep = activeStep.nextElementSibling;

		activeStep.classList.add( 'visited' );
		activeStep.classList.remove( 'active' );
	} else {
		nextStep = tourProgress.children[ 0 ];
	}

	if ( ! nextStep ) {
		tourProgress.style.display = 'none';

		return;
	}

	nextStep.classList.add( 'active' );
};

( ( $, Backbone ) => {
	$( function() {
		const $o = {
			loader: $( '.laterpay-loader-wrapper' ),
			snackBar: $( '#rg_js_SnackBar' ),
		};

		const MainView = Backbone.View.extend( {
			el: '#rg-contribution-builder-app',

			events: {
				'keyup [data-bind]': 'onInputChange',
				'change [data-bind]': 'onInputChange',
				'change [data-bind=layout_type]': 'onLayoutTypeChange',
				'focusout [data-validate]': 'onValidatedFieldFocusOut',
				'submit form': 'onFormSubmit',
				'click #rg_js_toggle_preview': 'onPreviewToggleClick',
			},

			initialize() {
				this.doing_ajax = false;
				this.originalData = this.model.toJSON();

				this.$o = {
					form: $( '#rg_js_form', this.$el ),
					submitButton: $( 'input[type=submit]', this.$el ),
					helperMessage: $(
						'.rg-contribution-builder__helper-message',
						this.$el
					),
					previewIframe: $(
						'#rg-contribution-builder-preview',
						this.$el
					),
				};

				const self = this;

				if (
					0 ===
						parseInt(
							options.globalOptions
								.is_contribution_tutorial_completed
						) ||
					0 === parseInt( options.has_contributions )
				) {
					window.addEventListener( 'load', function() {
						self.initializeTour();
					} );
				}

				window.addEventListener( 'rg-tour-init', function( e ) {
					if ( ! e.detail.startAt ) {
						return;
					}

					self.initializeTour( e.detail.startAt );
				} );

				this.listenTo( this, 'preview-update', this.onPreviewUpdate );
				this.listenTo( this.model, 'change', this.onModelChange );
			},

			initializeTour( startAt ) {
				startAt = parseInt( startAt, 10 );

				if ( ! startAt ) {
					startAt = 0;
				}

				this.tour = {};

				const tourProps = {
					steps: tourSettings.contribution.steps.builder[ startAt ],
					onStepHide: ( step ) => {
						if ( step.options.tracking ) {
							window.trackTourStep( step );
						}

						window.updateTourProgress();
					},
				};

				if (
					startAt + 1 ===
					tourSettings.contribution.steps.builder.length
				) {
					tourProps.onComplete = () => {
						// Create form data.
						const formData = {
							action: 'rg_complete_tour',
							config_key: 'is_contribution_tutorial_completed',
							config_value: 1,
							security: options.rg_paywall_nonce,
						};

						$.ajax( {
							url: options.ajaxUrl,
							method: 'POST',
							data: formData,
							dataType: 'json',
						} );
					};
				} else {
					tourProps.onComplete = () => {
						const event = new Event( 'rg-init-preview-tour' );
						window.dispatchEvent( event );
					};
				}

				this.tour = new RevGenTour( tourProps );
			},

			onPreviewUpdate( data ) {
				if ( ! data || ! data.attr || ! data.value ) {
					return;
				}

				const value = this.validateValue( data.attr, data.value );

				this.model.set( data.attr, value );
			},

			validateValue( attribute, value ) {
				let validatedValue = value;

				switch ( attribute ) {
					case 'amounts':
						validatedValue = [];

						for ( let i = 0; i < value.length; i++ ) {
							if ( 'custom' !== value ) {
								validatedValue.push( parseFloat( value[ i ] ) );
							} else {
								validatedValue.push( value );
							}
						}
						break;
					default:
						break;
				}

				return validatedValue;
			},

			onInputChange( e ) {
				const $input = $( e.target );
				const attr = $input.attr( 'data-bind' );
				let value = $input.val();

				value = this.validateValue( attr, value );

				this.model.set( attr, value );
			},

			onModelChange( model ) {
				if (
					JSON.stringify( model.toJSON() ) !==
					JSON.stringify( this.originalData )
				) {
					this.enableSubmit();
				} else {
					this.disableSubmit();
				}
			},

			onLayoutTypeChange( e ) {
				const value = $( e.target ).val();

				this.$o.previewIframe.addClass( 'loading' );

				let url = this.$o.previewIframe.attr( 'src' );
				url = addQueryArgs( url, { layout_type: value } );

				this.$o.previewIframe.attr( 'src', url );
			},

			enableSubmit() {
				this.$o.submitButton.removeAttr( 'disabled' );
			},

			disableSubmit() {
				this.$o.submitButton.attr( {
					disabled: 'disabled',
				} );
			},

			onValidatedFieldFocusOut( e ) {
				const $input = $( e.target );
				const isValid = this.validateInput( $input );

				if ( ! isValid ) {
					$input.addClass( 'error' );
				} else {
					$input.removeClass( 'error' );
				}
			},

			validateInput( $input ) {
				if ( ! $input ) {
					return;
				}

				let isValid = true;

				const validationType = $input.attr( 'data-validation' );
				const isRequired = $input.attr( 'required' );
				const value = $input.val();

				switch ( validationType ) {
					case 'url':
						if ( value ) {
							isValid = isURL( value );
						}

						break;

					default:
						isValid = value;

						break;
				}

				if ( isRequired && ! value ) {
					isValid = false;
				}

				return isValid;
			},

			isFormValid() {
				const self = this;

				$( '[data-bind]', this.$el ).each( function( i, el ) {
					const $el = $( el );
					const isValid = self.validateInput( $el );

					if ( ! isValid ) {
						$el.addClass( 'error' );
					} else {
						$el.removeClass( 'error' );
					}
				} );

				if ( ! $( '[data-bind].error', this.$el ).length ) {
					return true;
				}

				return false;
			},

			getJSONData() {
				let data = {};

				const formInputs = this.$o.form.serializeArray();
				const modelData = this.model.toJSON();

				_( formInputs ).each( function( item ) {
					data[ item.name ] = item.value;
				} );

				data = { ...data, ...modelData };

				return data;
			},

			onFormSubmit( e ) {
				e.preventDefault();

				if ( ! this.isFormValid() ) {
					return false;
				}

				// Check for non-verified merchant.
				if (
					0 === parseInt( options.globalOptions.is_merchant_verified )
				) {
					showAccountActivationModal();

					return false;
				}

				if ( ! this.doing_ajax ) {
					const self = this;

					this.doing_ajax = true;

					showLoader();

					const formData = this.getJSONData();

					$.ajax( {
						url: options.ajaxUrl,
						method: 'POST',
						data: formData,
						dataType: 'json',
					} ).done( function( r ) {
						$o.snackBar.showSnackbar( r.msg, 1500 );

						if ( r.success ) {
							copyToClipboard( r.code );

							self.$o.submitButton.text( r.button_text );
							self.disableSubmit();

							self.$o.helperMessage.show();

							const eventAction = 'New ShortCode	';
							const eventCategory = 'LP RevGen Contributions';
							const merchantId = getMerchantId();
							const amounts = self.model.get( 'amounts' );

							let eventLabel = merchantId + ' - ' + formData.name;

							_( amounts ).each( function( item ) {
								const price = item.price;
								eventLabel += ' - ' + price;
							} );

							rgGlobal.sendLPGAEvent(
								eventAction,
								eventCategory,
								eventLabel,
								0,
								true
							);

							if ( window.location.href !== r.edit_link ) {
								setTimeout( function() {
									window.location.href = r.edit_link;
								}, 1500 );
							}
						}

						self.doing_ajax = false;

						hideLoader();
					} );
				}
			},

			onPreviewToggleClick( e ) {
				const $link = $( e.target );

				if ( ! this.$el.hasClass( 'preview-focus' ) ) {
					this.$el.addClass( 'preview-focus' );
					$link.addClass( 'mode-collapse' );
					$link.text( $link.data( 'collapse-text' ) );
				} else {
					this.$el.removeClass( 'preview-focus' );
					$link.removeClass( 'mode-collapse' );
					$link.text( $link.data( 'expand-text' ) );
				}
			},
		} );

		const PreviewView = Backbone.View.extend( {
			el: '#rg-contribution-builder-preview',

			events: {},

			initialize() {},
		} );

		const initApp = function() {
			if ( ! $( '#rg-contribution-builder-app' ).length ) {
				return;
			}

			const data = RevGenContributionData;
			const model = new Backbone.Model( data );

			window.RevGenApp.views.app = new MainView( {
				model,
			} );

			window.RevGenApp.views.preview = new PreviewView( {
				model,
			} );
		};

		const getMerchantId = function() {
			let merchantId = options.merchant_id;

			if ( ! merchantId ) {
				merchantId = window.RevGenApp.vars.merchant_id;
			}

			return merchantId;
		};

		const showAccountActivationModal = function() {
			/* global Event */
			const closeEvent = new Event( 'rev-gen-modal-close' );

			new RevGenModal( {
				id: 'rg-modal-account-activation',
				keepOpen: true,
				templateData: {},
				onConfirm: ( e, el ) => {
					el.dispatchEvent( closeEvent );

					showAccountModal();
				},
				onCancel: ( e, el ) => {
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
				bindings: ( el ) => {
					const $el = $( el );
					const $merchantID = $( '#rev-gen-merchant-id', $el );
					const $merchantKey = $( '#rev-gen-api-key', $el );
					const $button = $( '#rg_js_modal_confirm', $el );

					$( 'input', $el ).on( 'keyup', () => {
						if ( $merchantID.val() && $merchantKey.val() ) {
							$button.removeAttr( 'disabled' );
						} else {
							$button.attr( 'disabled', 'disabled' );
						}
					} );
				},
				onConfirm: async ( e, el ) => {
					const closeEvent = new Event( 'rev-gen-modal-close' );
					const $el = $( el );
					const $tryAgain = $( '#rg_js_restartVerification', $el );
					const merchantID = $( '#rev-gen-merchant-id', $el ).val();
					const merchantKey = $( '#rev-gen-api-key', $el ).val();

					$el.addClass( 'loading' );

					$tryAgain.on( 'click', function() {
						el.dispatchEvent( closeEvent );
						showAccountModal();
					} );

					const verify = await verifyAccountCredentials(
						merchantID,
						merchantKey
					);

					if ( verify ) {
						window.RevGenApp.vars.merchant_id = merchantID;
						el.dispatchEvent( closeEvent );
					} else {
						$el.removeClass( 'loading' ).addClass( 'modal-error' );
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
		const verifyAccountCredentials = function( merchantId, merchantKey ) {
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
						data: formData,
						dataType: 'json',
					} ).done( function( r ) {
						$o.requestSent = false;

						// set connecting merchant ID.
						revenueGeneratorGlobalOptions.merchant_id =
							r.merchant_id;

						if ( true === r.success ) {
							setTimeout( function() {
								// Explicitly change loclized data.
								revenueGeneratorGlobalOptions.globalOptions.is_merchant_verified =
									'1';
								// Display message about Credentails.
								$o.snackBar.showSnackbar( r.msg, 1500 );
							}, 2000 );
							eventLabel = 'Success';

							success = true;
						} else {
							eventLabel = 'Failure - ' + r.msg;
							success = false;
						}

						resolve( success );

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
					} );
				}
			} );
		};

		initApp();

		/**
		 * Show the loader.
		 */
		const showLoader = function() {
			$o.loader.css( {
				display: 'flex',
			} );
		};

		/**
		 * Hide the loader.
		 */
		const hideLoader = function() {
			$o.loader.hide();
		};
	} );
} )( jQuery, Backbone ); // eslint-disable-line no-undef
