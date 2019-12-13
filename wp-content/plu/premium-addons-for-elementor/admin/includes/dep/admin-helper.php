<?php

namespace PremiumAddons\Admin\Includes;

use PremiumAddons\Helper_Functions;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin_Helper {
	
	protected $page_slug = 'premium-addons';
    
	private static $instance = null;
    
    public static $current_screen = null;
    
    /**
    * Constructor for the class
    */
    public function __construct() {
        
        add_action( 'current_screen', array( $this, 'get_current_screen' ) );
        
        add_filter( 'plugin_action_links_' . PREMIUM_ADDONS_BASENAME, array( $this, 'insert_action_links' ) );
        
        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
        
    }
    
    /**
	 * Insert action links.
	 *
	 * Adds action links to the plugin list table
	 *
	 * Fired by `plugin_action_links` filter.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 */
   public function insert_action_links( $links ) {

       $papro_path = 'premium-addons-pro/premium-addons-pro-for-elementor.php';
       
       $is_papro_installed = Helper_Functions::is_plugin_installed( $papro_path );
       
       $settings_link = sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'admin.php?page=' . $this->page_slug ), __( 'Settings', 'premium-addons-for-elementor' ) );
       
       $rollback_link = sprintf( '<a href="%1$s">%2$s</a>', wp_nonce_url( admin_url( 'admin-post.php?action=premium_addons_rollback' ), 'premium_addons_rollback' ), __( 'Rollback to Version ' . PREMIUM_ADDONS_STABLE_VERSION, 'premium-addons-for-elementor' ) );
       
       $new_links = array( $settings_link, $rollback_link );
       
       if( ! $is_papro_installed ) {
           
           $theme = Helper_Functions::get_installed_theme();
                    
           $link = sprintf( 'https://premiumaddons.com/pro/?utm_source=plugins-page&utm_medium=wp-dash&utm_campaign=get-pro&utm_term=%s', $theme );
           
           $pro_link = sprintf( '<a href="%s" target="_blank" style="color: #39b54a; font-weight: bold;">%s</a>', $link, __( 'Go Pro', 'premium-addons-for-elementor' ) );
           array_push ( $new_links, $pro_link );
       }
       
       $new_links = array_merge( $links, $new_links );

       return $new_links;
   }
   
   /**
	 * Plugin row meta.
	 *
	 * Extends plugin row meta links
	 *
	 * Fired by `plugin_row_meta` filter.
	 *
	 * @since 3.8.4
	 * @access public
	 *
     *  @return array An array of plugin row meta links.
	 */
    public function plugin_row_meta( $meta, $file ) {
        
        if( Helper_Functions::is_hide_row_meta() )
            return $meta;
        
        if ( PREMIUM_ADDONS_BASENAME == $file ) {
            
            $theme = Helper_Functions::get_installed_theme();
                    
            $link = sprintf( 'https://premiumaddons.com/support/?utm_source=plugins-page&utm_medium=wp-dash&utm_campaign=get-support&utm_term=%s', $theme );
            
            $row_meta = [
				'docs' => '<a href="' . esc_attr( $link ) . '" aria-label="' . esc_attr( __( 'View Premium Addons for Elementor Documentation', 'premium-addons-for-elementor' ) ) . '" target="_blank">' . __( 'Docs & FAQs', 'premium-addons-for-elementor' ) . '</a>',
				'videos' => '<a href="https://www.youtube.com/watch?v=D3INxWw_jKI&list=PLLpZVOYpMtTArB4hrlpSnDJB36D2sdoTv" aria-label="' . esc_attr( __( 'View Premium Addons Video Tutorials', 'premium-addons-for-elementor' ) ) . '" target="_blank">' . __( 'Video Tutorials', 'premium-addons-for-elementor' ) . '</a>',
			];

			$meta = array_merge( $meta, $row_meta );
        }

        return $meta;
       
    }
    
    /**
     * Gets current screen slug
     * 
     * @since 3.3.8
     * @access public
     * 
     * @return string current screen slug
     */
    public static function get_current_screen() {
        
        self::$current_screen = get_current_screen()->id;
        
        return isset( self::$current_screen ) ? self::$current_screen : false;
        
    }
    
    public static function get_instance() {
        if( self::$instance == null ) {
            self::$instance = new self;
        }
        return self::$instance;
    }
       
}

if( ! function_exists('get_admin_helper_instance') ) {
    /**
	 * Returns an instance of the plugin class.
     * 
	 * @since  3.3.8
     * 
	 * @return object
	 */
    function get_admin_helper_instance() {
        return Admin_Helper::get_instance();
    }
}

get_admin_helper_instance();