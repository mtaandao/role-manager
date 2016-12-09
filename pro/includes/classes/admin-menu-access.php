<?php

/*
 * User Role Editor Mtaandao plugin
 * Class URE_Admin_Menu_Access - prohibit selected menu items for role or user
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://role-editor.com
 * License: GPL v2+ 
 */

class URE_Admin_Menu_Access {

    const DEBUG = false;
    const BLOCKED_URLS = 'ure_admin_menu_blocked_urls';
    
    // reference to the code library object
    private $lib = null;    

    public function __construct() {
        
        $this->lib = URE_Lib_Pro::get_instance();
        
        add_action('ure_role_edit_toolbar_service', 'URE_Admin_Menu_View::add_toolbar_buttons');
        add_action('ure_load_js', 'URE_Admin_Menu_View::add_js');
        add_action('ure_dialogs_html', 'URE_Admin_Menu_View::dialog_html');
        add_action('ure_process_user_request', 'URE_Admin_Menu::update_data');
        
        add_action('activated_plugin', 'URE_Admin_Menu_Copy::force_update');
        add_action('admin_menu', array($this, 'menu_glitches_cleanup'), 999);
        add_action('admin_menu', 'URE_Admin_Menu_Copy::update', 1000);  // Jetpack uses 998. We should execute code later
        add_action('admin_head', array($this, 'protect'), 101);
        add_action( 'customize_controls_init', array($this, 'redirect_blocked_urls'), 10);  // Especially for the customize.php URL        
        add_action('admin_bar_menu', array($this, 'replace_admin_bar_my_sites_menu'), 19);
        add_action('mn_before_admin_bar_render', array($this, 'modify_admin_menu_bar'), 101);
        add_filter('media_view_strings', array($this, 'block_media_upload'), 99);
    }
    // end of __construct()


    public function protect() {
        
        $this->remove_blocked_menu_items();
        $this->redirect_blocked_urls();
        
    }
    // end of protect()
    
    
    /* 
     * Remove menus that have no accessible submenus and require privileges
     * that the user does not have.
     * 
     */
    private function remove_prohibited_items() {
        global $menu, $submenu;
        
        foreach ($menu as $menu_item) {
            if (current_user_can($menu_item[1])) {
                continue;
            }
            
            
        } // foreach($menu ...
        
    }
    // end of remove_prohibited_items()
    
    
    /**
     * Some plugins incorrectly modify globals $menu/$submenu and users with changed permissions may get broken $menu/submenu$ structures.
     * This function fixes that, removing broken menu/submenu items.
     * @global type $menu
     * @global array $submenu
     */
    public function menu_glitches_cleanup() {
        global $menu, $submenu;
        
        foreach($menu as $key=>$item) {
            if (!isset($item[1])) {
                unset($menu[$key]);
            }
        }
        foreach($submenu as $key=>$items_list) {
            foreach($items_list as $item_key=>$item) {
                if (!isset($item[1]) || empty($item[1])) {
                    unset($submenu[$key][$item_key]);
                }
            }
        }
        
        $this->remove_prohibited_items();
    }
    // end of menu_glitches_cleanup
    
    /** 
     * Compare links of the current Mtaandao admin submenu and its copy used by User Role Editor admin access add-on
     * The same menu items may have different indexes at the $submenu global array built for the current user and 
     * its copy made for the superadmin
     * 
     * @global array $submenu
     * @param string $key
     * @param string $key1
     * @param array $submenu_copy
     * @return boolean
     */
    private function find_submenu_item($key, $key1, $submenu_copy) {
        global $submenu;
        
        if (!isset($submenu_copy[$key]) || !is_array($submenu_copy[$key])) {
            return false;
        }
        
        $link1 = URE_Admin_Menu::normalize_link($submenu[$key][$key1][2]);
        if (isset($submenu_copy[$key][$key1])) {
            $link2 = URE_Admin_Menu::normalize_link($submenu_copy[$key][$key1][2]);
            if ($link1==$link2) { // submenu item does not match with the same index at a copy
                return $key1;
            }
        }
        
        $key2 = $this->get_key_from_menu_copy($submenu[$key][$key1], $submenu_copy[$key]);
        if (!empty($key2)) {
            $link2 = URE_Admin_Menu::normalize_link($submenu_copy[$key][$key2][2]);
            if ($link1==$link2) {
                return $key2;
            }
        }
        
        return false;
    }
    // end of find_submenu_item()
    
    
    // Check if Mtaandao admin menu link is included into the menu copy, used by User Role Editor admin access add-on
    private function get_key_from_menu_copy($menu_item, $menu_copy) {
        
        $key_found = false;
        foreach($menu_copy as $key=>$menu_item1) {
            if ($menu_item[2]==$menu_item1[2]) {
                $key_found = $key;
                break;
            }
        }
        
        return $key_found;
    }
    // end of get_key_from_menu_copy()
    
    
    private function remove_submenu_item($link, $blocked, $key, $key1, &$blocked_urls) {
        global $submenu;
        
        $item_id = URE_Admin_Menu::calc_menu_item_id('submenu', $link);
        if ( ($blocked['access_model']==1 && in_array($item_id, $blocked['data'])) ||
             ($blocked['access_model']==2 && !in_array($item_id, $blocked['data'])) ||
            // some plugins like 'Yith WooCommerce wishlist' builds menu incorrectly - 
            // menus "Yith Plugins" with non-available capabilities (manage_options, install_plugins) stay available to the user.
            // Therefor we duplicate access checking here
             !current_user_can($submenu[$key][$key1][1]) ) { 
            unset($submenu[$key][$key1]);
            $blocked_urls[] = $link;
        }
    }
    // end of remove_submenu_item()
    
    
    private function remove_from_submenu($blocked, $submenu_copy) {
        global $submenu;
        
        $blocked_urls = array();
        foreach($submenu as $key=>$menu_item) {
            foreach(array_keys($menu_item) as $key1) {
                $key2 = $this->find_submenu_item($key, $key1, $submenu_copy);
                if ($key2===false) {
                    continue;
                }                
                $link = URE_Admin_Menu::normalize_link($submenu_copy[$key][$key2][3]);
                $this->remove_submenu_item($link, $blocked, $key, $key1, $blocked_urls);
            }    
        }
        
        return $blocked_urls;
    }
    // end of remove_from_submenu()
    
    
    private function update_menu_selected($key, $submenu_key, $submenu_copy) {
        global $menu, $submenu;
        
        if (!isset($submenu[$submenu_key])) {
            unset($menu[$key]);
        } elseif (count($submenu[$submenu_key])==0) {
            unset($submenu[$submenu_key]);
            unset($menu[$key]);                        
        } else {
            reset($submenu[$submenu_key]);
            $submenu_1st_key = key($submenu[$submenu_key]);
            $key2 = $this->find_submenu_item($submenu_key, $submenu_1st_key, $submenu_copy);            
            if (count($submenu[$submenu_key])==1) {
                $menu[$key][0] = $submenu_copy[$submenu_key][$key2][0];   // replace Menu item title
            }
            $menu[$key][1] = $submenu_copy[$submenu_key][$key2][1];   // Menu item capability            
            //$menu[$key][2] = $submenu_copy[$submenu_key][$key2][3];   // absolute menu item link            
            // change the link for the current submenu
            //$tmp_copy = $submenu[$submenu_key];
            //unset($submenu[$submenu_key]);
            //$submenu[$menu[$key][2]] = $tmp_copy;
        }        
    }
    // end update_menu_selected()
    
    
    private function update_menu_not_selected($key, $submenu_key, $submenu_copy) {
        global $menu, $submenu;
        
        if (!isset($submenu[$submenu_key])) {
            unset($menu[$key]);
        } elseif (count($submenu[$submenu_key])==0) {
            unset($submenu[$submenu_key]);
            unset($menu[$key]);                    
        } else {
            reset($submenu[$submenu_key]);
            $submenu_1st_key = key($submenu[$submenu_key]);
            $key2 = $this->find_submenu_item($submenu_key, $submenu_1st_key, $submenu_copy);
            if (!empty($key2)) {
                if (count($submenu[$submenu_key])==1) {
                    $menu[$key][0] = $submenu_copy[$submenu_key][$key2][0];   // replace Menu item title
                }
                $menu[$key][1] = $submenu_copy[$submenu_key][$key2][1];   // Menu item capability
                $menu[$key][2] = $submenu_copy[$submenu_key][$key2][3];   // Menu item link
            }
        }        
    }
    // end of update_menu_not_selected()
    
        
    private function refresh_blocked_urls_transient($blocked_urls) {
        global $current_user;
     
        $data = get_transient(self::BLOCKED_URLS);
        if (!is_array($data)) {
            $data = array();
        }
        $data[$current_user->ID] = $blocked_urls;
        set_transient(self::BLOCKED_URLS, $data, 15);
    }
    // end of refresh_blocked_urls()
    
    
    /*
     * Remove "WCMp Commissions" menu item from "WooCommerce" menu and add it to the top level menu items
     */
    private function update_wcmp_menu(&$menu_copy, $submenu_copy) {
    
        $wcmp_item = null;
        foreach($submenu_copy['woocommerce'] as $key=>$item) {
            if ($item[2]==='edit.php?post_type=dc_commission') {
                $wcmp_item = $item; 
                unset($submenu_copy['woocommerce'][$key]);
                break;
            }
        }
        if (!empty($wcmp_item)) {
            $menu_copy[] = $item;
        }
        
    }
    // end of update_wcmp_menu()
    
    
    private function remove_blocked_menu_items() {
        global $current_user, $menu;
                        
        if ($this->lib->is_super_admin()) {
            return;
        }                
        $blocked = URE_Admin_Menu::load_menu_access_data_for_user($current_user);
        if (empty($blocked['data'])) {
            return;
        }

        $menu_copy = URE_Admin_Menu_Copy::get_menu();
        $submenu_copy = URE_Admin_Menu_Copy::get_submenu(); 
        if (URE_Plugin_Presence::is_active('wcmp') && !current_user_can('manage_woocommerce')) {
            $this->update_wcmp_menu($menu_copy, $submenu_copy);
        }
        $blocked_urls = $this->remove_from_submenu($blocked, $submenu_copy);

        foreach($menu as $key=>$menu_item) {
            $key1 = $this->get_key_from_menu_copy($menu_item, $menu_copy);
            if ($key1===false) { // menu item does not found at menu copy
                continue;
            }
            $link = URE_Admin_Menu::normalize_link($menu_copy[$key1][3]);
            
            // some plugins like 'Yith WooCommerce wishlist' builds menu incorrectly - 
            // menus "Yith Plugins" with non-available capabilities (manage_options, install_plugins) stay available to the user.
            // Therefor we duplicate access checking here
            if (!current_user_can($menu_item[1])) {
                $this->update_menu_selected($key, $menu_item[2], $submenu_copy);
                $blocked_urls[] = $link;
                continue;
            }
            
            $item_id1 = URE_Admin_Menu::calc_menu_item_id('menu', $link);
            $item_id2 = URE_Admin_Menu::calc_menu_item_id('submenu', $link);
            if ($blocked['access_model']==1) {
                if (in_array($item_id1, $blocked['data']) || in_array($item_id2, $blocked['data'])) {
                    $this->update_menu_selected($key, $menu_item[2], $submenu_copy);
                    $blocked_urls[] = $link;
                }
            } elseif ($blocked['access_model']==2) {
                if (!in_array($item_id1, $blocked['data']) && !in_array($item_id2, $blocked['data'])) { 
                    $this->update_menu_not_selected($key, $menu_item[2], $submenu_copy);
                    $blocked_urls[] = $link;
                }
            }
        }
        
        $this->refresh_blocked_urls_transient($blocked_urls);        
    }
    // end of remove_blocked_menu_items()
    
        
    protected function extract_command_from_url($url) {
        
        $path = parse_url($url, PHP_URL_PATH);
        $path_parts = explode('/', $path);
        $url_script = end($path_parts);
        $url_query = parse_url($url, PHP_URL_QUERY);
        
        $command = $url_script;
        if (!empty($url_query)) {
            $command .= '?'. $url_query;
        }
        $command = str_replace('&', '&amp;', $command);
        if (empty($command) && in_array('admin', $path_parts)) {
            $command = 'index.php';
        }
        
        return $command;
        
    }
    // end of extract_command_from_url()
    
    
    private function get_link_from_submenu_copy($subkey) {
        
        $submenu_copy = URE_Admin_Menu_Copy::get_submenu();
        foreach($submenu_copy as $sk=>$sm) {
            foreach($sm as $sk1=>$sm1) {
                if ($sm1[2]==$subkey) {
                    return $sm1[3];
                }
            }
        }
        
        return false;
    }
    // end of get_key_from_submenu_copy()
    
    
    private function get_first_available_menu_item($dashboard_allowed) {    
        global $menu;
        
        $menu_copy = URE_Admin_Menu_Copy::get_menu();
        
        $available = '';
        foreach ($menu as $key=>$menu_item) {
            if (URE_Admin_Menu::is_separator($menu_item[4])) {
                continue;
            }
            $key1 = $this->get_key_from_menu_copy($menu_item, $menu_copy);
            if ($key1===false) { // menu item does not found at menu copy
                $link = $this->get_link_from_submenu_copy($menu_item[2]);
            } else {
                $link = $menu_copy[$key1][3];
            }
            if (empty($link)) {
                continue;
            }
            $available = get_option('siteurl') .'/admin/'. $link;
            break;            
        }

        if (empty($available)) {
            $available = get_option('siteurl');
            if ($dashboard_allowed) {
                $available .= '/admin/index.php';
            }            
        }
        
        return $available;        
    }
    // end of get_first_available_menu()
    
    
    /*
     * remove Welcome panel from the dashboard as 
     * it's not good to show direct links to Mtaandao functionality for restricted user
     */ 
    private function remove_welcome_panel($command, $blocked_data, $access_model) {
        if ($command!=='index.php') { 
            return;
        }
        
        $customize_hash = '71cf5c9f472f8adbfc847a3f71ce9f0e'; /* 'submenu'.'customize.php' */
        if (($access_model==1 && in_array($customize_hash, $blocked_data)) || 
            ($access_model==2 && !in_array($customize_hash, $blocked_data))) {
            remove_action('welcome_panel', 'mn_welcome_panel'); 
        }
        
    }
    // end of remove_welcome_panel()
    
    /**
     * Extract edit.php part from string like edit.php?arg1=val1&arg2=val2#anchor
     * 
     * @param string $command
     * @return string
     */
    private function get_php_command($command) {
        
        $question_pos = strpos($command, '?');
        if ($question_pos!==false) {
            $php_command = substr($command, 0, $question_pos);
        } else {
            $php_command = $command;
        }
        if (empty($php_command)) {
            $php_command = 'index.php';
        }
        
        return $php_command;
    }
    // end of get_php_command()
    
    
    private function extract_command_args($command) {
        $args = array();
        $args_pos = strpos($command, '?');
        if ($args_pos==false) {
            return $args;
        }
        $args_str = substr($command, $args_pos + 1);
        $args0 = explode('&amp;', $args_str);
        foreach($args0 as $arg0) {
            $arg1 = explode('=', $arg0);
            $args[$arg1[0]] = isset($arg1[1]) ? $arg1[1] : null;
        }
        
        return $args;
    }
    // end of extract_command_args()


    private function is_command_args_registered($command, $args_to_check) {

        $allowed_args = URE_Admin_Menu_URL_Allowed_Args::get($command);
        $page = $this->get_page();
        if (!isset($allowed_args[$page])) {
            return false;
        }
        
        foreach(array_keys($args_to_check) as $arg) {
            if (!in_array($arg, $allowed_args[$page])) {
                return false;
            }
        }
                    
        return true;
    }
    // end of is_command_args_registered()
    
    
    private function is_command_args_blocked($command, $args_to_check, $blocked_args) {
    
        $result = true;
        if (!empty($blocked_args)) {
            foreach($blocked_args as $key=>$value) {
                if (!isset($args_to_check[$key]) || $args_to_check[$key]!==$value) {
                    $result = false;
                    break;
                }
            }
        } else {
            if ($this->is_command_args_registered($command, $args_to_check)) {
                $result = false;
            }
        }
        
        return $result;
    }
    // end of is_command_args_blocked()

    
    private function is_exact_menu_command($command) {
        $menu_hashes = URE_Admin_Menu::get_menu_hashes();
        if (empty($menu_hashes)) {
            return false;
        }
        $command_list = array_keys($menu_hashes);
        foreach($command_list as $menu_link) {
            if (empty($menu_link)) {
                continue;
            }
            if ($menu_link===$command) {
                return true;
            }
        }
        
        return false;
    }
    // end of is_exact_menu_command()
    

    private function is_blocked_selected_menu_item($command) {
        global $current_user;
        
        if (empty($current_user->ID)) {
            return false;
        }
        $data = get_transient(self::BLOCKED_URLS);
        if (empty($data) || !isset($data[$current_user->ID])) {
            return false;
        }
        $blocked_urls = $data[$current_user->ID];
        if (empty($blocked_urls)) {
            return false;
        }
        
        foreach($blocked_urls as $blocked_url) {
            if ($blocked_url===$command) {
                return true;
            }
        }
        
        if ($this->is_exact_menu_command($command)) {
            // it's unblocked and exactly matches to the command from the main menu
            return false;
        }
        
        foreach($blocked_urls as $blocked_url) {
            $php_command = $this->get_php_command($command);
            $blocked_php_command = $this->get_php_command($blocked_url);
            if ($php_command!==$blocked_php_command) {
                continue;
            }
            // compare command arguments with values together
            $command_args = $this->extract_command_args($command);
            $blocked_command_args = $this->extract_command_args($blocked_url);
            if ($this->is_command_args_blocked($php_command, $command_args, $blocked_command_args)) {
                return true;
            }
        }
        
        return false;
    }
    // end of is_blocked_selected_menu_item()    
    
    
    private function is_blocked_not_selected_menu_item($command) {
        global $current_user;
        
        if (empty($current_user->ID)) {
            return false;
        }
        $data = get_transient(self::BLOCKED_URLS);
        if (empty($data) || !isset($data[$current_user->ID])) {
            return false;
        }
        $blocked_urls = $data[$current_user->ID];
        if (empty($blocked_urls)) {
            return false;
        }
        
        foreach($blocked_urls as $blocked_url) {
            if ($blocked_url===$command) {
                return true;
            }
            $php_command = $this->get_php_command($command);
            $blocked_php_command = $this->get_php_command($blocked_url);
            if ($php_command!==$blocked_php_command) {
                continue;
            }
            // compare command arguments names only
            $command_args = $this->extract_command_args($command);
            $blocked_command_args = $this->extract_command_args($blocked_url);
            if ($this->is_command_args_blocked($php_command, $command_args, $blocked_command_args)) {
                return true;
            }
        }
        
        return false;
    }
    // end of is_blocked_not_selected_menu_item()

                
    private function command_from_main_menu($command) {
        
        if (strpos($command, 'upload.php?mode=')!==false) { // this is command inside Media Library page
            return false;
        }
                
        $result = false;
        $php_command = $this->get_php_command($command);
        $menu_hashes = URE_Admin_Menu::get_menu_hashes();
        $command_list = array_keys($menu_hashes);
        foreach($command_list as $menu_link) {
            if (empty($menu_link)) {
                continue;
            }
            if ($menu_link===$command || strpos($menu_link, $php_command)===0 || strpos($php_command, $menu_link)===0) {
                $result = true;
                break;
            }
        }
        
        return $result;
    }
    // end of command_from_main_menu()
    
    
    /**
     * Restore link back from its extracted parts
     * @param string $php_command - PHP file name, like edit.php
     * @param array $args
     * @return string   - restored link like edit.php?post_type=page&amp;status=trash
     */
    private function build_link_back($php_command, $args) {
        
        $params = '';           
        foreach($args as $param=>$value) {
            if (!empty($params)) {
                $params .= '&amp;';
            }
            $params .= $param .'='. $value;
        }
        $link = $php_command .'?'. $params;
        
        return $link;
    }
    // end of build_link_back()
            

    private function get_page() {
        $page = $this->lib->get_request_var('page', 'get');
        if (empty($page)) {
            $page = '';
        }
        
        return $page;
    }
    // end of get_page()
                    
    
    /**
     * Remove additional parameters which may be added to command inside allowed page, like 
     * edit.php?post_status=trash&post_type=page
     * post-new.php?post_type=page&lang=de
     */
    private function normalize_command($command) {
                
        $php_command = $this->get_php_command($command);
        $allowed_args = URE_Admin_Menu_URL_Allowed_Args::get($php_command);
        if (empty($allowed_args)) {
            return $command;
        }
        $page = $this->get_page();
        if (!isset($allowed_args[$page])) {
            return $command;
        }
                                
        $updated = false;
        $command_args = $this->extract_command_args($command);
        $args = array_keys($command_args);
        foreach($args as $arg) {            
            if (!in_array($arg, $allowed_args[$page])) {
                continue;
            }
            $updated = true;
            unset($command_args[$arg]);
            if (empty($command_args)) {
                return $php_command;
            }                            
        }
        if (!$updated) {
            return $command;
        }
        
        $command = $this->build_link_back($php_command, $command_args);                
        
        return $command;
    }
    // end of normalize_command()    
        
    
    /**
     * Is this command from inside of the allowed page
     * @param string $menu_link - link from admin menu which should be allowed
     * @param string $command - link from browser (without host and path)
     * @param array $selected - list of hashes for the allowed (selected) admin menu items
     * @return boolean
     */
    private function is_command_allowed($menu_link, $command, $allowed_hash, $hash1, $hash2) {
                
        if ($hash1!==$allowed_hash && $hash2!==$allowed_hash) {
           // checked page is blocked
            return false;
        }
        if (strpos($menu_link, $command)!==0) {
           // it's not command from the checked page - commands without additonal parameters should be equal
            return false;
        }
                    
        // this command is allowed, just contains additional parameters
        return true;
    }
    // end of is_command_allowed()
    
    
    private function exclusion_for_not_selected($command, $allowed) {
        
        if ($this->is_blocked_not_selected_menu_item($command)) {
            return false;
        }
        
        // if command was not selected but it does not match with any admin menu (submenu) item - do not block it
        if (!$this->command_from_main_menu($command)) {
            return true;
        }
        
        $n_command = $this->normalize_command($command);
        $menu_hashes = URE_Admin_Menu::get_menu_hashes();
        $menu_links = array_keys($menu_hashes);
        foreach($menu_links as $menu_link) {
            $hash1 = URE_Admin_Menu::calc_menu_item_id('menu', $menu_link);
            $hash2 = URE_Admin_Menu::calc_menu_item_id('submenu', $menu_link);
            
            foreach($allowed as $hash) {
                if ($this->is_command_allowed($menu_link, $n_command, $hash, $hash1, $hash2)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    // end of exclusions_for_not_selected()
    
    
    public function redirect_blocked_urls() {
        
        global $current_user;
        
        if (empty($current_user->ID)) {
            return;
        }
        if ($this->lib->is_super_admin()) {
            return;
        }        
        
        $blocked = URE_Admin_Menu::load_menu_access_data_for_user($current_user);
        if (empty($blocked['data'])) {
            return;
        }
        
        $url = strtolower($_SERVER['REQUEST_URI']);
        $command = $this->extract_command_from_url($url);
        $item_id1 = URE_Admin_Menu::calc_menu_item_id('menu', $command);
        $item_id2 = URE_Admin_Menu::calc_menu_item_id('submenu', $command);        
        if ($blocked['access_model']==1) {  // block selected
            if (!(in_array($item_id1, $blocked['data']) || in_array($item_id2, $blocked['data']) ||
                  $this->is_blocked_selected_menu_item($command))) {
            $this->remove_welcome_panel($command, $blocked['data'], 1);
            return;
            }
        } elseif ($blocked['access_model']==2) {    // block not selected
            if (in_array($item_id1, $blocked['data']) || 
                in_array($item_id2, $blocked['data']))  { 
                $this->remove_welcome_panel($command, $blocked['data'], 2);
                return;
            }          
            if ($this->exclusion_for_not_selected($command, $blocked['data'])) {
                return;                
            }                                    
        }
                
        $url = $this->get_first_available_menu_item($command!=='index.php');
        if (headers_sent()) {
?>
<script>
    document.location.href = '<?php echo $url; ?>';
</script>    
<?php
            die;
        } else {
            mn_redirect($url);
        }
        
    }
    // end of redirect_blocked_urls()

    
    /**
     * Return admin menu bar command string, 
     * but false for admin bar menu items which should be ignored
     * 
     * @param object $menu_item
     * @return boolean
     */
    protected function get_admin_menu_bar_command($menu_item) {
        
        $ignore_list = array(
            'about.php',
            'profile.php',
            'login.php'
        );
        if (empty($menu_item->href)) {
            return false;
        }
        $command = $this->extract_command_from_url($menu_item->href);
        foreach($ignore_list as $skip_it) {
            if (strpos($command, $skip_it)!==false) {
                return false;
            }
        }
                
        return $command;
    }
    // end of get_admin_menu_bar_command()


    private function is_link_blocked($link) {
        global $current_user;
        
        $blocked = URE_Admin_Menu::load_menu_access_data_for_user($current_user);                
        $command = $this->extract_command_from_url($link);
        $item_id1 = URE_Admin_Menu::calc_menu_item_id('menu', $command);
        $item_id2 = URE_Admin_Menu::calc_menu_item_id('submenu', $command);        
        if ($blocked['access_model']==1) {  // block selected
            if (!(in_array($item_id1, $blocked['data']) || in_array($item_id2, $blocked['data']) ||
                  $this->is_blocked_selected_menu_item($command))) {
                  return false;
            }
        } elseif ($blocked['access_model']==2) {    // block not selected
            if (in_array($item_id1, $blocked['data']) || 
                in_array($item_id2, $blocked['data']))  { 
                return false;
            }          
            if ($this->exclusion_for_not_selected($command, $blocked['data'])) {
                return false;
            }                                    
        }   
        
        return true;
    }
    // end of is_link_blocked()
    
    
    /**
     * Copy of res/admin-bar.php::admin_bar_my_sites_menu() function
     * Permissions control was added for access to the MN dasboard at the current site
     * @Important: compare with original code before every Mtaandao core update
     * 
     * @return void
     */
    public function admin_bar_my_sites_menu($admin_bar) {        
        
        // Don't show for logged out users or single site mode.
        if (!is_user_logged_in() || !is_multisite()) {
            return;
        }

        // Show only when the user has at least one site, or they're a super admin.
        if (count($admin_bar->user->blogs) < 1 && !is_super_admin()) {
            return;
        }

        if ($admin_bar->user->active_blog) {
            $my_sites_url = get_admin_url($admin_bar->user->active_blog->blog_id, 'my-sites.php');
        } else {
            $my_sites_url = admin_url('my-sites.php');
        }

        $admin_bar->add_menu(array(
            'id' => 'my-sites',
            'title' => __('My Sites'),
            'href' => $my_sites_url,
        ));

        if (is_super_admin()) {
            $admin_bar->add_group(array(
                'parent' => 'my-sites',
                'id' => 'my-sites-super-admin',
            ));

            $admin_bar->add_menu(array(
                'parent' => 'my-sites-super-admin',
                'id' => 'network-admin',
                'title' => __('Network Admin'),
                'href' => network_admin_url(),
            ));

            $admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-d',
                'title' => __('Dashboard'),
                'href' => network_admin_url(),
            ));
            $admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-s',
                'title' => __('Sites'),
                'href' => network_admin_url('sites.php'),
            ));
            $admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-u',
                'title' => __('Users'),
                'href' => network_admin_url('users.php'),
            ));
            $admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-t',
                'title' => __('Themes'),
                'href' => network_admin_url('themes.php'),
            ));
            $admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-p',
                'title' => __('Plugins'),
                'href' => network_admin_url('plugins.php'),
            ));
            $admin_bar->add_menu(array(
                'parent' => 'network-admin',
                'id' => 'network-admin-o',
                'title' => __('Settings'),
                'href' => network_admin_url('settings.php'),
            ));
        }

        // Add site links
        $admin_bar->add_group(array(
            'parent' => 'my-sites',
            'id' => 'my-sites-list',
            'meta' => array(
                'class' => is_super_admin() ? 'ab-sub-secondary' : '',
            ),
        ));

        $current_blog_id = get_current_blog_id();
        foreach ((array) $admin_bar->user->blogs as $blog) {
            switch_to_blog($blog->userblog_id);
            $blog_id = get_current_blog_id();
            $blavatar = '<div class="blavatar"></div>';

            $blogname = $blog->blogname;

            if (!$blogname) {
                $blogname = preg_replace('#^(https?://)?(www.)?#', '', get_home_url());
            }

            $menu_id = 'blog-' . $blog->userblog_id;

            $admin_bar->add_menu(array(
                'parent' => 'my-sites-list',
                'id' => $menu_id,
                'title' => $blavatar . $blogname,
                'href' => admin_url(),
            ));
            

            $link = admin_url();    // added
            if (current_user_can('read') && !$this->is_link_blocked($link)) {
                $admin_bar->add_menu(array(
                    'parent' => $menu_id,
                    'id' => $menu_id . '-d',
                    'title' => __('Dashboard'),
                    'href' => $link,
                ));
            }
            
            $pto = get_post_type_object('post');
            $link = admin_url('post-new.php');
            if (current_user_can($pto->cap->create_posts) && !$this->is_link_blocked($link)) {                                
                $admin_bar->add_menu(array(
                    'parent' => $menu_id,
                    'id' => $menu_id . '-n',
                    'title' => __('New Post'),
                    'href' => $link,
                ));
            }

            $link = admin_url('edit-comments.php');
            if (current_user_can($pto->cap->edit_posts) && !$this->is_link_blocked($link)) {
                $admin_bar->add_menu(array(
                    'parent' => $menu_id,
                    'id' => $menu_id . '-c',
                    'title' => __('Manage Comments'),
                    'href' => $link,
                ));
            }

            $admin_bar->add_menu(array(
                'parent' => $menu_id,
                'id' => $menu_id . '-v',
                'title' => __('Visit Site'),
                'href' => home_url('/'),
            ));

        }
        $this->lib->restore_after_blog_switching($current_blog_id);
        
    }
    // end of admin_bar_my_sites_menu()
        

    public function replace_admin_bar_my_sites_menu() {
        $multisite = $this->lib->get('multisite');
        if (is_admin() || !$multisite || is_super_admin()) {
            return;
        }
        
        remove_action('admin_bar_menu', 'admin_bar_my_sites_menu', 20);
        add_action('admin_bar_menu', array($this, 'admin_bar_my_sites_menu'), 20);
        
    }
    // end of replace_admin_bar_my_sites_menu()
            
    
    /**
     * Returns true if menu item is a child of 'My Sites' menu at admin top menu bar
     * @param string $parent
     * @return boolean
     */
    private function is_my_sites_child($parent) {
     
        if (empty($parent)) {
            return false;
        }
        if ($parent=='my-sites-list' || strpos($parent, 'blog-')===0) {
            return true;
        }
        
        return false;
    }
    // end of is_my_sites_child()
    
    /**
     * For front-end only
     * 
     * @global MN_User $current_user
     * @global type $admin_bar
     * @return void
     */
    public function modify_admin_menu_bar() {
        global $current_user, $admin_bar;
                
        $nodes = $admin_bar->get_nodes();
        if (empty($nodes)) {
            return;
        }
        
        if ($this->lib->is_super_admin()) {
            return;
        }        
        
        // remove 'SEO' menu from top bar
        if (!current_user_can('manage_options')) {
            $admin_bar->remove_menu('mnseo-menu');
        } 
        
        $blocked = URE_Admin_Menu::load_menu_access_data_for_user($current_user);
        if (empty($blocked)) {
            return;
        }                
        
        // if 'SEO' menu is blocked for the role, block it at top bar
        $seo_item_id = 'e960550080acc7b8154fddae02b72542';        // 'menu'.'admin.php?page=mnseo_dashboard'
        if ( ($blocked['access_model']==1 && in_array($seo_item_id, $blocked['data'])) ||
             ($blocked['access_model']==2 && !in_array($seo_item_id, $blocked['data'])) ) {
            $admin_bar->remove_menu('mnseo-menu');
        }
        
        // if 'Customize' command is blocked for the role, block it at top bar
        $customize_item_id = '71cf5c9f472f8adbfc847a3f71ce9f0e';
        if ( ($blocked['access_model']==1 && in_array($customize_item_id, $blocked['data'])) ||
             ($blocked['access_model']==2 && !in_array($customize_item_id, $blocked['data'])) ) {
            $admin_bar->remove_menu('customize');
        }
        
        
        // If UpdraftPlus menu is blocked, block it at the top bar
        $item_id = '65a42f8ea41f40edd4b652f10dd7a457';
        if ( ($blocked['access_model']==1 && in_array($item_id, $blocked['data'])) ||
             ($blocked['access_model']==2 && !in_array($item_id, $blocked['data'])) ) {
            $admin_bar->remove_menu('updraft_admin_node');
        }
        
        foreach($nodes as $key=>$menu_item) {
            $command = $this->get_admin_menu_bar_command($menu_item);
            if (empty($command)) {
                continue;
            }
            
            if ($this->is_my_sites_child($menu_item->parent) || $menu_item->id=='edit') {
                continue;
            }
            
            $item_id1 = URE_Admin_Menu::calc_menu_item_id('menu', $command);
            $item_id2 = URE_Admin_Menu::calc_menu_item_id('submenu', $command);
            
            if ($blocked['access_model']==1) {  // block selected
                if (in_array($item_id1, $blocked['data'])) {
                    $admin_bar->remove_menu($menu_item->id);
                } elseif (in_array($item_id2, $blocked['data'])) {
                    $admin_bar->remove_node($menu_item->id);
                }
            } elseif ($blocked['access_model']==2) {    // block not selected
                if (!in_array($item_id1, $blocked['data']) && !in_array($item_id2, $blocked['data'])) {
                    $admin_bar->remove_menu($menu_item->id);                
                }
            }
        }
                
    }
    // end of modify_admin_menu_bar()
    
    
    public function block_media_upload($strings) {
        
        global $current_user;
        
        if ($this->lib->is_super_admin()) {
            return $strings;
        }

        $blocked = URE_Admin_Menu::load_menu_access_data_for_user($current_user);
        if (empty($blocked)) {
            return $strings;
        }
        
        foreach($blocked['data'] as $menu_hash) {
            if ($menu_hash=='a6d96d2991e9d58c1d04ef3c2626da56') {  // Media -> Add New
                // Undocumented trick to remove "Upload Files" tab at the Post Editor "Add Media" popup window 
                // Be aware - it may stop working with next version of Mtaandao
                unset($strings['uploadFilesTitle']);    
                break;
            }
        }
                        
        return $strings;
    }
    // end of block_media_upload()
}
// end of URE_Admin_Menu_Access class
