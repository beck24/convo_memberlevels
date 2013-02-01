<?php

include_once 'lib/functions.php';

function convo_memberlevels_init(){
	elgg_register_js('sparkline', elgg_get_site_url() . 'mod/convo_memberlevels/lib/jquery.sparkline.min.js');
	elgg_register_js('farbtastic', elgg_get_site_url() . 'mod/convo_memberlevels/lib/farbtastic/farbtastic.js');
	elgg_register_css('farbtastic', elgg_get_site_url() . 'mod/convo_memberlevels/lib/farbtastic/farbtastic.css');
	
	elgg_extend_view('profile/profilelinks', 'convo_memberlevels/levelreport', 0);
	elgg_extend_view('css/elgg', 'convo_memberlevels/css');
	elgg_extend_view('css/admin', 'convo_memberlevels/css');
	
	elgg_register_page_handler('convo_memberlevels','convo_memberlevels_page_handler');
	
	elgg_register_event_handler('create', 'annotation', 'convo_memberlevels_rated');
	elgg_register_event_handler('login', 'user','convo_memberlevels_login');
	
	// override permissions for the access_plus_permissions context
	elgg_register_plugin_hook_handler('permissions_check', 'all', 'convo_memberlevels_permissions_check');
	
	// set the sync function to run every hour for users that have been active within the last hour
	elgg_register_plugin_hook_handler('cron', 'hourly', 'convo_memberlevels_cron');
	
	/*  PROFILE MANAGER MEMBER SEARCH REMOVED IN 1.8  */
	//elgg_register_plugin_hook_handler('extend_join', 'profile_manager_member_search', 'convo_memberlevels_search_join');
	//elgg_register_plugin_hook_hanlder('extend_where', 'profile_manager_member_search', 'convo_memberlevels_search_where');
	
	
	if(elgg_is_logged_in()){
		// now we know for sure every day they were online, not just on login
		convo_memberlevels_record_online(get_loggedin_user());
	}
}

elgg_register_event_handler('init', 'system', 'convo_memberlevels_init');