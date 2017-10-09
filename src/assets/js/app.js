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
		rowsCount: 0,
		processedRowsCount: 0,
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
		progressPercentage: function () {
			return  (this.processedRowsCount / this.rowsCount ) * 100;
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

			this.rowsCount = 0;
			this.processedRowsCount = 0;

			this
				.initiateImport()
				.then(this.progressImport.bind(this));
		},
		initiateImport: function () {
			this.logMessages = ['Initiating new import ... '];

			var formData = new FormData();

			for( var key in this.formData ) {
				if ( this.formData.hasOwnProperty(key) ) {
					formData.append( key, this.formData[key] );
				}
			}

			formData.append( 'file', this.file );

			return axios.post(ajaxurl, formData)
				.then(function (response) {
					this.token = response.data.token;
					this.rowsCount = response.data.rows_count;
				}.bind(this))
				.catch(this.handleAjaxError.bind(this));
		},
		progressImport: function () {
			axios({
				url: ajaxurl,
				method: 'post',
				data: {
					action: 'import_row',
					_wpnonce: this.formData._wpnonce,
					token: this.token,
					offset: this.processedRowsCount
				}
			}).then(function (response) {
				var resp = response.data;

				this.processedRowsCount += resp.processed_rows.length;
				
				if (this.processedRowsCount < this.rowsCount) {
					this.progressImport();
				} else {
					this.completeImport();
				}

			}.bind(this));
		},
		handleAjaxError: function () {
			debugger;	
		},
		completeImport: function () {
			debugger
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
							self.rowsCount = response.data.progress_bar.total;
						}

						if ( response.data.progress_bar.hasOwnProperty('current') ) {
							self.processedRowsCount = response.data.progress_bar.current;
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
						step: response.data.step
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
