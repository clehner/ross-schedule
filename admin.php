<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Schedule Management</title>
<style type="text/css">
body {
	font:13px Arial, sans-serif;
	line-height: 1.2em;
}
#heading {
	background-color: #00447c;
	margin: -8px -8px 1em;
	padding: .5em;
}
#school-link {
	float: right;
	margin: .5em;
}
#heading img {
	height: 25px;
}
#heading h1 {
	margin: 0;
	padding: .75em .25em .25em;
	font-family: Georgia, serif;
	font-weight: normal;
}
#heading h1 a {
	color: white;
	text-decoration: none;
}

#logout {
	text-align: right;
}

.error {
	color: #b00;
}
.msg {
	color: #09c;
}
small {
	color: #888;
	font-size: .9em;
}

#edit-table input {
	width: 3em;
}
#edit-table input.name {
	width: 6em;
}
.add-link, .remove-link {
	color: #00f;
	font-size: 1.5em;
	text-decoration: none;
	vertical-align: -20%;
}
</style>
</head>
<body>
<div id="heading">
	<a id="school-link" href="http://www.ross.org/"><img src="school-logo.png" alt="Ross School"></a>
	<h1><a href="">Schedule Management</a></h1>
</div>
	<?php
$thepassword = '*******';

$salt = 'q30984y7nvw347';
$logintimeout = 3600 * 12; // 12 hours
$cookiename = 'rossscheduleadminlogin';
$loggedin = false;

date_default_timezone_set('America/New_York');

function error($msg) {
	print "<p class=\"error\">" . htmlentities($msg) . "</p>";
}

function msg($msg) {
	print "<p class=\"msg\">" . htmlentities($msg) . "</p>";
}

function refresh($extra='') {
	header('Location: ' . $_SERVER["SCRIPT_NAME"].$extra);
}

function setlogin() {
	global $loggedin, $thepassword, $salt, $cookiename, $logintimeout;
	$date = time();
	$hash = md5($date . $salt . $thepassword);
	$cookie = implode(',', array($date, $hash));
	setcookie($cookiename, $cookie, $date + $logintimeout);
	$loggedin = true;
}

function logout() {
	global $cookiename;
	setcookie($cookiename, "", 1);
}

if (isset($_GET['logout'])) {
	// logout
	logout();
	refresh();
	
} elseif ($pass = @$_POST['password']) {
	// login
	if ($pass == $thepassword) {
		setlogin();
	} else {
		error('Incorrect password.');
	}

} elseif ($logincookie = @$_COOKIE[$cookiename]) {
	// check login cookie
	list($date, $hash) = explode(',', $logincookie, 2);
	if ($hash == md5($date . $salt . $thepassword)) {
		if ((time() - $date) < $logintimeout) {
			// renew cookie
			setlogin();
		} else {
			// delete bad cookie
			logout();
		}
	}
}

if (!$loggedin) { ?>
	<form action="" method="post">
		<p>Password: <input name="password" type="password"></p>
		<p><button type="submit">Login</button></p>
	</form>
<?php } else { ?>
<div id="logout"><a href="?logout">Logout</a></div>
<hr>
<form action="" method="post">
<h2>Schedules</h2>
<?php

// get list of schedules
function get_schedules() {
	global $schedule_names;
	$schedule_names = array();
	if ($handle = opendir('schedules')) {
		while (false !== ($file = readdir($handle))) {
			if (strrpos($file, ".json") == strlen($file) - 5) {
			   $schedule_names[] = substr($file, 0, -5);
			}
		}
		closedir($handle);
	}
}
get_schedules();

function schedule_name_to_filename($name) {
	return "schedules/$name.json";
}

function schedule_filename_to_name($filename) {
	return str_replace(array("schedules/", ".json"), "", $filename);
}

// get default schedule
$default_schedule_link_location = 'schedules/default';
$default_schedule_name = schedule_filename_to_name(
	@readlink($default_schedule_link_location));
if (!$default_schedule_name) {
	$default_schedule_name = schedule_name_to_filename($schedule_names[0]);
	symlink($default_schedule_name, $default_schedule_link_location) 
		or die('Couldn\'t make symbolic link to default schedule.');
}
function print_schedule_picker($name, $defaultfile="") {
	global $schedule_names;
	print "<select name=\"$name\">\n";
	foreach ($schedule_names as $name) {
		//$title = ucfirst($name);
		$title = $name;
		$selected = ($name == $defaultfile) ? " selected=\"selected\"" : "";
		print "\t<option value=\"$name\"$selected>$title</option>\n";
	}
	print "</select>\n";
}

function print_action_button($action, $extra="") {
	if ($extra) $extra = " " . $extra;
	print "<input type=\"submit\" name=\"action\" value=\"$action\"$extra>";
}

// make the form and stuff

$schedule_name = @$_POST['schedule'];
$action = @$_POST['action'];

if (@$_POST['cancel'] == "Cancel") {
	$action = '';
}

if ($schedule_name and $action) {
	$filename = schedule_name_to_filename($schedule_name);
	switch ($action) {
	case 'View':
		//refresh('?view_schedule=' . urlencode($schedule_name));
	break;
	case 'Edit':
		// below
	break;
	case 'Delete':
		if (@unlink($filename)) {
			msg("$schedule_name was deleted successfully.");
			get_schedules();
			foreach($schedule_names as $key => $value) {
				if ($value == $schedule_name) {
					unset($schedule_names[$key]);
				}
			}
		} else {
			error("Unable to delete $schedule_name.");
		}
	break;
	case 'Duplicate':
		$new_schedule_name = @$_POST['new_schedule_name'];
		if (!$new_schedule_name) {
			error('New schedule name was blank.');
		} else {
			//$new_schedule_name = strtolower($new_schedule_name);
			$dest_filename = schedule_name_to_filename($new_schedule_name);
			if (!copy($filename, $dest_filename)) {
				error('Unable to duplicate schedule.');
			} else {
				$schedule_name = $new_schedule_name;
				get_schedules();
				msg("$schedule_name was created successfully.");
			}
		}
	}
	// avoid form resubmission
	//refresh();
}

print "<p>";
if (!$schedule_name) $schedule_name = $default_schedule_name;
print_schedule_picker("schedule", $schedule_name);
print_action_button("View");
print_action_button("Edit");
print_action_button("Delete");
print_action_button("Duplicate", 'id="duplicate_schedule_btn"');
?>
<input type="hidden" name="new_schedule_name" id="new_schedule_name">
</p>
<script>
document.getElementById("duplicate_schedule_btn").onclick = function (e) {
	var name = prompt("Please title the new schedule:");
	if (!name) e.preventDefault();
	document.getElementById("new_schedule_name").value = name;
}
</script>
</form>
<?php
function pad_time($i) {
	return $i < 10 ? "0$i" : $i;
}
function format_seconds($secs) {
	$mins = ($secs / 60) % 60;
	$hours = (floor($secs / 3600) - 5) % 12;
	if ($hours == 0) $hours = 12;
	return $hours . ':' . pad_time($mins);
}
function time_to_seconds($time) {
	@list($hours, $mins) = explode(':', $time);
	// before 6 is PM
	if ($hours < 6) $hours += 12;
	return ((($hours + 5) * 60) + $mins) * 60;
}

if ($action == "View") {
	print "<h4>$schedule_name</h4>\n";
	print "<table>\n";
	print "<tr><th>Period</th><th>Start</th><th>End</th></tr>\n";
	$filename = schedule_name_to_filename($schedule_name);
	$schedule = json_decode(file_get_contents($filename));
	foreach ($schedule->periods as $period_info) {
		print "<tr><td>".$period_info[0]."</td>"
			. "<td>".format_seconds($period_info[1])."</td>"
			. "<td>".format_seconds($period_info[2])."</td>"
			. "</tr>\n";
	}
	print "</table>\n";
}

$saved_success = null;
if ($action == "save-edit") {
	$schedule = json_decode(file_get_contents($filename));
	$periods = array();
	foreach ($_POST['period']['name'] as $i => $name) {
		$periods[] = array(
			$name,
			time_to_seconds($_POST['period']['start'][$i]),
			time_to_seconds($_POST['period']['end'][$i])
		);
	}
	$schedule->periods = $periods;
	$saved_success =
		file_put_contents($filename, json_encode($schedule)) != false;
	// show form again.
	$action = "Edit";
}

if ($action == "Edit") { ?>
<hr>
<form action="" method="post">
<h4>Edit: <?php print $schedule_name; ?></h4>
<input type="hidden" name="action" value="save-edit">
<table id="edit-table">
<tr><th>Period</th><th>Start</th><th>End</th><td></td></tr>
<?php
	$filename = schedule_name_to_filename($schedule_name);
	$schedule = json_decode(file_get_contents($filename));
	foreach ($schedule->periods as $period_info) {
		$name = addcslashes($period_info[0], '"\\');
		$start = format_seconds($period_info[1]);
		$end = format_seconds($period_info[2]);
		print '<tr><td><input name="period[name][]" class="name" value="'.$name.'"></td>'
			. '<td><input name="period[start][]" value="'.$start.'"></td>'
			. '<td><input name="period[end][]" value="'.$end.'"></td>'
			. '<td><a href="" title="Remove this period" class="remove-link">&times;</a> <a href="" class="add-link" title="Add a period after this">+</a></td>'
			. "</tr>\n";
	}
	?></table>
	<input type="hidden" name="action" value="save-edit">
	<input type="hidden" name="schedule" value="<?php print $schedule_name; ?>">
	<input type="submit" value="Save">
	<input type="submit" name="cancel" value="Cancel">
	<?php
	if ($saved_success == true) {
		msg('Schedule was saved successfully.');
	} else if ($saved_success === false) {
		error('Unable to save schedule data.');
	}
	?>
</form>
<script>
document.getElementById("edit-table").onclick = function (e) {
	if (e.target.nodeName != "A") return;
	var link = e.target;
	var tr = link.parentNode.parentNode;
	if (link.className == "add-link") {
		e.preventDefault();
		// add a new period
		var newTr = tr.cloneNode(true);
		tr.parentNode.insertBefore(newTr, tr.nextSibling);
		[].forEach.call(newTr.getElementsByTagName("input"), function (input) {
			input.value = "";
		});
	}
	if (link.className == "remove-link") {
		e.preventDefault();
		// remove a period
		tr.parentNode.removeChild(tr);
	}
};
</script>
<?php
}

?>
<hr>
<form action="" method="post">
<h4>Default schedule</h4>
<p><?php

// set default schedule
if ($new_default_schedule_name = @$_POST['default_schedule']) {
	if ($default_schedule_name != $new_default_schedule_name) {
		if (!unlink($default_schedule_link_location)) {
			error('Couldn\'t remove the old default schedule.');
		} else {
			$new_default_filename = str_replace("schedules/", "",
				schedule_name_to_filename($new_default_schedule_name));
			if (!symlink($new_default_filename,
					$default_schedule_link_location)) {
				error('Couldn\'t link the new default schedule.');
			} else {
				$default_schedule_name = $new_default_schedule_name;
				msg('Default schedule was changed successfully.');
			}
		}
	}
}

print_schedule_picker("default_schedule", $default_schedule_name);
print_action_button("Set");
?>
</p>
</form>
<hr>
<form action="" method="post">
<h4>Post a future schedule</h4>
<p>
<small>YYYY-MM-DD</small><br>
<?php
print '<input name="date" value="' . @$_POST["date"] . '" size="12">';
print_schedule_picker("new_add_schedule", '');
print_action_button("Post");
print "</p>\n";

// load the future schedules list
$future_schedules_file = 'schedules/future';
$future_schedules = json_decode(file_get_contents($future_schedules_file), 1);
if ($future_schedules === null) {
	error("There was a problem loading the future schedules.");
	$future_schedules = array();
}

// remove past schedules
$present = time();
foreach ($future_schedules as $date => $schedule_name) {
	if (strtotime($date) < $present) {
		unset($future_schedules[$date]);
	}
}

// Posting a future schedule
if ($schedule_to_add = @$_POST["new_add_schedule"]) {
	$date = @$_POST["date"];
	$time = strtotime($date);
	if (!$time) {
		error("Could not understand that date.");
	} else {
		$date = date('Y-m-d', $time);
		if ($time < $present) {
			error("Schedule date must be in the future.");
		} else {
			$future_schedules[$date] = $schedule_to_add;
			file_put_contents($future_schedules_file,
				json_encode($future_schedules));
			msg("Schedule posted successfully.");
		}
	}
}

?>
</form>
<h4>Future schedules</h4>
<form action="" method="post">
<input type="hidden" name="action" value="remove-future">
<?php

// Delete a schedule.
if ($delete_date = @$_POST["schedule_date_to_delete"]) {
	if ($future_schedules[$delete_date]) {
		unset($future_schedules[$delete_date]);
		file_put_contents($future_schedules_file,
			json_encode($future_schedules));
		msg("Schedule on $delete_date deleted successfully.");
	}
}

if (!$future_schedules) {
	print "No upcoming special schedules have been posted.";
}

print "<ul id=\"future-schedules\">\n";
foreach ($future_schedules as $date => $schedule_name) {
	$time = strtotime($date);
	if ($time < $present) continue;
	print "<li>" .
		"<strong>$date</strong>: $schedule_name " .
		"<button type=\"submit\" name=\"schedule_date_to_delete\" value=\"$date\">&times;</button>" .
		"</li>\n";
}
?>
</ul>
<script>
document.getElementById("future-schedules").onclick = function (e) {
	if (e.target.name == "schedule_date_to_delete") {
		return confirm("Really delete this future schedule?\n(It will be replaced with the default schedule.)");
	}
};
</script>
</form>
<?php } ?>
</body>
</html>