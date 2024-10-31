export { default as debounce } from './debounce';

export const copyToClipboard = ( text ) => {
	const temp = document.createElement( 'input' );
	document.body.appendChild( temp );
	temp.value = text;
	temp.select();
	document.execCommand( 'copy' );
	temp.remove();
};
