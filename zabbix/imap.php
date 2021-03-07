<?php

bindtextdomain('imap', 'locale');
bind_textdomain_codeset('imap', 'UTF-8');

require_once '/usr/share/zabbix/include/config.inc.php';
require_once dirname(__FILE__).'/imap/DB.php';

textdomain("imap");
textdomain("frontend");
$page['file'] = 'imap.php';
$page['hist_arg'] = array('groupid', 'hostgroupid', 'hostid', 'show_severity','control_map','with_triggers_only');
$page['scripts'] = ['multiselect.js', 'gtlc.js'];

$googleapikey='-';
$weatherapikey = "-";
//!!!!!!!!!!!!
$defaultgroupid = '43';//modify this for normal work
//!!!!!!!!!!!!

if (function_exists('getRequest')) {
	$lat = getRequest('lat', null);
	$lng = getRequest('lng', null);
	$with_triggers_only = getRequest('with_triggers_only', 0);
	$control_map = getRequest('control_map', 0);
	$showSeverity = getRequest('severity_min', 0);
	$output = getRequest('output', false);
	$action_ajax = getRequest('action_ajax', false);
	$hostid = getRequest('hostid', null);
	$thostid = getRequest('thostid', null);
	$filtergroupid = getRequest('hostgroupid', null);
	$ajaxgroupid = getRequest('groupid', null);
	$lat = getRequest('lat', null);
	$lng = getRequest('lng', null);
	$hardware = ''.getRequest('hardware', '');
	$linkid = getRequest('linkid', null);
	$linkoptions = getRequest('linkoptions', null);
	$hardwareField = getRequest('hardwareField');
	$searchstring = getRequest('searchstring','');
};

if (function_exists('get_current_nodeid')) { 
    $nodeids = get_current_nodeid();
};

if (function_exists('GetCurrentNodeId')) { 
    $nodeids = getCurrentNodeId();
};

if ($output!='ajax') {
	require_once '/usr/share/zabbix/include/page_header.php';
};

$fields = array(
	'groupid' =>				array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'hostid' =>					array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'thostid' =>				array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'linkid' =>					array(T_ZBX_INT, O_OPT, P_SYS,	DB_ID,		null),
	'severity_min' =>			array(T_ZBX_INT, O_OPT, P_SYS,			IN('0,1,2,3,4,5'),		null),
	'fullscreen' =>				array(T_ZBX_INT, O_OPT, P_SYS,			IN('0,1'),	null),
	'control_map' =>			array(T_ZBX_INT, O_OPT, P_SYS,			IN('0,1'),	null),
	'with_triggers_only' =>		array(T_ZBX_INT, O_OPT, P_SYS,			IN('0,1'),	null),
	'output' =>					array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	'jsscriptid' =>				array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	// ajax
	'favobj' =>					array(T_ZBX_STR, O_OPT, P_ACT,	null,		null),
	'favref' =>					array(T_ZBX_STR, O_OPT, P_ACT,	null,		null),
	'favid' =>					array(T_ZBX_INT, O_OPT, P_ACT,	null,		null),
	'favcnt' =>					array(T_ZBX_INT, O_OPT, null,	null,		null),
	'pmasterid' =>				array(T_ZBX_STR, O_OPT, P_SYS,	null,		null),
	'favaction' =>				array(T_ZBX_STR, O_OPT, P_ACT,	IN("'add','remove','refresh','flop','sort'"), null),
	'favstate' =>				array(T_ZBX_INT, O_OPT, P_ACT,	NOT_EMPTY,	'isset({favaction})&&("flop"=={favaction})'),
	'favdata' =>				array(T_ZBX_STR, O_OPT, null,	null,		null),
	'hardwareField' =>			array(T_ZBX_STR, O_OPT, null,	null,		null),
	//стандартные
	'btnSelect' =>					[T_ZBX_STR, O_OPT, null,	null,		null],
	'filter_rst' =>					[T_ZBX_STR, O_OPT, P_SYS,	null,		null],
	'filter_set' =>					[T_ZBX_STR, O_OPT, P_SYS,	null,		null],
	'tags' =>						[T_ZBX_STR, O_OPT, null,	null,		null],
	'show_triggers' =>				[T_ZBX_INT, O_OPT, null,	null,		null],
	'show_events' =>				[T_ZBX_INT, O_OPT, P_SYS,	null,		null],
	'ack_status' =>					[T_ZBX_INT, O_OPT, P_SYS,	null,		null],
	'show_severity' =>				[T_ZBX_INT, O_OPT, P_SYS,	null,		null],
	'status_change_days' =>			[T_ZBX_INT, O_OPT, null,	BETWEEN(1, DAY_IN_YEAR * 2), null],
	'status_change' =>				[T_ZBX_INT, O_OPT, null,	null,		null],
	'txt_select' =>					[T_ZBX_STR, O_OPT, null,	null,		null],
	'application' =>				[T_ZBX_STR, O_OPT, null,	null,		null],
	'inventory' =>					[T_ZBX_STR, O_OPT, null,	null,		null]
);
check_fields($fields);

/*
 * Filter
 */
$config = select_config();

		if (!$filtergroupid) {
			$filtergroupid = $defaultgroupid;
		}
$data['filter']['hostgroupid'] = $filtergroupid;

function rightsErrorAjax() {
		$responseData = '{"jsonrpc": "2.0","error": {"message": "Access error. Check rights."}}';
		echo $responseData;
		exit;
};

function checkHostsIsWritable($hostsids) {
		$hosts = API::Host()->get(array('editable'=>true,'hostids'=>$hostsids));
		if (count($hosts) == count($hostsids)) return TRUE;
		return FALSE;
}

function rightsForLink($idl) {
	$glinks = DBfetchArray(DBselect(
	'SELECT host1, host2
	FROM hosts_links WHERE hosts_links.id = '.$idl
	));
	if (checkHostsIsWritable(array(1*$glinks[0]['host1'], 1*$glinks[0]['host2']))) return (true);
	return (false);
};

if ($output=='ajax') {
	
	if (!function_exists('json_encode')) {
		$responseData = '{"jsonrpc": "2.0","error": {"message": "No function `json_encode` in PHP. Look at <a target=_blank href=\'http://stackoverflow.com/questions/18239405/php-fatal-error-call-to-undefined-function-json-decode\'>link</a>"}}';
		echo $responseData;
		exit;
	};
	
	$options = array(
		'output' => API_OUTPUT_EXTEND,
		'expandDescription' => true,
		'preservekeys' => true,
		'monitored' => true
	);

		if ($hostid > 0) {
			$options['hostids'] = $hostid;
		}
		elseif ($ajaxgroupid > 0) {
			$options['groupids'] = $ajaxgroupid;
		} 
		else {
			$options['hostids'] = array();
		};

	if ($action_ajax=='get_triggers') {
		$options['expandData'] = true;
		$options['expandDescription'] = true;
		$options['selectLastEvent'] = 'extend';
		$options['monitored'] = true;
		$options['maintenance'] = false;
		$options['skipDependent'] = true;
		$options['sortfield'] = array('lastchange');
		$options['sortorder'] = 'DESC';
		$options['filter'] = array('value' => TRIGGER_VALUE_TRUE);
		$options['selectHosts'] = array('hostid', 'name');
		if ($showSeverity > TRIGGER_SEVERITY_NOT_CLASSIFIED) {
			$options['min_severity'] = $showSeverity;
		};

		$triggers = API::Trigger()->get($options);

		$ntriggers = array();
		foreach ($triggers as $tr) {
			$ntriggers[] = $tr;
		};
		
		$responseData = json_encode($ntriggers);
		echo $responseData;
		exit;
	};
	
	if ($action_ajax=='search_hosts') {
		//$options = array();
		$options['searchByAny'] = true;
		$options['output'] = 'hostid';
		$options['search'] = array(
			'host' => $searchstring,
			'name' => $searchstring,
			'dns' => $searchstring,
			'ip' => $searchstring
		 );
		
		$hosts = API::Host()->get($options);
		$responseData = json_encode($hosts, FALSE);
		echo $responseData;
		exit;
	};
	
	if ($action_ajax=='get_host') {
		$options['selectInterfaces'] = 'extend';
		//$options['selectInventory'] = array('location_lat','location_lon','url_a','url_b','url_c');
		$options['selectInventory'] = 'extend';
		//if ($hardwareField) $options['selectInventory'][] = $hardwareField;
		$options['selectMaintenances'] = 'extend';
		$hosts = API::Host()->get($options);
		
		//список скриптов
		$hostids = array();
		foreach ($hosts as $host) {
			$hostids[] = $host['hostid'];
		};
		$scripts = API::Script()->getScriptsByHosts($hostids);
		foreach ($hosts as $host) {
			$hosts[$host['hostid']]['scripts'] = $scripts[$host['hostid']];
		};
		
		$responseData = json_encode($hosts, FALSE);
		echo $responseData;
		exit;
	};
	//отсюда первоначально загружаем данные в карту1
	if ($action_ajax=='get_hosts') {
	
		$options['monitored_hosts'] = true;
		$options['withInventory'] = true;
		$options['output'] = array('hostid','name','description');
		$options['selectInventory'] = array('location_lat','location_lon','contract_number','url_a','url_b','url_c');
		if ($hardwareField) $options['selectInventory'][] = $hardwareField;
		$options['selectMaintenances'] = 'extend';
		$hosts = API::Host()->get($options);
		$responseData = json_encode($hosts, FALSE);
		echo $responseData;
		exit;
	};
	
	if ($action_ajax=='update_coords') {
	
		if (!checkHostsIsWritable(array($hostid))) rightsErrorAjax();
	
		if ((lat=='none') or ($lng=='none')) { 
			$lat=null; $lng=null;
		} else {
			$lat = 1*$lat; $lng = 1*$lng;
		};
		$options = array(
			'hostid' => $hostid,
			'inventory' => array(
				'location_lat' => $lat,
				'location_lon' => $lng
			)
		);
		
		$hosts = API::Host()->update($options);
		
		$responseData = json_encode(array('result' => $hosts), FALSE);
		echo $responseData;
		exit;
	};
	
	if ($action_ajax=='get_hardware') {
		if (is_dir('imap/hardware/')) {
			$tmp = scandir('imap/hardware/');
			$responseData = array();
			for ($nn=0; $nn<count($tmp); $nn++) {
				if ((mb_strtolower(substr($tmp[$nn],-4)) == '.png') and ($tmp[$nn]!='none.png'))
					$responseData[] = $tmp[$nn];
			};
		} else {
			$responseData=false;
		};
		$responseData = json_encode(array('result' => $responseData), FALSE);
		echo $responseData;
		exit;
	};
	
	if ($action_ajax=='set_hardware') {
		if (!checkHostsIsWritable(array($hostid))) rightsErrorAjax();
		$options = array(
			'hostid' => $hostid,
			'inventory' => array()
		);
		$options['inventory'][$hardwareField] = $hardware;
		$hosts = API::Host()->update($options);
		
		$responseData = json_encode(array('result' => $hosts), FALSE);
		echo $responseData;
		exit;
	};
	
	if ($action_ajax=='get_graphs') {
		$options['expandName'] = true;
		$graphs = API::Graph()->get($options);
		$responseData = json_encode($graphs, FALSE);
		echo $responseData;
		exit;
	};
	
	if ($action_ajax=='get_link') {
		
		$res1 = DBimap::find('hosts_links', array('id' => $linkid));
		$res2 = DBimap::find('hosts_links_settings', array('ids' => $linkid));
		
		$res = array();
		
		foreach ($res1 as $res1t) {
			foreach ($res2 as $res2t) {
				if ($res1t['id']==$res2t['ids']) {
					$res1t = $res1t+$res2t;
				};
			};
			if (!$res1t['dash']) $res1t['dash']=0;
			if (!$res1t['weight']) $res1t['weight']=0;
			if (!$res1t['color']) $res1t['color']=0;
			if (!$res1t['opacity']) $res1t['opacity']=0;
			$res[] = $res1t;
		};
		
		$responseData = json_encode($res, FALSE);
		echo $responseData;
		exit;
	};
	
	if ($action_ajax=='get_links') {
		
		$res1 = DBimap::find('hosts_links');
		$res2 = DBimap::find('hosts_links_settings');
		
		$res = array();
		
		foreach ($res1 as $res1t) {
			foreach ($res2 as $res2t) {
				if ($res1t['id']==$res2t['ids']) {
					$res1t = $res1t+$res2t;
				};
			};
			if (!$res1t['dash']) $res1t['dash']=0;
			if (!$res1t['weight']) $res1t['weight']=0;
			if (!$res1t['color']) $res1t['color']=0;
			if (!$res1t['opacity']) $res1t['opacity']=0;
			$res[] = $res1t;
		};
		
		$responseData = json_encode($res, FALSE);
		echo $responseData;
		exit;
	};
	
	if ($action_ajax=='add_links') {
		
		if (!checkHostsIsWritable(array($hostid))) rightsErrorAjax();
		$shost=$hostid;
			foreach ($thostid as $thost) {
				if (checkHostsIsWritable(array($hostid))) {
					$newlink = array('host1' => MIN($shost,$thost), 'host2' => MAX($shost,$thost));
					$res = DBimap::insert('hosts_links', array($newlink));
				};
			};

		$responseData = json_encode(array('result'=>$res), FALSE);
		echo $responseData;
		exit;
	};
	
	if ($action_ajax=='update_link') {
		
		if (!rightsForLink($linkid)) rightsErrorAjax();
		$link=$linkid;
		
		$newlink = array( 'values' => array('name' => $linkoptions['linkname'] ), 'where' => array( 'id' => $link ) );
		$res = DBimap::update('hosts_links', array($newlink));
		
		$res = DBimap::delete('hosts_links_settings', array('ids'=>array($link)));
		
		$res = DBimap::insert('hosts_links_settings', array(array( 'ids' => $link, 'color' => $linkoptions['linkcolor'], 'weight' => $linkoptions['linkweight'], 'opacity' => $linkoptions['linkopacity'] )));
		
		$responseData = json_encode(array('result'=>htmlspecialchars($res),'linkoptions'=>$linkoptions), FALSE);
		echo $responseData;
		exit;
	};
	
	if ($action_ajax=='del_link') {
		
		if (!rightsForLink($linkid)) rightsErrorAjax();
		$link=$linkid;
		$res = DBimap::delete('hosts_links_settings', array('ids'=>array($link)));
		$res = DBimap::delete('hosts_links', array('id'=>array($link)));
		
		$responseData = json_encode(array('result'=>TRUE), FALSE);
		echo $responseData;
		exit;
	};
	
};

if ($output!='block') {

		$host_groups = API::HostGroup()->get([
			'output' => ['name'],
			'monitored_hosts' => true,
			'preservekeys' => true
		]);
		$host_groups = enrichParentGroups($host_groups);
		CArrayHelper::sort($host_groups, ['name']);

		if (!array_key_exists($data['filter']['hostgroupid'], $host_groups)) {
			$data['filter']['hostgroupid'] = 0;
		}

		$hostgroupid_combobox = (new CComboBox('hostgroupid', $data['filter']['hostgroupid'], 'javascript:  _imap.filter.groupid = this.value; loadHosts(); loadTriggrs();'));
		$hostgroupid_combobox->addItem(0, _('all'));

		foreach ($host_groups as $groupid => $group) {
			$hostgroupid_combobox->addItem($groupid, $group['name']);
		}

		$hostgroupids = [];
		if ($data['filter']['hostgroupid'] != 0) {
			$hostgroupids[$data['filter']['hostgroupid']] = true;
			$parent = $host_groups[$data['filter']['hostgroupid']]['name'].'/';

			foreach ($host_groups as $groupid => $group) {
				if (strpos($group['name'], $parent) === 0) {
					$hostgroupids[$groupid] = true;
				}
			}
		}

	/*
	* Display
	*/
	textdomain("imap");
	$web_layout_mode = 'ZBX_LAYOUT_NORMAL';
	$triggerWidget = (new CWidget())
			->setTitle(_('Карта состояния объектов'))
			->setWebLayoutMode($web_layout_mode);
	$rightForm = (new CForm('get'));
	$controls = new CList();

	// filter
textdomain("frontend");
$triggerWidget->setControls((new CList([
		(new CForm('get'))
			->cleanItems()
			->setAttribute('aria-label', _('Main filter'))
			->addItem((new CList())
				->addItem([
					new CLabel(_('Host group'), 'filter_hstgrpid'),
					(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
					$hostgroupid_combobox
				])
			)])));
	// filter end
	$triggerWidget->show();

};

$version=trim(file_get_contents('imap/version'));

textdomain("imap");

//проверяем наличие таблиц в БД
$check_links = true;
if (!DBselect('SELECT 1 FROM hosts_links')) {
	$check_links=false;
	clear_messages(1);
};
if (!DBselect('SELECT 1 FROM hosts_links_settings')) {
	$check_links=false;
	clear_messages(1);
};

//проверяем наличие функции json_encode
if (!function_exists('json_encode')) {
	error("No function 'json_encode' in PHP. Look this http://stackoverflow.com/questions/18239405/php-fatal-error-call-to-undefined-function-json-decode");
};

//проверяем доступ к файлам скрипта
$needThisFiles = array('imap/leaflet/leaflet.js','imap/leaflet/plugins/leaflet.markercluster.js','imap/imap.js');
foreach ($needThisFiles as $file) {
	if ( !is_readable ( $file ) ) {
		error (_('If you see this message, it means that the script had problems with access to the files. Try to set read permissions for the web-server to a folder imap.'));
		break;
	};
};

?>

<div id=imapworkarea style="display:none; position:relative;">
	<div id=mapdiv style="width:100%; height:791px;"></div>
	<div id=ajax></div>
	<div id=imapmes><div id=mesLoading><div><?php echo _('Загрузка карты...'); ?></div></div></div>
</div>
 <?php
//<link href="imap/jquery-ui/jquery-ui.css" rel="stylesheet">
//  <script src="imap/jquery-ui/jquery-ui.js"></script>

 ?>
<link href="imap/jquery-ui/jquery-ui.css" rel="stylesheet">
<script src="imap/jquery-ui/jquery-ui.js"></script>

<link rel="stylesheet" href="imap/leaflet/leaflet.css" />
<script type="text/javascript" src="imap/leaflet/leaflet.js"></script>
<script type="text/javascript" src="imap/leaflet/plugins/leaflet.label.js"></script>
<link rel="stylesheet" href="imap/leaflet/plugins/leaflet.label.css" />

<link rel="stylesheet" href="imap/leaflet/plugins/MarkerCluster.css" />
<link rel="stylesheet" href="imap/leaflet/plugins/MarkerCluster.Default.css" />
<script src="imap/leaflet/plugins/leaflet.markercluster.js"></script>

<script src="imap/leaflet/plugins/layer/tile/Bing.js"></script>

<script src="https://api-maps.yandex.ru/2.1/?load=package.map&lang=<?php echo CWebUser::$data['lang']; ?>" type="text/javascript"></script>
<script src="imap/leaflet/plugins/layer/tile/Yandex.js"></script>

<script src="https://maps.google.com/maps/api/js?key=<?php echo $googleapikey; ?>&v=3&language=<?php echo CWebUser::$data['lang']; ?>" async defer></script>
<script src="imap/leaflet/plugins/layer/tile/Google.js"></script>

<script src="imap/leaflet/plugins/jquery.fs.stepper.min.js"></script>
<link rel="stylesheet" href="imap/leaflet/plugins/jquery.fs.stepper.css" />

<script type="text/javascript" src="imap/colorpicker/colors.js"></script>
<script type="text/javascript" src="imap/colorpicker/jqColorPicker.js"></script>

<link rel="stylesheet" href="imap/leaflet/plugins/L.Control.Zoomslider.css" />
<script src="imap/leaflet/plugins/L.Control.Zoomslider.js"></script>

<script src="imap/leaflet/plugins/leaflet.measure/leaflet.measure.js"></script>
<link rel="stylesheet" href="imap/leaflet/plugins/leaflet.measure/leaflet.measure.css" />

<link rel="stylesheet" href="imap/markers.css?<?php echo rand(); ?>" />

<?php if (file_exists('imap/userstyles.css')) echo '<link rel="stylesheet" href="imap/userstyles.css" />'; ?>


<script type="text/javascript">

	var _imap = new Object;

	_imap.settings = new Object;
	_imap.settings.lang = "<?php echo CWebUser::$data['lang']; ?>";
	
	
	/* This settings changing in interactive mode */
	_imap.settings.do_map_control = <?php echo $control_map; ?>;
	_imap.settings.pause_map_control = false;
	_imap.settings.show_with_triggers_only = <?php echo $with_triggers_only; ?>;
	_imap.settings.min_status = <?php echo $showSeverity; ?>;
	_imap.mapcorners = new Object;
	_imap.version='<?php echo $version; ?>';
	_imap.zabbixversion='<?php echo ZABBIX_VERSION; ?>';

	/* This settings changing in file settings.js */
	_imap.settings.show_icons = true;
	_imap.settings.use_search = true;
	_imap.settings.use_zoom_slider = true;
	_imap.settings.links_enabled = false;
	_imap.settings.debug_enabled = false;
	_imap.settings.hardware_field = 'type';
	_imap.settings.maxMarkersSpiderfy = 50;
	_imap.settings.exluding_inventory = ['hostid','location_lat','location_lon','url_a','url_b','url_c'];
	_imap.settings.useIconsInMarkers = false;
	_imap.settings.startCoordinates = [59.95, 30.29];
	_imap.settings.startZoom = 4;
	_imap.settings.mapAnimation = true;
	_imap.settings.intervalLoadHosts = 20;
	_imap.settings.intervalLoadTriggers = 10;
	_imap.settings.intervalLoadLinks = 60;
	_imap.settings.showMarkersLabels = false;
	_imap.settings.spiderfyDistanceMultiplier = 1;
	_imap.settings.defaultbaselayer = "OpenStreetMap";
	_imap.settings.weather_apikey = '<?php echo $weatherapikey; ?>';
	_imap.settings.bing_apikey=false;
	
	_imap.mapcorners['googlesearch'] = 0;
	_imap.mapcorners['lasttriggers'] = 0;
	_imap.mapcorners['layers'] = 1;
	_imap.mapcorners['hosts'] = 1;
	_imap.mapcorners['attribution'] = 3;
	_imap.mapcorners['scale'] = 3;
	_imap.mapcorners['measure'] = 3;
	_imap.mapcorners['mylocationbutton'] = 2;
	_imap.mapcorners['zoom'] = 2;
	
	
	/* Перевод для текущего языка */
	<?php textdomain("frontend"); ?>
	locale.Search = '<?php echo _('Search'); ?>';
	
	locale.inventoryfields = new Object;
	<?php foreach (getHostInventories() as $field): ?>
		locale.inventoryfields["<?php echo $field['db_field'] ?>"] = "<?php echo $field['title'] ?>";
	<?php endforeach; ?>
	
	locale['Ack'] = '<?php echo _('Ack'); ?>';
	locale['Yes'] = '<?php echo _('Yes'); ?>';
	locale['No'] = '<?php echo _('No'); ?>';
	
	locale['Host inventory'] = '<?php echo _('Host inventory'); ?>';
	locale['Triggers'] = '<?php echo _('Triggers'); ?>';
	locale['Graphs'] = '<?php echo _('Graphs'); ?>';
	locale['Latest data'] = '<?php echo _('Latest data'); ?>';
	locale['Host'] = '<?php echo _('Host'); ?>';
	locale['Applications'] = '<?php echo _('Applications'); ?>';
	locale['Items'] = '<?php echo _('Items'); ?>';
	locale['Discovery rules'] = '<?php echo _('Discovery rules'); ?>';
	locale['Web scenarios'] = '<?php echo _('Web scenarios'); ?>';
	
	<?php textdomain("imap"); ?>
	locale['Change location'] = '<?php echo _('Изменить точку'); ?>';
	locale['Delete location'] = '<?php echo _('Удалить точку'); ?>';
	locale['Add a link to another host'] = '<?php echo _('Добавить связь с другой точкой'); ?>';
	locale['Select a new position'] = '<?php echo _('Выберите новое положение'); ?>';
	locale['Failed to update data'] = '<?php echo _('Не удалось обновить'); ?>';
	locale['Failed to get data'] = '<?php echo _('Не удалось получить'); ?>';
	locale['Error'] = '<?php echo _('Ошибка'); ?>';
	locale['Hosts'] = '<?php echo _('Хосты'); ?>';
	locale['This host does not have coordinates'] = '<?php echo _('Хост без координат'); ?>';
	locale['Set a hardware type'] = '<?php echo _('Установить иконку'); ?>';
	locale["Host's links"] = "<?php echo _("Связи точки"); ?>";
	locale['Show debug information'] = "<?php echo _("Показать служебную информацию"); ?>";
	locale['Debug information'] = "<?php echo _("Служебная информация"); ?>";
	locale['Select hosts for links'] = "<?php echo _("Select hosts for links"); ?>";
	locale['Name'] = "<?php echo _("Name"); ?>";
	locale['Delete link'] = "<?php echo _("Удалить связь"); ?>";
	locale['Link options'] = "<?php echo _("Параметры связи"); ?>";
	locale['Link name'] = "<?php echo _("Имя"); ?>";
	locale['Link color'] = "<?php echo _("Цвет"); ?>";
	locale['Link width'] = "<?php echo _("Толщина"); ?>";
	locale['Link opacity'] = "<?php echo _("Прозрачность"); ?>";
	locale['Link dash'] = "<?php echo _("Черта"); ?>";
	locale['Delete confirm'] = "<?php echo _("Подтвердить удаление"); ?>";
	locale['Successful'] = "<?php echo _("Успешно"); ?>";
	locale['Zoom in'] = "<?php echo _("Приблизить"); ?>";
	locale['Zoom out'] = "<?php echo _("Отдалить"); ?>";
	locale['No hosts with inventory'] = "<?php echo _("Нет хостов с заполненными координатами"); ?>";
	locale['Keep'] = "<?php echo _("Keep"); ?>";
	locale['Tools'] = "<?php echo _("Действия"); ?>";
	locale['Sort by severity'] = "<?php echo _("по важности"); ?>";
	locale['Sort by time'] = "<?php echo _("по времени"); ?>";
	locale['Config'] = '<?php echo _('Настройки'); ?>';
	locale['Host config'] = '<?php echo _('Настройки узла сети'); ?>';
	locale['Host view'] = '<?php echo _('Подробная информация'); ?>';
	locale['Wind speed'] = "<?php echo _("Скорость ветра"); ?>";
	locale['Wind points'] = "<?php echo _("Ветер"); ?>";
	locale['Wind type'] = "<?php echo _("Ветер"); ?>";
	locale['Wind direction'] = "<?php echo _("Направление ветра"); ?>";
	locale['Temperature'] = "<?php echo _("Темература"); ?>";
	locale['Humidity'] = "<?php echo _("Влажность"); ?>";
	locale['Pressure'] = "<?php echo _("Давление"); ?>";
	locale['Sunset'] = "<?php echo _("Закат"); ?>";
	locale['Sunrise'] = "<?php echo _("Восход"); ?>";
	locale['Data obtained'] = "<?php echo _("Дата данных"); ?>";
	locale['Show weather'] = "<?php echo _("Посмотреть погоду"); ?>";
	
	/* Фильтр для отбора хостов и групп */
	_imap.filter = {
		show_severity: 0,
/*		hostid: <?php echo '1'; ?>, */
		groupid: <?php echo $filtergroupid; ?>

	};


</script>
<script type="text/javascript" src="imap/imap.js<?php echo '?'.rand(); ?>"></script>

<?php

	if (file_exists('imap/settings.js')) echo '<script src="imap/settings.js?'.rand().'"></script>';
	if (file_exists('imap/additions.js')) echo '<script src="imap/additions.js?'.rand().'"></script>';
	if (!$check_links) echo '<script type="text/javascript"> _imap.settings.links_enabled = false; </script>';

	if (file_exists('imap/js')) {
		$files = scandir('imap/js');
		foreach($files as $file) {
			if (substr('imap/js/'.$file,-3)=='.js') echo '<script type="text/javascript" src="imap/js/'.$file.'"></script>';
		};
	};

textdomain("frontend");
if ($output!='block') {
	require_once '/usr/share/zabbix/include/page_footer.php';
};
