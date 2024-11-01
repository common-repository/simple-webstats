<?php

namespace Blucube\SWSTATS;

// disallow direct access
if(!defined('WPINC')) die;

include_once(SWSTATS_PATH.'vendor-prefixed/autoload.php');

use Blucube\SWSTATS\DeviceDetector\ClientHints;
use Blucube\SWSTATS\DeviceDetector\DeviceDetector;
use Blucube\SWSTATS\DeviceDetector\Parser\Device\AbstractDeviceParser;

class Collector {
	
	private $uidSalt;
	
	public function __construct()
	{
		
		// enqueue client side script
		add_action('wp_enqueue_scripts', function(){
			wp_enqueue_script('swstats_collector', SWSTATS_URL.'js/collector.min.js', [], filemtime(SWSTATS_PATH.'js/collector.min.js'), true);
			$inlineConfig = 'const swstatsConfig = '.wp_json_encode(array(
				'ajaxURL' => sanitize_url(admin_url('admin-ajax.php')),
				'viewNonce' => sanitize_text_field(wp_create_nonce('swstats_view_nonce')),
				'responseCode' => intval(http_response_code())
			));
			wp_add_inline_script('swstats_collector', $inlineConfig, 'before');
		});
		
		// set up endpoints
		add_action('wp_ajax_swstats_post_view', array($this, 'post_view'));
		add_action('wp_ajax_nopriv_swstats_post_view', array($this, 'post_view'));
		
		// set/get UID salt
		$this->uidSalt = get_option('swstats_uid_salt');
		$uidSaltExpiry = intval(get_option('swstats_uid_salt_expiry'));
		if(!$this->uidSalt || !$uidSaltExpiry || ($uidSaltExpiry <= time()))
		{
			$this->uidSalt = bin2hex(random_bytes(8));
			update_option('swstats_uid_salt', $this->uidSalt);
			update_option('swstats_uid_salt_expiry', time()+(60*60*24*7));
		}
		
	}
	
	// post view endpoint
	function post_view()
	{	
		
		global $wpdb;
		$nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce']));
		if(!wp_verify_nonce($nonce, 'swstats_view_nonce'))
		{
			echo wp_json_encode(array('error' => 'Cannot verify nonce'));
			exit();
		}
		if(!$_REQUEST['location'])
		{
			echo wp_json_encode(array('error' => 'No location passed'));
		}
		
		$device = $this->detect_device();
		if(!$device['bot'])
		{
		
			$uid = $this->get_uid(sanitize_text_field($_SERVER['REMOTE_ADDR']), sanitize_text_field($_SERVER['HTTP_USER_AGENT']));
			$location = sanitize_url($_REQUEST['location']);
			$referrer = sanitize_url($_REQUEST['referrer']);
			if(strlen($referrer) == 0) $referrer = null;
			$ref = parse_url($referrer);
			$referrerDomain = (isset($ref['host'])) ? preg_replace('/^www./', '', strtolower($ref['host'])) : null;
			$site = parse_url(get_site_url());
			$siteDomain = (isset($site['host'])) ? preg_replace('/^www./', '', strtolower($site['host'])) : null;
			$isEntryPage = ($referrerDomain == $siteDomain) ? 0 : 1;
			
			// add a user row if first visit
			$user = $wpdb->get_var($wpdb->prepare("SELECT id FROM %i WHERE uid = %s LIMIT 1", $wpdb->prefix.SWSTATS_USERS_TABLE, $uid));
			if(!$user)
			{			
				
				$type = (isset($device['type'])) ? $device['type'] : null;
				$platform = (isset($device['os'])) ? $device['os'] : null;
				$browser = (isset($device['browser'])) ? $device['browser'] : null;
				$version = (isset($device['version'])) ? $device['version'] : null;
				$country = $this->get_ip_country(sanitize_text_field($_SERVER['REMOTE_ADDR']));
				$wpdb->insert(
					$wpdb->prefix.SWSTATS_USERS_TABLE,
					array(
						'uid' => $uid,
						'country' => $country,
						'type' => $type,
						'platform' => $platform,
						'browser' => $browser,
						'version' => $version,
					)
				);
			}
			
			// update previous time on page
			if($isEntryPage == 0)
			{
				$sql = $wpdb->prepare("SELECT id, entrytime FROM %i WHERE uid = %s ORDER BY entrytime DESC LIMIT 1", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $uid);
				$previousPage = $wpdb->get_results($sql);
				if(count($previousPage) == 1)
				{
					if(isset($previousPage[0]->id) && isset($previousPage[0]->entrytime))
					{
						$timeOnPage = time() - $previousPage[0]->entrytime;
						$wpdb->update(
							$wpdb->prefix.SWSTATS_VIEWS_TABLE,
							array(
								'timeonpage' => $timeOnPage,
							),
							array(
								'id' => $previousPage[0]->id,
								'uid' => $uid,
							)
						);
					}	
				}
			}
			
			// add view row
			$rows = $wpdb->insert(
				$wpdb->prefix.SWSTATS_VIEWS_TABLE,
				array(
					'uid' => $uid,
					'isentrypage' => $isEntryPage,
					'entrytime' => time(),
					'referrerdomain' => $referrerDomain,
					'location' => $location,
					'referrer' => $referrer,
				)
			);
			
		}
		
		exit();
		
	}
	
	
	// generate unique id hash
	function get_uid($ip, $userAgent)
	{
		return md5($this->uidSalt.$ip.$userAgent);
	}
	
	// device detector
	function detect_device()
	{
		$userAgent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
		$clientHints = ClientHints::factory(array_map(function($v) { return sanitize_text_field($v); }, $_SERVER));
		$dd = new DeviceDetector($userAgent, $clientHints);
		$dd->parse();
		$device = array();
		$device['bot'] = $dd->isBot();
		if(!$device['bot'])
		{
			$device['type'] = $dd->getDeviceName();
			if($device['type']) $device['type'] = ucwords($device['type']);
			$device['os'] = $dd->getOs('family');
			$device['browser'] = $dd->getClient('name');
			$device['version'] = $dd->getClient('version');
		}
		return $device;
	}
	
	// geolocate ip
	function get_ip_country($ip)
	{
		$response = json_decode(wp_remote_retrieve_body(wp_remote_get('https://api.country.is/'.$ip)));
		if($response && isset($response->country)) return $response->country;
		return null;
	}
	
}

?>