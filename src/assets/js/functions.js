;(function($, window, document, undefined) {
	var $win = $(window);
	var $doc = $(document);

	$doc.ready(function () {

		$('.crb-ik-form .advanced').on('click', function (e) {
			e.preventDefault();

			$(this).next('.settings-section').stop(true, false).slideToggle();
		});

		$('.crb-ik-form').on('submit', function (e) {
			e.preventDefault();

			var $self = $(this),
				$file = $self.find('#choose-file');
			if ( ! $file[0].files.length ) {
				alert( 'Please choose a file.' );
				return;
			}

			var file = $file[0].files[0];
			if ( file.size > crbikSettings.maxUploadSizeBytes ) {
				alert( 'File must be below ' + crbikSettings.maxUploadSizeHumanReadable + '.' );
			}

			var $messageArea = $self.next('.result-card'),
				formData = new FormData();
			formData.append('action', $self.find('input[name="action"]').val());
			formData.append('_wpnonce', $self.find('input[name="_wpnonce"]').val());
			formData.append('encoding', $self.find('select[name="encoding"]').val());
			formData.append('separator', $self.find('select[name="separator"]').val());
			formData.append('enclosure', $self.find('select[name="enclosure"]').val());
			formData.append('file', file);

			$.ajax({
				method: 'POST',
				url: ajaxurl,
				data: formData,
				processData: false,
				contentType: false,
				beforeSend: function () {
					$messageArea.html('<div class="spinner"></div>');
					$messageArea.addClass('loading');
					$messageArea.removeClass('success error');
					$messageArea.show();
				},
				error: function () {
					alert('Something went wrong, please try again later');
				},
				success: function ( response ) {
					var message;
					if ( typeof response.message === 'object' ) {
						var messageParts = [];
						$.each(response.message, function (key, value) {
							messageParts.push(value);
						});

						message = messageParts.join('<br/>');
					} else {
						message = response.message;
					}

					$messageArea.html( message );

					if ( response.status === 'success' ) {
						$messageArea.addClass('success');
						if ( response.data.importer === 'started' ) {
							processStep('import_step', response.data.token, 1);
						}
					} else if (response.status === 'error') {
						$messageArea.addClass('error');
					} else {
						$messageArea.hide();
						alert('Something went wrong.');
					}
				},
				complete: function() {
					$messageArea.removeClass('loading');
				}
			});
		});

	});

	function processStep( action, token, step ) {
		var $messageArea = $('.crb-ik-form').next('.result-card');

		$.ajax({
			method: 'POST',
			url: ajaxurl,
			data: {
				action: action,
				token: token,
				step: step
			},
			success: function ( response ) {
				if ( step === '' ) {
					return;
				}

				if ( step === 1 ) {
					$messageArea.html('');
				}

				if ( typeof response.rows !== 'undefined' && response.rows.length ) {
					processStep( response.data.next_action, response.data.token, response.step );
					$messageArea.append('<p>' + response.rows.join('</p><p>') + '</p>');
				} else {
					processStep( 'import_ended', response.data.token, response.step );
				}
			}
		});
	}

})(jQuery, window, document);
