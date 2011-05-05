var daysOfWeek = ["Sunday", "Monday", "Tuesday",
	"Wednesday", "Thursday", "Friday", "Saturday"],
	periods = [
	  [45000,47100],	//  7 *12+ 30,  8 *12+ 05
	  [47400,50100],	//  8 *12+ 10,  8 *12+ 55
	  [50400,53100],	//  9 *12+ 00,  9 *12+ 45
	  [53400,56100],	//  9 *12+ 50, 10 *12+ 35
	  [56400,59100],	// 10 *12+ 40, 11 *12+ 25
	  [59400,62100],	// 11 *12+ 30, 12 *12+ 15
	  [62400,65100],	// 12 *12+ 20, 13 *12+ 05
	  [65400,68100],	// 13 *12+ 10, 13 *12+ 55
	  [68400,71100],	// 14 *12+ 00, 14 *12+ 45
	  [71400,74100],	// 14 *12+ 50, 15 *12+ 35
	  [74400,77100] 	// 15 *12+ 40, 16 *12+ 25
	],
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
	firstRowOffset = periodRows[0].offsetTop;
	if (minute != lastMinute || firstRowOffset != oldFirstRowOffset) {
		$("date").innerHTML = daysOfWeek[(today = d.getDay())] + ", " +
			(d.getMonth() + 1) + "/" + d.getDate() + "/" + d.getFullYear();
		var curtime = d.getTime() + (dst ? 3600000 : 0);
		if (d.getDay() % 6) { // must be a school day (not a weekend) {
			secs = Math.floor((curtime/1000) % 86400);
			for (var i = period; i < periods.length; i++) {
				if (secs < periods[0][0] || secs > periods[numPeriods-1][1]) {
				$("time-marker").style.display = "none";
				if ($("current-period"))
					$("current-period").id = "";
				} else {
					$("time-marker").style.display = "block";
					if (secs < periods[i][1]) {
						if (secs >= periods[i][0]) {
						if ($("current-period")) $("current-period").id = "";
						periodRows[i].id = "current-period";
						period = i;
						$("time-marker").style.top = Math.round(periodRows[period].firstChild.offsetTop + spacingFix + (periodRows[period].firstChild.clientHeight) * (secs - periods[period][0]) / (periods[period][1] - periods[period][0])) + "px";
						break;
						} else if (i)
						if (secs >= periods[i-1][1]) {
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

function padTime(i) {
	return i < 10 ? "0" + i : i;
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
		window.applicationCache.addEventListener('updateready', function (e) {
			if (window.applicationCache.status == window.applicationCache.UPDATEREADY) {
				// Browser downloaded a new app cache. Refresh the page.
				window.applicationCache.swapCache();
				location.reload();
			}
		}, false);
	}
}

var started;
function initSchedule() {
	if (started) return;
	started = true;
	periodRows = $("tbody").getElementsByTagName("tr");
	periodCells = $("tbody").getElementsByTagName("td");
	theadHeight = $("thead").getElementsByTagName("tr")[0].clientHeight;
	spacingFix = (typeof(periodRows[0].clientTop) != "undefined" ||
		periodRows[0].clientHeight == 0) ? 1 : 0;
	setInterval(updateTime, 1000);
	updateTime();
	checkManifest();
}
