<?php 
if(get_context() == "profile"){
?>

<script type="text/javascript" src="<?php echo $vars['url']; ?>mod/convo_memberlevels/lib/jquery.sparkline.min.js"></script>

<?php 
}

if(strpos($_SERVER['REQUEST_URI'], "pluginsettings/admin/convo_memberlevels")){
?>

<script type="text/javascript" src="<?php echo $vars['url']; ?>mod/convo_memberlevels/lib/jquery.sparkline.min.js"></script>
<script type="text/javascript" src="<?php echo $vars['url']; ?>mod/convo_memberlevels/lib/farbtastic/farbtastic.js"></script>
<link rel="stylesheet" href="<?php echo $vars['url'] . "mod/convo_memberlevels/lib/farbtastic/farbtastic.css"; ?>" type="text/css" />

<?php
}
