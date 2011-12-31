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

class GEOFOX_DATABASE extends mysqli {

	private $connected = false;

	// connect to server
	public function __construct($hostname,$username,$password,$database) {
		// get a database handle
		$handle = parent::__construct($hostname,$username,$password,$database);
		// check for connection error
		if ( $error = $this->connect_errno ) 
			throw new chain_exception("Unable to connect to database server. SQL Error: $error",201);
		else $this->connected = true;
		// return created object
		return $handle;
	}
	
	// disconnect from server
	public function close() {
		if ( $this->connected ) {
			parent::close();
			$this->connected = false;
		}
	}
	
	// check for active database connection
	public function connected() {
		return $this->connected;
	}
	
	// prepare database table structure
	public function reset() {

		$this->backup();
		
		###########################################################################################
		# WARNING: THIS WILL DROP ALL TABLES AND RELOAD THE DATABASE FROM SCRATCH!
		###########################################################################################
		
		// drop existing tables
		$sql =	"SET foreign_key_checks = 0";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to remove key constraints. ".$this->error,201);
		$sql =	"DROP TABLE IF EXISTS users";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to drop users table. ".$this->error,201);
		$sql = 	"DROP TABLE IF EXISTS sessions";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to drop sessions table. ".$this->error,201);
		$sql = 	"DROP TABLE IF EXISTS confirmation";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to drop confirmation table. ".$this->error,201);
		$sql = 	"DROP TABLE IF EXISTS locations";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to drop locations table. ".$this->error,201);
		$sql = 	"DROP TABLE IF EXISTS places";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to drop places table. ".$this->error,201);
		$sql = 	"DROP TABLE IF EXISTS checkins";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to drop checkins table. ".$this->error,201);
		$sql = 	"DROP TABLE IF EXISTS recommendations";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to drop recommendations table. ".$this->error,201);
		$sql = 	"DROP TABLE IF EXISTS place_cache";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to drop place_cache table. ".$this->error,201);
		$sql =	"SET foreign_key_checks = 1";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to add key constraints. ".$this->error,201);

						
		// USERS
		// keep track of all system users
		$sql = 	"CREATE TABLE IF NOT EXISTS users( 
				user_id INT UNSIGNED UNIQUE NOT NULL AUTO_INCREMENT, 
				password VARCHAR(128) NOT NULL, 
				fname VARCHAR(50) NOT NULL DEFAULT '', 
				lname VARCHAR(50) NOT NULL DEFAULT '', 
				email VARCHAR(50) UNIQUE NOT NULL DEFAULT '',
				email_valid BOOLEAN NOT NULL DEFAULT FALSE, 
				phone VARCHAR(50) NOT NULL DEFAULT '', 
				created INT NOT NULL, 
				PRIMARY KEY (user_id) 
				) ENGINE=INNODB";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to create users table. ".$this->error,201);
		
		$sql = "ALTER TABLE users AUTO_INCREMENT = 100";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to update user auto_increment. ".$this->error,201);
		
		// SESSIONS
		// keep track of all client-server connections
		$sql = 	"CREATE TABLE IF NOT EXISTS sessions( 
				auth_key VARCHAR(255) NOT NULL UNIQUE, 
				user_id INT UNSIGNED NOT NULL, 
				timestamp INT UNSIGNED NOT NULL, 
				PRIMARY KEY (auth_key), 
				FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE 
				) ENGINE=INNODB";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to create sessions table. ".$this->error,201);
	
		// CONFIRMATION
		// keep track of email confirmation codes
		$sql = 	"CREATE TABLE IF NOT EXISTS confirmation( 
				code VARCHAR(20) UNIQUE NOT NULL, 
				user_id INT UNSIGNED NOT NULL, 
				expiration INT UNSIGNED NOT NULL, 
				FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE, 
				PRIMARY KEY (code) 
				) ENGINE=INNODB";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to create confirmation table. ".$this->error,201);
	
		// LOCATIONS
		// keep track of user checkin locations
		// INSERT INTO locations (user_id,lat,lon,altitude,accuracy,timestamp) VALUES (?,?,?,?,?,?)
		$sql = 	"CREATE TABLE IF NOT EXISTS locations( 
				id INT UNSIGNED UNIQUE NOT NULL AUTO_INCREMENT, 
				user_id INT UNSIGNED NOT NULL, 
				lat FLOAT NOT NULL, 
				lon FLOAT NOT NULL, 
				altitude FLOAT NOT NULL DEFAULT 0, 
				accuracy FLOAT NOT NULL DEFAULT 0, 
				timestamp INT UNSIGNED NOT NULL,
				FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE, 
				PRIMARY KEY (id), 
				INDEX(user_id), INDEX(lat,lon) 
				) ENGINE=INNODB";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to create locations table. ".$this->error,201);
		
		// PLACES
		$sql = "CREATE TABLE IF NOT EXISTS places(
				place_id VARCHAR(50) UNIQUE NOT NULL,
				lat FLOAT NOT NULL,
				lon FLOAT NOT NULL,
				name VARCHAR(50) NOT NULL,
				category1 VARCHAR(50) NOT NULL DEFAULT '',
				category2 VARCHAR(50) NOT NULL DEFAULT '',
				category3 VARCHAR(50) NOT NULL DEFAULT '',
				PRIMARY KEY (place_id),
				INDEX(place_id), INDEX(lat,lon)
				) ENGINE=INNODB";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to create places table. ".$this->error,201);
		
		// CHECKINS
		$sql = "CREATE TABLE IF NOT EXISTS checkins(
				id INT UNSIGNED UNIQUE NOT NULL AUTO_INCREMENT,
				place_id VARCHAR(50) NOT NULL,
				user_id INT UNSIGNED NOT NULL,
				note VARCHAR(250) NOT NULL DEFAULT '',
				timestamp INT UNSIGNED NOT NULL,
				FOREIGN KEY (place_id) REFERENCES places (place_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(id), INDEX(place_id), INDEX(user_id)
				) ENGINE=INNODB";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to create checkins table. ".$this->error,201);
		
		// RECOMMENDATIONS
		$sql = "CREATE TABLE IF NOT EXISTS recommendations(
				id INT UNSIGNED UNIQUE NOT NULL AUTO_INCREMENT,
				user_id INT UNSIGNED NOT NULL,
				place_id VARCHAR(50) NOT NULL,
				rank FLOAT NOT NULL DEFAULT 0,
				FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (place_id) REFERENCES places (place_id) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(user_id)
				) ENGINE=INNODB";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to create recomendations table. ".$this->error,201);
		
		// PLACE_CACHE
		$sql = "CREATE TABLE IF NOT EXISTS place_cache(
				id VARCHAR(50) UNIQUE NOT NULL,
				rating_img_url VARCHAR(150) NOT NULL DEFAULT '',
				country_code VARCHAR(10) NOT NULL DEFAULT '',
				is_closed BOOLEAN NOT NULL DEFAULT FALSE,
				city VARCHAR(150) NOT NULL DEFAULT '',
				mobile_url VARCHAR(150) NOT NULL DEFAULT '',
				review_count INT NOT NULL DEFAULT 0,
				zip INT NOT NULL DEFAULT 0,
				state VARCHAR(10) NOT NULL DEFAULT '',
				latitude FLOAT NOT NULL,
				rating_img_url_small VARCHAR(150) NOT NULL DEFAULT '',
				address1 VARCHAR(150) NOT NULL DEFAULT '',
				address2 VARCHAR(150) NOT NULL DEFAULT '',
				address3 VARCHAR(150) NOT NULL DEFAULT '',
				phone VARCHAR(50) NOT NULL DEFAULT '', 
				state_code VARCHAR(10) NOT NULL DEFAULT '',
				photo_url VARCHAR(150) NOT NULL DEFAULT '',
				distance FLOAT NOT NULL,
				name VARCHAR(150) NOT NULL DEFAULT '',
				url VARCHAR(150) NOT NULL DEFAULT '',
				country VARCHAR(10) NOT NULL DEFAULT '',
				avg_rating FLOAT NOT NULL,
				longitude FLOAT NOT NULL,
				nearby_url VARCHAR(150) NOT NULL DEFAULT '',
				photo_url_small VARCHAR(150) NOT NULL DEFAULT '',
				timestamp INT NOT NULL,
				FOREIGN KEY (id) REFERENCES places (place_id) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(id)
				) ENGINE=INNODB;";
		if ( $this->query($sql) != TRUE ) throw new chain_exception("Unable to create place_cache table. ".$this->error,201);
		
	}
	
	// backup database to file
	private function backup() {
		global $settings;
		// set backup file location
		$dir = $settings["db"]["backup_path"];
		$file = "geofox-backup-".time().".sql";
		// prepare a unique mysql database backuo
		$path = $dir.$file;
		// prepare table dump command
		$command = "/usr/bin/mysqldump --opt --host=".$settings["db"]["hostname"]." --user=".$settings["db"]["username"]." 
					--password=".$settings["db"]["password"]." ".$settings["db"]["database"]." > $path";
		$output = shell_exec($command);
		if ( $output ) return false;
		else return $path;
	}
	

}

?>