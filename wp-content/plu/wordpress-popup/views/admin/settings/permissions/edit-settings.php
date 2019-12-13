<div class="sui-box-settings-row">

	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Edit Settings', 'wordpress-popup' ); ?></span>
		<span class="sui-description"><?php esc_html_e( 'Choose the user roles which can access the Settings page and update any settings.', 'wordpress-popup' ); ?></span>
	</div>

	<div class="sui-box-settings-col-2">
        <select class="hustle-update-field-ajax sui-select" data-index="edit_settings" multiple>

			<?php
			$current = isset( $hustle_settings['permission_edit_settings'] )? (array)$hustle_settings['permission_edit_settings'] : array();

			foreach ( $roles as $value => $label ) {
				$admin = 'administrator' === $value;
				printf(
					'<option value="%s" %s %s>%s</option>',
					esc_attr( $value ),
					selected( in_array($value, $current, true) || $admin, true, false ),
					disabled( $admin, true, false ),
					esc_html( $label )
				);
			}
			?>

		</select>
	</div>

</div>
