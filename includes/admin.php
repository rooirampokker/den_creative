<?php

/**
 * Class FraudCheckerAdmin
 * Adds backend management options
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

if ( ! class_exists( 'DenFraudCheckerAdmin' ) ) :
class DenFraudCheckerAdmin {
    private $default_tab;

	public function __construct() {
        $this->default_tab = 'api_settings';

		add_action('admin_menu', array($this, 'create_admin_settings'));
		add_action('admin_init', array($this, 'setup_admin_page'));

		add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
		add_action('plugins_loaded', [$this, 'after_plugins_loaded']);
	}
    function screen_option() {
	    return false;
    }
	/**
	 *
	 */
	function create_admin_settings() {
		// Add the menu item and page
		$page_title = 'Den Fraud Checker Configuration';
		$menu_title = 'Fraud Checker';
		$capability = 'manage_options';
		$slug       = 'den_fields';
		$callback   = array($this, 'admin_settings_page_content');
		$icon       = 'dashicons-money-alt';
		$position   = 100;

		$hook = add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);
		add_action("load-$hook", [$this, 'screen_option']);
	}

	/**
	 *
	 */
	public function set_admin_headings()
	{
		$html = '<h2 class="nav-tab-wrapper">';
		$html .= "<h2>Den Fraud Checker plugin configuration</h2>";

		foreach ($tabs as $tab => $name) {
			$class = ($tab == $current_tab) ? 'nav-tab-active' : '';
			$html .= '<a id="'. $tab .'_tab" class="nav-tab ' . $class . '" href="?page=den_fields&tab=' . $tab . '">' . $name . '</a>';
		}
		$html .= '</h2>';

		echo $html;
	}

	/**
	 *
	 */
	public function admin_settings_page_content() { ?>
      <h2>Den Fraud Checker plugin configuration</h2>
      <form method='post' action='options.php'>
            <?php
                settings_fields('api_settings');
                do_settings_sections('api_settings');
                submit_button();
                wp_nonce_field('den_fraud_check_save', 'den_fraud_check_settings');
            ?> </form> <?php
	}

	/**
	 *
	 */
	public function setup_admin_page() {
		add_settings_section('api_settings', '', false, 'api_settings');
		add_settings_section('error_messages', '', false, 'error_messages');

		register_setting('api_settings', 'api_url');
		register_setting('api_settings', 'api_user');
		register_setting('api_settings', 'api_password');

		add_settings_field('api_url', 'Endpoint', array($this, 'api_url_callback'), 'api_settings', 'api_settings');
		add_settings_field('api_user', 'Username', array($this, 'api_user_callback'), 'api_settings', 'api_settings');
		add_settings_field('api_password', 'Password', array($this, 'api_password_callback'), 'api_settings', 'api_settings');
	}

	public function api_url_callback($arguments)
	{
		echo '<input name="api_url" id="api_url" type="text" size="120" value="' . get_option('api_url') . '" />';
	}

	public function api_user_callback($arguments)
	{
		echo '<input name="api_user" id="api_user" type="text" size="120" value="' . get_option('api_user') . '" />';
	}
	public function api_password_callback($arguments)
	{
		echo '<input name="api_password" id="api_password" type="password" size="120" value="' . get_option('api_password') . '" />';
	}
}
endif;