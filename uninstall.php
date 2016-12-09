<?php
/* 
 * User Role Editor plugin uninstall script
 * Author: vladimir@shinephp.com
 *
 */


if (!defined('ABSPATH') || !defined('MN_UNINSTALL_PLUGIN')) {
	 exit();  // silence is golden
}

global $mndb;

function ure_delete_options() {
  
  global $mndb;
  
  $backup_option_name = $mndb->prefix.'backup_user_roles';
  delete_option($backup_option_name);
  delete_option('external_updates-user-role-editor-pro');
  delete_option('user_role_editor');
  delete_option('ure_role_additional_options_values');
  delete_option('ure_admin_menu_copy');
  delete_option('ure_admin_submenu_copy');
  delete_option('ure_admin_menu_hashes');
  delete_option('ure_admin_menu_access_data');
  delete_option('ure_widgets_access_data');
  delete_option('ure_widgets_show_access_data');
  delete_option('ure_other_roles_access_data');
  delete_option('ure_task_queue');
  delete_option('ure_admin_menu_access_data_version');
  delete_site_option('ure_assign_role_job');
}


if (!is_multisite()) {
    ure_delete_options();
} else {
  $old_blog = $mndb->blogid;
  // Get all blog ids
  $blogIds = $mndb->get_col("SELECT blog_id FROM $mndb->blogs");
  foreach ($blogIds as $blog_id) {
    switch_to_blog($blog_id);
    ure_delete_options();
  }
  switch_to_blog($old_blog);
}

// clear users meta

$query = "delete from {$mndb->usermeta} 
             where meta_key like '%ure_allow_plugins_activation' or meta_key like '%ure_posts_list' or
                   meta_key like '%ure_posts_restriction_type' or meta_key like '%ure_post_types'";
$mndb->query($query);

if (class_exists('GFForms')) {
    $query = "delete from {$mndb->usermeta} where meta_key like '%ure_allow_gravity_forms'";
    $mndb->query($query);
}