/* global Shepherd */
import { tourSettings } from './tour-settings';

class RevGenTour {
	/**
	 * Constructor
	 *
	 * @param {Object} options - Tour options.
	 */
	constructor( options ) {
		/** @type {Object} */
		const defaultOptions = {
			stepOptions: {
				classes: 'rev-gen-tutorial-card',
				scrollTo: { behavior: 'smooth', block: 'center' },
			},
			autoStart: true,
			steps: {},
			onStart: () => {},
			onStepHide: () => {},
			onComplete: () => {},
		};

		/** @type {Object} */
		this.options = {
			...defaultOptions,
			...options,
		};

		// Call init method to set up tour.
		this.init();
	}

	/**
	 * Bindings.
	 */
	init() {
		const self = this;

		// Create new Shepherd tour.
		this.tour = new Shepherd.Tour( {
			defaultStepOptions: self.options.stepOptions,
		} );

		// Add tour steps.
		this.addTourSteps();

		// If autostart, call Shepherd's `.start()` method and `onStart()` callback.
		if ( this.options.autoStart ) {
			this.tour.start();
			this.options.onStart();
		}

		/**
		 * When tour is complete, call `onStepHide()` and `onComplete()` functions.
		 */
		Shepherd.on( 'complete', () => {
			const currentStep = Shepherd.activeTour.getCurrentStep();

			self.options.onStepHide( currentStep );
			self.options.onComplete();
		} );

		/**
		 * When tour is cancelled, add a cancelled flag to the step and call 'onStepHide()`.
		 */
		Shepherd.on( 'cancel', () => {
			const currentStep = Shepherd.activeTour.getCurrentStep();
			currentStep.options.cancelled = true;

			self.options.onStepHide( currentStep );
		} );
	}

	/**
	 * Add tour steps from `tourSettings`.
	 */
	addTourSteps() {
		const self = this;
		const steps = this.options.steps;
		const buttons = tourSettings.buttons;

		steps.forEach( ( step ) => {
			const buttonsProp = [];

			/**
			 * Add buttons represented by a string in `tourSettings` JSON to the step
			 * in the format understandable by Shepherd.
			 */
			step.buttons.forEach( ( item ) => {
				const buttonSettings = buttons[ item ];

				const button = {
					text: buttonSettings.text,
					action: self.tour[ buttonSettings.action ],
					classes: buttonSettings.classes,
				};

				buttonsProp.push( button );
			} );

			step.buttons = buttonsProp;

			const tourStep = self.tour
				.addStep( step )
				.on( 'before-hide', () => {
					const optionClasses = tourStep.options.classes;

					tourStep.options.classes = optionClasses.replace(
						'fade-in',
						'fade-out'
					);

					tourStep.updateStepOptions( tourStep.options );
				} )
				.on( 'hide', () => {
					if ( 'function' === typeof self.options.onProgress ) {
						self.options.onStepHide( tourStep );
					}

					tourStep.el.removeAttribute( 'hidden' );

					setTimeout( () => {
						tourStep.el.setAttribute( 'hidden', '' );
					}, 700 );
				} );
		} );
	}
}

export { RevGenTour, tourSettings };
