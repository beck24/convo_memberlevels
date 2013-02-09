<?php

//
//	Called when a user logs in, or when someone rates a users wire post, or on cron
//	Calculates the current level score for the current month and saves as metadata
function convo_memberlevels_calculate_level($user, $month = NULL, $day = NULL, $year = NULL){
	
	if (!elgg_instanceof($user, 'user')) {
		return;
	}
	
	if (!$month || !is_numeric($month) || $month > 12) {
		$month = date("m");
	}
	
	if (!$day || !is_numeric($day) || $day > 31) {
		$day = date("j");
	}
	
	if (!$year || !is_numeric($year) || $year > date("Y")) {
		$year = date("Y");
	}
	
	elgg_set_ignore_access(TRUE);
	$context = elgg_get_context();
	elgg_set_context('convo_memberlevels_permission');
	
	$loginhistoryfield = 'convo_memberlevels-history-' . $month . '-' . $year; // array of login days
	$loginbonusfield = 'convo_memberlevels-loginbonus-' . $month . '-' . $year; // numerical bonus 0-1
	$loginscorefield = 'convo_memberlevels-loginscore-' . $month . '-' . $year; // % of days logged in
	$convoscorefield = 'convo_memberlevels-convoscore-' . $month . '-' . $year; // avg convo rating 0-5
	$memberscorefield = 'convo_memberlevels-memberscore-' . $month . '-' . $year; // monthly score, 0-5
	
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
		$num = elgg_get_annotations(array(
			'type' => 'object',
			'subtype' => 'thewire',
			'annotation_name' => 'generic_rate',
			'annotation_calculation' => 'count'
		));
		
		$replytotal += $num;
		$scoretotal += ($avg * $num);
	}
	
	
	if (empty($replytotal)) { // prevent division by 0
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
	
	$loginscore = round((count($loginhistory) / $day), 4) * 100; // gives % of days logged in
	
	$loginbonus = -2;
	
	if ($loginscore > 25) {
		$loginbonus += 1;	
	}
	
	if ($loginscore >= 50) {
		$loginbonus += 1.5;
	}
	
	if ($loginscore >= 75) {
		$loginbonus += 0.5;
	}
	
	// token score just for logging in this month
	$memberscore = $convoscore + $loginbonus;
	if ($memberscore <= 0) {
		$memberscore = 0.1;
	}
	
	if ($memberscore > 5) {
		$memberscore = 5;
	}
	
	// no login history = automatic 0
	if (count($loginhistory) == 0) {
	  $memberscore = 0;
	}
	
	// now we save the metadata
	$user->$loginbonusfield = $loginbonus;
	$user->$loginscorefield = $loginscore;
	$user->$convoscorefield = $convoscore;
	$user->$memberscorefield = $memberscore;
	
	$user->save();
	
	elgg_set_ignore_access(FALSE);
	elgg_set_context($context);
}

// this runs hourly, don't want to overload the server
// so we'll split up the calculations so that the entire userbase is
// covered in 24 hours, each hour doing 1/24th of the population
// @TODO - with 1.8 we can do this once a day and use ElggBatch
function convo_memberlevels_cron($hook, $type, $returnvalue, $params){
	
	$options = array(
		'types' => array('user'),
		'limit' => 0
	);
	
	// process daily
	$batch = new ElggBatch('elgg_get_entities', $options, 'convo_memberlevels_cron_batch', 25);
}


function convo_memberlevels_cron_batch($result, $getter, $options) {
  convo_memberlevels_calculate_level($result);
}

//
//	Called when a user logs in
// 	This function logs the day they have logged in
//	History stored in metadata on the user entity
//  $user->convo_memberlevels-MM-YYYY
// 	The history is recorded as an array of days logged in, array('1' => 1, '14' => 1) means logged in
// 	on the first and 14th of the month 
function convo_memberlevels_login($event, $object_type, $object) {
	$user = $object;

	convo_memberlevels_record_online($user);
	convo_memberlevels_calculate_level($user);
}


function convo_memberlevels_rated($event, $object_type, $object) {
	$wirepost = get_entity($object->entity_guid);
	
	if ($object->name == 'generic_rate' && $wirepost->getSubtype() == 'thewire') {
		$user = get_user($wirepost->owner_guid);
		
		if ($user) {
			convo_memberlevels_calculate_level($user);
		}
	}
}


function convo_memberlevels_permission_check($hook_name, $entity_type, $return_value, $parameters) {
	if (elgg_get_context() == "convo_memberlevels_permission") {
		return TRUE;
	}
	return NULL;
}


function convo_memberlevels_record_online($user) {
	$day = date("j");
	$field = 'convo_memberlevels-history-' . date("m") . '-' . date("Y");
	$history = unserialize($user->$field);
	
	if (!is_array($history)) {
		$history = array();
	}
	
	$history[$day] = 1;
	$user->$field = serialize($history);
	$user->save();
}

/*
 *	  SEARCH DISABLED IN 1.8
 * 
function convo_memberlevels_search_join($hook_name, $entity_type, $return_value, $parameters) {
  global $CONFIG;
  
  $levelfilter = get_input('convo_memberlevels');
  
  if($levelfilter){
    $return_value .= " JOIN {$CONFIG->dbprefix}metadata cml_md ON e.guid = cml_md.entity_guid JOIN {$CONFIG->dbprefix}metastrings cml_ms ON cml_ms.id = cml_md.value_id";
  
    return $return_value;
  }
}

function convo_memberlevels_search_where($hook_name, $entity_type, $return_value, $parameters){
  $levelfilter = get_input('convo_memberlevels');
  
  if($levelfilter){
    $silver = get_plugin_setting('silver_limit', 'convo_memberlevels') ? get_plugin_setting('silver_limit', 'convo_memberlevels') : 20;
    $gold = get_plugin_setting('gold_limit', 'convo_memberlevels') ? get_plugin_setting('gold_limit', 'convo_memberlevels') : 40;
    $platinum = get_plugin_setting('platinum_limit', 'convo_memberlevels') ? get_plugin_setting('platinum_limit', 'convo_memberlevels') : 60;
    $elite = get_plugin_setting('elite_limit', 'convo_memberlevels') ? get_plugin_setting('elite_limit', 'convo_memberlevels') : 80;
    
    $silverlimit = round(($silver / 20), 2);
    $goldlimit = round(($gold / 20), 2);
    $platinumlimit = round(($platinum / 20), 2);
    $elitelimit = round(($elite / 20), 2);
    
    $roundmod = 0.025;  // = 0.5% when rounded, so subtract that to include people who get rounded up
  
    switch ($levelfilter) {
      case elgg_echo('convo_memberlevels:color:bronze'):
        $lower = 0.005;
        $upper = $silverlimit - $roundmod;  
        break;
      case elgg_echo('convo_memberlevels:color:silver'):
        $lower = $silverlimit - $roundmod;
        $upper = $goldlimit - $roundmod;  
        break;
      case elgg_echo('convo_memberlevels:color:gold'):
        $lower = $goldlimit - $roundmod;
        $upper = $platinumlimit - $roundmod;  
        break;
      case elgg_echo('convo_memberlevels:color:platinum'):
        $lower = $platinumlimit - $roundmod;
        $upper = $elitelimit - $roundmod;  
        break;
      case elgg_echo('convo_memberlevels:color:elite'):
        $lower = $elitelimit - $roundmod;
        $upper = 5;  
        break;
      default:
        $lower = 0;
        $upper = 5;
        break;
    }
  
  
    $name_metastring_id = get_metastring_id('convo_memberlevels-memberscore-' . date("m") . '-' . date("Y"));

    $return_value .= " cml_md.name_id = {$name_metastring_id} AND cml_ms.string BETWEEN {$lower} AND {$upper} AND ";
    
    return $return_value;
  }
}
*/
