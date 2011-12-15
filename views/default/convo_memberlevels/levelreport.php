<?php
convo_memberlevels_calculate_level($vars['entity']);

$access = get_plugin_setting('access', 'convo_memberlevels');

if($access == 'public' || isadminloggedin()){

$month = date("m");
$year = date("Y");

$loginhistoryfield = 'convo_memberlevels-history-' . $month . '-' . $year; // array of login days
$loginbonusfield = 'convo_memberlevels-loginbonus-' . $month . '-' . $year; // numerical bonus 0-1
$loginscorefield = 'convo_memberlevels-loginscore-' . $month . '-' . $year; // % of days logged in
$convoscorefield = 'convo_memberlevels-convoscore-' . $month . '-' . $year; // avg convo rating 0-5
$memberscorefield = 'convo_memberlevels-memberscore-' . $month . '-' . $year; // monthly score, 0-5

$history = unserialize($vars['entity']->$loginhistoryfield);
$loginbonus = $vars['entity']->$loginbonusfield;
$loginscore = $vars['entity']->$loginscorefield;
$convoscore = $vars['entity']->$convoscorefield;
$memberscore = $vars['entity']->$memberscorefield;

$attendancevalues = "var attendance = [" . round($loginscore) . "," . (100 - round($loginscore)) . "];";
$convovalues = "var convovalues = [" . ($convoscore * 100) . "," . (500 - ($convoscore * 100)) . "];";


$background = get_plugin_setting('background', 'convo_memberlevels') ? get_plugin_setting('background', 'convo_memberlevels') : '#ffffff';
$bronze = get_plugin_setting('bronze', 'convo_memberlevels') ? get_plugin_setting('bronze', 'convo_memberlevels') : '#d26439';
$silver = get_plugin_setting('silver', 'convo_memberlevels') ? get_plugin_setting('silver', 'convo_memberlevels') : '#969696';
$gold = get_plugin_setting('gold', 'convo_memberlevels') ? get_plugin_setting('gold', 'convo_memberlevels') : '#ccad13';
$platinum = get_plugin_setting('platinum', 'convo_memberlevels') ? get_plugin_setting('platinum', 'convo_memberlevels') : '#e9ebe9';
$elite = get_plugin_setting('elite', 'convo_memberlevels') ? get_plugin_setting('elite', 'convo_memberlevels') : '#10c637';
$complete = get_plugin_setting('complete', 'convo_memberlevels') ? get_plugin_setting('complete', 'convo_memberlevels') : '#ff0000';


$scorearray = array(
	"'" . $bronze . "'",
	"'" . $silver . "'",
	"'" . $gold . "'",
	"'" . $platinum . "'",
	"'" . $elite . "'",
);

//calculate the membervalues
$memberpercent = round($memberscore * 20); // creates the memberscore as a %


$offset = floor($memberscore);
$remainder = $memberpercent % 20;

foreach($scorearray as $key => $value){
	if($key < $offset){
		unset($scorearray[$key]);
	}
}

$scorearray = array_values($scorearray);

$complete = $scorearray[0];

// so now our $scorearray only has colors left that we haven't fully completed
$colors = array($complete, "'".$background."'");

$weights = array($memberpercent, (100 - $memberpercent));

// set a parallel array with the weights
/*
$weights = array();
for($i=0; $i<count($colors); $i++){
	switch($i){
		case 0:
			$weights[] = $memberpercent;
			break;
		case 1:
			$weights[] = 20 - $remainder;
			break;
		default:
			$weights[] = 20;
			break;
	}
}
*/

$membervalues = "var membervalues = [" . implode(",", $weights) . "];";

$membercolors = "[" . implode(",", $colors) . "]";

//find medal
if($memberpercent < 20){
	$image = "bronze.png";
}
elseif($memberpercent < 40){
	$image = "silver.png";
}
elseif($memberpercent < 60){
	$image = "gold.png";
}
elseif($memberpercent < 80){
	$image = "platinum.png";
}
else{
	$image = "elite.png";
}

$medal = "<img src=\"{$vars['url']}mod/convo_memberlevels/graphics/$image\" class=\"convo_memberlevels_medal\">";


$js = <<<JS

<script>

$attendancevalues
$convovalues
$membervalues

$(document).ready( function(){
	$('#convo_memberlevels_attendancescore').sparkline(attendance, {type: 'pie', sliceColors: [$complete,'$background'], width: '50px', height: '50px'} );
	$('#convo_memberlevels_convoscore').sparkline(convovalues, {type: 'pie', sliceColors: [$complete,'$background'], width: '50px', height: '50px'} );
	$('#convo_memberlevels_memberscore').sparkline(membervalues, {type: 'pie', sliceColors: $membercolors, width: '50px', height: '50px'} );
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
echo "<h4>" . elgg_echo('convo_memberlevels:membership:level') . "</h4>";
echo "<div style=\"text-align: center;\">";
echo "<span id=\"convo_memberlevels_memberscore\">Loading...</span>" . " " . $memberpercent . "% =" . " " . $medal;
echo "</div>";
echo "<div class=\"convo_memberlevels_legend\">";
echo "<div class=\"convo_memberlevels_legendblock\" style=\"background-color: $complete;\"></div>";
echo elgg_echo('convo_memberlevels:your:score') . "<br>";
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
echo "<br style=\"clear: both;\">";

} // admin or public