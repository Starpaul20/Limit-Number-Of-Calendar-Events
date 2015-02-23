<?php
/**
 * Limit Number of Calendar Events
 * Copyright 2011 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Tell MyBB when to run the hooks
$plugins->add_hook("calendar_do_addevent_start", "limitcalendarevents_run");
$plugins->add_hook("calendar_addevent_start", "limitcalendarevents_run");

$plugins->add_hook("admin_formcontainer_output_row", "limitcalendarevents_usergroup_permission");
$plugins->add_hook("admin_user_groups_edit_commit", "limitcalendarevents_usergroup_permission_commit");

// The information that shows up on the plugin manager
function limitcalendarevents_info()
{
	global $lang;
	$lang->load("limitcalendarevents", true);

	return array(
		"name"				=> $lang->limitcalendarevents_info_name,
		"description"		=> $lang->limitcalendarevents_info_desc,
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.1",
		"codename"			=> "limitcalendarevents",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is activated.
function limitcalendarevents_activate()
{
	global $db, $cache;

	switch($db->type)
	{
		case "pgsql":
			$db->add_column("usergroups", "maxeventsday", "int NOT NULL default '10'");
			break;
		case "sqlite":
			$db->add_column("usergroups", "maxeventsday", "int(3) NOT NULL default '10'");
			break;
		default:
			$db->add_column("usergroups", "maxeventsday", "int(3) unsigned NOT NULL default '10'");
			break;
	}

	$cache->update_usergroups();
}

// This function runs when the plugin is deactivated.
function limitcalendarevents_deactivate()
{
	global $db, $cache;
	if($db->field_exists("maxeventsday", "usergroups"))
	{
		$db->drop_column("usergroups", "maxeventsday");
	}

	$cache->update_usergroups();
}

// Limit Calendar Events per day
function limitcalendarevents_run()
{
	global $mybb, $db, $lang;
	$lang->load("limitcalendarevents");

	// Check group limits
	if($mybb->usergroup['maxeventsday'] > 0)
	{
		$query = $db->simple_select("events", "COUNT(*) AS post_count", "uid='".(int)$mybb->user['uid']."' AND dateline >= '".(TIME_NOW - (60*60*24))."'");
		$post_count = $db->fetch_field($query, "post_count");
		if($post_count >= $mybb->usergroup['maxeventsday'])
		{
			$lang->error_max_events_day = $lang->sprintf($lang->error_max_events_day, $mybb->usergroup['maxeventsday']);
			error($lang->error_max_events_day);
		}
	}
}

// Admin CP permission control
function limitcalendarevents_usergroup_permission($above)
{
	global $mybb, $lang, $form;
	$lang->load("limitcalendarevents", true);

	if($above['title'] == $lang->calendar && $lang->calendar)
	{
		$above['content'] .= "<div class=\"group_settings_bit\">{$lang->max_events_per_day}:<br /><small>{$lang->max_events_per_day_desc}</small><br /></div>".$form->generate_numeric_field('maxeventsday', $mybb->input['maxeventsday'], array('id' => 'maxeventsday', 'class' => 'field50', 'min' => 0));
	}

	return $above;
}

function limitcalendarevents_usergroup_permission_commit()
{
	global $mybb, $updated_group;
	$updated_group['maxeventsday'] = $mybb->get_input('maxeventsday', MyBB::INPUT_INT);
}

?>