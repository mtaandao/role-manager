<?php
/**
 * Load related files
 * Project: User Role Editor Pro Mtaandao plugin
 * 
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 *
**/

require_once(URE_PLUGIN_DIR .'pro/includes/classes/ajax-processor.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/utils.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/mutex.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/license-key.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugin-presence.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/bbpress.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/wc-bookings.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/assign-role-pro.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/screen-help-pro.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/access-ui-controller.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/create-posts-cap.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/post-types-own-caps.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/export-import.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/shortcodes.php');


// Additional modules:

require_once(URE_PLUGIN_DIR .'pro/includes/classes/addons-manager.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/network-addons-data-replicator.php');

// Admin menu access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-copy.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-hashes.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-view.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-url-allowed-args.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/admin-menu-access.php');

// Widgets Admin access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/widgets-admin.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/widgets-admin-access.php');

// Widgets Show access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/widgets-show-view.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/widgets-show-controller.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/widgets-show-access.php');

// Metaboxes access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/meta-boxes.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/meta-boxes-access.php');

// Other Roles access
require_once( URE_PLUGIN_DIR .'pro/includes/classes/other-roles.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/other-roles-access.php');

// Posts edit access
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-view.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-role-controller.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-role.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-user-meta.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-user.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access-bulk-action.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/posts-edit-access.php');

// Plugins activation access
require_once(URE_PLUGIN_DIR .'pro/includes/classes/plugins-activation-access.php');

// Themes activation access
require_once(URE_PLUGIN_DIR .'pro/includes/classes/themes-access.php');

// Gravity Forms Access
require_once(URE_PLUGIN_DIR .'pro/includes/classes/gf-access.php');

// Content view restricitons
require_once( URE_PLUGIN_DIR .'pro/includes/classes/content-view-restrictions-controller.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/posts-view.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/posts-view-access.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/content-view-restrictions-posts-list.php');
require_once( URE_PLUGIN_DIR .'pro/includes/classes/content-view-restrictions.php');


require_once(URE_PLUGIN_DIR .'pro/includes/classes/user-role-editor-pro-view.php');
require_once(URE_PLUGIN_DIR .'pro/includes/classes/user-role-editor-pro.php');