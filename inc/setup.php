<?php

namespace Blucube\SWSTATS;

// disallow direct access
if(!defined('WPINC')) die;

class Setup {
	
	public function __construct()
	{

		// register hook to set up database tables
		add_action('plugins_loaded', array($this, 'setup_tables'));
		
	}
	
	// create tables
	function setup_tables()
	{
		
		global $wpdb;
		
		// check tables exist
		$usersTableExists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix.SWSTATS_USERS_TABLE));
		$viewsTableExists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix.SWSTATS_VIEWS_TABLE));

		if(!$usersTableExists || !$viewsTableExists || (get_option('swstats_version') !== SWSTATS_VERSION))
		{
			require_once ABSPATH.'wp-admin/includes/upgrade.php';
			$charsetCollate = $wpdb->get_charset_collate();
			
			$sql = $wpdb->prepare("CREATE TABLE %i (
				id int UNSIGNED NOT NULL AUTO_INCREMENT,
				uid char(32) NOT NULL,
				country char(2) DEFAULT NULL,
				type char(24) DEFAULT NULL,
				platform char(24) DEFAULT NULL,
				browser char(24) DEFAULT NULL,
				version char(24) DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY uid (uid)
			) %1s;", $wpdb->prefix.SWSTATS_USERS_TABLE, $charsetCollate);
			dbDelta($sql);
			
			$sql = $wpdb->prepare("CREATE TABLE %i (
				id int UNSIGNED NOT NULL AUTO_INCREMENT,
				uid char(32) NOT NULL,
				isentrypage tinyint UNSIGNED DEFAULT 0 NOT NULL,
				entrytime int UNSIGNED DEFAULT 0 NOT NULL,
				timeonpage int UNSIGNED DEFAULT NULL,
				referrerdomain varchar(256) DEFAULT NULL,
				location varchar(1024) DEFAULT '' NOT NULL,
				referrer varchar(1024) DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY entrytime (entrytime),
				KEY isentrypage (isentrypage)
			) %1s;", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $charsetCollate);
			dbDelta($sql);
			
			update_option('swstats_version', SWSTATS_VERSION);	
			
		}
	}
	
}

?>