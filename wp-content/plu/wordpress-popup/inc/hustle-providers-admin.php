<?php
/**
 * Class Hustle_Providers_Admin
 * This class handles the global "Integrations" page view.
 *
 * @since 4.0
 *
 */
class Hustle_Providers_Admin extends Hustle_Admin_Page_Abstract {

	public function init() {

		$this->page = 'hustle_integrations';

		$this->page_title = __( 'Hustle Integrations', 'wordpress-popup' );

		$this->page_menu_title = __( 'Integrations', 'wordpress-popup' );

		$this->page_capability = 'hustle_edit_integrations';

		$this->page_template_path = 'admin/integrations';

		add_filter( 'hustle_optin_vars', array( $this, 'register_current_json' ) );
	}

	/**
	 * Get the arguments used when rendering the main page.
	 * 
	 * @since 4.0.1
	 * @return array
	 */
	public function get_page_template_args() {
		$accessibility = Hustle_Settings_Admin::get_hustle_settings( 'accessibility' );
		return array(
			'accessibility' => $accessibility,
			'sui' => Opt_In::get_sui_summary_config(),
		);
	}

	/**
	 * Register js variables.
	 * Used for when an integration comes back from an external redirect.
	 * For example, when doing oAuth with Hubspot.
	 * 
	 * @since 4.0.2
	 *
	 * @param array $current_array
	 * @return array
	 */
	public function register_current_json( $current_array ) {
		
		$current_array['integration_redirect'] = $this->grab_integration_external_redirect();
		$current_array['integrations_url'] = add_query_arg( 'page', Hustle_Module_Admin::INTEGRATIONS_PAGE, admin_url( 'admin.php' ) );

		return $current_array;
	}
	
	/**
	 * Attach back the addon after its external redirect.
	 * Return an array provided by the provider for handling
	 * the user's experience after coming back from the redirect.
	 * 
	 * @since 4.0.2
	 * @return array
	 */
	private function grab_integration_external_redirect() {

		$response = array();
		$action = filter_input ( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

		if ( 'external-redirect' === $action ) {
			
			$nonce = filter_input ( INPUT_GET, 'nonce', FILTER_SANITIZE_STRING );

			if ( $nonce && wp_verify_nonce( $nonce, 'hustle_provider_external_redirect' ) ) {

				$slug = filter_input ( INPUT_GET, 'slug', FILTER_SANITIZE_STRING );
	
				$provider = Hustle_Provider_Utils::get_provider_by_slug( $slug );
	
				if ( $provider instanceof Hustle_Provider_Abstract ) {
	
					$response = $provider->process_external_redirect();
					if ( ! empty( $response ) ) {
						$response['slug'] = $slug;
					}
				}

			} else {

				$response = array(
					'action'	=> 'notification',
					'status'	=> 'error',
					'message'	=> __( "You're not allowed to do this request.", 'wordpress-popup' ),
				);
			}
		}

		return $response;
	}

}
