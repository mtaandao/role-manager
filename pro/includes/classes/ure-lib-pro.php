<?php
/*
 * Stuff specific for User Role Editor Pro Mtaandao plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * 
*/

class Ure_Lib_Pro extends Ure_Lib {
    
    public static function get_instance($options_id = '') {
        
        if (self::$instance === null) {
            if (empty($options_id)) {
                throw new Exception('URE_Lib_Pro::get_inctance() - Error: plugin options ID string is required');
            }
            // new static() will work too
            self::$instance = new URE_Lib_Pro($options_id);
        }

        return self::$instance;
    }
    // end of get_instance()
    
    
    /**
     * Is this the Pro version?
     * @return boolean
     */ 
    public function is_pro() {
        return true;
    }
    // end of is_it_pro()
    
    
    public function reset_active_addons() {
        $this->active_addons = array();
    }
    // end of init_active_addons()
    
    
    public function add_active_addon($addon_id) {
        $this->active_addons[$addon_id] = 1;
    }
    // end of add_active_addon()
    
    
    public function get_active_addons() {
        
        return $this->active_addons;
    }
    // end of get_active_addon()
    
    
    /**
     * Return MN_User object
     * 
     * @param mix $user
     * @return MN_User
     */
    public function get_user($user) {
        if ($user instanceof MN_User) {
            return $user;
        }    

        if (is_int($user)) {    // user ID
            $user = get_user_by('id', $user);
        } else {        // user login
            $user = get_user_by('login', $user);
        }                
        
        return $user;
    }
    // end of get_user()
    
            
    public function get_ure_caps() {
        $ure_caps = parent::get_ure_caps();
        
        $ure_caps['ure_export_roles'] = 1;
        $ure_caps['ure_import_roles'] = 1;
        $ure_caps['ure_admin_menu_access'] = 1;
        $ure_caps['ure_widgets_access'] = 1;
        $ure_caps['ure_widgets_show_access'] = 1;
        $ure_caps['ure_meta_boxes_access'] = 1;
        $ure_caps['ure_other_roles_access'] = 1;
        $ure_caps['ure_edit_posts_access'] = 1;
        $ure_caps['ure_plugins_activation_access'] = 1;   
        $ure_caps['ure_view_posts_access'] = 1;
        if ($this->multisite) {
            $ure_caps['ure_themes_access'] = 1;
        }
        
        return $ure_caps;
    }
    // end of get_ure_caps()
    
    /**
     * return key capability to have access to User Role Editor Plugin
     * override the same method at UreLib to support custom key capability set by the user
     * 
     * @return string
     */
    public function get_key_capability() {
        $ure_key_capability = $this->get_option('ure_key_capability');
        if (!$this->multisite) {
            $key_capability = empty($ure_key_capability) ? URE_KEY_CAPABILITY : $ure_key_capability;
        } else {
            $enable_simple_admin_for_multisite = $this->get_option('enable_simple_admin_for_multisite', 0);
            if ( (defined('URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE') && URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE == 1) || 
                 $enable_simple_admin_for_multisite) {
                $key_capability = empty($ure_key_capability) ? URE_KEY_CAPABILITY : $ure_key_capability;
            } else {
                $key_capability = 'manage_network_users';
            }
        }
        
        return $key_capability;
    }
    // end of get_key_capability()    
    
    
    /**
     * if returns true - make full syncronization of roles for all sites with roles from the main site
     * else - only currently selected role update is replicated
     * 
     * @return boolean
     */
    public function is_full_network_synch() {
        
        if (is_network_admin()) {
            $result = true;
        } else {
            $result = parent::is_full_network_synch();
        }
        
        return $result;
    }
    // end of is_full_network_synch()
       
    
    public function user_can_which($user, $caps) {
    
        foreach($caps as $cap){
            if ($this->user_has_capability($user, $cap)) {
                return $cap;
            }
        }

        return '';        
    }
    // end of user_can_which()
 
    
    public function user_can_role($user, $role) {
        
        if (empty($user) || !is_a($user, 'MN_USER') || empty($user->roles)) {
            return false;
        }
        
        foreach($user->roles as $user_role) {
            if ($user_role===$role) {
                return true;
            }
        }
        
        return false;
    }
    // end of user_can_role()
    
    /**
     * if existing user was not added to the current blog - add him
     * @global type $blog_id
     * @param type $user
     * @return bool
     */
    protected function check_blog_user($user) {
        global $blog_id;
        
        $result = true;
        if (is_network_admin()) {
            if (!array_key_exists($blog_id, get_blogs_of_user($user->ID)) ) {
                $result = add_existing_user_to_blog( array( 'user_id' => $user->ID, 'role' => 'subscriber' ) );
            }
        }

        return $result;
    }
    // end of check_blog_user()
    
    
    /** Get user roles and capabilities from the main blog
     * 
     * @param int $user_id
     * @return boolean
     */
    protected function get_user_caps_from_main_blog($user_id) {
        global $mndb;
        
        $meta_key = $mndb->prefix.'capabilities';
        $query = "select meta_value
                    from $mndb->usermeta
                    where user_id=$user_id and meta_key='$meta_key' limit 0, 1";
        $user_caps = $mndb->get_var($query);
        if (empty($user_caps)) {
            return false;
        }
        return $user_caps;      
     
    }
    // end of get_user_caps_from_main_blog()
    
    
    protected function update_user_caps_for_blog($blog_id, $user_id, $user_caps) {
        global $mndb;
        
        $meta_key = $mndb->prefix.$blog_id.'_capabilities';
        $query = "update $mndb->usermeta
                    set meta_value='$user_caps'
                    where user_id=$user_id and meta_key='$meta_key' limit 1";
        $result = $mndb->query($query);
        
        return $result;
    }
    // end of update_user_caps_for_blog()
    
    
    protected function network_update_user($user) {        
                        
        $user_caps = $this->get_user_caps_from_main_blog($user->ID);
        $user_blogs = get_blogs_of_user($user->ID); // list of blogs, where user was registered           
        $blog_ids = $this->blog_ids;    // full list of blogs
        unset($blog_ids[0]);  // do not touch the main blog, it was updated already
        foreach($blog_ids as $blog_id) {
            if (!array_key_exists($blog_id, $user_blogs)) {
                $result = add_user_to_blog($blog_id, $user->ID, 'subscriber');
                if ($result!==true) {
                   return false;
                }
                do_action('added_existing_user', $user->ID, $result);                
            }
            $result = $this->update_user_caps_for_blog($blog_id, $user->ID, $user_caps);
            if ($result===false) {
                return false;
            }
        }
        
        return true;
    }
    // end of network_update_user()

    
    public function init_result() {
        
        $result = new stdClass();
        $result->success = false;
        $result->message = '';
        
        return $result;
    }
    // end of init_result()
                    
         
    /**
     * Initializes roles and capabiliteis list if it is not done yet
     * 
     */
    protected function init_caps() {
        if (empty($this->full_capabilities)) {
            $this->roles = $this->get_user_roles();
            $this->init_full_capabilities();
        }        
    }
    // end of init_caps()
    
    
    public function build_html_caps_blocked_for_single_admin() {
        $this->init_caps();
        $allowed_caps = $this->get_option('caps_allowed_for_single_admin', array());
        $html = '';
        // Core capabilities list
        foreach ($this->full_capabilities as $capability) {
            if (!$capability['mn_core']) { // show MN built-in capabilities 1st
                continue;
            }
            if (!in_array($capability['inner'], $allowed_caps)) {
                $html .= '<option value="' . $capability['inner'] . '">' . $capability['inner'] . '</option>' . "\n";
            }
        }
        // Custom capabilities
        $quant = count($this->full_capabilities) - count($this->get_built_in_mn_caps());
        if ($quant > 0) {            
            // Custom capabilities list
            foreach ($this->full_capabilities as $capability) {
                if ($capability['mn_core']) { // skip MN built-in capabilities 1st
                    continue;
                }
                if (!in_array($capability['inner'], $allowed_caps)) {
                    $html .= '<option value="' . $capability['inner'] . '" style="color: blue;">' . $capability['inner'] . '</option>' . "\n";
                }
            }
        }

        return $html;
    }
    // end of build_html_caps_blocked_for_single_admin()


    public function build_html_caps_allowed_for_single_admin() {
        $allowed_caps = $this->get_option('caps_allowed_for_single_admin', array());
        if (count($allowed_caps)==0) {
            return '';
        }
        $this->init_caps();
        $build_in_mn_caps = $this->get_built_in_mn_caps();
        $html = '';
        // Core capabilities list
        foreach ($allowed_caps as $cap) {
            if (!isset($build_in_mn_caps[$cap])) { // show MN built-in capabilities 1st
                continue;
            }
            $html .= '<option value="' . $cap . '">' . $cap . '</option>' . "\n";
        }
        // Custom capabilities
        $quant = count($this->full_capabilities) - count($this->get_built_in_mn_caps());
        if ($quant > 0) {
            // Custom capabilities list
            foreach ($allowed_caps as $cap) {
                if (isset($build_in_mn_caps[$cap])) { // skip MN built-in capabilities 1st
                    continue;
                }
                $html .= '<option value="' . $cap . '" style="color: blue;">' . $cap . '</option>' . "\n";
            }
        }

        return $html;
    }
    // end of build_html_caps_allowed_for_single_admin()
    

    /**
     * Exclude unexisting capabilities
     * @param string $user_caps_array - name of POST variable with array of capabilities from user input
     */
    public function filter_existing_caps_input($user_caps_array) {
        
        if (isset($_POST[$user_caps_array]) && is_array($_POST[$user_caps_array])) {
            $user_caps = $_POST[$user_caps_array];
        } else {
            $user_caps = array();
        }
        if (count($user_caps)) {
            $this->init_caps();            
            foreach ($user_caps as $cap) {
                if (!isset($this->full_capabilities[$cap])) {
                    unset($user_caps[$cap]);
                }
            }
        }

        return $user_caps;
    }
    // end of filter_existing_caps_input()
            
    
    public function get_edit_custom_post_type_caps() {
        $caps = get_transient('ure_edit_custom_post_type_caps');
        if (empty($caps)) {
            // Such CPT as a WooCommerce shop_order hast public set to false, but show_ui to true
            $post_types = get_post_types(array(/*'public'=>true,*/ 'show_ui'=>true), 'objects');
            $caps = array();
            foreach($post_types as $post_type) {
                if (!in_array($post_type->cap->edit_post, $caps)) {
                    $caps[] = $post_type->cap->edit_post;
                }
                if (!in_array($post_type->cap->edit_posts, $caps)) {
                    $caps[] = $post_type->cap->edit_posts;
                }
            }
            set_transient('ure_edit_custom_post_type_caps', $caps, 15);
        }
        
        return $caps;
    }
    // end of get_edit_custom_post_type_caps()
    
    
    /**
     * Update roles for all network using direct database access - quicker in several times
     * 
     * @global mndb $mndb
     * @return boolean
     */
    public function direct_network_roles_update() {
        $result = parent::direct_network_roles_update();
        if (!$result) {
            return false;
        }
        
        // replicate addons access data from the main site to the whole network
        $replicator = new Ure_Network_Addons_Data_Replicator();
        $result = $replicator->act();
        
        return $result;
    }
    // end of direct_network_roles_update()
    
    
    
    // create assign_role object
    public function get_assign_role() {
        $assign_role = new URE_Assign_Role_Pro($this);
        
        return $assign_role;
    }
    // end of get_assign_role()

    
    /**
     * Returns a list of post IDs for provided terms ID list
     * @param (array or string of comma separated integers) $terms_list
     * @return array
     */
    public function get_posts_by_terms($terms_list) {
        global $mndb;        
        
        if (is_array($terms_list)) {
            $terms_list_str = URE_Utils::filter_int_array_to_str($terms_list);
        } else {
            $terms_list_str = trim($terms_list);
        }
        if (empty($terms_list)) {
            return array();
        }
        
        $query = "select object_id from {$mndb->term_relationships} where term_taxonomy_id in ($terms_list_str)";
        $post_ids = $mndb->get_col($query);
        if (!is_array($post_ids)) {
            $post_ids = array();
        }
        
        return $post_ids;
    }
    // end of get_posts_by_terms()
    
    /**
     * returns true if current user has a super administrator permissions
     * @global MN_User $current_user
     * @return boolean
     */
    public function is_super_admin() {
        
        global $current_user;
                    
        if (!$this->multisite && $this->user_has_capability($current_user, 'administrator')) {
            return true;
        }
        if ($this->multisite && is_super_admin() && !$this->raised_permissions) {
            return true;
        }
        
        return false;
    }
    // end of is_super_admin()

    
    /**
     * Check if current user can edit a post
     * 
     * @param int/MN_Post $post
     * @return boolean
     */
    public function can_edit($post) {        
        global $current_user;
        
        if (!is_a( $post, 'MN_Post' )) {
            $post = get_post($post);
            if (empty($post)) {
                return false;
            }
        }
        
        $checked_posts = get_transient('ure_checked_posts');
        if (!is_array($checked_posts)) { 
            $checked_posts = array();
        }
        if (!isset($checked_posts[$post->ID]) || !isset($checked_posts[$post->ID][$current_user->ID])) {
            $post_type_obj = get_post_type_object($post->post_type);
            if (empty($post_type_obj)) {
                return false;
            }
            $can_it = current_user_can($post_type_obj->cap->edit_post, $post->ID);            
            $checked_posts[$post->ID][$current_user->ID] = $can_it;
            set_transient('ure_checked_posts', $checked_posts, 30);
        }
        
        return $checked_posts[$post->ID][$current_user->ID];
    }
    // end of can_edit()

    
    
    public function about() {
?>       
        <h2>User Role Editor Pro</h2>
        <table>
            <tr>
                <td>
                    <strong>Version:</strong>
                </td> 
                <td>
                    <?php echo URE_VERSION ;?>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Plugin URL:</strong>
                </td> 
                <td>
                    <a href="https://www.role-editor.com">www.role-editor.com</a>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Dowload URL:</strong>
                </td> 
                <td>
                    <a href="https://www.role-editor.com/download-plugin">www.role-editor.com/download-plugin</a>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Author:</strong>
                </td> 
                <td>
                    <a href="https://www.role-editor.com/about">Vladimir Garagulya</a>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <a href="mailto:support@role-editor.com" target="_top">Send support question</a>
                </td>
            </tr>
        </table>        
<?php        
    }
    // end of about()

}
// end of Ure_Lib_Pro()