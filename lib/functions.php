<?php

//
//	Called when a user logs in, or when someone rates a users wire post, or on cron
//	Calculates the current level score for the current month and saves as metadata
function convo_memberlevels_calculate_level($user, $month = NULL, $day = NULL, $year = NULL){
	
	if(!($user instanceof ElggUser)){
		return;
	}
	
	if(!$month || !is_numeric($month) || $month > 12){
		$month = date("m");
	}
	
	if(!$day || !is_numeric($day) || $day > 31){
		$day = date("j");
	}
	
	if(!$year || !is_numeric($year) || $year > date("Y")){
		$year = date("Y");
	}
	
	elgg_set_ignore_access(TRUE);
	$context = get_context();
	set_context('convo_memberlevels_permission');
	
	$loginhistoryfield = 'convo_memberlevels-history-' . $month . '-' . $year; // array of login days
	$loginbonusfield = 'convo_memberlevels-loginbonus-' . $month . '-' . $year; // numerical bonus 0-1
	$loginscorefield = 'convo_memberlevels-loginscore-' . $month . '-' . $year; // % of days logged in
	$convoscorefield = 'convo_memberlevels-convoscore-' . $month . '-' . $year; // avg convo rating 0-5
	$memberscorefield = 'convo_memberlevels-memberscore-' . $month . '-' . $year; // monthly score, 0-6
	$calculatedfield = 'convo_memberlevels-calculated-' . $month . '-' . $day . '-' . $year; // flag to find out if this was calculated for this user
	
	$nextmonth = ($month == 12) ? 1 : $month + 1;
	$nextyear = ($nextmonth > 1) ? $year : $year + 1; 
	
	// get all convos for this month for this user
	$params = array(
		'types' => array('object'),
		'subtypes' => array('thewire'),
		'owner_guids' => array($user->guid),
		'limit' => 0,
		'created_time_lower' => mktime(0,0,0,$month,1,$year),
		'created_time_upper' => mktime(0,0,0,$nextmonth,1,$nextyear),
	);
	
	$wireposts = elgg_get_entities($params);
	
	if(!is_array($wireposts)){
		$wireposts = array();
	}
	
	foreach($wireposts as $wirepost){
		$avg = $wirepost->getAnnotationsAvg('generic_rate');
		$num = count_annotations($entity_guid = 0, $entity_type = "object", $entity_subtype = "thewire", $name = "generic_rate", $value = "", $value_type = "", $owner_guid = 0, $timelower = 0, $timeupper = 0);
		
		$replytotal += $num;
		$scoretotal += ($avg * $num);
	}
	
	
	if(empty($replytotal)){ // prevent division by 0
		$convoscore = 0;	
	}
	else{
		$convoscore = round(($scoretotal / $replytotal), 2); // will be a number between 0 and 5
	}
	
	
	// now provide the login bonus
	$loginhistory = unserialize($user->$loginhistoryfield);
	
	if(!is_array($loginhistory)){
		$loginhistory = array();
	}
	
	$loginscore = round( (count($loginhistory) / $day), 4 ) * 100; // gives % of days logged in
	
	$loginbonus = -1;
	
	if($loginscore > 25){
		$loginbonus += 0.5;	
	}
	
	if($loginscore >= 50){
		$loginbonus += 1;
	}
	
	if($loginscore >= 75){
		$loginbonus += 0.5;
	}
	
	$memberscore = $convoscore + $loginbonus;
	if($memberscore < 0){
		$memberscore = 0;
	}
	if($memberscore > 5){
		$memberscore = 5;
	}
	
	// now we save the metadata
	$user->$loginbonusfield = $loginbonus;
	$user->$loginscorefield = $loginscore;
	$user->$convoscorefield = $convoscore;
	$user->$memberscorefield = $memberscore;
	$user->$calculatedfield = 1;
	
	// delete yesterdays calculation flag, because we want to keep the db clean
	
	$yesterday = time() - (60*60*24);
	
	remove_metadata($user->guid, 'convo_memberlevels-calculated-' . date("m", $yesterday) . '-' . date("j", $yesterday) . '-' . date("Y", $yesterday));
	
	elgg_set_ignore_access(FALSE);
	set_context($context);
}

// this runs hourly, don't want to overload the server
// so we'll split up the calculations so that the entire userbase is
// covered in 24 hours, each hour doing 1/24th of the population
function convo_memberlevels_cron($hook, $type, $returnvalue, $params){
	global $CONFIG;
	$interval = get_plugin_setting('interval', 'convo_memberlevels');
	
	if(!$interval || $interval == 24){
		$interval = 0;
	}
	
	$interval++;
	
	set_plugin_setting('interval', $interval, 'convo_memberlevels');
	
	$name_metastring_id = get_metastring_id('convo_memberlevels-calculated-' . date("m") . '-' . date("j") . '-' . date("Y"));
	
	$params = array(
		'types' => array('user'),
		'limit' => 0,
		'count' => TRUE,
		'wheres' => array(
			"NOT EXISTS (
			SELECT 1 FROM {$CONFIG->dbprefix}metadata md
			WHERE md.entity_guid = e.guid
				AND md.name_id = $name_metastring_id)"
			),
	);

	$numusers = elgg_get_entities($params);
	$limit = ceil($numusers / 24);

	$offset = $limit * $interval;
	
	$params = array(
		'types' => array('user'),
		'limit' => $limit,
		'offset' => $offset,
		'wheres' => array(
			"NOT EXISTS (
			SELECT 1 FROM {$CONFIG->dbprefix}metadata md
			WHERE md.entity_guid = e.guid
				AND md.name_id = $name_metastring_id)"
			),
	);
	
	$users = elgg_get_entities($params);
	
	foreach($users as $user){
		convo_memberlevels_calculate_level($user);
	}
}

//
//	Called when a user logs in
// 	This function logs the day they have logged in
//	History stored in metadata on the user entity
//  $user->convo_memberlevels-MM-YYYY
// 	The history is recorded as an array of days logged in, array('1' => 1, '14' => 1) means logged in
// 	on the first and 14th of the month 
function convo_memberlevels_login($event, $object_type, $object){
	$user = $object;

	convo_memberlevels_record_online($user);
	convo_memberlevels_calculate_level($user);
}


function convo_memberlevels_rated($event, $object_type, $object){
	$wirepost = get_entity($object->entity_guid);
	
	if($object->name == 'generic_rate' && $wirepost->getSubtype() == 'thewire'){
		$user = get_user($wirepost->owner_guid);
		
		if($user){
			convo_memberlevels_calculate_level($user);
		}
	}
}


function convo_memberlevels_permission_check($hook_name, $entity_type, $return_value, $parameters){
	if(get_context() == "convo_memberlevels_permission"){
		return TRUE;
	}
	return NULL;
}


function convo_memberlevels_record_online($user){
	$day = date("j");
	$field = 'convo_memberlevels-history-' . date("m") . '-' . date("Y");
	$history = unserialize($user->$field);
	
	if(!is_array($history)){
		$history = array();
	}
	
	$history[$day] = 1;
	$user->$field = serialize($history);
}