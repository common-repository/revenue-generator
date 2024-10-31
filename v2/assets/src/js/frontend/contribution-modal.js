import { RevGenContribution } from './contribution';

export class RevGenContributionModal {
	constructor( el ) {
		this.closeTimeout = '';
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
		const self = this;

		this.$modal.closeButton.addEventListener(
			'click',
			this.closeButtonClick.bind( this )
		);

		this.$modal.contributionEl.addEventListener(
			'rg-contribution-success',
			() => {
				self.closeTimeout = setTimeout( () => {
					self.close();
				}, 5000 );
			}
		);

		this.$modal.contributionEl.addEventListener( 'click', () => {
			clearTimeout( self.closeTimeout );
		} );
	}

	closeButtonClick( e ) {
		e.preventDefault();

		this.close();
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

	close() {
		const $modal = this.$modal.el;

		$modal.classList.remove( 'active' );

		setTimeout( function() {
			if ( $modal ) {
				$modal.remove();
			}
		}, 200 );
	}
}
