var app = new Vue({
	el: '#crb-import-app',
	data: {
		advancedSettingsVisible: false,
		loading: false,
		file: false,
		formData: [
			{ name: 'action', value : '' },
			{ name: '_wpnonce', value : '' },
			{ name: 'encoding', value : 'UTF-8' },
			{ name: 'separator', value : ';' },
			{ name: 'enclosure', value : "'" }
		],
		progressAreaShow: false,
		progressAreaMessages: [],
	},
	mounted: function () {
		this.formData[0].value = this.$refs.form.getAttribute('data-action');
		this.formData[1].value = document.getElementById('_wpnonce').value;
	},
	// computed: {
	// 	hasMessages: function () {
	// 		return !!this.messages.length;
	// 	}
	// },
	watch: {
		loading: function () {
			this.progressAreaShow = true;
		},
		progressAreaMessages: function (value) {
			this.progressAreaShow = !!value.length;
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

			this.progressAreaMessages = [];

			this.sendRequest( formData );

		},
		populateFormData: function () {
			var formData = new FormData();

			for (var i = 0; i <= this.formData.length - 1; i++) {
				var name = this.formData[i].name;
				var value = this.formData[i].value;

				formData.append( name, value );
			}

			formData.append( 'file', this.file );

			return formData;
		},
		sendRequest: function ( formData ) {
			var self = this;
			self.loading = true;

			axios.post(ajaxurl, formData)
				.then(function (response) {
					self.loading = false;

					if ( typeof response.data.message !== 'undefined' ) {
						self.progressAreaMessages.push(response.data.message);
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
					self.loading = false;
					// console.log(response);
				});
		}
	}
});
