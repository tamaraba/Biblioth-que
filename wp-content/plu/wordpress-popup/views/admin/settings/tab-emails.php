<div id="emails-box" class="sui-box" data-tab="emails" <?php if ( 'emails' !== $section ) echo 'style="display: none;"'; ?>>

	<div class="sui-box-header">
		<h2 class="sui-box-title"><?php esc_html_e( 'Emails', 'wordpress-popup' ); ?></h2>
	</div>

	<div class="sui-box-body">

		<div class="sui-box-settings-row">

			<div class="sui-box-settings-col-1">
				<span class="sui-settings-label"><?php esc_html_e( 'From Headers', 'wordpress-popup' ); ?></span>
				<span class="sui-description"><?php esc_html_e( 'Choose the default sender name and sender email address for all of your outgoing emails from Hustle.', 'wordpress-popup' ); ?></span>
			</div>

			<div class="sui-box-settings-col-2">
				<div class="sui-form-field">
					<label class="sui-label"><?php esc_html_e( 'Sender email address', 'wordpress-popup' ); ?></label>
					<input type="email" name="sender_email_address" placeholder="admin@website.com" class="sui-form-control" value="<?php echo isset( $settings['sender_email_address'] )? esc_attr( $settings['sender_email_address'] ):''; ?>" />
				</div>
				<div class="sui-form-field">
					<label class="sui-label"><?php esc_html_e( 'Sender name', 'wordpress-popup' ); ?></label>
					<input type="text" name="sender_email_name" placeholder="<?php esc_attr_e( 'Website Title', 'wordpress-popup' ); ?>" class="sui-form-control" value="<?php echo isset( $settings['sender_email_name'] )? esc_attr( $settings['sender_email_name'] ):''; ?>" />
				</div>
			</div>

		</div>

	</div>

	<div class="sui-box-footer">

		<div class="sui-actions-right">

			<button class="sui-button sui-button-blue hustle-settings-save" data-nonce="<?php echo esc_attr( wp_create_nonce( 'hustle-settings' ) ); ?>">
				<span class="sui-loading-text"><?php esc_html_e( 'Save Settings', 'wordpress-popup' ); ?></span>
				<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
			</button>

		</div>

	</div>

</div>
