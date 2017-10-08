<div id="crb-import-app" class="wrap crb-ik-wrapper">
	<h1 class="wp-heading-inline"><?php echo esc_html($title) ?></h1><!-- /.wp-heading-inline -->
	<form action="" method="post" class="crb-ik-form" enctype="multipart/form-data" @submit.prevent="processForm" ref="form" data-action="<?php echo esc_attr($ajax_action) ?>">
		<fieldset :disabled="state === 'loading'">
			<input type="hidden" name="action" value="<?php echo esc_attr( $ajax_action ); ?>" v-model="formData.action">

			<div class="card main-card">
				<h2><?php _e( 'Choose CSV', 'crbik' ); ?></h2>
				<input type="file" name="file" id="choose-file" accept=".csv, text/csv" @change="onFileChange">

				<div class="advanced-settings-wrapper">
					<a href="#" class="advanced" @click.prevent="toggleAdvancedSettings"><?php _e( 'Advanced Settings', 'crbik' ); ?></a>
					<div class="form-table settings-section" v-if="advancedSettingsVisible">
						<table>
							<tr>
								<th><label for="encoding"><?php _e( 'Encoding', 'crbik' ); ?></label></th>
								<td>
									<select name="encoding" id="encoding" v-model="formData.encoding">
										<?php foreach (mb_list_encodings() as $encoding): ?>
											<option value="<?php echo $encoding ?>"><?php echo $encoding ?></option>
										<?php endforeach ?>
									</select>
									<p class="description"><?php _e( 'Specify encoding of the selected file.', 'crbik' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="separator"><?php _e( 'Separator', 'crbik' ); ?></label></th>
								<td>
									<select name="separator" id="separator" v-model="formData.separator">
										<option value=",">Comma ( , )</option>
										<option value=";">Semi-colon ( ; )</option>
										<option value=":">Colon ( : )</option>
										<option value="|">Pipe ( | )</option>
									</select>
								</td>
							</tr>
							<tr>
								<th><label for="enclosure"><?php _e( 'Enclosure', 'crbik' ); ?></label></th>
								<td>
									<select name="enclosure" id="enclosure" v-model="formData.enclosure">
										<option value='"'>Quotation Mark ( " )</option>
										<option value="'">Apostrophe ( ' )</option>
									</select>
								</td>
							</tr>
						</table>
					</div><!-- /.form-table -->
				</div><!-- /.advanced-settings-wrapper -->
				<?php
				submit_button( __( 'Import', 'crbik' ) );
				?>
			</div><!-- /.card -->
		</fieldset>
		<?php wp_nonce_field( 'crb_csv_import' ); ?>
	</form>

	<template v-if="state !== 'pending'">
		<div class="card progress-card" v-if="rowsCount > 0 && processedRowsCount > 0">
			<div class="progress-bar" :style="{ width: progressPercentage + '%' }"><i class="fa fa-refresh fa-spin" v-if="state === 'loading'"></i></div><!-- /.progress-bar -->
		</div><!-- /.card -->

		<p v-for="message in logMessages">
			<span v-text="message"></span> <i class="fa fa-refresh fa-spin" v-if="state === 'loading'"></i>
		</p>
		
	</template>

</div><!-- /.wrap -->