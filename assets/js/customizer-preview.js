/**
 * Leafpress Digest Customizer 实时预览脚本。
 */
( function ( $ ) {
	'use strict';

	if ( ! window.wp || ! window.wp.customize ) {
		return;
	}

	var api = window.wp.customize;

	api( 'blogname', function ( value ) {
		value.bind( function ( to ) {
			$( '.lp-site-title a' ).text( to );
		} );
	} );

	api( 'blogdescription', function ( value ) {
		value.bind( function ( to ) {
			$( '.lp-site-description' ).text( to );
		} );
	} );
} )( window.jQuery );
