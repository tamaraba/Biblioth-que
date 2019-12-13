<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Hustle_Provider_Admin_Ajax {

	/**
	 * Default nonce action
	 *
	 * @since 4.0
	 * @var string
	 */
	private static $_nonce_action = 'hustle_provider_action';

   private static $_instance = null;

	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private static $is_ajax_hooked = false;


	public function __construct() {
		if ( ! self::$is_ajax_hooked ) {
			add_action( 'wp_ajax_hustle_provider_get_providers', array( $this, 'get_addons' ) );
			add_action( 'wp_ajax_hustle_provider_get_form_providers', array( $this, 'get_form_addons' ) );
			add_action( 'wp_ajax_hustle_provider_deactivate', array( $this, 'deactivate' ) );
			add_action( 'wp_ajax_hustle_provider_is_on_module', array( $this, 'is_on_module' ) );
			add_action( 'wp_ajax_hustle_provider_settings', array( $this, 'settings' ) );
			add_action( 'wp_ajax_hustle_provider_form_settings', array( $this , 'form_settings' ) );
			add_action( 'wp_ajax_hustle_provider_form_deactivate', array( $this, 'form_deactivate' ) );
			add_action( 'wp_ajax_hustle_refresh_email_lists', array( $this, 'refresh_email_lists' ) );
			add_action( 'wp_ajax_hustle_provider_insert_local_list', array( $this, 'insert_local_list' ) );

			self::$is_ajax_hooked = true;
		}
	}

	/**
	 * Validate Ajax request
	 *
	 * @since 4.0
	 */
	private function validate_ajax() {
		Opt_In_Utils::validate_ajax_call( self::$_nonce_action );
		Opt_In_Utils::is_user_allowed_ajax( "hustle_edit_integrations" );
	}

	public function deactivate() {
		$this->validate_ajax();

		$data  =  Opt_In_Utils::validate_and_sanitize_fields( $_POST['data'], array( 'slug' ) ); // WPCS: CSRF ok.
		$slug  = $data['slug'];
		$addon = Hustle_Provider_Utils::get_provider_by_slug( $slug );

		//Hustle_Provider_Utils::maybe_attach_addon_hook( $addon );
		$title = $addon->get_title();

		// handling multi_id
		if ( isset( $data['global_multi_id'] ) ) {

			$multi_id_label = '';
			$multi_ids = $addon->get_global_multi_ids();
			foreach ( $multi_ids as $key => $multi_id ) {
				if ( isset( $multi_id['id'] ) && $multi_id['label'] ) {
					if ( $multi_id['id'] === $data['global_multi_id'] ) {
						$multi_id_label = $multi_id['label'];
						break;
					}
				}
			}

			if ( ! empty( $multi_id_label ) ) {
				$title .= ' - ' . $multi_id_label;
			}

		}

		$deactivated = Hustle_Providers::get_instance()->deactivate_addon( $slug, $data );

		if ( ! $deactivated ) {
			wp_send_json_error(
				array(
					'message' => Hustle_Providers::get_instance()->get_last_error_message(),
					'data'    => array(
						'notification' => array(
							'type' => 'error',
							'text' => Hustle_Providers::get_instance()->get_last_error_message(),
						),
					),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Addon Deactivated', 'wordpress-popup' ),
				'data'    => array(
					'notification' => array(
						'type' => 'success',
						'text' => '<strong>' . $title . '</strong> ' . __( 'Successfully disconnected' ),
					),
				),
			)
		);
	}


	/**
	 * Get Addons list, grouped by connected status
	 *
	 * @since 4.0
	 */
	public function get_addons() {
		$this->validate_ajax();

		$providers = Hustle_Provider_Utils::get_registered_addons_grouped_by_connected();
		$connected_html = Opt_In::static_render( 'admin/integrations/page-table-connected', array( 'providers' => $providers['connected'] ), true );
		$not_connected_html = Opt_In::static_render( 'admin/integrations/page-table-not-connected', array( 'providers' => $providers['not_connected'] ), true );;

		wp_send_json_success( array(
			'connected' => $connected_html,
			'not_connected' => $not_connected_html,
		) );

	}

	/**
	 * Refresh email lists
	 *
	 * @since 4.0.2
	 */
	public function refresh_email_lists() {
		$this->validate_ajax();

		$module_id = filter_input( INPUT_POST, 'id' );
		$slug = filter_input( INPUT_POST, 'slug' );
		$type = filter_input( INPUT_POST, 'type' );

		$provider = Hustle_Provider_Utils::get_provider_by_slug( $slug );

		if ( ! $provider ) {
			wp_send_json_error( __( 'Provider not found', 'wordpress-popup' ) );
		}

		$class_name = $provider->get_form_settings_class_name();
		if ( empty( $class_name ) || !class_exists( $class_name ) ) {
			wp_send_json_error( __( 'Settings class not found', 'wordpress-popup' ) );
		}
		$form_settings_instance = new $class_name( $provider, '' );
		$lists = $form_settings_instance->get_global_multi_lists( true, $module_id, $type );

		$list_id = empty( $type ) || 'forms' !== $type ? 'list_id' : 'form_id';
		$options = array(
			'list'  => array(
				'id'       => $list_id,
				'type'     => 'select',
				'name'     => $list_id,
				'default'  => '',
				'options'  => $lists,
				'value'    => '',
				'selected' => '',
				'class'    => 'sui-select',
			),
		);
		$select = Hustle_Provider_Utils::get_html_for_options( $options );

		wp_send_json_success( array(
			'select' => $select,
		) );
	}

	/**
	 * Get providers list, grouped by connected status with module
	 *
	 * @since 4.0
	 */
	public function get_form_addons() {
		$this->validate_ajax();

		$sanitized_data = Opt_In_Utils::validate_and_sanitize_fields( $_POST['data'], array( 'moduleId' => 'moduleId' ) ); // CSRF: ok.
		$module_id = isset( $sanitized_data['moduleId'] ) ? $sanitized_data['moduleId'] : '' ;
		$providers = Hustle_Provider_Utils::get_registered_addons_grouped_by_form_connected( $module_id );
		$connected_html = Opt_In::static_render( 'admin/integrations/wizard-table-connected', array(
			'providers' => $providers['connected'],
			'module_id' => $module_id,
		), true );
		$not_connected_html = Opt_In::static_render( 'admin/integrations/wizard-table-not-connected', array(
			'providers' => $providers['not_connected'],
			'module_id' => $module_id,
		), true );

		$list_connected = array();

		if( ! empty( $providers ) && isset( $providers['connected'] ) ){
			foreach ($providers['connected'] as $key => $value) {
				$list_connected[] = $value['slug'];
			}
		}

		wp_send_json_success( array(
			'connected' => $connected_html,
			'not_connected' => $not_connected_html,
			'list_connected'=> implode( ',', $list_connected ),
			'list_connected_total'=> count( $list_connected ),
		) );
	}

	public function settings() {
		$this->validate_ajax();

		// Sanitizes the data from $_POST['data'] and validate required fields
		$data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT );
		if ( !$data ) {
			$data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		}
		$sanitized_post_data = Opt_In_Utils::validate_and_sanitize_fields( $data, array( 'slug', 'step', 'current_step' ) );
		if( isset( $sanitized_post_data['errors'] ) ){
			wp_send_json_error(
				array(
					'message'	=> __( 'Please check the required fields.', 'wordpress-popup' ),
					'errors'	=> $sanitized_post_data['errors']
				)
			);
		}
		$slug                = $sanitized_post_data['slug'];
		$step                = $sanitized_post_data['step'];
		$current_step        = $sanitized_post_data['current_step'];
		$module_id			 = 0;
		if ( isset( $sanitized_post_data['module_id'] ) ) {
			$module_id = $sanitized_post_data['module_id'];
			// module_id could be unset from $sanitized_post_data when the providers don't expect it anymore within they params
		}

		$provider = Hustle_Provider_Utils::get_provider_by_slug( $slug );

		if ( ! $provider ) {
			wp_send_json_error( __( 'Provider not found', 'wordpress-popup' ) );
		}

		if ( ! $provider->is_settings_available() ) {
			wp_send_json_error(
				array(
					'data' =>  $provider->get_empty_wizard( __( 'This provider does not have settings available', 'wordpress-popup' ) ),
				)
			);
		}

		Hustle_Provider_Utils::maybe_attach_addon_hook( $provider );

		unset( $sanitized_post_data['slug'] );
		unset( $sanitized_post_data['current_step'] );
		unset( $sanitized_post_data['step'] );

		//$wizard = $provider->get_settings_wizard( $sanitized_post_data, $module_id, $current_step, $step, false, $is_step );
		$wizard = $provider->get_settings_wizard( $sanitized_post_data, $module_id, $current_step, $step, true );

		wp_send_json_success(
			array(
				'data' => $wizard
			)
		);
	}

	public function form_settings() {
		$this->validate_ajax();

		// Sanitizes the data from $_POST['data'] and validate required fields
		// 'module_id' will throw errors when creating a new module. This is expected and will stop happening once the modules' wizards are adjusted for 4.0
		if ( isset( $_POST['data'] ) && is_array( $_POST['data'] ) ) { // WPCS: CSRF ok.
			$post_data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		} else {
			$post_data = filter_input( INPUT_POST, 'data' );
		}
		$sanitized_post_data = Opt_In_Utils::validate_and_sanitize_fields( $post_data, array( 'slug', 'step', 'module_id', 'current_step' ) );
		if( isset( $sanitized_post_data['errors'] ) ){
			wp_send_json_error(
				array(
					'message'	=> __( 'Please check the required fields.', 'wordpress-popup' ),
					'errors'	=> $sanitized_post_data['errors']
				)
			);
		}
		$slug                = $sanitized_post_data['slug'];
		$step                = (int)$sanitized_post_data['step'];
		$current_step        = (int)$sanitized_post_data['current_step'];
		$module_id			 = $sanitized_post_data['module_id'];

		$provider = Hustle_Provider_Utils::get_provider_by_slug( $slug );

		if ( ! $provider ) {
			wp_send_json_error( __( 'Provider not found', 'wordpress-popup' ) );
		}

		if ( ! $provider->is_form_settings_available( $module_id ) ) {
			wp_send_json_success(
				array(
					'data' =>  $provider->get_empty_wizard( __( 'This provider does not have form settings available', 'wordpress-popup' ) ),
				)
			);
		}

		Hustle_Provider_Utils::maybe_attach_addon_hook( $provider );

		unset( $sanitized_post_data['slug'] );
		unset( $sanitized_post_data['current_step'] );
		unset( $sanitized_post_data['step'] );
		unset( $sanitized_post_data['module_id'] );

		//$wizard = $provider->get_form_settings_wizard( $sanitized_post_data, $module_id, $current_step, $step, false, $is_step );
		$wizard = $provider->get_form_settings_wizard( $sanitized_post_data, $module_id, $current_step, $step );

		wp_send_json_success(
			array(
				'data' => $wizard
			)
		);
	}

	public function form_deactivate() {
		$this->validate_ajax();

		$sanitized_data = Opt_In_Utils::validate_and_sanitize_fields( $_POST['data'], array( 'slug', 'module_id' ) ); // CSRF: ok.
		$slug                = $sanitized_data['slug'];
		$module_id             = $sanitized_data['module_id'];

		$provider = Hustle_Provider_Utils::get_provider_by_slug( $slug );
		$provider_title = $provider->get_title();

		if ( ! $provider ) {
			$response = array(
				'message' => __( 'Addon not found', 'wordpress-popup' ),
				'data' => array(
					'notification' => array(
						'type' => 'error',
						'text' => '<strong>' . $slug . '</strong> ' . __( 'integration not found', 'wordpress-popup' ),
					),
				)
			);
			wp_send_json_error( $response );
		}

		$form_settings = $provider->get_provider_form_settings( $module_id );
		if ( $form_settings instanceof Hustle_Provider_Form_Settings_Abstract ) {
			unset( $sanitized_data['slug'] );
			unset( $sanitized_data['module_id'] );

			// handling multi_id
			if ( isset( $sanitized_data['multi_id'] ) ) {
				$multi_id_label = '';
				$multi_ids = $form_settings->get_multi_ids();
				foreach ( $multi_ids as $key => $multi_id ) {
					if ( isset( $multi_id['id'] ) && $multi_id['label'] ) {
						if ( $multi_id['id'] === $sanitized_data['multi_id'] ) {
							$multi_id_label = $multi_id['label'];
							break;
						}
					}
				}

				if ( ! empty( $multi_id_label ) ) {
					$provider_title .= ' [' . $multi_id_label . '] ';
				}
			}

			$form_settings->disconnect_form( $sanitized_data );

			$response = array(
				'message' => sprintf( __( 'Successfully disconnected $1$s from this form', 'wordpress-popup' ), $provider_title ),
				'data' => array(
					'notification' => array(
						'type' => 'success',
						'text' => '<strong>' . $provider_title . '</strong> ' . __( 'successfully disconnected from this form', 'wordpress-popup' ),
					),
				)
			);
			wp_send_json_success( $response );
		} else {
			$response = array(
				'message' => sprintf( __( 'Failed to disconnect $1$s from this form', 'wordpress-popup' ), $provider_title ),
				'data' => array(
					'notification' => array(
						'type' => 'error',
						'text' => '<strong>' . $provider->get_title() . '</strong> ' . __( 'Failed to disconnected from this form', 'wordpress-popup' ),
					),
				)
			);
			wp_send_json_error( $response );
		}
	}

	/**
	 * Insert local list into module
	 *
	 * @since 4.0.1
	 */
	public function insert_local_list() {
		$this->validate_ajax();

		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$module = Hustle_Module_Model::instance()->get( $id );

		if ( 0 < $id && ! is_wp_error( $module ) ) {
			$module->update_meta( 'local_list_provider_settings', array( 'local_list_name' => 'hustle-' . wp_rand() ) );

			$integrations_settings = $module->get_integrations_settings()->to_array();
			$integrations_settings['active_integrations'] = 'local_list';
			$module->update_meta( Hustle_Module_Model::KEY_INTEGRATIONS_SETTINGS, $integrations_settings );

			wp_send_json_success();
		}

		if ( is_wp_error( $module ) ) {
			wp_send_json_error( sprintf( __( 'Invalid module!', 'wordpress-popup' ) ) );
		}

		wp_send_json_error();
	}

	/**
	 * Check if is active on module
	 *
	 * @since 4.0.1
	 */
	public function is_on_module() {
		$this->validate_ajax();

		$data  		= Opt_In_Utils::validate_and_sanitize_fields( $_POST['data'], array( 'slug' ) ); // WPCS: CSRF ok.
		$slug		= $data['slug'];

		$provider	= Hustle_Provider_Utils::get_provider_by_slug( $slug );
		$is_multi_on_global = $provider->is_allow_multi_on_global();
		$is_multi_on_form 	= $provider->is_allow_multi_on_form();

		$global_multi_id = filter_var( $_POST['data']['globalMultiId'], FILTER_SANITIZE_STRING );
		$global_multi_id = ( $is_multi_on_global && ! $is_multi_on_form && ! empty( $global_multi_id ) ) ? $global_multi_id : false;

		$modules = Hustle_Provider_Utils::get_modules_by_active_provider( $slug, $global_multi_id );

		$module_data = array();
		foreach( $modules as $module ) {

			$meta 			= $module->get_meta( 'integrations_settings' );
			$module_data[ $module->module_id ] = array(
				'edit_url'	=> html_entity_decode( esc_url( $module->decorated->get_edit_url( 'integrations' ) ) ),
				'name'		=> $module->module_name,
				'type' 		=> $module->module_type,
				'active' 	=> json_decode( $meta )
			);
		}
		if ( ! empty( $module_data ) ) {
			wp_send_json_success( $module_data );
		}

		if ( is_wp_error( $modules ) ) {
			wp_send_json_error( sprintf( __( 'Invalid module!', 'wordpress-popup' ) ) );
		}

		wp_send_json_error();
	}
}
