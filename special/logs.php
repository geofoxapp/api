<html>
<head>
<title>GEOfox Log Browser</title>
</head>
<body>

<?php

if ( isset($_REQUEST["term"])) $term = $_REQUEST["term"];
else $term = "";
if ( isset($_REQUEST["file"])) $file = $_REQUEST["file"];
else $file = "stable";
if ( isset($_REQUEST["day"])) $day = $_REQUEST["day"];
else $day = "today";

?>

<form action="logs.php" method="post">
	<select id="file" name="file">
		<option value="stable" <?php if($file=="stable") echo "selected='selected'"; ?>>Stable API</option>
		<option value="test" <?php if($file=="test") echo "selected='selected'"; ?>>Test API</option>
	</select>
	<select id="day" name="day">
		<option value="today" <?php if($day=="today") echo "selected='selected'"; ?>>Today</option>
		<option value="yesterday" <?php if($day=="yesterday") echo "selected='selected'"; ?>>Yesterday</option>
	</select>
	<input type="text" id="term" name="term" value="<?=$term?>" />
	<input type="submit" value="Search" />
</form>
<pre>

<?php
if ( $day == "today" ) {
	if ( $file == "stable" ) $fp = fopen("/home/geofox/logs/api.geofoxapp.com/http/error.log","r");
	else $fp = fopen("/home/geofox/logs/dev.api.geofoxapp.com/http/error.log","r");
}
else {
	if ( $file == "stable" ) $fp = fopen("/home/geofox/logs/api.geofoxapp.com/http/error.log.0","r");
	else $fp = fopen("/home/geofox/logs/dev.api.geofoxapp.com/http/error.log.0","r");
}

if ($fp == false) die("Unable to open log file!");
$found = false;

while ( $line = fgets($fp) ) {
	if ( $term != "" ) {
		if ( stripos($line,$term) ) {
			echo $line;
			$found = true;
		}
	}
	else {
		echo $line;
		$found = true;
	}
}

if ( !$found ) echo "No results found!";

fclose($fp);

?>

</pre>
</body>
</html>