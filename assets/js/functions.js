;(function($, window, document, undefined) {
	var $win = $(window);
	var $doc = $(document);

	$doc.ready(function () {

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
					$messageArea.show();
				},
				success: function ( response ) {
					if ( response.status === 'success' ) {
						$messageArea.html( response.message );
					} else {
						alert(response.message);
					}
				},
				complete: function() {
					$messageArea.removeClass('loading');
				}
			});
		});

	});

})(jQuery, window, document);
