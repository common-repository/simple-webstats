<?php

/**
* Plugin Name: Simple Webstats
* Description: Privacy-focused cookie-free web analytics for WordPress.
* Version: 1.2.3
* Requires at least: 4.6
* Requires PHP: 7.3
* License: GPLv2 or later
* Author: Blucube
* Author URI: https://blucube.net
* License: GPL2
* Text Domain: simple-webstats
*/

namespace Blucube\SWSTATS;

// disallow direct access
if(!defined('WPINC')) die;

// config
define('SWSTATS_VERSION', '1.2.3');
define('SWSTATS_PATH',  plugin_dir_path(__FILE__));
define('SWSTATS_URL',  plugin_dir_url(__FILE__));
define('SWSTATS_USERS_TABLE', 'swstats_users');
define('SWSTATS_VIEWS_TABLE', 'swstats_views');

// include classes
require_once(SWSTATS_PATH.'inc/setup.php');
require_once(SWSTATS_PATH.'inc/collector.php');
require_once(SWSTATS_PATH.'inc/dashboard.php');

// init
$swstatsSetup = new Setup();
$swstatsCollector = new Collector();
$swstatsDashboard = new Dashboard();

// uninstall
function uninstall()
{

	// drop tables
	global $wpdb;
	$wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $wpdb->prefix.SWSTATS_USERS_TABLE));
	$wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $wpdb->prefix.SWSTATS_VIEWS_TABLE));
	
	// delete options
	delete_option('swstats_version');
	delete_option('swstats_uid_salt');
	delete_option('swstats_uid_salt_expiry');
	
	// delete user options
	$users = get_users();
	foreach($users as $k => $user)
	{
		delete_user_option($user->ID, 'swstats_period');
	}
	
}
register_uninstall_hook(__FILE__, 'Blucube\SWSTATS\uninstall');

?>