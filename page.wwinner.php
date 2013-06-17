<?php
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//Handling form stuff....
isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';
//the item we are currently displaying
isset($_REQUEST['itemid'])?$itemid=$db->escapeSimple($_REQUEST['itemid']):$itemid='';

switch ($action) {
	case "add":
		needreload();
	break;
	case "delete":
		needreload();
		redirect_standard();
	break;
	case "edit": 
		needreload();
		redirect_standard();
	break;
}

$variables = array(
	'astmanconnected' => $astman->connected(),
	'listcommands' => $astman->ListCommands(),
	'astdatabase' => $astman->database_show(),
	'ds' => drawselects('',1,false,false),
	'amp_conf' => $amp_conf
);
$html = load_view(dirname(__FILE__).'/views/main.tpl', $variables);
echo $html;

$astman->database_put('family','key','valuer');
$out = $astman->database_get('family','key');
//echo $out;