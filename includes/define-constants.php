<?php

/*
 * Constant definitions for use in User Role Editor Mtaandao plugin
 * Author: Vladimir Garagulya
 * Author email: vladimir@shinephp.com
 * Author URI: http://shinephp.com
 * 
*/

define('URE_ADMIN_URL', admin_url());
define('URE_ERROR', 'Error is encountered');
define('URE_PARENT', is_network_admin() ? 'network/users.php':'users.php');
define('URE_KEY_CAPABILITY', 'ure_manage_options');