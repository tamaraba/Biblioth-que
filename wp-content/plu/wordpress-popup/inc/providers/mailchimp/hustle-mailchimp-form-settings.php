<?php
if( !class_exists("Hustle_Mailchimp_Form_Settings") ):

/**
 * Class Hustle_Mailchimp_Form_Settings
 * Form Settings Mailchimp Process
 *
 */
class Hustle_Mailchimp_Form_Settings extends Hustle_Provider_Form_Settings_Abstract {

	/**
	 * stores mailchimp group data
	 */
	private $groups_data 	= array();

	/**
	 *
	 * stores mailchimp tags data ( static segments )
	 *
	 * @since 4.0.2
	 */
	private $tags_data 	= array();

	/**
	 * For settings Wizard steps
	 *
	 * @since 3.0.5
	 * @since 4.0 Second and third steps added.
	 *
	 * @return array
	 */
	public function form_settings_wizards() {
		// already filtered on Abstract
		// numerical array steps
		return array(
			// 0 - Select List
			array(
				'callback'     => array( $this, 'first_step_callback' ),
				'is_completed' => array( $this, 'is_first_step_completed' ),
			),
			// 3 - Select Tags (yes, fourth step here. It's temporary)
			array(
				'callback'     => array( $this, 'second_step_callback' ),
				'is_completed' => array( $this, 'step_is_completed' ),
			),
			// 1 - Select Group
			array(
				'callback'     => array( $this, 'third_step_callback' ),
				'is_completed' => array( $this, 'step_is_completed' ),
			),
			// 2 - Select Default interests
			array(
				'callback'     => array( $this, 'fourth_step_callback' ),
				'is_completed' => array( $this, 'step_is_completed' ),
			),
		);
	}

	// -------------------------------------------------------
	// Step 'is completed' validations
	// -------------------------------------------------------

	/**
	 * Check if step is completed
	 *
	 * @since 3.0.5
	 * @return bool
	 */
	public function is_first_step_completed() {
		$this->addon_form_settings = $this->get_form_settings_values();
		if ( ! isset( $this->addon_form_settings['list_id'] ) ) {
			// preliminary value
			$this->addon_form_settings['list_id'] = 0;

			return false;
		}

		if ( empty( $this->addon_form_settings['list_id'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return as if the step is indeed completed.
	 * The second and third steps are optional, so no real validation is done here.
	 *
	 * @return boolean
	 */
	public function step_is_completed() {
		return $this->is_first_step_completed();
	}

	// -------------------------------------------------------
	// Main steps callbacks
	// -------------------------------------------------------

	/**
	 * Returns all settings and conditions for the "list" settings.
	 * Select list step.
	 *
	 * @since 3.0.5
	 * @since 4.0 param $is_submit removed.
	 *
	 * @param array $submitted_data
	 * @return array
	 */
	public function first_step_callback( $submitted_data ) {

		$this->addon_form_settings = $this->get_form_settings_values();
		$current_data = array(
			'auto_optin' => '0',
			'list_id' => '',
		);
		$current_data = $this->get_current_data( $current_data, $submitted_data );

		$is_submit = ! empty( $submitted_data['hustle_is_submit'] ) && empty( $submitted_data['page'] );
		if ( $is_submit && empty( $submitted_data['list_id'] ) ) {
			$error_message = __( 'The email list is required.', 'wordpress-popup' );
		}
		if ( !$is_submit && ! empty( $submitted_data['page'] ) ) {
			$settings = array();
			$settings['page'] = $submitted_data['page'];
			$this->save_form_settings_values( $settings );
		}

		$options = $this->get_first_step_options( $current_data );

		if ( is_wp_error( $options ) ) {
			$error_message = $options->get_error_message();
			$options = array();
			// There was an error with the API. No sense on continuing to next step.
			$buttons = array();

		} else {
			// TODO: show "disconnect" only if it's connected alreay.
			$buttons = array(
				'disconnect' => array(
					'markup' => Hustle_Provider_Utils::get_provider_button_markup(
						__( 'Disconnect', 'wordpress-popup' ),
						'sui-button-ghost',
						'disconnect_form',
						true
					),
				),
				'save' => array(
					'markup' => Hustle_Provider_Utils::get_provider_button_markup(
						__( 'Continue', 'wordpress-popup' ),
						'',
						'next',
						true
					),
				),
			);
		}

		if ( ! isset( $error_message ) ) {
			$has_errors = false;
		} else {
			$options[] = array(
				'type'  => 'error',
				'id'    => '',
				'value' => $error_message,
			);
			$has_errors = true;
		}

		$step_html = Hustle_Provider_Utils::get_integration_modal_title_markup(
			__( 'Mailchimp List', 'wordpress-popup' ),
			__( 'Choose the list you want to send form data to.', 'wordpress-popup' )
		);
		$step_html .= Hustle_Provider_Utils::get_html_for_options( $options );

		$response = array(
			'html'       => $step_html,
			'buttons'    => $buttons,
			'has_errors' => $has_errors,
		);

		// Save only after the step has been validated and there are no errors
		if( $is_submit && ! $has_errors ){
			// Save additional data for submission's entry
			if ( !empty( $current_data['list_id'] ) ) {
				$current_data['list_name'] = !empty( $this->lists[ $current_data['list_id'] ] )
						? $this->lists[ $current_data['list_id'] ] . ' (' . $current_data['list_id'] . ')' : $current_data['list_id'];
			}
			if (
				empty( $current_data['list_id'] ) ||
				empty( $this->addon_form_settings['list_id'] ) ||
				$current_data['list_id'] !== $this->addon_form_settings['list_id']
			) {
				$current_data['group'] = null;
				$current_data['group_interest'] = null;
			}

			$this->save_form_settings_values( $current_data );
		}

		return $response;
	}

	/**
	 * Returns all settings and conditions for the "group" settings.
	 * Select group step. This step is optional.
	 *
	 * @since 4.0
	 *
	 * @param array $submitted_data
	 * @return array
	 */
	public function third_step_callback( $submitted_data ) {

		$this->addon_form_settings = $this->get_form_settings_values( false );

		if ( empty( $this->addon_form_settings['list_id'] ) ) {
			error_log( 'missing list id on second step' );
			//go to previous step. this should be set.
		}

		$groups = $this->get_groups( $this->addon_form_settings['list_id'] );

		// If the selected list doesn't have groups, close the modal. No need for this step.
		if( empty( $groups ) || ! is_array( $groups ) ) {
			return array(
				'html' => '',
				'notification' => array(
					'type' => 'success',
					'text' => '<strong>' . $this->provider->get_title() . '</strong> ' . __( 'successfully connected to your form', 'wordpress-popup' ),
				),
				'is_close' => true,
			);
		}


		$is_submit = ! empty( $submitted_data );

		//check groups
		if ( $is_submit && isset( $submitted_data['group'] ) ) {
			$group_id = $submitted_data['group'];
		} elseif ( isset( $this->addon_form_settings['group'] ) ) {
			$group_id = $this->addon_form_settings['group'];
		} else {
			$group_id = '-1';
		}
		$html = '';

		if( ! empty( $groups ) && is_array( $groups ) ){
			$options = $this->get_second_step_options( $groups, $group_id );
			$html .= Hustle_Provider_Utils::get_integration_modal_title_markup(
				__( 'Mailchimp Group', 'wordpress-popup' ),
				__( 'Mailchimp group allows you to categorize your audience based on their interest. Add a group category to your opt-in form and let your visitors choose their interested group.', 'wordpress-popup' )
			);
			$html .= Hustle_Provider_Utils::get_html_for_options( $options );
		}

		if ( $is_submit ) {

			// Store the selected group_id
			$this->addon_form_settings['group'] = $group_id;

			if ( '-1' !== $group_id ) {
				// Store the group name.
				$this->addon_form_settings['group_name'] = $this->groups_data[ $group_id ]['name'];

				// Store the group type. New in 4.0
				$this->addon_form_settings['group_type'] = $this->groups_data[ $group_id ]['type'];
			}

			$this->save_form_settings_values( $this->addon_form_settings );

		}

		$buttons = array(
			'cancel' => array(
				'markup' => Hustle_Provider_Utils::get_provider_button_markup( __( 'Back', 'wordpress-popup' ), '', 'prev', true ),
			),
			'save' => array(
				'markup' => Hustle_Provider_Utils::get_provider_button_markup( __( 'Continue', 'wordpress-popup' ), '', 'next', true ),
			),
		);
		return array(
			'html' => $html,
			'buttons' => $buttons,
		);
	}


	/**
	 * Returns all settings and conditions for the "default interests" settings.
	 * Select group step. This step is optional.
	 *
	 * @since 4.0
	 *
	 * @param array $submitted_data
	 * @return array
	 */
	public function fourth_step_callback( $submitted_data ) {

		$this->addon_form_settings = $this->get_form_settings_values( false );

		if ( ! isset( $this->addon_form_settings['list_id'] ) || empty( $this->addon_form_settings['list_id'] ) ) {
			error_log( 'missing list id on third step' );
			//go to previous step. this should be set.
		}

		$interests = $this->provider->get_remote_interest_options(
			$this->addon_form_settings['selected_global_multi_id'],
			$this->addon_form_settings['list_id'],
			$this->addon_form_settings['group']
		);

		// If no group was selected or the selected group doesn't have interests, close the modal. No need for this step.
		if( empty( $interests ) || ! is_array( $interests ) ) {
			return array(
				'html' => '',
				'notification' => array(
					'type' => 'success',
					'text' => '<strong>' . $this->provider->get_title() . '</strong> ' . __( 'successfully connected to your form', 'wordpress-popup' ),
				),
				'is_close' => true,
			);
		}

		$is_submit = ! empty( $submitted_data );

		if ( $is_submit && isset( $submitted_data['group_interest'] ) ) {
			$interest_id = $submitted_data['group_interest'];
		} elseif ( isset( $this->addon_form_settings['group_interest'] ) ) {
			$interest_id = $this->addon_form_settings['group_interest'];
		} else {
			$interest_id = '';
		}

		$options = $this->get_third_step_options( $interests, $interest_id );
		$html = Hustle_Provider_Utils::get_integration_modal_title_markup(
			__( 'Group Interest', 'wordpress-popup' ),
			__( 'Pre-select an option or leave it empty for users to pick one.', 'wordpress-popup' )
		);
		$html .= Hustle_Provider_Utils::get_html_for_options( $options );


		if ( $is_submit ) {

			$this->addon_form_settings['group_interest'] = isset( $submitted_data['group_interest'] ) ? $submitted_data['group_interest'] : '';

			$this->addon_form_settings['interest_options'] = $interests;

			$this->save_form_settings_values( $this->addon_form_settings );

		}

		$buttons = array(
			'cancel' => array(
				'markup' => Hustle_Provider_Utils::get_provider_button_markup( __( 'Back', 'wordpress-popup' ), '', 'prev', true ),
			),
			'save' => array(
				'markup' => Hustle_Provider_Utils::get_provider_button_markup( __( 'Save', 'wordpress-popup' ), '', 'next', true ),
			),
		);
		return array(
			'html' => $html,
			'buttons' => $buttons,
		);
	}

	/**
	 * Returns all settings and conditions for the "tags" settings.
	 * Select tags. This step is optional
	 *
	 * @since 4.0.2
	 *
	 * @param array $submitted_data
	 * @param bool $is_submit
	 * @return array
	 */
	public function second_step_callback( $submitted_data, $is_submit ) {

		$this->addon_form_settings = $this->get_form_settings_values( false );
		$tags = $this->get_tags( $this->addon_form_settings['list_id'] );

		//check tags
		if ( $is_submit && isset( $submitted_data['tags'] ) ) {
			$tags_id = $submitted_data['tags'];
		} elseif ( isset( $this->addon_form_settings['tags'] ) ) {
			$tags_id = $this->addon_form_settings['tags'];
		} else {
			$tags_id = '-1';
		}

		$options = $this->get_second_step_options_tags( $tags, $tags_id );

		$html = Hustle_Provider_Utils::get_integration_modal_title_markup(
			__( 'Mailchimp Tags', 'wordpress-popup' ),
			__( 'Mailchimp tags help you organize your contacts. You can add as many tags as you want to the subscribers.', 'wordpress-popup' )
		);
		$html .= Hustle_Provider_Utils::get_html_for_options( $options );

		$buttons = array(
			'cancel' => array(
				'markup' => Hustle_Provider_Utils::get_provider_button_markup( __( 'Back', 'wordpress-popup' ), '', 'prev', true ),
			),
			'save' => array(
				'markup' => Hustle_Provider_Utils::get_provider_button_markup( __( 'Continue', 'wordpress-popup' ), '', 'next', true ),
			),
		);

		if ( $is_submit ) {

			if ( is_array( $tags_id ) ) {

				$save_tags = array();
				// Store the tag id and tag name.
				foreach ( $tags_id as $key => $tag_id ) {

					if ( '-1' === $tag_id || empty( $this->tags_data[ $tag_id ] ) ) continue;

					$save_tags[ $tag_id ] = esc_html( $this->tags_data[ $tag_id ] );
				}

				$this->addon_form_settings['tags'] = $save_tags;
			}

			$this->save_form_settings_values( $this->addon_form_settings );

		}

		return array(
			'html' => $html,
			'buttons' => $buttons,
		);
	}

	/**
	 * Refresh list array via API
	 *
	 * @param object $provider
	 * @param string $global_multi_id
	 * @return array
	 */
	public function refresh_global_multi_lists( $provider, $global_multi_id ) {
		$api_key = $provider->get_setting( 'api_key', '', $global_multi_id );
		$api = $provider->get_api( $api_key );

		$lists = array();
		$limit = 50;
		$offset = 0;

		do {
			$response = $api->get_lists( $offset, $limit );

			if ( is_wp_error( $response ) ) {
				$integrations_global_url = add_query_arg( 'page', Hustle_Module_Admin::INTEGRATIONS_PAGE, admin_url( 'admin.php' ) );
				$message = sprintf( __( 'There was an error fetching the lists. Please make sure the %1$sselected account settings%2$s are correct.', 'wordpress-popup' ), '<a href="' . $integrations_global_url . '" target="_blank">', '</a>' );

				// TODO: handle errors from here on all providers gracefully.

				return array();
			}

			$_lists   = $response->lists;
			$total    = $response->total_items;
			if( is_array( $_lists ) ) {
				$lists += wp_list_pluck( $_lists, 'name', 'id' );
			}

			$offset += $limit;
		} while ( $total > $offset );

		return $lists;
	}

	// -------------------------------------------------------
	// Getting the array of options for each step
	// -------------------------------------------------------

	/**
	 * Return an array of options used to display the settings of the 1st step.
	 *
	 * @since 4.0
	 *
	 * @param array $submitted_data
	 * @return array
	 */
	private function get_first_step_options( $submitted_data ) {

		$checked    = ! isset( $submitted_data['auto_optin'] ) ? '' : $submitted_data['auto_optin'];

		$lists = $this->get_global_multi_lists();
		$this->lists = $lists;
		try {
			$selected_list = $this->get_selected_list( $submitted_data );
		} catch ( Exception $e ) {
			return new WP_Error( 'api_error', $e->getMessage() );
		}

		$options =  array(
			array(
				'type'     => 'wrapper',
				'elements' => array(
					'label' => array(
						'type'  => 'label',
						'for'   => 'list_id',
						'value' => __( 'Email List', 'wordpress-popup'),
					),
					'wrapper' => array(
						'type'     => 'wrapper',
						'class'    => 'hui-select-refresh',
						'elements' => array(
							'lists' => array(
								'type'     => 'select',
								'id'       => 'list_id',
								'class'    => 'sui-select',
								'name'     => 'list_id',
								'value'    => $selected_list,
								'options'  => $lists,
								'selected' => $selected_list,
							),
							'refresh' => array(
								'type' => 'raw',
								'value' => Hustle_Provider_Utils::get_provider_button_markup( __( 'Refresh', 'wordpress-popup' ), '', 'refresh_list', true ),
							),
						),
						'is_not_field_wrapper' => true,
					),
				),
			),
			array(
				'type'     => 'wrapper',
				'style'    => 'margin-bottom: 0;',
				'elements' => array(
					'label' => array(
						'type'  => 'label',
						'value' => __( 'Extra Options', 'wordpress-popup' ),
					),
					'new_users' => array(
						'type'       => 'checkbox',
						'name'       => 'auto_optin',
						'value'      => 'subscribed',
						'id'         => 'auto_optin',
						'class'      => 'sui-checkbox-sm sui-checkbox-stacked',
						'attributes' => array(
							'checked' => ( 'subscribed' === $checked || '1' === $checked ) ? 'checked' : ''
						),
						'label'      => __( 'Automatically opt-in new users to the mailing list.', 'wordpress-popup' ),
					),
				),
			),
		);

		return $options;
	}

	/**
	 * Return an array of options used to display the settings of the 2nd step.
	 *
	 * @since 4.0
	 *
	 * @param array $groups
	 * @param string $group_id
	 * @return array
	 */
	private function get_second_step_options( $groups, $group_id = '-1' ) {

		$options = array('-1' => __( 'No group', 'wordpress-popup' ) );

		$groups_data = array();

		foreach( $groups as $group_key => $group ) {
			$group = (array) $group;
			// Create an array with the proper format for the select options.
			$options[ $group['id'] ] = $group['title'] . " ( " . ucfirst( $group['type'] ) . " )";

			// Create an array with the groups data to use it before saving.
			$groups_data[ $group['id'] ]['type'] = $group['type'];
			$groups_data[ $group['id'] ]['name'] = $group['title'];

		}
		$this->groups_data = $groups_data;

		if ( '-1' !== $group_id && isset( $options[ $group_id ] ) ) {
			$first = $group_id;
		} else {
			$first = array_key_first( $options );
		}

		return array(
			'group_id_setup' => array(
				'type'     => 'wrapper',
				'style'    => 'margin-bottom: 0;',
				'elements' => array(
					'label' => array(
						'type'  => 'label',
						'for'   => 'group',
						'value' => __( 'Group Category', 'wordpress-popup' ),
					),
					'group' => array(
						'type'      => 'select',
						'name'      => 'group',
						'value'     => $first,
						'id'        => 'group',
						'class'     => 'hustle_provider_on_change_ajax',
						'options'   => $options,
						'selected'  => $first,
					),
				)
			)
		);
	}

	/**
	 * Return an array of options used to display the settings of the 2nd step for tags.
	 *
	 * @since 4.0
	 *
	 * @param array $tags
	 * @param string $tag_id
	 * @return array
	 */
	private function get_second_step_options_tags( $tags, $tag_ids = array( '-1' ) ) {

		$tags 		= $tags->segments;
		$options 	= wp_list_pluck( $tags, 'name', 'id' );

		$this->tags_data = $options;

		if ( '-1' !== $tag_ids && is_array( $tag_ids ) ) {
			$selected = array_intersect( array_keys( $tag_ids ), array_keys( $options ) );
		} else {
			$selected = array_key_first( $options );
		}

		return array(
			'tag_id_setup' => array(
				'type'     => 'wrapper',
				'style'    => 'margin-bottom: 0;',
				'elements' => array(
					'label' => array(
						'type'  => 'label',
						'for'   => 'tags',
						'value' => __( 'Tags', 'wordpress-popup' ),
					),
					'tags' => array(
						'type'      => 'multiselect',
						'name'      => 'tags[]',
						'id'        => 'tags',
						'class'     => 'sui-select',
						'options'   => $options,
						'selected'  => $selected,
						'attributes'=> array( 'multiple' => 'multiple' ),
					),
				)
			)
		);
	}

	/**
	 * Return an array of options used to display the settings of the 3rd step.
	 * @todo use $interest_id to show the selected values if set. This can be an array if group type is checkbox.
	 *
	 * @since 4.0
	 *
	 * @param array $interests
	 * @param string $interest_id
	 * @return array
	 */
	private function get_third_step_options( $interests, $interest_id ) {

		$interests_options = array( '-1' => __( 'No default choice', 'wordpress-popup' ) );

		$_type = $this->addon_form_settings['group_type'];

		// TODO: this can probably be improved
		$type = 'radio' === $_type ? 'radios' : $_type;
		$type = 'dropdown' === $type || 'hidden' === $type ? 'select' : $type;

		if ( 'select' !== $type ) {
			$interests_options = array();
		}

		$interests_options += $interests;

		$first = array_key_first( $interests_options );

		$field_type = $type;
		$choose_prompt = __( 'Default Interest', 'wordpress-popup' );
		$input_name = 'group_interest';

		switch ( $_type ) {

			case 'dropdown' :
				$field_type = 'select';
				$class      = 'sui-select';
				break;

			case 'checkboxes' :
				$choose_prompt = __( 'Default Interest(s)', 'wordpress-popup' );
				$input_name    = 'group_interest[]';
				$class         = 'sui-checkbox-sm sui-checkbox-stacked';
				break;

			case 'radio' :
				$field_type    = 'radios';
				$class         = 'sui-radio-sm sui-radio-stacked';
				$choose_prompt = sprintf(
					__( 'Default Interest %1$s(clear selection)%2$s', 'wordpress-popup' ),
					'<a href="#" class="hustle-provider-clear-radio-options" style="margin-left: 5px;" data-name="group_interest">',
					'</a>'
				);
				break;

			case 'hidden' :
				$class = '';
				$choose_prompt = __( 'Default Interest', 'wordpress-popup' );
				break;
		}

		return array(
			array(
				'type'     => 'wrapper',
				'style'    => 'margin-bottom: 0;',
				'elements' => array(
					'label' => array(
						'type'  => 'label',
						'for'   => 'group_interest',
						'value' => $choose_prompt,
					),
					'group_interest' => array(
						'type'            => $field_type,
						'name'            => $input_name,
						'value'           => $first,
						'id'              => 'group_interest',
						'class'           => $class,
						'options'         => $interests_options,
						'selected'        => $interest_id,
						'item_attributes' => array()
					),
				)
			),
		);

	}


	// -------------------------------------------------------
	// Retrieving and formatting the API responses
	// -------------------------------------------------------


	/**
	 * Get the groups that belong to the given list.
	 *
	 * @param string $list_id
	 * @param string $api_key
	 * @return array
	 */
	private function get_groups( $list_id, $api_key = '' ) {

		if ( empty( $api_key ) ) {
			$settings = $this->get_form_settings_values( false );
			$global_multi_id = $settings['selected_global_multi_id'];
			$api_key = $this->provider->get_setting( 'api_key', '', $global_multi_id );
		}

		$api = null;
		try {
			$api = $this->provider->get_api( $api_key );
			// TODO: There's a field to retrieve only the total for lists. check if this exists for groups
			$api_categories = $api->get_interest_categories( $list_id );
			if ( is_wp_error( $api_categories ) ) {

				// TODO: handle the wp error properly.
				// Check out how it's handled in first step.
				return array();
				/*return array(
					array(
						"value" => "<label class='wpmudev-label--notice'><span>" . __( 'There was an error fetching the data. Please review your settings and try again.', 'wordpress-popup' ) . "</span></label>",
						"type"  => "label",
					)
				);*/
			}

			$total_groups = $api_categories->total_items;
			if ( $total_groups < 10 ) {
				$total_groups = 10;
			}
			// TODO: avoid this if possible. We're making 2 requests for getting the groups
			$groups = (array) $api->get_interest_categories( $list_id, $total_groups )->categories;

			return $groups;

		}catch (Exception $e){
			// TODO: handle exception
			// return $e;
			return array();
		}

	}

	// -------------------------------------------------------
	// Retrieving and formatting the API responses
	// -------------------------------------------------------


	/**
	 * Get the tags on the given list.
	 *
	 * @since 4.0.2
	 *
	 * @param string $list_id
	 * @param string $api_key
	 * @return array
	 */
	private function get_tags( $list_id, $api_key = '' ) {

		if ( empty( $api_key ) ) {
			$settings = $this->get_form_settings_values( false );
			$global_multi_id = $settings['selected_global_multi_id'];
			$api_key = $this->provider->get_setting( 'api_key', '', $global_multi_id );
		}

		$api = $this->provider->get_api( $api_key );
		$api_tags = $api->get_tags( $list_id );

		// error handling on first step
		return $api_tags;
	}

} // Class end.

endif;
