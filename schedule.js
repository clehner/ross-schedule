var daysOfWeek = ["Sunday", "Monday", "Tuesday",
	"Wednesday", "Thursday", "Friday", "Saturday"],
	periods = []/*[
		["Breakfast",45000, 47100],
		["Period 1", 47400, 50100],
		["Period 2", 50400, 53100],
		["Period 3", 53400, 56100],
		["Period 4", 56400, 59100],
		["Period 5", 59400, 62100],
		["Period 6", 62400, 65100],
		["Period 7", 65400, 68100],
		["Period 8", 68400, 71100],
		["Period 9", 71400, 74100],
		["Period 10",74400, 77100] 
	]*/,
	period = 0,
	numPeriods = periods.length,
	periodRows,
	periodCells,
	theadHeight,
	lastMinute,
	spacingFix,
	firstRowOffset, oldFirstRowOffset,
	dst = getDST();

function $(id) {
	return document.getElementById(id);
}

function loadSchedule() {
	var script = document.createElement("script");
	script.src = "http://localhost/schedule/today.php?" +
		"c=receiveSchedule&" + Math.random();
	var head = document.documentElement.firstChild;
	head.insertBefore(script, head.firstChild);
}

function receiveSchedule(schedule) {
	if (schedule && schedule.periods) {
		periods = schedule.periods;
		//console.log('got schedule');
		renderTable();
		updateTime();
	} else {
		//console.log('getting schedule failed');
		// Loading schedule failed.
	}
}

function padTime(i) {
	return i < 10 ? "0" + i : i;
}

function formatSeconds(secs) {
	var mins = (secs / 60) % 60;
	var hours = (Math.floor(secs / 3600) - 5) % 12 || 12;
	return hours + ":" + padTime(mins);
}

function renderTable() {
	var tbody = $("tbody");
	tbody.innerHTML = "";
	for (var i = 0; i < periods.length; i++) {
		var period = periods[i];
		var tr = document.createElement("tr");
		var titleTd = document.createElement("td");
		var timeTd = document.createElement("td");
		titleTd.appendChild(document.createTextNode(period[0]));
		var timeString = formatSeconds(period[1]) +
			"-" + formatSeconds(period[2]);
		timeTd.appendChild(document.createTextNode(timeString));
		tr.appendChild(titleTd);
		tr.appendChild(timeTd);
		tbody.appendChild(tr);
	}
	periodRows = tbody.getElementsByTagName("tr");
	periodCells = tbody.getElementsByTagName("td");
	theadHeight = $("thead").getElementsByTagName("tr")[0].clientHeight;
	
	var firstRow = periodRows[0] || {};
	spacingFix = (typeof(firstRow.clientTop) != "undefined" ||
		firstRow.clientHeight == 0) ? 1 : 0;
}

// get the seconds past midnight
function daySeconds(d) {
	var t = +d + (dst ? 3600000 : 0);
	return Math.floor((t / 1000) % 86400);
}

function updateTime() {
	var d = new Date();
	//d.setTime(d.getTime()-3600000*8 - 360000);
	var hour = d.getHours();
	var timeOfDay = hour > 12 ? "PM" : "AM";
	hour = (hour % 12) || "12";
	minute = d.getMinutes();
	$("time").innerHTML =
		[hour, padTime(minute), padTime(d.getSeconds())].join(":") +
		" " + timeOfDay;
	firstRowOffset = periodRows[0] ? periodRows[0].offsetTop : 0;
	if (minute != lastMinute || firstRowOffset != oldFirstRowOffset) {
		$("date").innerHTML = daysOfWeek[(today = d.getDay())] + ", " +
			(d.getMonth() + 1) + "/" + d.getDate() + "/" + d.getFullYear();
		if (d.getDay() % 6) { // must be a school day (not a weekend) {
			var secs = daySeconds(d);
			for (var i = period; i < periods.length; i++) {
				if (secs < periods[0][1] || secs > periods[numPeriods-1][2]) {
				$("time-marker").style.display = "none";
				if ($("current-period"))
					$("current-period").id = "";
				} else {
					$("time-marker").style.display = "block";
					if (secs < periods[i][2]) {
						if (secs >= periods[i][1]) {
						if ($("current-period")) $("current-period").id = "";
						periodRows[i].id = "current-period";
						period = i;
						$("time-marker").style.top = Math.round(periodRows[period].firstChild.offsetTop + spacingFix + (periodRows[period].firstChild.clientHeight) * (secs - periods[period][1]) / (periods[period][2] - periods[period][1])) + "px";
						break;
						} else if (i)
						if (secs >= periods[i-1][2]) {
							if ($("current-period")) $("current-period").id = "";
							period = i;
							$("time-marker").style.top = periodRows[period].firstChild.offsetTop + spacingFix + "px";
							break;
						}
					}
				}
			}
		}
		lastMinute = minute;
		oldFirstRowOffset = firstRowOffset;
	}
}

// from http://www.csgnetwork.com/timezoneproginfo.html
function getDST() {
	var rightNow = new Date();
	var date1 = new Date(rightNow.getFullYear(), 0, 1, 0, 0, 0, 0);
	var date2 = new Date(rightNow.getFullYear(), 6, 1, 0, 0, 0, 0);
	var temp = date1.toGMTString();
	var date3 = new Date(temp.substring(0, temp.lastIndexOf(" ")));
	var temp = date2.toGMTString();
	var date4 = new Date(temp.substring(0, temp.lastIndexOf(" ")));
	var hoursDiffStdTime = (date1 - date3) / (1000 * 60 * 60);
	var hoursDiffDaylightTime = (date2 - date4) / (1000 * 60 * 60);
	//"Time zone is GMT " + hoursDiffStdTime
	var dst = (hoursDiffDaylightTime != hoursDiffStdTime);
	return dst;
}

function checkManifest() {
	if (window.applicationCache) {
		// Check if a new cache is available on page load.
		window.applicationCache.addEventListener("updateready", function (e) {
			if (window.applicationCache.status == window.applicationCache.UPDATEREADY) {
				// Browser downloaded a new app cache. Refresh the page.
				window.applicationCache.swapCache();
				location.reload();
			}
		}, false);
	}
}
checkManifest();

var lastDay;
function checkIfNeedNewSchedule() {
	var today = new Date().getDate();
	if (lastDay != today) {
		lastDay = today;
		// need to load a new schedule.
		loadSchedule();
	} else {
		// load at the beginning of the first period
		var secsTillLoad = periods[0][1] - daySeconds(new Date());
		setTimeout(checkIfNeedNewSchedule, secsTillLoad * 1000);
	}
}

var started;
function initSchedule() {
	if (started) return;
	started = true;
	renderTable();
	updateTime();
	setInterval(updateTime, 1000);
	checkIfNeedNewSchedule();
}
