/* global FormData, XMLHttpRequest, ResizeObserver */

/**
 * JS to handle contribution dialog.
 */
export class RevGenContribution {
	constructor( el ) {
		this.el = el;
		this.$o = {
			donateBox: el.querySelector( '.rev-gen-contribution__donate' ),
			customBox: {
				el: el.querySelector( '.rev-gen-contribution__custom' ),
				form: el.querySelector( 'form' ),
				input: el.querySelector( '.rev-gen-contribution-custom input' ),
				backButton: el.querySelector(
					'.rev-gen-contribution-custom__back'
				),
				send: el.querySelector( '.rev-gen-contribution-custom__send' ),
			},
			amounts: el.getElementsByClassName(
				'rev-gen-contribution__donation'
			),
			customAmount: el.querySelector(
				'.rev-gen-contribution__donation--custom'
			),
			tip: el.querySelector( '.rev-gen-contribution__tip' ),
		};

		this.bindEvents();
	}

	/**
	 * Binds all events.
	 */
	bindEvents() {
		for ( const amount of this.$o.amounts ) {
			const link = amount.querySelector( 'a' );

			if ( ! link ) {
				continue;
			}

			/**
			 * On mouse over, we either show or hide the 'contribute now, pay later'
			 * helper message depending on the amount.
			 */
			link.addEventListener( 'mouseover', () => {
				const type = amount.dataset.revenue;

				if ( 'ppu' === type ) {
					this.$o.tip.classList.remove( 'rev-gen-hidden' );
				} else {
					this.$o.tip.classList.add( 'rev-gen-hidden' );
				}
			} );

			/**
			 * Hide the helper message on mouse out.
			 */
			link.addEventListener( 'mouseout', () => {
				this.$o.tip.classList.add( 'rev-gen-hidden' );
			} );

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
		}

		/**
		 * On 'Custom' item click, hide pre-defined amounts and display
		 * custom contribution box with input and a button.
		 */
		this.$o.customAmount.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			this.$o.donateBox.classList.add( 'rev-gen-hidden' );
			this.$o.customBox.el.classList.remove( 'rev-gen-hidden' );
			this.$o.customBox.el.removeAttribute( 'hidden' );
			this.$o.customBox.input.focus();
		} );

		/**
		 * On 'Back' button click in custom contribution box, display
		 * pre-defined amounts and hide custom contribution elements.
		 */
		this.$o.customBox.backButton.addEventListener( 'click', () => {
			this.$o.customBox.el.classList.add( 'rev-gen-hidden' );
			this.$o.customBox.el.setAttribute( 'hidden', '' );
			this.$o.donateBox.classList.remove( 'rev-gen-hidden' );
		} );

		/**
		 * Handle `change` event in custom input. Two things going on here:
		 *
		 * - We validate the amount.
		 * - We either hide or show the 'contribute now, pay later' message
		 *   depending on the amount.
		 */
		this.$o.customBox.input.addEventListener( 'change', () => {
			this.validateAmount();

			if ( 199 >= this.getCustomAmount( true ) ) {
				this.$o.tip.classList.remove( 'rev-gen-hidden' );
			} else {
				this.$o.tip.classList.add( 'rev-gen-hidden' );
			}
		} );

		/**
		 * Listens to `keyup` event in custom amount input.
		 *
		 * Hide or show 'contribute now, pay later' message depending
		 * on the amount entered.
		 */
		this.$o.customBox.input.addEventListener( 'keyup', () => {
			if ( 199 >= this.getCustomAmount( true ) ) {
				this.$o.tip.classList.remove( 'rev-gen-hidden' );
			} else {
				this.$o.tip.classList.add( 'rev-gen-hidden' );
			}
		} );

		/**
		 * Handle custom contribution form submit.
		 *
		 * @param {Object} e Event.
		 */
		this.$o.customBox.form.addEventListener( 'submit', ( e ) => {
			e.preventDefault();

			this.$o.customBox.send.classList.add( 'loading' );
			this.$o.customBox.send.setAttribute( 'disabled', true );

			// Store reference to this context.
			const self = this;

			// Get form data.
			const data = new FormData( this.$o.customBox.form );

			// Create ajax object.
			const req = new XMLHttpRequest();

			req.open(
				'POST',
				this.$o.customBox.form.getAttribute( 'action' ),
				true
			);
			req.send( data );

			/**
			 * On success, redirect to the URL returned from backend.
			 *
			 * On failure, add error class to the form.
			 */
			req.onreadystatechange = function() {
				if ( 4 === this.readyState ) {
					self.$o.customBox.send.classList.remove( 'loading' );
					self.$o.customBox.send.removeAttribute( 'disabled' );

					if ( 200 === this.status ) {
						const res = JSON.parse( this.response );

						if ( res.data ) {
							self.$o.customBox.form.classList.remove( 'error' );
							window.open( res.data );
						} else {
							self.$o.customBox.form.classList.add( 'error' );
						}
					} else {
						self.$o.customBox.form.classList.add( 'error' );
					}
				}
			};
		} );
	}

	/**
	 * Validates user input to floats. This is basic client-side
	 * validation before passing the value to backend.
	 */
	validateAmount() {
		let amount = this.$o.customBox.input.value;

		amount = amount.toString().replace( /[^0-9\,\.]/g, '' );

		// convert price to proper float value
		if ( typeof amount === 'string' && amount.indexOf( ',' ) > -1 ) {
			amount = parseFloat( amount.replace( ',', '.' ) );
		} else {
			amount = parseFloat( amount );
		}

		amount = amount.toFixed( 2 );

		// prevent non-number prices
		if ( isNaN( amount ) ) {
			amount = 0.05;
		}

		// prevent negative prices
		amount = Math.abs( amount );

		// correct prices outside the allowed range of 0.05 - 1000.00
		if ( amount > 1000.0 ) {
			amount = 1000.0;
		} else if ( amount < 0.05 ) {
			amount = 0.05;
		}

		// Update input value to validated amount.
		this.$o.customBox.input.value = amount;

		return amount;
	}

	/**
	 * Returns amount from the custom amount input.
	 *
	 * @param {boolean} amountInCents Whether to return amount in cents or a float.
	 */
	getCustomAmount( amountInCents ) {
		let amount = this.$o.customBox.input.value;

		if ( amountInCents ) {
			amount = amount * 100;
		}

		return amount;
	}
}
