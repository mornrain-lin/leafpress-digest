/**
 * Leafpress Digest 导航交互脚本。
 *
 * 负责：移动端分区导航展开、搜索抽屉开合、快捷键聚焦搜索。
 */
( function () {
	'use strict';

	/**
	 * 移动端分区导航。
	 */
	function initNavToggle() {
		var toggle = document.querySelector( '[data-lp-nav-toggle]' );

		if ( ! toggle ) {
			return;
		}

		var nav = document.getElementById( toggle.getAttribute( 'aria-controls' ) );

		if ( ! nav ) {
			return;
		}

		toggle.addEventListener( 'click', function () {
			var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';

			toggle.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
			nav.classList.toggle( 'is-open', ! expanded );
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! nav.classList.contains( 'is-open' ) ) {
				return;
			}

			if ( nav.contains( event.target ) || toggle.contains( event.target ) ) {
				return;
			}

			nav.classList.remove( 'is-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' !== event.key ) {
				return;
			}

			nav.classList.remove( 'is-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
		} );
	}

	/**
	 * 移动端分区导航的二级菜单折叠。
	 */
	function initSubmenuToggle() {
		var nav = document.getElementById( 'lp-section-nav' );

		if ( ! nav ) {
			return;
		}

		var parents = nav.querySelectorAll( 'li:has(> .sub-menu)' );

		Array.prototype.forEach.call( parents, function ( item ) {
			var link = item.querySelector( ':scope > a' );

			if ( ! link ) {
				return;
			}

			link.addEventListener( 'click', function ( event ) {
				if ( window.matchMedia( '(min-width: 783px)' ).matches ) {
					return;
				}

				var expanded = item.classList.toggle( 'is-expanded' );

				event.preventDefault();
				link.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
			} );
		} );
	}

	/**
	 * 报头搜索抽屉。
	 */
	function initSearchDrawer() {
		var toggle = document.querySelector( '[data-lp-search-toggle]' );

		if ( ! toggle ) {
			return;
		}

		var drawer = document.getElementById( toggle.getAttribute( 'aria-controls' ) );

		if ( ! drawer ) {
			return;
		}

		toggle.addEventListener( 'click', function () {
			var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';

			toggle.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
			drawer.classList.toggle( 'is-open', ! expanded );

			if ( ! expanded ) {
				var field = drawer.querySelector( '.lp-search-form__field' );

				if ( field ) {
					field.focus();
				}
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' !== event.key ) {
				return;
			}

			drawer.classList.remove( 'is-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
		} );
	}

	/**
	 * 快捷键：按 / 聚焦搜索框。
	 */
	function initSearchHotkey() {
		document.addEventListener( 'keydown', function ( event ) {
			if ( '/' !== event.key || event.metaKey || event.ctrlKey || event.altKey ) {
				return;
			}

			var active = document.activeElement;
			var tag = active ? active.tagName : '';

			if ( 'INPUT' === tag || 'TEXTAREA' === tag || 'SELECT' === tag || ( active && active.isContentEditable ) ) {
				return;
			}

			var drawer = document.getElementById( 'lp-search-drawer' );
			var field = document.querySelector( '.lp-search-form__field' );

			if ( ! field ) {
				return;
			}

			event.preventDefault();

			if ( drawer && ! drawer.classList.contains( 'is-open' ) ) {
				var toggle = document.querySelector( '[data-lp-search-toggle]' );

				if ( toggle ) {
					toggle.click();
					return;
				}
			}

			field.focus();
			field.select();
		} );
	}

	function boot() {
		initNavToggle();
		initSubmenuToggle();
		initSearchDrawer();
		initSearchHotkey();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
