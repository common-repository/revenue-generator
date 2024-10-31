/* globals Backbone, ResizeObserver, CustomEvent */
import { RevGenTour, tourSettings } from './utils/tour';

const ContributionView = Backbone.View.extend( {
	el: '.rev-gen-contribution',

	events: {
		'keyup [contenteditable]': 'onEditableContentChange',
	},

	initialize() {
		const self = this;

		this.bindEvents();

		window.parent.addEventListener( 'rg-init-preview-tour', () => {
			if ( ! self.tutorialHasCompleted ) {
				self.initializeTour();
			}
		} );
	},

	onEditableContentChange( e ) {
		e.stopPropagation();

		const el = e.target;
		const attr = el.dataset.bind;
		let value = el.innerText;

		if ( 'amounts' === attr ) {
			value = this.getAllAmounts();
		}

		window.parent.handlePreviewUpdate( attr, value );
	},

	getAllAmounts() {
		const amounts = document.querySelectorAll( '[data-bind="amounts"]' );

		if ( ! amounts.length ) {
			return;
		}

		const validatedValue = [];

		amounts.forEach( ( el ) => {
			const price = el.innerText.trim();

			validatedValue.push( price );
		} );

		return validatedValue;
	},

	bindEvents() {
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

		observer.observe( this.$el[ 0 ] );
	},

	initializeTour() {
		const self = this;

		this.tour = new RevGenTour( {
			steps: tourSettings.contribution.steps.preview,
			onStart: () => {
				window.parent.updateTourProgress();
			},
			onStepHide: ( step ) => {
				if ( step.options.tracking ) {
					window.parent.trackTourStep( step );
				}

				window.parent.updateTourProgress();
			},
			onComplete: () => {
				self.tutorialHasCompleted = true;

				const event = new CustomEvent( 'rg-tour-init', {
					detail: {
						startAt: 1,
					},
				} );

				window.parent.dispatchEvent( event );
			},
		} );
	},
} );

window.addEventListener( 'DOMContentLoaded', () => {
	new ContributionView();
} );
