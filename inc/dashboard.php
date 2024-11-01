<?php

namespace Blucube\SWSTATS;

// disallow direct access
if(!defined('WPINC')) die;


class Dashboard {
	
	
	private $wpTimezone, $dateFormat, $yearFormat, $monthFormat, $hourFormat;
	
	
	public function __construct()
	{

		// add menu item, dashboard page, enqueue scripts
		add_action('admin_menu', function() {
			$dash = add_menu_page('Simple Webstats', 'Simple Webstats', 'read', 'swstats_dash', array($this, 'dashboard'), 'dashicons-chart-bar');
			add_action('load-'.$dash, function() {
				add_action('admin_enqueue_scripts', function() {
					wp_enqueue_script('chartjs', SWSTATS_URL.'lib/chartjs/chart.umd.min.js', [], false, true);
					wp_enqueue_script('swstats_dash', SWSTATS_URL.'js/dashboard.min.js', ['chartjs'], filemtime(SWSTATS_PATH.'js/dashboard.min.js'), true);
					wp_enqueue_style('swstats_global', SWSTATS_URL.'css/global.min.css', [], filemtime(SWSTATS_PATH.'css/global.min.css'));
					wp_enqueue_style('swstats_dash', SWSTATS_URL.'css/dashboard.min.css', [], filemtime(SWSTATS_PATH.'css/dashboard.min.css'));
					wp_enqueue_style('freakflags', SWSTATS_URL.'lib/freakflags/freakflags.css', [], filemtime(SWSTATS_PATH.'lib/freakflags/freakflags.css'));
				});
			});
		});

		// set up endpoints
		add_action('wp_ajax_swstats_get_summary_data', array($this, 'get_summary_data'));
		add_action('wp_ajax_swstats_get_table_data', array($this, 'get_table_data'));
		
		// set up timezone and formatting
		$this->wpTimezone = wp_timezone();
		$this->dateFormat = get_option('date_format');
		$timeFormat = get_option('time_format');
		$this->yearFormat = 'Y';
		$this->monthFormat = 'F Y';
		$this->hourFormat = (stristr($timeFormat, 'a') === false) ? 'H' : 'ga';
		
		// set up dashboard widget
		add_action('wp_dashboard_setup', function() {
			add_action('admin_enqueue_scripts', function() {
				wp_enqueue_script('chartjs', SWSTATS_URL.'lib/chartjs/chart.umd.min.js', [], false, true);
				wp_enqueue_script('swstats_dash', SWSTATS_URL.'js/dashboard.min.js', ['chartjs'], filemtime(SWSTATS_PATH.'js/dashboard.min.js'), true);
				wp_enqueue_style('swstats_global', SWSTATS_URL.'css/global.min.css', [], filemtime(SWSTATS_PATH.'css/global.min.css'));
				wp_enqueue_style('swstats_widget', SWSTATS_URL.'css/widget.min.css', [], filemtime(SWSTATS_PATH.'css/widget.min.css'));
			});
			wp_add_dashboard_widget('swstats-widget', 'Simple Webstats - '.__('Last 24 hours', 'simple-webstats'), array($this, 'widget'));
		});
	
	}
	
	
	// output dashboard widget
	function widget()
	{
		echo '<div class="panel" data-type="summary" data-nonce="'.esc_attr(wp_create_nonce('swstats_get_summary')).'" data-ajaxurl="'.esc_url(admin_url('admin-ajax.php')).'"><div class="inner"></div><div class="loading show"><i></i><i></i><i></i></div></div>';
	}
	
	
	// output dashboard page
	function dashboard()
	{
			
		// period options
		$periods = array(
			'last24hours' => __('Last 24 hours', 'simple-webstats'),
			'today' => __('Today', 'simple-webstats'),
			'yesterday' => __('Yesterday', 'simple-webstats'),
			'last7days' => __('Last 7 days', 'simple-webstats'),
			'last30days' => __('Last 30 days', 'simple-webstats'),
			'thismonth' => __('Month to date', 'simple-webstats'),
			'lastmonth' => __('Last month', 'simple-webstats'),
			'thisyear' => __('Year to date', 'simple-webstats'),
			'last12months' => __('Last 12 months', 'simple-webstats'),
			'all' => __('All time', 'simple-webstats')
		);
		
		// get period option
		$period = get_user_option('swstats_period');
		if(!$period || !array_key_exists($period, $periods)) $period = 'last24hours';
				
		echo '<div class="swstats-dash wrap" data-ajaxurl="'.esc_url(admin_url('admin-ajax.php')).'">';
		echo '<h1>Simple Webstats</h1>';
		echo '<select id="periodselect">';
		foreach($periods as $val => $label)
		{
			echo '<option value="'.esc_attr($val).'"';
			if($val === $period) echo ' selected';
			echo '>'.esc_html($label).'</option>'; 
		}
		echo '</select>';

		echo '<div class="panels">';
		echo '<div class="summary-panel" data-type="summary" data-nonce="'.esc_attr(wp_create_nonce('swstats_get_summary')).'"><div class="inner"></div><div class="loading show"><i></i><i></i><i></i></div></div>';
		$this->table_panel('pages', array('pages' => __('Pages', 'simple-webstats')), 'half');
		$this->table_panel('sources', array('sources' => __('Sources', 'simple-webstats')), 'half');
		$this->table_panel('countries', array('countries' => __('Countries', 'simple-webstats')), 'third');
		$this->table_panel('devices', array('types' => __('Device types', 'simple-webstats'), 'devices' => __('Devices', 'simple-webstats')), 'third');
		$this->table_panel('browsers', array('browsers' => __('Browsers', 'simple-webstats'), 'versions' => __('Browser versions', 'simple-webstats')), 'third');
		echo '</div>';
		echo '</div>';
	}
	
	
	// output data panel
	function table_panel($id, $views, $width)
	{
		$id = sanitize_key($id);
		$width = sanitize_key($width);
		$initialViewID = sanitize_key(array_key_first($views));
		$initialViewTitle = sanitize_text_field($views[$initialViewID]);
		echo '<div class="data-panel '.esc_attr($width).'" data-type="'.esc_attr($id).'" data-viewid="'.esc_attr($initialViewID).'" data-nonce="'.esc_attr(wp_create_nonce('swstats_get_'.$id)).'">';
		echo '<div class="inner">';
		echo '<h2>'.esc_html($initialViewTitle).'</h2>';
		if(count($views) > 1)
		{
			echo '<div class="switches">';
			foreach($views as $viewID => $title) echo '<a href="#" data-viewid="'.esc_attr(sanitize_key($viewID)).'">'.esc_html(sanitize_text_field($title)).'</a>';
			echo '</div>';
		}
		echo '<div class="table"></div>';
		echo '</div>';
		echo '<div class="loading show"><i></i><i></i><i></i></div>';
		echo '<p class="nodata">'.esc_html(__('No data', 'simple-webstats')).'</p>';
		echo '</div>';
	}
	
	
	// stringify where clauses
	function where_clauses($clauses)
	{
		return (count($clauses) > 0) ? 'WHERE '.implode(' AND ', $clauses) : '';
	}
	
	
	// prepare summary sql queries, including comparatives if required
	function prepare_summary_sql($sqlPart, $timeClauses, $comparativeTimeClauses, $whereClauses = array())
	{
		global $wpdb;
		if(!isset($whereClauses)) $whereClauses = array();
		if(count($comparativeTimeClauses) > 0) return $wpdb->prepare("SELECT * FROM (%1s %1s) AS a, (%1s %1s) AS b", $sqlPart, $this->where_clauses(array_merge($timeClauses, $whereClauses)), str_replace('AS val', 'AS comp', str_replace('AS samplesize', 'AS compsamplesize', $sqlPart)), $this->where_clauses(array_merge($comparativeTimeClauses, $whereClauses)));	
		return $wpdb->prepare("%1s %1s", $sqlPart, $this->where_clauses(array_merge($timeClauses, $whereClauses)));
	}
	
	
	// get summary data endpoint
	function get_summary_data()
	{
		
		global $wpdb;
		$nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce']));
		$period = sanitize_key($_REQUEST['period']);
		$isWidget = (sanitize_key($_REQUEST['isWidget']) === 'true') ? true : false;
		$data = array();
		
		if(!wp_verify_nonce($nonce, 'swstats_get_summary'))
		{
			echo wp_json_encode(array('error' => 'Cannot verify nonce'));
			exit();
		}
		
		$timeBounds = $this->get_time_bounds($period);
		
		// set period option
		if(!$isWidget) update_user_option(get_current_user_id(), 'swstats_period', $period);
		
		// visitors
		$dataPoint = array();
		$dataPoint['label'] = __('Unique visitors', 'simple-webstats');
		$dataPoint['value'] = null;
		$dataPoint['comparison'] = null;
		$dataPoint['format'] = 'number';
		$sqlPart = $wpdb->prepare("SELECT COUNT(DISTINCT(v.uid)) AS val FROM %i AS v", $wpdb->prefix.SWSTATS_VIEWS_TABLE);
		$sql = $this->prepare_summary_sql($sqlPart, $timeBounds['whereClauses'], $timeBounds['comparativeWhereClauses']);
		$result = $wpdb->get_results($sql);
		if(isset($result[0]->val))
		{
			$dataPoint['value'] = $result[0]->val;
			if(isset($result[0]->comp)) $dataPoint['comparison'] = $result[0]->comp;
		}
		$data['visitors'] = $dataPoint;
		
		// visits
		$dataPoint = array();
		$dataPoint['label'] = __('Total visits', 'simple-webstats');
		$dataPoint['value'] = null;
		$dataPoint['comparison'] = null;
		$dataPoint['format'] = 'number';
		$whereClauses = array(
			'v.isentrypage = 1'
		);
		$sqlPart = $wpdb->prepare("SELECT COUNT(*) AS val FROM %i AS v", $wpdb->prefix.SWSTATS_VIEWS_TABLE);
		$sql = $this->prepare_summary_sql($sqlPart, $timeBounds['whereClauses'], $timeBounds['comparativeWhereClauses'], $whereClauses);
		$result = $wpdb->get_results($sql);
		if(isset($result[0]->val))
		{
			$dataPoint['value'] = max($result[0]->val, $data['visitors']['value']);
			if(isset($result[0]->comp)) $dataPoint['comparison'] = max($result[0]->comp, $data['visitors']['comparison']);
		}
		$data['visits'] = $dataPoint;
		
		// views
		$dataPoint = array();
		$dataPoint['label'] = __('Page views', 'simple-webstats');
		$dataPoint['value'] = null;
		$dataPoint['comparison'] = null;
		$dataPoint['format'] = 'number';
		$sqlPart = $wpdb->prepare("SELECT COUNT(*) AS val FROM %i AS v", $wpdb->prefix.SWSTATS_VIEWS_TABLE);
		$sql = $this->prepare_summary_sql($sqlPart, $timeBounds['whereClauses'], $timeBounds['comparativeWhereClauses']);
		$result = $wpdb->get_results($sql);
		if(isset($result[0]->val))
		{
			$dataPoint['value'] = $result[0]->val;
			if(isset($result[0]->comp)) $dataPoint['comparison'] =  $result[0]->comp;
		}
		$data['views'] = $dataPoint;
		
		// views per visit
		$dataPoint = array();
		$dataPoint['label'] = __('Avg views per visit', 'simple-webstats');
		$dataPoint['value'] = null;
		$dataPoint['comparison'] = null;
		$dataPoint['format'] = 'number';
		if(isset($data['visits']['value']) && isset($data['views']['value']))
		{
			if($data['visits']['value'] > 0) $dataPoint['value'] = $data['views']['value'] / $data['visits']['value'];
			if(($dataPoint['value'] !== null) && isset($data['visits']['comparison']) && isset($data['views']['comparison']) && ($data['visits']['comparison'] > 0)) $dataPoint['comparison'] = $data['views']['comparison'] / $data['visits']['comparison'];
		}
		$data['viewsPerVisit'] = $dataPoint;
		
		// time on site - MEDIAN
		$dataPoint = array();
		$dataPoint['label'] = __('Avg time on site', 'simple-webstats');
		$dataPoint['value'] = null;
		$dataPoint['comparison'] = null;
		$dataPoint['format'] = 'time';
		$whereClauses = $this->where_clauses(array_merge($timeBounds['whereClauses'], array('v.timeonpage > 0')));
		if(count($timeBounds['comparativeWhereClauses']) > 0)
		{
			$comparativeWhereClauses = $this->where_clauses(array_merge($timeBounds['comparativeWhereClauses'], array('v.timeonpage > 0')));
			$sql = $wpdb->prepare("SELECT * FROM (SELECT AVG(sub.timeonpage) AS val FROM (SELECT @row_index_a := @row_index_a + 1 AS row_index, v.timeonpage FROM %i AS v, (SELECT @row_index_a := -1) r %1s ORDER BY v.timeonpage) AS sub WHERE sub.row_index IN (FLOOR(@row_index_a / 2), CEIL(@row_index_a / 2))) AS a, (SELECT AVG(sub.timeonpage) AS comp FROM (SELECT @row_index_b := @row_index_b + 1 AS row_index, v.timeonpage FROM %i AS v, (SELECT @row_index_b := -1) r %1s ORDER BY v.timeonpage) AS sub WHERE sub.row_index IN (FLOOR(@row_index_b / 2), CEIL(@row_index_b / 2))) AS b", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $whereClauses, $wpdb->prefix.SWSTATS_VIEWS_TABLE, $comparativeWhereClauses);
		}
		else
		{
			$sql = $wpdb->prepare("SELECT AVG(sub.timeonpage) AS val FROM (SELECT @row_index := @row_index + 1 AS row_index, v.timeonpage FROM %i AS v, (SELECT @row_index := -1) r %1s ORDER BY v.timeonpage) AS sub WHERE sub.row_index IN (FLOOR(@row_index / 2), CEIL(@row_index / 2))", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $whereClauses);
		}
		$result = $wpdb->get_results($sql);
		if(isset($result[0]))
		{
			if(isset($result[0]->val) && $result[0]->val && $data['viewsPerVisit']['value']) $dataPoint['value'] = $result[0]->val * $data['viewsPerVisit']['value'];
			if(($dataPoint['value'] !== null) && isset($result[0]->comp) && $result[0]->comp && $data['viewsPerVisit']['comparison']) $dataPoint['comparison'] = $result[0]->comp * $data['viewsPerVisit']['comparison'];
		}
		$data['timeonsite'] = $dataPoint;
		
		// bounce rate
		$dataPoint = array();
		$dataPoint['label'] = __('Bounce rate', 'simple-webstats');
		$dataPoint['value'] = null;
		$dataPoint['comparison'] = null;
		$dataPoint['format'] = 'percent';
		$whereClauses = array(
			'v.isentrypage = 1',
			'v.timeonpage IS NULL'
		);
		$sqlPart = $wpdb->prepare("SELECT COUNT(*) AS val FROM %i AS v", $wpdb->prefix.SWSTATS_VIEWS_TABLE);
		$sql = $this->prepare_summary_sql($sqlPart, $timeBounds['whereClauses'], $timeBounds['comparativeWhereClauses'], $whereClauses);
		$result = $wpdb->get_results($sql);
		if(isset($result[0]->val))
		{
			if($data['visits']['value']) $dataPoint['value'] = $result[0]->val / $data['visits']['value'];
			if(($dataPoint['value'] !== null) && isset($result[0]->comp) && $data['visits']['comparison']) $dataPoint['comparison'] = $result[0]->comp / $data['visits']['comparison'];
		}
		$data['bouncerate'] = $dataPoint;
		
		// chart data
		$chartdata = array();

		// visits dataset
		$whereClauses = $this->where_clauses(array_merge($timeBounds['whereClauses'], array('v.isentrypage = 1')));
		$sql = $wpdb->prepare("SELECT DATE_FORMAT(FROM_UNIXTIME(v.entrytime), '%%Y%%m%%d%%H') AS grouptime, MIN(v.entrytime) AS ts, COUNT(*) AS val FROM %i AS v %1s GROUP BY grouptime ORDER BY grouptime", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $whereClauses);
		$visitsResult = $wpdb->get_results($sql);
		$visits = $this->format_chart_data($visitsResult, $timeBounds, $period);
		
		// views dataset
		$whereClauses = $this->where_clauses($timeBounds['whereClauses']);
		$sql = $wpdb->prepare("SELECT DATE_FORMAT(FROM_UNIXTIME(v.entrytime), '%%Y%%m%%d%%H') AS grouptime, MIN(v.entrytime) AS ts, COUNT(*) AS val FROM %i AS v %1s GROUP BY grouptime ORDER BY grouptime", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $whereClauses);
		$viewsResult = $wpdb->get_results($sql);
		$views = $this->format_chart_data($viewsResult, $timeBounds, $period);
				
		$chartdata = array('granularities' => $timeBounds['chartGranularities'], 'defaultGranularity' => $timeBounds['defaultChartGranularity'], 'visits' => $visits, 'views' => $views, 'visitsLabel' => __('Visits', 'simple-webstats'), 'viewsLabel' => __('Views', 'simple-webstats'));		
		
		$newNonce = wp_create_nonce('swstats_get_summary');
		echo wp_json_encode(array('data' => $data, 'chartdata' => $chartdata, 'newNonce' => $newNonce));
		exit();
		
	}
	
	
	// format chart data, labelling datetimes according to timezone, and filling in zero values
	function format_chart_data($data, $timeBounds, $period)
	{
		$dataSets = array();
		foreach($timeBounds['chartGranularities'] as $granularity => $gLabel)
		{
			$labelSuffix = '';
			if($granularity == 'year')
			{
				$increment = '+1 year';
				$format = $this->yearFormat;
			}
			elseif($granularity == 'month')
			{
				$increment = '+1 month';
				$format = $this->monthFormat;
			}
			elseif($granularity == 'day')
			{
				$increment = '+1 day';
				$format = $this->dateFormat;
			}
			elseif($granularity == 'hour')
			{
				$increment = "+1 hour";
				if(($period == 'last24hours') || ($period == 'today') || ($period == 'yesterday')) $format = $this->hourFormat;
				else $format = $this->dateFormat.' '.$this->hourFormat;
				if($this->hourFormat == 'H') $labelSuffix = ':00';
			}
			else return false;
			$formattedData = $this->get_time_intervals($timeBounds['start'], $timeBounds['end'], $increment, $format, $labelSuffix);
			foreach($data as $key => $row) {
				$dateTime = new \DateTime('@'.$row->ts);
				$dateTime->setTimezone($this->wpTimezone);
				$label = $dateTime->format($format);
				$formattedData[$label.$labelSuffix] += $row->val;	
			}
			$dataSets[$granularity] = $formattedData;
		}
		return $dataSets;
	}
	
	
	// get table data endpoint
	function get_table_data()
	{
		
		global $wpdb;
		$nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce']));
		$dataType = sanitize_key($_REQUEST['dataType']);
		$viewID = sanitize_key($_REQUEST['viewID']);
		$period = sanitize_key($_REQUEST['period']);
		$numResults = sanitize_key($_REQUEST['numResults']);
		
		if(!wp_verify_nonce($nonce, 'swstats_get_'.$dataType))
		{
			echo wp_json_encode(array('error' => 'Cannot verify nonce'));
			exit();
		}
		
		$timeBounds = $this->get_time_bounds($period);
		
		if($dataType == 'pages')
		{
			$headers = array(__('Page', 'simple-webstats'), __('Visitors', 'simple-webstats'), __('Views', 'simple-webstats'));	
			$whereClauses = $this->where_clauses($timeBounds['whereClauses']);
			$countSQL = $wpdb->prepare("SELECT SUM(visitors) FROM (SELECT v.location, COUNT(DISTINCT(v.uid)) AS visitors FROM %i AS v %1s GROUP BY v.location) AS temptable", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $whereClauses);
			$count = $wpdb->get_var($countSQL);
			$dataSQL = $wpdb->prepare("SELECT v.location, COUNT(DISTINCT(v.uid)) AS visitors, COUNT(*) AS views FROM %i AS v %1s GROUP BY v.location ORDER BY visitors DESC, views DESC", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $whereClauses);
			if($numResults) $dataSQL .= $wpdb->prepare(" LIMIT %d", $numResults);
			$data = $wpdb->get_results($dataSQL, ARRAY_N);
		}
		
		elseif($dataType == 'sources')
		{
			$headers = array(__('Source', 'simple-webstats'), __('Visits', 'simple-webstats'));
			$whereClauses = $this->where_clauses(array_merge($timeBounds['whereClauses'], array('v.isentrypage = 1')));
			$countSQL = $wpdb->prepare("SELECT COUNT(*) FROM %i AS v %1s", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $whereClauses);
			$count = $wpdb->get_var($countSQL);
			$dataSQL = $wpdb->prepare("SELECT v.referrerdomain, COUNT(v.uid) AS num FROM %i AS v %1s GROUP BY v.referrerdomain ORDER BY num DESC", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $whereClauses);
			if($numResults) $dataSQL .= $wpdb->prepare(" LIMIT %d", $numResults);
			$data = $wpdb->get_results($dataSQL, ARRAY_N);
			for($i = 0; $i < count($data); $i++)
			{
				$domain = $data[$i][0];
				if(!$domain) $data[$i][0] = 'Direct/None';
			}
		}
		
		elseif($dataType == 'countries')
		{
			$headers = array(__('Country', 'simple-webstats'), __('Visitors', 'simple-webstats'));
			$whereClauses = $this->where_clauses($timeBounds['whereClauses']);
			$countSQL = $wpdb->prepare("SELECT COUNT(DISTINCT(v.uid)) FROM %i AS v %1s", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $whereClauses);
			$count = $wpdb->get_var($countSQL);
			$dataSQL = $wpdb->prepare("SELECT u.country, COUNT(DISTINCT(v.uid)) AS num FROM %i AS v INNER JOIN %i AS u ON u.uid = v.uid %1s GROUP BY u.country ORDER BY num DESC", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $wpdb->prefix.SWSTATS_USERS_TABLE, $whereClauses);
			if($numResults) $dataSQL .= $wpdb->prepare(" LIMIT %d", $numResults);
			$data = $wpdb->get_results($dataSQL, ARRAY_N);
		}
		
		elseif($dataType == 'devices')
		{	
			$headers = array(__('Device', 'simple-webstats'), __('Visitors', 'simple-webstats'));
			$whereClauses = $this->where_clauses($timeBounds['whereClauses']);
			$countSQL = $wpdb->prepare("SELECT COUNT(DISTINCT(v.uid)) FROM %i AS v %1s", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $whereClauses);
			$count = $wpdb->get_var($countSQL);
			if($viewID == 'types') $dataSQL = $wpdb->prepare("SELECT u.type, COUNT(DISTINCT(v.uid)) AS num FROM %i AS v INNER JOIN %i AS u ON u.uid = v.uid %1s GROUP BY u.type ORDER BY num DESC", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $wpdb->prefix.SWSTATS_USERS_TABLE, $whereClauses);
			else $dataSQL = $wpdb->prepare("SELECT u.platform, COUNT(DISTINCT(v.uid)) AS num FROM %i AS v INNER JOIN %i AS u ON u.uid = v.uid %1s GROUP BY u.platform ORDER BY num DESC", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $wpdb->prefix.SWSTATS_USERS_TABLE, $whereClauses);
			if($numResults) $dataSQL .= $wpdb->prepare(" LIMIT %d", $numResults);
			$data = $wpdb->get_results($dataSQL, ARRAY_N);
			for($i = 0; $i < count($data); $i++)
			{
				$device = $data[$i][0];
				if(!$device) $data[$i][0] = 'Unknown';
			}
		}
		
		elseif($dataType == 'browsers')
		{
			$headers = array(__('Browser', 'simple-webstats'), __('Visitors', 'simple-webstats'));
			$whereClauses = $this->where_clauses($timeBounds['whereClauses']);
			$countSQL = $wpdb->prepare("SELECT COUNT(DISTINCT(v.uid)) FROM %i AS v %1s", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $whereClauses);
			$count = $wpdb->get_var($countSQL);
			if($viewID == 'versions') $dataSQL = $wpdb->prepare("SELECT CONCAT(u.browser, ' ', u.version) AS label, COUNT(DISTINCT(v.uid)) AS num FROM %i AS v INNER JOIN %i AS u ON u.uid = v.uid %1s GROUP BY label ORDER BY num DESC", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $wpdb->prefix.SWSTATS_USERS_TABLE, $whereClauses);
			else $dataSQL = $wpdb->prepare("SELECT u.browser AS label, COUNT(DISTINCT(v.uid)) AS num FROM %i AS v INNER JOIN %i AS u ON u.uid = v.uid %1s GROUP BY u.browser ORDER BY num DESC", $wpdb->prefix.SWSTATS_VIEWS_TABLE, $wpdb->prefix.SWSTATS_USERS_TABLE, $whereClauses);
			if($numResults) $dataSQL .= $wpdb->prepare(" LIMIT %d", $numResults);
			$data = $wpdb->get_results($dataSQL, ARRAY_N);
			for($i = 0; $i < count($data); $i++)
			{
				$browser = $data[$i][0];
				if(!$browser) $data[$i][0] = 'Unknown';
			}
		}
		
		else
		{
			echo wp_json_encode(array('error' => 'Invalid or missing dataType'));
			exit();
		}
		
		$newNonce = wp_create_nonce('swstats_get_'.$dataType);
		echo wp_json_encode(array('headers' => $headers, 'data' => $data, 'count' => $count, 'newNonce' => $newNonce));
		exit();
		
	}
	
	
	// get array of time intervals
	function get_time_intervals($start, $end, $increment, $format, $labelSuffix = '')
	{
		$intervals = array();
		$dateTime = new \DateTime('@'.$start);
		$dateTime->setTimezone($this->wpTimezone);
		$currentTS = $start;
		while($currentTS < $end)
		{
			$label = $dateTime->format($format);
			$intervals[$label.$labelSuffix] = 0;
			$dateTime->modify($increment);
			$currentTS = $dateTime->format('U');
		}
		$dateTime = new \DateTime('@'.($end-1));
		$dateTime->setTimezone($this->wpTimezone);
		$label = $dateTime->format($format);
		$intervals[$label.$labelSuffix] = 0;
		return $intervals;
	}
	
	
	// get unix timestamp bounds based on period, using WP timezone
	function get_time_bounds($period)
	{
		
		global $wpdb;
		
		if($period != 'all') {
			$dateTimeStart = new \DateTime("now", $this->wpTimezone);
			$dateTimeEnd = new \DateTime("now", $this->wpTimezone);
			$dateTimeComparativeStart = new \DateTime("now", $this->wpTimezone);
			$dateTimeComparativeEnd = new \DateTime("now", $this->wpTimezone);
		}
		
		if($period == 'last24hours') {
			$hour = $dateTimeStart->format('H');
			$dateTimeStart->modify('-1 day')->setTime($hour+1, 0, 0);
			$dateTimeEnd->setTime($hour+1, 0, 0);
			$dateTimeComparativeStart->modify('-2 days')->setTime($hour+1, 0, 0);
			$dateTimeComparativeEnd->modify('-1 day')->setTime($hour+1, 0, 0);
		}
		elseif($period == 'today') {
			$dateTimeStart->setTime(0, 0, 0);
			$dateTimeComparativeStart->modify('-1 day')->setTime(0, 0, 0);
			$dateTimeComparativeEnd->modify('-1 day');
		}
		elseif($period == 'yesterday') {
			$dateTimeStart->modify('-1 day')->setTime(0, 0, 0);
			$dateTimeEnd->setTime(0, 0, 0);
			$dateTimeComparativeStart->modify('-2 days')->setTime(0, 0, 0);
			$dateTimeComparativeEnd->modify('-1 day')->setTime(0, 0, 0);
		}
		elseif($period == 'last7days') {
			$dateTimeStart->modify('-6 days')->setTime(0, 0, 0);
			$dateTimeComparativeStart->modify('-13 days')->setTime(0, 0, 0);
			$dateTimeComparativeEnd->modify('-7 days');
		}
		elseif($period == 'last30days') {
			$dateTimeStart->modify('-29 days')->setTime(0, 0, 0);
			$dateTimeComparativeStart->modify('-59 days')->setTime(0, 0, 0);
			$dateTimeComparativeEnd->modify('-30 days');
		}
		elseif($period == 'thismonth') {
			$dateTimeStart->modify('first day of this month')->setTime(0, 0, 0);
			$dateTimeComparativeStart->modify('first day of previous month')->setTime(0, 0, 0);
			$dateTimeComparativeEnd->modify('-1 month');
		}
		elseif($period == 'lastmonth') {
			$dateTimeStart->modify('first day of previous month')->setTime(0, 0, 0);
			$dateTimeEnd->modify('first day of this month')->setTime(0, 0, 0);
			$dateTimeComparativeStart->modify('first day of previous month')->modify('-1 month')->setTime(0, 0, 0);
			$dateTimeComparativeEnd->modify('first day of previous month')->setTime(0, 0, 0);
		}
		elseif($period == 'thisyear') {
			$dateTimeStart->modify('first day of january this year')->setTime(0, 0, 0);
			$dateTimeComparativeStart->modify('first day of january this year')->modify('-1 year')->setTime(0, 0, 0);
			$dateTimeComparativeEnd->modify('-1 year');
		}
		elseif($period == 'last12months') {
			$dateTimeStart->modify('first day of this month')->modify('-11 months')->setTime(0, 0, 0);
			$dateTimeComparativeStart->modify('first day of this month')->modify('-11 months')->modify('-1 year')->setTime(0, 0, 0);
			$dateTimeComparativeEnd->modify('-1 year');
		}
		
		$bounds = array(
			'period' => $period,
			'start' => false,
			'end' => false,
			'comparativeStart' => false,
			'comparativeEnd' => false,
			'whereClauses' => array(),
			'comparativeWhereClauses' => array(),
			'chartGranularities' => array(),
			'defaultChartGranularity' => false
		);
		
		if(isset($dateTimeStart))
		{
			$bounds['start'] = $dateTimeStart->format('U');
			$bounds['whereClauses'][] = $wpdb->prepare("v.entrytime >= %d", $dateTimeStart->format('U'));
		}
		else
		{
			$bounds['start'] = $wpdb->get_var($wpdb->prepare("SELECT v.entrytime FROM %i AS v ORDER BY v.entrytime ASC LIMIT 1", $wpdb->prefix.SWSTATS_VIEWS_TABLE));
		}
		
		if(isset($dateTimeEnd))
		{
			$bounds['end'] = $dateTimeEnd->format('U');
			$bounds['whereClauses'][] = $wpdb->prepare("v.entrytime < %d", $dateTimeEnd->format('U'));
		}
		else
		{
			$bounds['end'] = time();
		}
		
		if(isset($dateTimeComparativeStart))
		{
			$bounds['comparativeStart'] = $dateTimeComparativeStart->format('U');
			$bounds['comparativeWhereClauses'][] = $wpdb->prepare("v.entrytime >= %d", $dateTimeComparativeStart->format('U'));
		}
		
		if(isset($dateTimeComparativeEnd))
		{
			$bounds['comparativeEnd'] = $dateTimeComparativeEnd->format('U');
			$bounds['comparativeWhereClauses'][] = $wpdb->prepare("v.entrytime < %d", $dateTimeComparativeEnd->format('U'));
		}
		
		$years = count($this->get_time_intervals($bounds['start'], $bounds['end'], '+1 year', 'Y'));
		$months = count($this->get_time_intervals($bounds['start'], $bounds['end'], '+1 month', 'Ym'));
		$days = count($this->get_time_intervals($bounds['start'], $bounds['end'], '+1 day', 'Ymd'));
		$hours = count($this->get_time_intervals($bounds['start'], $bounds['end'], '+1 hour', 'YmdH'));
				
		if($years >= 3) $bounds['chartGranularities']['year'] = __('Years', 'simple-webstats');
		if(($months >= 3) && ($months <= 120)) $bounds['chartGranularities']['month'] = __('Months', 'simple-webstats');
		if(($days >= 3) && ($days <= 366)) $bounds['chartGranularities']['day'] = __('Days', 'simple-webstats');
		if($hours <= 240) $bounds['chartGranularities']['hour'] = __('Hours', 'simple-webstats');
		
		if($years >= 4) $bounds['defaultChartGranularity'] = 'year';
		elseif($months >= 6) $bounds['defaultChartGranularity'] = 'month';
		elseif($days >= 4) $bounds['defaultChartGranularity'] = 'day';
		else $bounds['defaultChartGranularity'] = 'hour';
		
		return $bounds;
	}


}

?>