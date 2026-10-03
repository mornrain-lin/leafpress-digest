/**
 * Leafpress Digest 订阅表单脚本。
 *
 * 用原生 fetch 提交到 admin-ajax.php，带 Nonce。
 * 提交期间禁用按钮，成功后在表单下方显示结果文案。
 * JS 不可用时表单仍可正常提交（无 JS 增强也能工作）。
 */
( function () {
	'use strict';

	var forms = document.querySelectorAll( '[data-leafpress-form]' );

	if ( ! forms.length ) {
		return;
	}

	/**
	 * 从结果文案中推断样式类型。
	 */
	function messageClass( code ) {
		if ( 'ok' === code || 'already_subscribed' === code || 'pending' === code ) {
			return 'lp-subscribe__message--success';
		}

		return 'lp-subscribe__message--error';
	}

	Array.prototype.forEach.call( forms, function ( form ) {
		var button = form.querySelector( '.lp-subscribe__submit' );
		var result = form.querySelector( '[data-leafpress-result]' );
		var emailField = form.querySelector( 'input[name="leafpress_email"]' );

		if ( ! button || ! result ) {
			return;
		}

		form.addEventListener( 'submit', function ( event ) {
			// 依赖浏览器的 HTML5 必填校验。
			if ( ! form.checkValidity() ) {
				return;
			}

			// JS 可用时拦截，走 AJAX。
			event.preventDefault();

			if ( button.disabled ) {
				return;
			}

			var originalText = button.textContent;

			button.disabled = true;
			button.textContent = ( window.leafpressL10n && window.leafpressL10n.loading ) || '提交中…';
			result.innerHTML = '';

			var data = new FormData( form );

			fetch( form.getAttribute( 'action' ), {
				method: 'POST',
				credentials: 'same-origin',
				body: data
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( payload ) {
					var body = payload && payload.data ? payload.data : {};
					var ok = payload && payload.success;
					var message = body.message || ( window.leafpressL10n && window.leafpressL10n.error ) || '提交失败，请稍后重试。';

					var paragraph = document.createElement( 'p' );
					paragraph.className = 'lp-subscribe__message ' + messageClass( body.code || '' );
					paragraph.textContent = message;
					result.appendChild( paragraph );

					if ( ok ) {
						form.reset();

						if ( emailField ) {
							emailField.blur();
						}
					} else {
						button.disabled = false;
					}
				} )
				.catch( function () {
					var paragraph = document.createElement( 'p' );
					paragraph.className = 'lp-subscribe__message lp-subscribe__message--error';
					paragraph.textContent = ( window.leafpressL10n && window.leafpressL10n.networkError ) || '网络错误，请稍后重试。';
					result.appendChild( paragraph );
					button.disabled = false;
				} )
				.then( function () {
					button.textContent = originalText;
					button.disabled = false;
				} );
		} );
	} );
} )();
