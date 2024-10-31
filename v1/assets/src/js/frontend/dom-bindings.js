import { RevGenContribution } from './contribution';
import { RevGenContributionModal } from './contribution-modal';

/**
 * Loop through contribution elements found on page and initialize
 * `RevGenContribution` on DOM load.
 */
document.addEventListener( 'DOMContentLoaded', () => {
	const contributions = document.getElementsByClassName(
		'rev-gen-contribution'
	);

	for ( const item of contributions ) {
		if ( 'button' !== item.dataset.type ) {
			new RevGenContribution( item );
		} else {
			new RevGenContributionModal( item );
		}
	}
} );
