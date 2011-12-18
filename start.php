<?php

include_once 'lib/functions.php';

function convo_memberlevels_init(){
	global $CONFIG;
	
	elgg_extend_view('profile/profilelinks', 'convo_memberlevels/levelreport', 0);
	elgg_extend_view('metatags', 'convo_memberlevels/metatags');
	elgg_extend_view('css', 'convo_memberlevels/css');
	
	// Load the language file
	register_translations($CONFIG->pluginspath . "convo_memberlevels/languages/");
	
	register_page_handler('convo_memberlevels','convo_memberlevels_page_handler');
	
	register_elgg_event_handler('create', 'annotation', 'convo_memberlevels_rated');
	register_elgg_event_handler('login','user','convo_memberlevels_login');
	
	// override permissions for the access_plus_permissions context
	register_plugin_hook('permissions_check', 'all', 'convo_memberlevels_permissions_check');
	
	// set the sync function to run every hour for users that have been active within the last hour
	register_plugin_hook('cron', 'hourly', 'convo_memberlevels_cron');
	
	register_plugin_hook('extend_join', 'profile_manager_member_search', 'convo_memberlevels_search_join');
	register_plugin_hook('extend_where', 'profile_manager_member_search', 'convo_memberlevels_search_where');
	
	
	if(isloggedin()){
		// now we know for sure every day they were online, not just on login
		convo_memberlevels_record_online(get_loggedin_user());
	}
}

register_elgg_event_handler('init', 'system', 'convo_memberlevels_init');