<?php
date_default_timezone_set('America/New_York');
$today = date('Y-m-d');
$future_schedules = json_decode(file_get_contents('schedules/future'));
$schedule_name = @$future_schedules->$today;
$filename = $schedule_name ? "$schedule_name.json" : "default";
//print $filename;
$schedule = file_get_contents("schedules/$filename");
header("Content-type: text/javascript");
if (isset($_GET['c'])) {
	$callback = preg_replace('/[^a-zA-Z0-9.]/', '', $_GET['c']);
	print $callback . '(' . $schedule . ')';
} else {
	print $schedule;
}
