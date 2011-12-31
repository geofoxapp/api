<?php

####################################################################################################
#
# GEOFOX GROUP CONFIDENTIAL
# Unpublished Copyright (c) 2010 GEOFOX GROUP, All Rights Reserved.
# 
# Viewing and/or usage of this source code indicates acceptance of the full license terms as 
# outlined in the index.php file.
#
####################################################################################################

// Here, and forever more, a tab is 4 spaces, as God intended.

// sub-classing exception to add PHP 5.3 chaining compatability on a 5.2 server...

class chain_exception extends Exception {

	private $previous = null;
	
	public function __construct($message = "",$code = 0,$previous = null) {
		// copy previous exception
		$this->previous = $previous;
		// call parent constructor
		parent::__construct($message,$code);
	}
	
	// return previous exception
	public function getPrevious() {
		if ( $this->previous ) return $this->previous;
		else return false;
	}
	
	// "recursive" stack trace
	public function fullTrace() {
		$trace = array();
		$e = $this;
		do {
			$entry = array();
			$entry["message"] = $e->getMessage();
			$entry["file"] = $e->getFile();
			$entry["line"] = $e->getLine();
			$entry["code"] = $e->getCode();
			$trace[] = $entry;			
		} while ( $e = $e->getPrevious() );
		return $trace;
	}
	
	public function base_code() {
		$e = $this->get_base();
		return $e->code;
	}
	
	public function base_message() {
		$e = $this->get_base();
		return $e->message;
	}
	
	private function get_base() {
		$e = $prev = $this;
		do {
			$prev = $e;
		} while ( $e = $e->getPrevious() );
		return $prev;
	}

}

?>