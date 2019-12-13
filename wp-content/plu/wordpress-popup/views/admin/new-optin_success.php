<?php
/**
 *
 */
?>
<div id="wpoi-complete-message" style="display: none;">

    <div class="wpoi-complete-mask"></div>

    <div class="wpoi-complete-wrap">

        <div class="row">

            <section class="box content-box">

                <div class="box-title">

                    <h3 class="title-alternative"><?php esc_attr_e('SUCCESS', 'wordpress-popup'); ?></h3>

                </div>

                <div class="box-content">

                    <div class="wpoi-message">

                        <p><?php esc_attr_e('Nice one, you’ve just created your first opt-in. Visitors will see it and start sending emails your way right after you activate it. You can check out your current opt-in and change its status from the Opt-ins menu. When you’re in Test Mode, only you will be able to see your opt-ins.', 'wordpress-popup'); ?></p>

                    </div>

                    <table class="wpoi-success-table">

                        <thead>

                        <tr>

                            <th><?php echo esc_html( $new_optin->optin_name ); ?></th>

                            <th>
                                <?php esc_attr_e('Admin Test', 'wordpress-popup'); ?>
                                <span class="wpoi-tooltip"  tooltip="<?php esc_attr_e('Allows logged-in admins to test Opt-in before Activating it.', 'wordpress-popup'); ?>">
                                    <span class="dashicons dashicons-editor-help  wpoi-icon-info"></span>
                                </span>
                            </th>

                            <th><?php esc_attr_e('Active', 'wordpress-popup'); ?></th>

                        </tr>

                        </thead>

                        <tbody>

                        <?php foreach( $types as $type_key => $type ): ?>

                            <tr>

                                <td class="display-settings-icon">
										<span class="success-settings-list icon after-c">
											<?php echo esc_html( $type ); ?>
										</span>
                                </td>

                                <td class="tc">

										<span class="toggle test-mode">

											<input id="new-optin-testmode-active-state-<?php echo esc_attr($type_key) ."-". esc_attr( $new_optin->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce('inc_opt_toggle_type_test_mode') ); ?>" class="toggle-checkbox wpoi-testmode-active-state" type="checkbox" data-type="<?php echo esc_attr($type_key); ?>" data-id="<?php echo esc_attr($new_optin->id); ?>" <?php checked( (bool) $new_optin->is_test_type_active( $type_key ), true ); ?>  >

											<label class="toggle-label" for="new-optin-testmode-active-state-<?php echo esc_attr($type_key) ."-". esc_attr( $new_optin->id ); ?>"></label>

										</span>

                                </td>

                                <td class="tc">

										<span class="toggle">

											<input id="<?php echo esc_attr( 'new-optin-' . $type_key . '-active-state-' . $new_optin->id ); ?>" class="toggle-checkbox <?php echo esc_attr( 'wpoi-' . $type_key . '-active-state' ); ?> optin-type-active-state" data-nonce="<?php echo esc_attr( wp_create_nonce('inc_opt_toggle_optin_type_state') ); ?>"  data-type="<?php echo esc_attr($type_key); ?>" data-id="<?php echo esc_attr($new_optin->id); ?>" type="checkbox"  <?php checked( $new_optin->settings->{$type_key}->enabled, true ); ?> >

											<label class="toggle-label" for="<?php echo esc_attr( 'new-optin-' . $type_key . '-active-state-' . $new_optin->id ); ?>"></label>

										</span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                    <p class="next-button"><button class="button button-dark-blue" ><?php esc_attr_e('FINISH', 'wordpress-popup'); ?></button></p>

                </div>

            </section>

        </div>

    </div>

</div>
