<div class="wrap crb-ik-wrapper">
	<h1 class="wp-heading-inline"><?php _e( 'CSV Import', 'crbik' ); ?></h1><!-- /.wp-heading-inline -->
	<form action="" method="post" class="crb-ik-form" enctype="multipart/form-data">
		<?php
		wp_nonce_field( 'crb_csv_import' );
		?>
		<input type="hidden" name="action" value="crb_ik_file_import">
		<div class="card main-card">
			<h2><label for="choose-file"><?php _e( 'Choose file', 'crbik' ); ?></label></h2>
			<input type="file" name="file" id="choose-file" accept=".csv, text/csv">
			<?php
			submit_button( __( 'Import', 'crbik' ) );
			?>
		</div><!-- /.card -->
	</form>
	<div class="card result-card" style="display: none;"></div><!-- /.card result-card -->
</div><!-- /.wrap -->