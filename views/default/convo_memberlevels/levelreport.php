<?php

elgg_register_js('farbtastic');
elgg_register_css('farbtastic');
elgg_register_js('sparkline');

convo_memberlevels_calculate_level($vars['entity']);

$access = elgg_get_plugin_setting('access', 'convo_memberlevels');

if ($access == 'public' || elgg_is_admin_logged_in()) {

$month = date("m");
$year = date("Y");

//$loginhistoryfield = 'convo_memberlevels-history-' . $month . '-' . $year; // array of login days
$loginbonusfield = 'convo_memberlevels-loginbonus-' . $month . '-' . $year; // numerical bonus 0-1
$loginscorefield = 'convo_memberlevels-loginscore-' . $month . '-' . $year; // % of days logged in
$convoscorefield = 'convo_memberlevels-convoscore-' . $month . '-' . $year; // avg convo rating 0-5
$memberscorefield = 'convo_memberlevels-memberscore-' . $month . '-' . $year; // monthly score, 0-5

//$history = unserialize($vars['entity']->$loginhistoryfield);
$loginbonus = $vars['entity']->$loginbonusfield;
$loginscore = $vars['entity']->$loginscorefield;
$convoscore = $vars['entity']->$convoscorefield;
$memberscore = $vars['entity']->$memberscorefield;

/* http://trac.elgg.org/ticket/4268
 * 
 * has manifested itself here at least once
 * try to identify and mitigate the damage
 * - will break on the first load attempt but a refresh should fix it
 * */
$arraycheck = array(
  $loginbonusfield => $loginbonus,
  $loginscorefield => $loginscore,
  $convoscorefield => $convoscore,
  $memberscorefield => $memberscore
);

foreach ($arraycheck as $key => $value) {
  if (is_array($value)) {
	$metadata = elgg_get_metadata(array(
		'guid' => $vars['entity']->guid,
		'metadata_name' => $key,
		'limit' => 0
	));
	
    for ($i=1; $i<count($metadata); $i++) {
      $metadata[$i]->delete();
    }
  }
}
// END BUGFIX

$attendancevalues = "var attendance = [" . round($loginscore) . "," . (100 - round($loginscore)) . "];";
$convovalues = "var convovalues = [" . ($convoscore * 100) . "," . (500 - ($convoscore * 100)) . "];";


$background = elgg_get_plugin_setting('background', 'convo_memberlevels') ? elgg_get_plugin_setting('background', 'convo_memberlevels') : '#ffffff';
$bronze = elgg_get_plugin_setting('bronze', 'convo_memberlevels') ? elgg_get_plugin_setting('bronze', 'convo_memberlevels') : '#d26439';
$silver = elgg_get_plugin_setting('silver', 'convo_memberlevels') ? elgg_get_plugin_setting('silver', 'convo_memberlevels') : '#969696';
$gold = elgg_get_plugin_setting('gold', 'convo_memberlevels') ? elgg_get_plugin_setting('gold', 'convo_memberlevels') : '#ccad13';
$platinum = elgg_get_plugin_setting('platinum', 'convo_memberlevels') ? elgg_get_plugin_setting('platinum', 'convo_memberlevels') : '#e9ebe9';
$elite = elgg_get_plugin_setting('elite', 'convo_memberlevels') ? elgg_get_plugin_setting('elite', 'convo_memberlevels') : '#10c637';
$bronzepoint = elgg_get_plugin_setting('silver_limit', 'convo_memberlevels') ? elgg_get_plugin_setting('silver_limit', 'convo_memberlevels') : 20;
$silverpoint = elgg_get_plugin_setting('gold_limit', 'convo_memberlevels') ? elgg_get_plugin_setting('gold_limit', 'convo_memberlevels') : 40;
$goldpoint = elgg_get_plugin_setting('platinum_limit', 'convo_memberlevels') ? elgg_get_plugin_setting('platinum_limit', 'convo_memberlevels') : 60;
$platinumpoint = elgg_get_plugin_setting('elite_limit', 'convo_memberlevels') ? elgg_get_plugin_setting('elite_limit', 'convo_memberlevels') : 80;


//calculate the membervalues
$memberpercent = round($memberscore * 20); // creates the memberscore as a %

//find medal
if($memberpercent < $bronzepoint){
	$image = "bronze.png";
	$complete = $bronze;
	$level = elgg_echo('convo_memberlevels:color:bronze');
}
elseif($memberpercent < $silverpoint){
	$image = "silver.png";
	$complete = $silver;
	$level = elgg_echo('convo_memberlevels:color:silver');
}
elseif($memberpercent < $goldpoint){
	$image = "gold.png";
	$complete = $gold;
	$level = elgg_echo('convo_memberlevels:color:gold');
}
elseif($memberpercent < $platinumpoint){
	$image = "platinum.png";
	$complete = $platinum;
	$level = elgg_echo('convo_memberlevels:color:platinum');
}
else{
	$image = "elite.png";
	$complete = $elite;
	$level = elgg_echo('convo_memberlevels:color:elite');
}



// so now our $scorearray only has colors left that we haven't fully completed
//$colors = array($complete, "'".$background."'");

$weights = array($memberpercent, (100 - $memberpercent));


$membervalues = "var membervalues = [" . implode(",", $weights) . "];";

$site_url = elgg_get_site_url();
$medal = "<img src=\"{$site_url}mod/convo_memberlevels/graphics/$image\" class=\"convo_memberlevels_medal\">";


$js = <<<JS

<script>

$attendancevalues
$convovalues
$membervalues

$(document).ready( function(){
	$('#convo_memberlevels_attendancescore').sparkline(attendance, {type: 'pie', sliceColors: ['$complete','$background'], width: '50px', height: '50px'} );
	$('#convo_memberlevels_convoscore').sparkline(convovalues, {type: 'pie', sliceColors: ['$complete','$background'], width: '50px', height: '50px'} );
	$('#convo_memberlevels_memberscore').sparkline(membervalues, {type: 'pie', sliceColors: ['$complete','$background'], width: '50px', height: '50px'} );
	$('#convo_memberlevels_memberscore, #convo_memberlevels_attendancescore, #convo_memberlevels_convoscore').mouseenter( function(){
		$('.convo_memberlevels_legend').toggle();
	});
	$('#convo_memberlevels_memberscore, #convo_memberlevels_attendancescore, #convo_memberlevels_convoscore').mouseleave( function(){
		$('.convo_memberlevels_legend').toggle();
	});
});
</script>
JS;

echo $js;

echo "<div class=\"convo_memberlevels_report\">";
echo "<h4>" . elgg_echo('convo_memberlevels:monthly:stats') . "</h4>";
echo "<div class=\"convo_memberlevels_piewrapper\">";
echo elgg_echo('convo_memberlevels:login:frequency') . "<br>";
echo "<span id=\"convo_memberlevels_attendancescore\">Loading...</span><br>" . " " . $loginscore . "%";
echo "</div>";
echo "<div class=\"convo_memberlevels_piewrapper\">";
echo elgg_echo('convo_memberlevels:avg:convo:rating') . "<br>";
echo "<span id=\"convo_memberlevels_convoscore\">Loading...</span><br>" . " " . $convoscore . "/5";
echo "</div>";

if ($memberscore > 0) {
  echo "<h4>" . elgg_echo('convo_memberlevels:membership:level', array($level)) . "</h4>";
}

echo "<div style=\"text-align: center;\">";

if ($memberscore > 0) {
  echo "<span id=\"convo_memberlevels_memberscore\">Loading...</span>" . " " . $memberpercent . "% =" . " " . $medal;
}

echo "</div>";

if ($memberscore > 0) {
  // only show ranking if they've logged in at least once
  echo "<div class=\"convo_memberlevels_legend\">";
  echo "<div class=\"convo_memberlevels_legendblock\" style=\"background-color: $bronze;\"></div>";
  echo elgg_echo('convo_memberlevels:bronze') . "<br>";
  echo "<div class=\"convo_memberlevels_legendblock\" style=\"background-color: $silver;\"></div>";
  echo elgg_echo('convo_memberlevels:silver') . "<br>";
  echo "<div class=\"convo_memberlevels_legendblock\" style=\"background-color: $gold;\"></div>";
  echo elgg_echo('convo_memberlevels:gold') . "<br>";
  echo "<div class=\"convo_memberlevels_legendblock\" style=\"background-color: $platinum;\"></div>";
  echo elgg_echo('convo_memberlevels:platinum') . "<br>";
  echo "<div class=\"convo_memberlevels_legendblock\" style=\"background-color: $elite;\"></div>";
  echo elgg_echo('convo_memberlevels:elite') . "<br>";
  echo "</div>"; // legend
  echo "</div>";//report
}
echo "<br style=\"clear: both;\">";

} // admin or public

/*
 * Debugging
 */
if (elgg_is_admin_logged_in()) {
  /*
    $silverlimit = round(($bronzepoint / 20), 2);
    $goldlimit = round(($silverpoint / 20), 2);
    $platinumlimit = round(($goldpoint / 20), 2);
    $elitelimit = round(($platinumpoint / 20), 2);
  
    echo "raw memberscore = {$memberscore}";
    
    echo "<br>silver limit = {$silverlimit}";
    echo "<br>gold limit = {$goldlimit}";
    echo "<br>platinum limit = {$platinumlimit}";
    echo "<br>elite limit = {$elitelimit}";
  */  
 // echo "history = <pre>" . print_r($history,1) . "</pre>";
}
