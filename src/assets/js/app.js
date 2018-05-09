if ( document.getElementById('crb-import-app') ) {
	var app = new Vue({
		el: '#crb-import-app',
		data: {
			advancedSettingsVisible: false,

			state: 'pending', // loading, done, error

			file: false,
			formData: {
				'action' : '',
				'_wpnonce' : '',
				'encoding' : 'UTF-8',
				'separator' : ',',
				'enclosure' : '"'
			},
			progressBarTotal: 0,
			progressBarCurrent: 0,
			logMessages: [],
		},
		mounted: function () {
			this.formData.action = this.$refs.form.getAttribute('data-action');
			this.formData._wpnonce = document.getElementById('_wpnonce').value;

			var self = this;
			window.addEventListener('beforeunload', function (e) {
				var message = 'An import is currently running. Please confirm that you\'d like to abort it';

				if (self.state === 'loading') {
					e.returnValue = message;
					return message;
				}
			});
		},
		computed: {
			progressBarPassed: function () {
				if ( this.progressBarTotal < this.progressBarCurrent ) {
					this.progressBarCurrent = this.progressBarTotal;
				}

				return  (this.progressBarCurrent / this.progressBarTotal ) * 100;
			}
		},
		methods: {
			toggleAdvancedSettings: function () {
				this.advancedSettingsVisible = !this.advancedSettingsVisible;
			},
			onFileChange: function (e) {
				if ( typeof e.target.files[0] !== 'undefined' ) {
					this.file = e.target.files[0];
				} else {
					this.file = false;
				}
			},
			processForm: function (e) {
				if ( ! this.file ) {
					alert( 'Please choose a file.' );
					return;
				}

				if ( this.file.size > crbikSettings.maxUploadSizeBytes ) {
					alert( 'File must be below ' + crbikSettings.maxUploadSizeHumanReadable + '.' );
				}

				var formData = this.populateFormData();

				this.logMessages = ['Initiating new import ... '];
				this.progressBarTotal = 0;
				this.progressBarCurrent = 0;

				this.sendRequest( formData );

			},
			populateFormData: function () {
				var formData = new FormData();

				for( var key in this.formData ) {
					if ( this.formData.hasOwnProperty(key) ) {
						formData.append( key, this.formData[key] );
					}
				}

				formData.append( 'file', this.file );

				return formData;
			},
			sendRequest: function ( formData ) {
				var self = this;
				self.state = 'loading';

				axios.post(ajaxurl, formData)
					.then(function (response) {
						if (typeof response.data !== 'object') {
							// the server returned malformed JSON
							self.logMessages.push('Got bad JSON response from the server. See the console for more info. ');
							console.error('Bad JSON response: ' + response.data);
							self.state = 'error';
							return;
						}
						self.state = 'done';

						if ( typeof response.data.progress_bar !== 'undefined' ) {
							if ( response.data.progress_bar.hasOwnProperty('total') ) {
								self.progressBarTotal = response.data.progress_bar.total;
							}

							if ( response.data.progress_bar.hasOwnProperty('current') ) {
								self.progressBarCurrent = response.data.progress_bar.current;
							}
						}

						if ( typeof response.data.message !== 'undefined' ) {
							self.logMessages.push(response.data.message);
						}

						if ( !response.data.hasOwnProperty('step') ) {
							return;
						}

						var data = {
							action: response.data.next_action,
							token: response.data.token,
							step: response.data.step,
							enclosure: response.data.data.enclosure,
							encoding: response.data.data.encoding,
							separator: response.data.data.separator,
						};

						var formData = new FormData();
						for( var key in data ) {
							if ( data.hasOwnProperty(key) ) {
								formData.append( key, data[key] );
							}
						}

						self.sendRequest(formData);
					})
					.catch(function (response) {
						self.state = 'error';
					});
			}
		}
	});
}

