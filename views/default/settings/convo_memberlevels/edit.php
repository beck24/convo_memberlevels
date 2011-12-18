<?php

	echo "<br><br><label>" . elgg_echo('convo_memberlevels:access:description') . "</label>  ";

	$params = array(
		'internalname' => 'params[access]',
		'value' => !empty($vars['entity']->access) ? $vars['entity']->access : 'admin',
		'options_values' => array(
			'public' => 'Public',
			'admin' => 'Admin Only',
		),
	);
	
	echo elgg_view('input/pulldown', $params) . '<br><br>';

echo "<label>" . elgg_echo('convo_memberlevels:limit:description') . "</label><br>";
echo elgg_echo('convo_memberlevels:color:bronze') . " > 0<br>";
echo elgg_echo('convo_memberlevels:color:silver') . " > ";
echo elgg_view('input/text', array('internalname' => "params[silver_limit]", 'value' => $vars['entity']->silver_limit ? $vars['entity']->silver_limit : 20, 'js' => 'style="width: 40px"')) . "<br>";

echo elgg_echo('convo_memberlevels:color:gold') . " > ";
echo elgg_view('input/text', array('internalname' => "params[gold_limit]", 'value' => $vars['entity']->gold_limit ? $vars['entity']->gold_limit : 40, 'js' => 'style="width: 40px"')) . "<br>";

echo elgg_echo('convo_memberlevels:color:platinum') . " > ";
echo elgg_view('input/text', array('internalname' => "params[platinum_limit]", 'value' => $vars['entity']->platinum_limit ? $vars['entity']->platinum_limit : 60, 'js' => 'style="width: 40px"')) . "<br>";

echo elgg_echo('convo_memberlevels:color:elite') . " > ";
echo elgg_view('input/text', array('internalname' => "params[elite_limit]", 'value' => $vars['entity']->elite_limit ? $vars['entity']->elite_limit : 80, 'js' => 'style="width: 40px"')) . "<br>";

echo "<br><br>";

$array = array(
	'background' => '#000000',
	'bronze' => '#d26439',
	'silver' => '#969696',
	'gold' => '#ccad13',
	'platinum' => '#e9ebe9',
	'elite' => '#10c637',
);

foreach($array as $key => $value){
?>
<div class="convo_memberlevels_colorpicker_wrapper">
<div id="convo_memberlevels_<?php echo $key; ?>_color"></div>
<label><?php echo elgg_echo('convo_memberlevels:color:'.$key); ?></label><br>
<?php echo elgg_view('input/text', array('internalname' => 'params['.$key.']', 'internalid' => 'convo_memberlevels_'.$key, 'value' => $vars['entity']->$key ? $vars['entity']->$key : $value, 'class' => 'convo_memberlevels_colorpicker')); ?>
<?php echo "<br>" . elgg_echo("Default") . ": " . $value; ?>
</div>
<?php 
}
?>

<script type="text/javascript">
  $(document).ready(function() {
<?php
	foreach($array as $key => $value){
?> 
    $('#convo_memberlevels_<?php echo $key; ?>_color').farbtastic('#convo_memberlevels_<?php echo $key; ?>');
    
<?php
	}
?>
  });
</script>