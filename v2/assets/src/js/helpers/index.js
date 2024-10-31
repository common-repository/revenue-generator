export { default as debounce } from './debounce';

export const copyToClipboard = ( text ) => {
	const temp = document.createElement( 'input' );
	document.body.appendChild( temp );
	temp.value = text;
	temp.select();
	document.execCommand( 'copy' );
	temp.remove();
};

export const getCookie = ( cname ) => {
	const name = cname + '=';
	const decodedCookie = decodeURIComponent( document.cookie );
	const ca = decodedCookie.split( ';' );
	for ( let i = 0; i < ca.length; i++ ) {
		let c = ca[ i ];
		while ( c.charAt( 0 ) === ' ' ) {
			c = c.substring( 1 );
		}
		if ( c.indexOf( name ) === 0 ) {
			return c.substring( name.length, c.length );
		}
	}
	return '';
};

export const setCookie = ( cname, cvalue, expSeconds ) => {
	const d = new Date();
	d.setTime( d.getTime() + 1000 * expSeconds );
	const expires = 'expires=' + d.toUTCString();
	document.cookie = cname + '=' + cvalue + ';' + expires + ';path=/';
};
