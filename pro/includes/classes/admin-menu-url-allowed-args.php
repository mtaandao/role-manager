<?php

/*
 * User Role Editor Mtaandao plugin
 * Class URE_Admin_Menu_URL_Allowed_Args - support stuff for Amin Menu Access add-on
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * License: GPL v2+ 
 */

class URE_Admin_Menu_URL_Allowed_Args {

    
    static private function get_for_supported_plugins(&$args, $plugins, $page) {
        foreach($plugins as $plugin) {
            if (!URE_Plugin_Presence::is_active($plugin)) {
                continue;
            }
            $file = URE_PLUGIN_DIR .'pro/includes/classes/supported_plugins/admin-menu-'. $plugin .'-args.php';
            if (!file_exists($file)) {
                continue;
            }
            require_once($file);
            $method = 'get_for_'. $page;
            $plugin_id = str_replace(' ', '_', ucwords(str_replace('-', ' ', $plugin) ) );
            $class = 'URE_Admin_Menu_'. $plugin_id .'_Args';
            if (method_exists($class, $method)) {
                //$args = $class::$method($args); // for PHP version 5.3+
                $args = call_user_func(array($class, $method), $args);  // for PHP veriosn <5.3
            }
        }
    }
    // end of get_for_supported_plugins()
    
    
    static private function get_for_edit() {
        $args = array(
                ''=>array(
                    'post_type',
                    'post_status', 
                    'orderby',
                    'order',
                    's',                    
                    'action',
                    'm',
                    'cat',
                    'filter_action',
                    'paged',
                    'action2',
                    'author',
                    'all_posts',
                    'trashed',
                    'ids',
                    'untrashed',
                    'deleted'
                )  
            );
    
        $plugins = array(
            'download-monitor',
            'eventon',
            'ninja-forms',
            'mnml'
            );
        self::get_for_supported_plugins($args, $plugins, 'edit');
        
        return $args;
    }
    // end of get_for_edit()

    
    static private function get_for_post_new() {
    
        $args = array(''=>array('post_type'));
        $plugins = array('mnml');
        self::get_for_supported_plugins($args, $plugins, 'post_new');
        
        return $args;
    }
    // end of get_args_for_post_new()
    
    
    static private function get_for_upload() {
        
        $args = array(''=>array('mode'));
        
        return $args;
    }
    // end of get_for_upload()
                
    
    static private function get_for_admin() {
                
        $plugins = array(                        
            'global-content-blocks',
            'ninja-forms',
            'unitegallery',
            'mnml'            
            );
        $args = array();        
        self::get_for_supported_plugins($args, $plugins, 'admin');
        
        return $args;
    }
    // end of get_for_admin()

    
    static public function get($command) {
        
        $edit = self::get_for_edit();                        
        $post_new = self::get_for_post_new();                
        $upload = self::get_for_upload();        
        $admin = self::get_for_admin();
        
        $args0 = array(
            'edit.php'=>$edit,  
            'post-new.php'=>$post_new,            
            'upload.php'=>$upload,
            'admin.php'=>$admin
        );
        $args1 = apply_filters('ure_admin_menu_access_allowed_args', $args0);
        
        $result = isset($args1[$command]) ? $args1[$command] : array();        
        
        return $result;
        
    }
    // end of get()

}
// end of class URE_URL_Allowed_Args