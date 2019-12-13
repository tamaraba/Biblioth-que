<div class="sui-box-settings-row">

	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Colors Palette', 'wordpress-popup' ); ?></span>
		<span class="sui-description"><?php printf( esc_html__( 'Choose a pre-made palette for your %s and further customize it’s appearance.', 'wordpress-popup' ), esc_html( $smallcaps_singular ) ); ?></span>
	</div>

	<div class="sui-box-settings-col-2">

		<div class="sui-form-field">

			<label class="sui-label"><?php esc_html_e( 'Select a color palette', 'wordpress-popup' ); ?></label>

			<select name="color_palette" data-attribute="color_palette">

				<option value="gray_slate"
					{{ ( 'gray_slate' === color_palette ) ? 'selected' : '' }}>
					<?php esc_html_e( 'Gray Slate', 'wordpress-popup' ); ?>
				</option>

				<option value="coffee"
					{{ ( 'coffee' === color_palette ) ? 'selected' : '' }}>
					<?php esc_html_e( 'Coffee', 'wordpress-popup' ); ?>
				</option>

				<option value="ectoplasm"
					{{ ( 'ectoplasm' === color_palette ) ? 'selected' : '' }}>
					<?php esc_html_e( 'Ectoplasm', 'wordpress-popup' ); ?>
				</option>

				<option value="blue"
					{{ ( 'blue' === color_palette ) ? 'selected' : '' }}>
					<?php esc_html_e( 'Blue', 'wordpress-popup' ); ?>
				</option>

				<option value="sunrise"
					{{ ( 'sunrise' === color_palette ) ? 'selected' : '' }}>
					<?php esc_html_e( 'Sunrise', 'wordpress-popup' ); ?>
				</option>

				<option value="midnight"
					{{ ( 'midnight' === color_palette ) ? 'selected' : '' }}>
					<?php esc_html_e( 'Midnight', 'wordpress-popup' ); ?>
				</option>

			</select>

		</div>

		<div id="hustle-palette-colors" class="sui-form-field">

			<label class="sui-label"><?php esc_html_e( 'Customize the color palette', 'wordpress-popup' ); ?></label>

			<div class="sui-side-tabs" style="margin-top: 5px;">

				<div class="sui-tabs-menu">

					<label
						for="hustle-custom-palette--on"
						class="sui-tab-item {{ _.class( _.isTrue( customize_colors ), 'active' ) }}"
					>
						<input
							type="radio"
							data-attribute="customize_colors"
							value="1"
							id="hustle-custom-palette--on"
							data-tab-menu="hustle-custom-palette"
							{{ _.checked( ( _.isTrue( customize_colors ) ), true ) }}
						/>
						<?php esc_html_e( 'Customize', 'wordpress-popup' ); ?>
					</label>

					<label
						for="hustle-custom-palette--off"
						class="sui-tab-item {{ _.class( _.isFalse( customize_colors ), 'active' ) }}"
					>
						<input
							type="radio"
							data-attribute="customize_colors"
							value="0"
							id="hustle-custom-palette--off"
							data-tab-menu="none"
							{{ _.checked( ( _.isFalse( customize_colors ) ), true ) }}
						/>
						<?php esc_html_e( 'Use Default Colors', 'wordpress-popup' ); ?>
					</label>

				</div>

				<div class="sui-tabs-content">

					<div
						class="sui-tab-content {{ _.class( _.isTrue( customize_colors ), 'active' ) }}"
						data-tab-content="hustle-custom-palette"
					>

						<?php if ( $is_optin ) {
							self::static_render( 'admin/commons/sui-wizard/elements/palette-optin', array( 'module_type' => $module_type ) );
						} else {
							self::static_render( 'admin/commons/sui-wizard/elements/palette-informational', array( 'module_type' => $module_type ) );
						} ?>

					</div>

				</div>

			</div>

		</div>

	</div>

</div>
