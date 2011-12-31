<?php

// set location of error logs
$file_log_new = "/home/geofox/logs/api.geofoxapp.com/http/error.log";
$file_log_old = "/home/geofox/web/api/scratch/error.log.old";

// check if files exist
$log_new = $log_old = null;
if ( file_exists($file_log_new) ) $log_new = file($file_log_new);
else die("Log file does not exist!");
if ( file_exists($file_log_old) ) $log_old = file($file_log_old);
else $log_old = array();

// run an array difference
$diff = array_diff($log_new,$log_old);

// check for changes
if ( count($diff) && count($log_new) > 0 ) {
	// send notification email
	$to = "geofox-admins@test.com";
	$subject = "The GEOfox API has encountered an error!";
	$message = "Error details have been included below.\r\n\r\n";
	foreach( $diff as $line ) {
		$message = $message.$line."\r\n";
	}
	$message = $message."The full error log can be found in $file_log_new.\r\n\r\n";
	$message = $message."---\r\nThe happy GEOfox error mailer";
	$message = wordwrap($message,100);
	if ( !mail($to,$subject,$message) ) die("Unable to send email message!");
	echo "Found ".count($diff)." new log file entries!";;
}
else echo "No new entries found!";

// copy log file to scratch space
if ( !copy($file_log_new,$file_log_old) ) {
	die("Unable to copy log file!");
} 
else echo "Log file copied.";

?>