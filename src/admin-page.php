<div class="wrap crb-ik-wrapper">
	<h1 class="wp-heading-inline">{{title}}</h1><!-- /.wp-heading-inline -->
	<form action="" method="post" class="crb-ik-form" enctype="multipart/form-data">
		<input type="hidden" name="action" value="{{ajax-action}}">
		
		<div class="card main-card">
			<h2><?php _e( 'Choose CSV', 'crbik' ); ?></h2>
			<input type="file" name="file" id="choose-file" accept=".csv, text/csv">

			<div class="advanced-settings-wrapper">
				<a href="#" class="advanced"><?php _e( 'Advanced Settings', 'crbik' ); ?></a>
				<div class="form-table settings-section">
					<table>
						<tr>
							<th><label for="encoding"><?php _e( 'Encoding', 'crbik' ); ?></label></th>
							<td>
								<select name="encoding" id="encoding">
									<?php foreach (mb_list_encodings() as $encoding): ?>
										<option value="<?php echo $encoding ?>" <?php echo strtolower($encoding) === 'utf-8' ? 'selected' : '' ?>><?php echo $encoding ?></option>
									<?php endforeach ?>
								</select>
								<p class="description"><?php _e( 'Specify encoding of the selected file.', 'crbik' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="separator"><?php _e( 'Separator', 'crbik' ); ?></label></th>
							<td>
								<select name="separator" id="separator">
									<option value="," selected>Comma ( , )</option>
									<option value=";">Semi-colon ( ; )</option>
									<option value=":">Colon ( : )</option>
									<option value="|">Pipe ( | )</option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="enclosure"><?php _e( 'Enclosure', 'crbik' ); ?></label></th>
							<td>
								<select name="enclosure" id="enclosure">
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
		
		<?php wp_nonce_field( 'crb_csv_import' ); ?>
	</form>
	<div class="card result-card" style="display: none;"></div><!-- /.card result-card -->
</div><!-- /.wrap -->