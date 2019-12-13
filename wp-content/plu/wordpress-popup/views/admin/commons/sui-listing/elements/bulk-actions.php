<form class="sui-bulk-actions">

	<label for="hustle-check-all" class="sui-checkbox">
		<input type="checkbox" id="hustle-check-all">
		<span aria-hidden="true"></span>
		<span class="sui-screen-reader-text"><?php esc_html_e( 'Select all', 'wordpress-popup' ); ?></span>
	</label>

	<select
		class="sui-select-sm sui-select-inline"
		data-width="200"
		data-placeholder="<?php esc_html_e( 'Bulk actions', 'wordpress-popup' ); ?>"
	>
		<option value="publish" data-icon="upload-cloud"><?php esc_html_e( 'Publish', 'wordpress-popup' ); ?></option>
		<option value="unpublish" data-icon="unpublish"><?php esc_html_e( 'Unpublish', 'wordpress-popup' ); ?></option>
		<option value="clone" data-icon="copy"><?php esc_html_e( 'Duplicate', 'wordpress-popup' ); ?></option>
		<option value="reset-tracking" data-icon="undo"><?php esc_html_e( 'Reset Tracking Data', 'wordpress-popup' ); ?></option>
		<option value="enable-tracking" data-icon="graph-line"><?php esc_html_e( 'Enable Tracking', 'wordpress-popup' ); ?></option>
		<option value="disable-tracking" data-icon="tracking-disabled"><?php esc_html_e( 'Disable Tracking', 'wordpress-popup' ); ?></option>
		<option value="delete" data-icon="trash"><?php esc_html_e( 'Delete', 'wordpress-popup' ); ?></option>
	</select>

	<button
		type="button"
		class="sui-button hustle-bulk-apply-button"
		data-type="<?php echo esc_attr( $module_type ); ?>"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'hustle-bulk-action' ) ); ?>"
		<?php disabled( true ); ?>
	>
		<span class="sui-loading-text">
			<?php esc_html_e( 'Apply', 'wordpress-popup' ); ?>
		</span>
		<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
	</button>
</form>

<div class="sui-dialog sui-dialog-sm" aria-hidden="true" tabindex="-1" id="hustle-bulk-action-delete-confirmation" data-a11y-dialog-original="true">
	<div class="sui-dialog-overlay" data-a11y-dialog-hide=""></div>
	<div class="sui-dialog-content" aria-labelledby="dialogTitle" aria-describedby="dialogDescription" role="dialog">
		<div class="sui-box" role="document">
			<div class="sui-box-header">
				<h3 class="sui-box-title" id="dialogTitle"><?php esc_html_e( 'Are you sure?', 'wordpress-popup' ); ?></h3>
				<div class="sui-actions-right">
					<button data-a11y-dialog-hide="" class="sui-dialog-close" aria-label="Close this dialog window"></button>
				</div>
			</div>
			<div class="sui-box-body">
				<p id="dialogDescription"><?php esc_html_e( 'Are you sure to delete selected modules? Their additional data, like subscriptions and tracking data, will be deleted as well.', 'wordpress-popup' ); ?></p>
			</div>
			<div class="sui-box-footer">
				<button type="button" class="sui-button" data-a11y-dialog-hide><?php esc_html_e( 'Cancel', 'wordpress-popup' ); ?></button>
				<button type="button" class="sui-button sui-button-red sui-button-ghost hustle-delete">
					<span class="sui-loading-text">
						<i class="sui-icon-trash" aria-hidden="true"></i><?php esc_html_e( 'Delete', 'wordpress-popup' ); ?>
					</span>
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
				</button>
			</div>
		</div>
	</div>
</div>
<div class="sui-dialog sui-dialog-sm" aria-hidden="true" tabindex="-1" id="hustle-bulk-action-reset-tracking-confirmation" data-a11y-dialog-original="true">
	<div class="sui-dialog-overlay" data-a11y-dialog-hide=""></div>
	<div class="sui-dialog-content" aria-labelledby="dialogTitle" aria-describedby="dialogDescription" role="dialog">
		<div class="sui-box" role="document">
			<div class="sui-box-header">
				<h3 class="sui-box-title" id="dialogTitle"><?php esc_html_e( 'Are you sure?', 'wordpress-popup' ); ?></h3>
				<div class="sui-actions-right">
					<button data-a11y-dialog-hide="" class="sui-dialog-close" aria-label="Close this dialog window"></button>
				</div>
			</div>
			<div class="sui-box-body">
				<p id="dialogDescription"><?php esc_html_e( 'Are you sure to reset tracking data of selected modules?', 'wordpress-popup' ); ?></p>
			</div>
			<div class="sui-box-footer">
				<button type="button" class="sui-button" data-a11y-dialog-hide><?php esc_html_e( 'Cancel', 'wordpress-popup' ); ?></button>
				<button type="button" class="sui-button sui-button-red sui-button-ghost hustle-delete">
					<span class="sui-loading-text">
						<i class="sui-icon-trash" aria-hidden="true"></i><?php esc_html_e( 'Reset Tracking', 'wordpress-popup' ); ?>
					</span>
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
				</button>
			</div>
		</div>
	</div>
</div>
