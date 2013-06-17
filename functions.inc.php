<?php /* $Id */

if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//This file is part of FreePBX.
//
//    This is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 2 of the License, or
//    (at your option) any later version.
//
//    This module is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    see <http://www.gnu.org/licenses/>.
//



//  check for settings and return
function wwinner_config($id) {
	$sql = "SELECT * FROM wwinner_config WHERE `id` = '".$id".'";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	return is_array($results)?$results:array();
}

// store settings
function wwinner_edit($id,$post){
	global $db;

	$var1 = $db->escapeSimple($post['enable']);
	$var2 = $db->escapeSimple($post['record']);
	$var3 = $db->escapeSimple($post['destination']);



	$results = sql("
		UPDATE wwinner_config 
		SET 
			enable = '$var1', 
			record = '$var2', 
			destination = '$var3'
		WHERE id = '$id'");

}

function wwinner_get_config($engine) {
	// This generates the dialplan
	global $ext;
	global $asterisk_conf;
	switch($engine) {
		case "asterisk":
			$config = wwinner_config();
			$context = "app-wwinner-reset";
			$exten = "s";

			if ($config[0]['enable']=='CHECKED' && $config[0]['record']=='CHECKED')
			{
				$ext->splice($context, $exten, 4, new ext_gosub('1', 's', 'sub-record-check', 'rg,s,always'));
				$ext->splice($context, $exten, 5, new ext_dial($config[0]['destination'],'60,rL,240000'));
				$ext->splice($context, $exten, 6, new ext_hangup);
			}
			else if ($config[0]['enable']=='CHECKED')
			{
				$ext->splice($context, $exten, 4, new ext_dial($config[0]['destination'],'60,rL,240000'));
				$ext->splice($context, $exten, 5, new ext_hangup);
			}
			
		break;
	}
}

function wwinner_wwinner($c) {
	global $ext;
	global $asterisk_conf;
	$config = wwinner_config();
	$id = "app-wwinner-check"; // The context to be included

	$ext->addInclude('from-internal-additional', $id); // Add the include from from-internal
	$ext->add($id, $c, '', new ext_setvar('foo', '${SET(GLOBAL(CONCOUNT)=${MATH(${CONCOUNT} + 1,i)})}'));
	$ext->add($id, $c, '', new ext_noop("WinnerWinner: Contest caller number ${foo} of ${CONWIN}"));
	$ext->add($id, $c, '', new ext_wait('1'));
	$ext->add($id, $c, '', new ext_gotoif('$["${foo}"="${CONWIN}"]', 'winner'));
//	$ext->add($id, $c, '', new ext_gotoif('$["${foo}">"${CONWIN}"]', 'done'));
	$ext->add($id, $c, '', new ext_playback('im-sorry&you-are-caller-num'));
	$ext->add($id, $c, '', new ext_saydigits('${foo}'));
// redirect to user selected destination	
	$ext->add($id, $c, '', new ext_playback('goodbye'));
	$ext->add($id, $c, '', new ext_macro('hangupcall'));
	$ext->add($id, $c, 'winner', new ext_macro('user-callerid'));
	$ext->add($id, $c, '', new ext_noop("WinnerWinner: Caller ${AMPUSER} is a winner!"));
	$ext->add($id, $c, '', new ext_playback('you-are-caller-num'));
	$ext->add($id, $c, '', new ext_saydigits('${foo}'));
	$ext->add($id, $c, '', new ext_playback('one-moment-please'));
// redirect to user selected destination
	$ext->add($id, $c, '', new ext_playback('goodbye'));
	$ext->add($id, $c, '', new ext_macro('hangupcall'));
	$ext->add($id, $c, 'reset', new ext_setvar('GLOBAL(CONCOUNT)', '0'));
	$ext->add($id, $c, '', new ext_setvar('GLOBAL(CONWIN)', '10'));
	$ext->add($id, $c, '', new ext_read('GLOBAL(CONWIN)', 'please-enter-the&count&number&of-calls'));
	$ext->add($id, $c, '', new ext_noop("WinnerWinner: Contest counter reset to ${CONCOUNT} winning number is set to ${CONWIN}"));
	$ext->add($id, $c, '', new ext_playback('count&is-set-to'));
	$ext->add($id, $c, '', new ext_saydigits('${CONWIN}'));
	$ext->add($id, $c, '', new ext_playback('goodbye'));
	$ext->add($id, $c, '', new ext_macro('hangupcall'));


}

		
function wwinner_vercheck() {
	$newver = false;
	
	$module_local = wwinner_xml2array("modules/wwinner/module.xml");
	$module_remote = wwinner_xml2array("https://github.com/POSSA/freepbx-winner_winner/master/module.xml");
	
	if ( $module_remote[module][version] > $module_local[module][version]) {
		$newver = true;
	}
	return ($newver);
}

//Parse XML file into an array
function wwinner_xml2array($url, $get_attributes = 1, $priority = 'tag')  {
	$contents = "";
	if (!function_exists('xml_parser_create'))
	{
		return array ();
	}
	$parser = xml_parser_create('');
	if(!($fp = @ fopen($url, 'rb')))
	{
		return array ();
	}
	while(!feof($fp))
	{
		$contents .= fread($fp, 8192);
	}
	fclose($fp);
	xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, trim($contents), $xml_values);
	xml_parser_free($parser);
	if(!$xml_values)
	{
		return; //Hmm...
	}
	$xml_array = array ();
	$parents = array ();
	$opened_tags = array ();
	$arr = array ();
	$current = & $xml_array;
	$repeated_tag_index = array ();
	foreach ($xml_values as $data)
	{
		unset ($attributes, $value);
		extract($data);
		$result = array ();
		$attributes_data = array ();
		if (isset ($value))
		{
			if($priority == 'tag')
			{
				$result = $value;
			}
			else
			{
				$result['value'] = $value;
			}
		}
		if(isset($attributes) and $get_attributes)
		{
			foreach($attributes as $attr => $val)
			{
				if($priority == 'tag')
				{
					$attributes_data[$attr] = $val;
				}
				else
				{
					$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
				}
			}
		}
		if ($type == "open")
		{
			$parent[$level -1] = & $current;
			if(!is_array($current) or (!in_array($tag, array_keys($current))))
			{
				$current[$tag] = $result;
				if($attributes_data)
				{
					$current[$tag . '_attr'] = $attributes_data;
				}
				$repeated_tag_index[$tag . '_' . $level] = 1;
				$current = & $current[$tag];
			}
			else
			{
				if (isset ($current[$tag][0]))
				{
					$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
					$repeated_tag_index[$tag . '_' . $level]++;
				}
				else
				{
					$current[$tag] = array($current[$tag],$result);
					$repeated_tag_index[$tag . '_' . $level] = 2;
					if(isset($current[$tag . '_attr']))
					{
						$current[$tag]['0_attr'] = $current[$tag . '_attr'];
						unset ($current[$tag . '_attr']);
					}
				}
				$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
				$current = & $current[$tag][$last_item_index];
			}
		}
		else if($type == "complete")
		{
			if(!isset ($current[$tag]))
			{
				$current[$tag] = $result;
				$repeated_tag_index[$tag . '_' . $level] = 1;
				if($priority == 'tag' and $attributes_data)
				{
					$current[$tag . '_attr'] = $attributes_data;
				}
			}
			else
			{
				if (isset ($current[$tag][0]) and is_array($current[$tag]))
				{
					$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
					if ($priority == 'tag' and $get_attributes and $attributes_data)
					{
						$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag . '_' . $level]++;
				}
				else
				{
					$current[$tag] = array($current[$tag],$result);
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if ($priority == 'tag' and $get_attributes)
					{
						if (isset ($current[$tag . '_attr']))
						{
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset ($current[$tag . '_attr']);
						}
						if ($attributes_data)
						{
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
					}
					$repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
				}
			}
		}
		else if($type == 'close')
		{
			$current = & $parent[$level -1];
		}
	}
	return ($xml_array);
}