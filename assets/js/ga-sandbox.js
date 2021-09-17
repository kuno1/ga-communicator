/*!
 * Sandbox
 *
 * @package ga-communicator
 * @handle ga-sandbox
 * @deps wp-codemirror, wp-api-fetch
 */

const { CodeMirror, apiFetch } = wp;

// Initialize.
const editor = CodeMirror.fromTextArea( document.getElementById( 'ga-sandbox-inner' ), {
	mode: 'application/json',
	matchBrackets: true,
	autoCloseBrackets: true,
	lineWrapping: true
} );

const pushError = ( message, type = 'success' ) => {
	const text = document.getElementById( 'ga-sandbox-result' );
	[ 'success', 'busy', 'error' ].forEach( ( status ) => {
		if ( status === type ) {
			text.classList.add( status );
		} else {
			text.classList.remove( status );
		}
	} );
	text.value = message;
}

const button = document.getElementById( 'ga-sandbox-exec' );

document.getElementById( 'ga-sandbox-exec' ).addEventListener( 'click', ( e ) => {
	e.preventDefault();
	const value = editor.getValue();
	try {
		// Check validity.
		JSON.parse( value );
		// Start fetch.
		pushError( '', 'busy' );
		button.classList.add( 'is-busy' );
		apiFetch( {
			path: 'ga/v1/batch',
			method: 'post',
			data: {
				data: value,
			}
		} ).then( ( res ) => {
			pushError( JSON.stringify( res, null, 2 ), 'success' );
		} ).catch( ( res ) => {
			pushError( res.message, 'error' );
		} ).finally( () => {
			button.classList.remove( 'is-busy' );
		} );
	} catch ( err ) {
		pushError( err, 'error' );
	}
} );
