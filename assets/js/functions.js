;(function($, window, document, undefined) {
	var $win = $(window);
	var $doc = $(document);

	$doc.ready(function () {

		$('.crb-ik-form').on('submit', function (e) {
			e.preventDefault();

			var $self = $(this);
			var $file = $self.find('#choose-file');
			if ( ! $file[0].files.length ) {
				alert( 'Please choose a file.' );
				return;
			}

			var file = $file[0].files[0];
			if ( file.size > crbikSettings.maxUploadSizeBytes ) {
				alert( 'File must be below ' + crbikSettings.maxUploadSizeHumanReadable + '.' );
			}

			var formData = new FormData();
			formData.append('action', $self.find('input[name="action"]').val());
			formData.append('_wpnonce', $self.find('input[name="_wpnonce"]').val());
			formData.append('file', file);

			$.ajax({
				method: 'POST',
				url: ajaxurl,
				data: formData,
				processData: false,
				contentType: false,
				success: function ( response ) {
					if ( response.status === 'success' ) {
						
					} else {
					}
						alert(response.message);
				}
			});
		});

	});

})(jQuery, window, document);
