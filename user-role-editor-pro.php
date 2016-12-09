<?php
/*
Plugin Name: User Role Manager
Description: Change/add/delete Mtaandao user roles and capabilities.
Version: 4.28.2
Text Domain: ure
Domain Path: /lang/
*/

/*
Copyright 2010-2016  Vladimir Garagulya  (email: support@role-editor.com)
*/

if (!function_exists('get_option')) {
  header('HTTP/1.0 403 Forbidden');
  die;  // Silence is golden, direct call is prohibited
}

if (defined('URE_PLUGIN_URL')) {
   mn_die('It seems that other version of User Role Editor is active. Please deactivate it before use this version');
}
    
define('URE_VERSION', '4.28.2');
define('URE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('URE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('URE_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('URE_PLUGIN_FILE', basename(__FILE__));
define('URE_PLUGIN_FULL_PATH', __FILE__);
define('URE_UPDATE_URL', 'https://www.role-editor.com/update');

require_once(URE_PLUGIN_DIR.'includes/classes/base-lib.php');
require_once( URE_PLUGIN_DIR .'includes/classes/ure-lib.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/ure-lib-pro.php');

// check PHP version
$ure_required_php_version = '5.2.4';
$exit_msg = sprintf( 'User Role Editor requires PHP %s or newer.', $ure_required_php_version ) . 
                         '<a href="http://mtaandao.co.ke/about/requirements/"> ' . 'Please update!' . '</a>';
URE_Lib_Pro::check_version( PHP_VERSION, $ure_required_php_version, $exit_msg, __FILE__ );

// check MN version
$ure_required_mn_version = '4.0';
$exit_msg = sprintf( 'User Role Editor requires Mtaandao %s or newer.', $ure_required_mn_version ) . 
                        '<a href="http://mtaandao.github.io/Upgrading_Mtaandao"> ' . 'Please update!' . '</a>';
URE_Lib_Pro::check_version(get_bloginfo('version'), $ure_required_mn_version, $exit_msg, __FILE__ );

require_once(URE_PLUGIN_DIR .'includes/loader.php');
require_once(URE_PLUGIN_DIR .'pro/includes/loader.php');

$GLOBALS['user_role_editor'] = new User_Role_Editor_Pro();

