import { RevGenContribution } from './contribution';

export class RevGenContributionModal {
	constructor( el ) {
		this.$button = {
			trigger: el.querySelector( 'button' ),
			modal: el.querySelector( '.rev-gen-contribution-modal' ),
		};

		this.$modal = {
			el: '',
		};

		this.bindButtonEvents();
	}

	bindButtonEvents() {
		this.$button.trigger.addEventListener(
			'click',
			this.open.bind( this )
		);
	}

	bindModalEvents() {
		this.$modal.closeButton.addEventListener(
			'click',
			this.close.bind( this )
		);
	}

	open( e ) {
		e.preventDefault();

		const modal = this.$button.modal.cloneNode( true );

		this.$modal.el = modal;
		this.$modal.contributionEl = modal.querySelector(
			'.rev-gen-contribution'
		);
		this.$modal.closeButton = modal.querySelector(
			'.rev-gen-contribution-modal__close'
		);

		document.querySelector( 'body' ).appendChild( modal );

		this.bindModalEvents();
		this.initContributionRequest();

		setTimeout( function() {
			modal.classList.add( 'active' );
		}, 100 );
	}

	initContributionRequest() {
		this.contributionInstance = new RevGenContribution(
			this.$modal.contributionEl
		);
	}

	close( e ) {
		e.preventDefault();

		const $modal = this.$modal.el;

		$modal.classList.remove( 'active' );

		setTimeout( function() {
			document.querySelector( 'body' ).removeChild( $modal );
		}, 200 );
	}
}
