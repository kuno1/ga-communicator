'use strict';

/*!
 * Package screen helper.
 *
 * @deps wp-api-fetch, jquery, wp-i18n, ga-custom-dimensions, ga-sandbox
 */

const $ = jQuery;
const { sprintf } = wp.i18n;
const { apiFetch } = wp;

const addError = ( key, message ) => {
	const $message = $( sprintf( '<div class="notice notice-error">%s<span class="notice-dismiss"></span></div>', message ) );
	$( `div[data-key="${ key }"]` ).append( $message );
	setTimeout( () => {
		$message.remove();
	}, 3000 );
};

const update = ( div ) => {
	const key = $( div ).attr( 'data-key' );
	$( div ).addClass( 'loading' );
	// Fetch request.
	$( div ).addClass( 'loading' );
	$( div ).find( 'option[class!="ga-setting-choices-default"]' ).remove();
	apiFetch( {
		path: getPath( key ),
	} ).then( ( res ) => {
		const $select = $( div ).find( '.ga-setting-choices' );
		const curValue = $( div ).find( 'input[type="hidden"]' ).val();
		res.map( ( option ) => {
			const $option = $( sprintf( '<option value="%s">%s(%s)</option>', option.id, option.name, option.id ) );
			if ( option.id === curValue ) {
				$option.attr( 'selected', true );
			}
			$select.append( $option );
		} );
	} ).catch( ( res ) => {
		addError( key, res.message );
	} ).finally( () => {
		$( div ).removeClass( 'loading' );
	} );
};

/**
 * Update handler.
 *
 * @param {Node} div
 */
const onChange = ( div ) => {
	const key = $( div ).attr( 'data-key' );
	$( div ).find( 'input[type="hidden"]' ).val( $( div ).find( 'select' ).val() );
	let index = 0;
	const keys = [ 'ga-profile', 'ga-property' ];
	switch ( key ) {
		case 'ga-account':
			index = 2;
			break;
		case 'ga-property':
			index = 1;
			break;
	}
	for ( let i = 0; i < index; i++ ) {
		update( $( `div[data-key="${ keys[ i ] }"]` ) );
	}
};

/**
 * Get path value.
 *
 * @param {string} key
 * @returns {string}
 */
const getPath = ( key ) => {
	let path = 'ga/v1';
	switch ( key ) {
		case 'ga-account':
			path += '/accounts';
			break;
		case 'ga-property':
			path += sprintf( '/properties/%s', getValue( 'ga-account' ) || ' ' );
			break;
		case 'ga-profile':
			const account = getValue( 'ga-account' );
			const property = getValue( 'ga-property' );
			path += sprintf( '/profiles/%s/%s', getValue( 'ga-account' ) || ' ', getValue( 'ga-property' ) || ' ' );
			break;
	}
	return path;
}

/**
 * Get predefined value.
 *
 * @param {string} key
 * @returns {string}
 */
const getPredefined = ( key ) => {
	const $predefined = $( `code[data-predefined="${ key }"]` );
	if ( $predefined.length ) {
		return $predefined.text();
	} else {
		return '';
	}
}

/**
 * Get value.
 *
 * @param {string} key
 * @returns {string}
 */
const getValue = ( key ) => {
	return getPredefined( key ) || $( `input[name="${ key }"]` ).val();
}

const toggleExample = ( key ) => {
	$( '.ga-setting-example' ).each( function( index, pre ) {
		if ( key === $( pre ).attr( 'data-sample' ) ) {
			$( pre ).addClass( 'toggle' );
		} else {
			$( pre ).removeClass( 'toggle' );
		}
	} );
	let placeholder = '';
	if ( key.length ) {
		placeholder = $( `pre[data-example="${key}"]` ).text();
	}
	$( '#ga-extra' ).attr( 'placeholder', "e.g.\n" + placeholder );
};

$( () => {
	$( '.ga-setting-row' ).each( function ( index, div ) {
		update( div );
		$( div ).find( 'select' ).change( function () {
			onChange( div );
		} );
	} );

	const $select = $( 'select#ga-tag' );
	if ( $select.length ) {
		toggleExample( $select.val() );
		$select.change( function() {
			toggleExample( $( this ).val() );
		} );
	}

	$( '.ga-nav-tab' ).click( function( e ) {
		e.preventDefault();
		$( '.ga-nav-tab-content' ).css( 'display', 'none' );
		$( '.ga-nav-tab' ).removeClass( 'nav-tab-active' );
		$( $( this ).attr( 'href' ) ).css( 'display', 'block' );
		$( this ).addClass( 'nav-tab-active' );
	} );
} );
