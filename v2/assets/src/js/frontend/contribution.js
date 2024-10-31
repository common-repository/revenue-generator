/* eslint-disable @wordpress/no-global-event-listener */
/* global FormData, XMLHttpRequest, Event, ResizeObserver, MouseEvent, CustomEvent */

import { setCookie, getCookie } from '../helpers/index';

/**
 * JS to handle contribution dialog.
 */
export class RevGenContribution {
	constructor( el ) {
		this.el = el;
		this.itemId = this.el.dataset.contributionId;
		this.successEvent = new Event( 'rg-contribution-success' );
		this.hiddenClassName = 'rev-gen-hidden';

		this.$o = {
			form: el.querySelector( '.rev-gen-contribution__form' ),
			submitButton: el.querySelector(
				'button[type=submit][data-mytab-button]'
			),
			amounts: el.querySelector( '.rev-gen-contribution__amounts' ),
			choose: el.querySelector( '.rev-gen-contribution__choose' ),
			response: el.querySelector( '.rev-gen-contribution__response' ),
			custom: {
				el: el.querySelector( '.rev-gen-contribution__custom' ),
				input: el.querySelector(
					'.rev-gen-contribution__custom input'
				),
				choice: el.querySelector(
					'.rev-gen-contribution__amount--custom'
				),
				backButton: el.querySelector(
					'.rev-gen-contribution-custom__back'
				),
			},
			modal: {
				el: el.querySelector( '.rev-gen-contribution-info-modal' ),
				openButton: el.querySelector(
					'.rev-gen-contribution__question-mark'
				),
				closeButton: el.querySelector(
					'.rev-gen-contribution-info-modal__x-mark'
				),
			},
			footer: {
				el: el.parentElement,
				toggle: el.querySelector(
					'.rev-gen-footer-contribution .rev-gen-contribution__toggle'
				),
			},
		};

		this.isFooter = this.$o.footer.el.classList.contains(
			'rev-gen-footer-contribution'
		);
		this.dismissedFooterCookieName = 'rg_footer_contribution_dismiss_until';

		this.setStep( 'default' );
		this.bindEvents();
		this.maybeContinueFlow();

		if ( this.isFooter ) {
			this.initFooter();
		}
	}

	/**
	 * Binds all events.
	 */
	bindEvents() {
		const self = this;

		const observer = new ResizeObserver( ( els ) => {
			els.forEach( ( el ) => {
				const breakpoints = el.target.dataset.breakpoints
					? JSON.parse( el.target.dataset.breakpoints )
					: '';

				if ( ! breakpoints ) {
					return;
				}

				Object.keys( breakpoints ).forEach( ( breakpoint ) => {
					const minWidth = breakpoints[ breakpoint ];
					const className = 'size-' + breakpoint;

					if ( el.contentRect.width >= minWidth ) {
						el.target.classList.add( className );
					} else {
						el.target.classList.remove( className );
					}
				} );
			} );
		} );

		observer.observe( this.el );

		/**
		 * On 'Custom' item click, hide pre-defined amounts and display
		 * custom contribution box with input and a button.
		 */
		this.$o.custom.choice.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			this.setStep( 'custom' );
			this.$o.custom.input.focus();
		} );

		/**
		 * When typing to 'Custom' input, disable button if value is not a valid
		 * number. Otherwise, enable it.
		 */
		this.$o.custom.input.addEventListener( 'keyup', () => {
			const value = parseFloat( this.$o.custom.input.value );

			if ( ! isNaN( value ) && isFinite( value ) ) {
				this.$o.submitButton.removeAttribute( 'disabled' );
			} else {
				this.$o.submitButton.setAttribute( 'disabled', 'disabled' );
			}
		} );

		/**
		 * On 'Back' button click in custom contribution box, display
		 * pre-defined amounts and hide custom contribution elements.
		 */
		this.$o.custom.backButton.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			this.setStep( 'default' );
		} );

		/**
		 * Toggle visibility of submit button on form change.
		 */
		this.$o.form.addEventListener( 'change', () => {
			/**
			 * Return early when in custom amount mode because the submit button
			 * should display there at all times.
			 */
			if ( this.customModeActive ) {
				return;
			}

			const data = new FormData( this.$o.form );

			// Show button if the amount is selected, hide it otherwise.
			if ( data.get( 'amount' ) ) {
				this.setStep( 'valid' );
			} else {
				this.setStep( 'default' );
			}
		} );

		/**
		 * Handle form submit.
		 */
		this.$o.form.addEventListener( 'submit', ( e ) => {
			e.preventDefault();

			this.setStep( 'loading' );

			// Get form data.
			const data = new FormData( this.$o.form );
			data.append( 'rg_key', getCookie( 'rg_key' ) );

			if ( ! data.get( 'amount' ) && ! data.get( 'custom_amount' ) ) {
				this.setStep( 'error' );

				return;
			}

			// Create ajax object.
			const req = new XMLHttpRequest();

			req.open( 'POST', this.$o.form.getAttribute( 'action' ), true );
			req.send( data );

			req.onreadystatechange = function() {
				if ( 4 === this.readyState ) {
					const res = JSON.parse( this.response );

					switch ( this.status ) {
						// Item added to the tab.
						case 200:
							const script = document.createElement( 'script' );
							script.src = self.$o.form.dataset.tabWidgetUrl;

							self.$o.response.appendChild( script );
							self.$o.response.innerHTML = res.data.html;

							script.onload = () => {
								self.setStep( 'loaded' );

								const aboutTabLink = self.el.querySelectorAll(
									'.tab_widget__link'
								)[ 1 ];

								aboutTabLink.addEventListener( 'click', () => {
									self.openInfoModal();
								} );

								self.el.dispatchEvent( self.successEvent );
							};
							break;

						case 402:
							self.$o.response.innerHTML = res.data.html;
							break;

						// User not authorized.
						case 401:
							setCookie( 'rg_key', res.data.session_key );
							setCookie(
								'rg_contribution_data',
								JSON.stringify( res.data.handover )
							);

							// Redirect to auth.
							window.location.href = res.data.auth_url;
							break;
					}
				}
			};
		} );

		/**
		 * Modal trigger click.
		 */
		this.$o.modal.openButton?.addEventListener( 'click', () => {
			self.openInfoModal();
		} );

		/**
		 * Modal close click.
		 */
		this.$o.modal.closeButton?.addEventListener( 'click', () => {
			self.closeInfoModal();
		} );

		/**
		 * Close the footer and set cookie to prevent footer from showing up again
		 * for the defined period of time.
		 */
		this.el.addEventListener( 'rg-contribution-success', () => {
			if ( ! this.isFooter ) {
				return;
			}

			const dismissForSeconds =
				parseInt( this.el.dataset.dismissFor, 10 ) || 86400;
			setCookie(
				this.dismissedFooterCookieName,
				'yes',
				dismissForSeconds
			);

			setTimeout( () => {
				self.setStep( 'default' );
				self.completelyHideFooter();
			}, 5000 );
		} );

		/**
		 * Listen to `postMessage` event sent by payment component when
		 * info modal should be opened.
		 */
		window.addEventListener( 'message', ( e ) => {
			if ( e.data && 'OPEN_INFO_MODAL' === e.data ) {
				self.openInfoModal();
			}

			if ( e.data && 'PAYMENT_SUCCESSFUL' === e.data ) {
				self.el.dispatchEvent( self.successEvent );
			}
		} );

		/**
		 * Listen to custom event `rev-gen-iframe-load` to adjust
		 * iframe height on load and toggle elements not needed
		 * in payment context.
		 */
		this.el.addEventListener( 'rev-gen-iframe-load', ( e ) => {
			const iframe = e.detail || '';

			if ( ! iframe ) {
				return;
			}

			this.setStep( 'loaded' );

			iframe.style.height =
				iframe.contentWindow.document.body.scrollHeight + 'px';
		} );

		this.$o.footer.toggle?.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			this.$o.footer.el.classList.toggle(
				'rev-gen-footer-contribution--collapsed'
			);
		} );
	}

	openInfoModal() {
		clearTimeout( this.closeTimeout );
		this.$o.modal.el.classList.remove( this.hiddenClassName );
	}

	closeInfoModal() {
		this.$o.modal.el.classList.add( this.hiddenClassName );
	}

	initFooter() {
		const dismissedCookieVal = getCookie( this.dismissedFooterCookieName );

		if ( ! dismissedCookieVal.length ) {
			this.$o.footer.el.classList.add(
				'rev-gen-footer-contribution--active'
			);
		}
	}

	completelyHideFooter() {
		this.$o.footer.el.classList.remove(
			'rev-gen-footer-contribution--active'
		);
	}

	setStep( newStep ) {
		this.step = newStep;
		this.el.dataset.step = newStep;
		this.customModeActive = false;

		if ( newStep === 'custom' ) {
			this.customModeActive = true;

			if ( ! this.$o.custom.input.value ) {
				this.$o.submitButton.setAttribute( 'disabled', 'disabled' );
			}

			const allInputs = this.$o.amounts.querySelectorAll( 'input' );
			const checked = [].filter.call( allInputs, ( input ) => {
				return input.checked;
			} );

			if ( checked.length ) {
				checked[ 0 ].checked = false;
			}
		} else if ( newStep === 'default' ) {
			this.$o.submitButton.removeAttribute( 'disabled' );
			this.$o.custom.input.value = '';
		}
	}

	/**
	 * Reset view to default view.
	 */
	reset() {
		this.$o.response.innerHTML = '';
		this.el.classList.remove( 'rev-gen-contribution--payment' );
		this.$o.form.reset();
		this.$o.choose.classList.remove( this.hiddenClassName );
	}

	/**
	 * If user has `rev-gen_data` cookie set, it means they returned from
	 * the auth flow. Initialize flow immediately for them so they continue
	 * where they left off.
	 *
	 * Submit the form automatically so they can contribute the amount without
	 * clicking amount and submitting the form manually again.
	 */
	maybeContinueFlow() {
		if ( ! getCookie( 'rg_contribution_data' ) ) {
			return;
		}

		const clientCookie = JSON.parse( getCookie( 'rg_contribution_data' ) );

		if (
			parseInt( this.itemId, 10 ) !==
			parseInt( clientCookie.contribution.item_id, 10 )
		) {
			return;
		}

		const amount = this.$o.form.querySelector(
			'[value="' + clientCookie.contribution.amount + '"]'
		);

		window.scrollTo( {
			top: this.el.offsetTop,
			behavior: 'smooth',
		} );

		// If amount is found in the radio inputs, check that.
		if ( amount ) {
			amount.checked = true;
		} else {
			/**
			 * Otherwise, populate the amount to a custom input and display
			 * custom form.
			 */
			this.$o.custom.input.value = parseFloat(
				clientCookie.contribution.amount
			);

			this.setStep( 'custom' );
		}

		// Submit the form.
		const clickEvent = new MouseEvent( 'click' );
		this.$o.submitButton.dispatchEvent( clickEvent );

		// Reset session cookie to empty value after making a contribution.
		setCookie( 'rg_contribution_data', '' );
	}
}

/**
 * Listen to when iframe with payment component loads.
 *
 * @param {Object} iframe Iframe element.
 */
window.laterpayIframeLoaded = ( iframe ) => {
	const contributionId = iframe.dataset.contributionId;

	if ( ! contributionId ) {
		return;
	}

	const event = new CustomEvent( 'rev-gen-iframe-load', { detail: iframe } );
	let contribution = document.querySelectorAll(
		'.rev-gen-contribution[data-contribution-id="' + contributionId + '"]'
	);
	contribution = contribution[ contribution.length - 1 ];

	contribution.dispatchEvent( event );
};
