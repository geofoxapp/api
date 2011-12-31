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

####################################################################################################
# AUTHENTICATION
####################################################################################################

// generate a random string of length $length
function random_string($length) {
	// setup available characters
	$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"; 
	// assemble random key
	$key = "";
	for($pos = 0; $pos < $length; $pos++) {
		$key .= $characters[rand(0,strlen($characters)-1)];
	}
	return $key;
}

// encrypt a string
function encrypt($cleartext) {
	global $settings;
	return md5($cleartext.$settings["encrypt_salt"]);
}

// expire old authentication keys
function expire_keys() {
	global $settings, $db;
	if ( $settings["timeout"] > 0 ) {
		$expire = time()-$settings["timeout"];
		$statement = $db->prepare("DELETE FROM sessions WHERE timestamp < ?");
		$statement->bind_param("i",$expire);
		$statement->execute();
		$statement->close();
	}
}

// get a new authentication key
function generate_key($user_id) {
	global $settings, $db;
	// create a new authentication key (loop for duplicates)
	$statement = $db->prepare("INSERT INTO sessions (auth_key,user_id,timestamp) VALUES (?,?,?)");
	$new_key = "";
	do {
		$new_key = random_string($settings["key_length"]);
		$statement->reset();
		$timestamp = time();		
		$statement->bind_param("sii",$new_key,$user_id,$timestamp);
	} while ( !$statement->execute() );
	// return new key
	$statement->close();
	return $new_key;
}

####################################################################################################
# OTHER
####################################################################################################

// clean up a phone number string
function phone_clean($phone) {
	return ereg_replace("[^0-9]", "", $phone );
}

// check for valid phone number
function valid_phone($phone) {
	$phone = phone_clean($phone);
	if ( strlen($phone) == 10 ) return true;
	else return false;
}

// check and sanitize server input
// valid expected types: 'bool', 'int', 'float', 'string', 'null', 'time'
function sanitize($input,$expected) 
{
	switch ( $expected ){
		case "null":
			$clean = null; 
			break;
		case "bool":
			$clean = ($input === "true" or $input === 1 or $input === true ) ? true : false;
			break;
		case "int":
			$clean = (int) $input;
			if ( !is_int($clean) ) return 0;
			break;
		case "float":
			$clean = (float) $input; 
			if ( !is_float($clean) ) return 0.0;
			break;
		case "string";
			$clean = (string) addslashes($input);
			if ( strlen($clean) == 0 ) return "";
			break;
		case "time":
			$clean = (int) $input;
			if ( !is_int($clean) ) return 0;
			if ( $clean < 0 ) $clean = 0;
			if ( $clean > time() ) $clean = time();
			break;
	}
	return $clean;
}

####################################################################################################
# SAMPLE DATA
####################################################################################################

function load_data() {
	try {
		$id1 = user_create("123456","Abe","Lincoln","geofox-testuser1@test.com","5555555555");
		$id2 = user_create("123456","Ben","Finch","geofox-testuser2@test.com","5555555555");
		$id3 = user_create("123456","Carla","Carlotestein","geofox-testuser3@test.com","5555555555");
		$id4 = user_create("123456","Denna","McMacalot","geofox-testuser4@test.com","5555555555");
		$id5 = user_create("123456","Edward","Definator","geofox-testuser5@test.com","5555555555");
		place_search_neighborhood(42.2937296,-83.7162503); // north campus
		place_search_neighborhood(42.277166,-83.73822); // central campus
		// testuser1
		checkin_add($id1,"nRrzWMP2u7qz1UfOBYctIA","Food!");
		checkin_add($id1,"cK6HRIm1lGVF5ID9CaOQfw","Mmmmmmm!");
		checkin_add($id1,"TzmK34Us1S-Edzvsi7BFGg","Look, I don't know what you expect of me here.");
		checkin_add($id1,"nRrzWMP2u7qz1UfOBYctIA","Food!");
		checkin_add($id1,"8vxDpQlZioyONGlv8JMZ4w","Something in Kerrytown.");
		checkin_add($id1,"jTPr0dFk2JgGYdDiIGro3Q","Mmm, pizza.");
		checkin_add($id1,"HK_6kdKUqlnSzyJjgIwHFQ","Book festival!.");
		checkin_add($id1,"Xqd3zkqH7OlDwr0x8A2MOQ","The mind's eye.");
		checkin_add($id1,"XCAXq_v7o7FisTOcLGUTcw","Yoga is the morning, man.");
		checkin_add($id1,"eSxwOb0HfqZ110hsDfIFgQ","Jazz and martinis.");
		// testuser2
		checkin_add($id2,"nRrzWMP2u7qz1UfOBYctIA","Food!");
		checkin_add($id2,"TzmK34Us1S-Edzvsi7BFGg","Look, I don't know what you expect of me here.");
		checkin_add($id2,"Osvi0im8a1HL2bwroBqjgw","Bears and beer!");
		checkin_add($id2,"Hc3d_UDV7qE30o6WeQBEmQ","Conor O' Neill's");
		checkin_add($id2,"ZJVhCAjBeRlzLhgRVVJD5Q","Pumpkin Chunkin");
		checkin_add($id2,"7bG_BRrmIaAux3r0fcfr5g","Garden.");
		// testuser3
		checkin_add($id3,"THJN6kVcPUD2T04TrcmZng","Blimpy Burger!");
		checkin_add($id3,"NX0LCwfBz-zZHVBqFmJAvA","Le Dog!");
		checkin_add($id3,"nRrzWMP2u7qz1UfOBYctIA","Food!");
		checkin_add($id3,"HK_6kdKUqlnSzyJjgIwHFQ","Book festival!.");
		checkin_add($id3,"Osvi0im8a1HL2bwroBqjgw","Bears and beer!");
		checkin_add($id3,"nRrzWMP2u7qz1UfOBYctIA","Food!");
		// testuser4
		checkin_add($id4,"TzmK34Us1S-Edzvsi7BFGg","Look, I don't know what you expect of me here.");
		checkin_add($id4,"nRrzWMP2u7qz1UfOBYctIA","Food!");
		checkin_add($id4,"eSxwOb0HfqZ110hsDfIFgQ","Jazz and martinis.");
		checkin_add($id4,"3UlHZkdgedfdJ-XvXPmo_Q","Frank's.");
		checkin_add($id4,"3mtur-MoEG6OgdgRg-vooA","Nice place to stay.");
		// testuser5
		checkin_add($id5,"nRrzWMP2u7qz1UfOBYctIA","Food!");
		checkin_add($id5,"cK6HRIm1lGVF5ID9CaOQfw","Mmmmmmm!");
		checkin_add($id5,"R6Hbmm0L6a4aV1DyuRUq7A","Guest Haus?");
		checkin_add($id5,"aSpYVx3g3wldx09HQVl5LQ","Cheap and long.");
	} catch ( chain_exception $e ) {
		throw new chain_exception("Unable to load test data in load_data.",200,$e);
	}
}

####################################################################################################
# EMAIL TESTING
####################################################################################################

# check for valid email address
function valid_email($email) {
	if (valid_email_string($email)) {
		if (valid_email_mx($email)) {
			return true;
		}
	}
	return false;
}

# check for a valid email address form
function valid_email_string($email)  {
	return rfc3696_valid_email_address($email) ? true : false;
}

# check for a valid email address mx record
function valid_email_mx($email) {
	return validate_email_mx_record($email) ? true : false;
}

function validate_email_mx_record($email) {
	$host = substr(strstr($email, '@'), 1)."."; 
	# check for valid DNS MX record
	if ( getmxrr($host, $validate_email_temp) ) return true; 
	# check for any valid DNS record
	// if ( checkdnsrr($host,"ANY") ) return true; 
	return false;
}

# RFC3696 Email Parser
#
# By Cal Henderson <cal@iamcal.com>
#
# This code is dual licensed:
# CC Attribution-ShareAlike 2.5 - http://creativecommons.org/licenses/by-sa/2.5/
# GPLv3 - http://www.gnu.org/copyleft/gpl.html
#
# $Revision: 5039 $
# Source: http://code.iamcal.com/php/rfc822/

function rfc3696_strip_comments($comment, $email, $replace='')
{
	while (1){
		$new = preg_replace("!$comment!", $replace, $email);
		if (strlen($new) == strlen($email)){
			return $email;
		}
		$email = $new;
	}
}

function rfc3696_valid_email_address($email)
{
	# NO-WS-CTL       =       %d1-8 /         ; US-ASCII control characters
	#                         %d11 /          ;  that do not include the
	#                         %d12 /          ;  carriage return, line feed,
	#                         %d14-31 /       ;  and white space characters
	#                         %d127
	# ALPHA          =  	  %x41-5A / %x61-7A   ; A-Z / a-z
	# DIGIT          = 	      %x30-39

	$no_ws_ctl	= "[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]";
	$alpha		= "[\\x41-\\x5a\\x61-\\x7a]";
	$digit		= "[\\x30-\\x39]";
	$cr		= "\\x0d";
	$lf		= "\\x0a";
	$crlf		= "(?:$cr$lf)";

	# obs-char        =       %d0-9 / %d11 /          ; %d0-127 except CR and
	#                         %d12 / %d14-127         ;  LF
	# obs-text        =       *LF *CR *(obs-char *LF *CR)
	# text            =       %d1-9 /         ; Characters excluding CR and LF
	#                         %d11 /
	#                         %d12 /
	#                         %d14-127 /
	#                         obs-text
	# obs-qp          =       "\" (%d0-127)
	# quoted-pair     =       ("\" text) / obs-qp

	$obs_char	= "[\\x00-\\x09\\x0b\\x0c\\x0e-\\x7f]";
	$obs_text	= "(?:$lf*$cr*(?:$obs_char$lf*$cr*)*)";
	$text		= "(?:[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f]|$obs_text)";

	# there's an issue with the definition of 'text', since 'obs_text' can
	# be blank and that allows qp's with no character after the slash. we're
	# treating that as bad, so this just checks we have at least one
	# (non-CRLF) character

	$text		= "(?:$lf*$cr*$obs_char$lf*$cr*)";
	$obs_qp		= "(?:\\x5c[\\x00-\\x7f])";
	$quoted_pair	= "(?:\\x5c$text|$obs_qp)";

	# obs-FWS         =       1*WSP *(CRLF 1*WSP)
	# FWS             =       ([*WSP CRLF] 1*WSP) /   ; Folding white space
	#                         obs-FWS
	# ctext           =       NO-WS-CTL /     ; Non white space controls
	#                         %d33-39 /       ; The rest of the US-ASCII
	#                         %d42-91 /       ;  characters not including "(",
	#                         %d93-126        ;  ")", or "\"
	# ccontent        =       ctext / quoted-pair / comment
	# comment         =       "(" *([FWS] ccontent) [FWS] ")"
	# CFWS            =       *([FWS] comment) (([FWS] comment) / FWS)

	# note: we translate ccontent only partially to avoid an infinite loop
	# instead, we'll recursively strip *nested* comments before processing
	# the input. that will leave 'plain old comments' to be matched during
	# the main parse.
	
	$wsp		= "[\\x20\\x09]";
	$obs_fws	= "(?:$wsp+(?:$crlf$wsp+)*)";
	$fws		= "(?:(?:(?:$wsp*$crlf)?$wsp+)|$obs_fws)";
	$ctext		= "(?:$no_ws_ctl|[\\x21-\\x27\\x2A-\\x5b\\x5d-\\x7e])";
	$ccontent	= "(?:$ctext|$quoted_pair)";
	$comment	= "(?:\\x28(?:$fws?$ccontent)*$fws?\\x29)";
	$cfws		= "(?:(?:$fws?$comment)*(?:$fws?$comment|$fws))";

	# these are the rules for removing *nested* comments. we'll just detect
	# outer comment and replace it with an empty comment, and recurse until
	# we stop.

	$outer_ccontent_dull	= "(?:$fws?$ctext|$quoted_pair)";
	$outer_ccontent_nest	= "(?:$fws?$comment)";
	$outer_comment		= "(?:\\x28$outer_ccontent_dull*(?:$outer_ccontent_nest$outer_ccontent_dull*)+$fws?\\x29)";

	# atext           =       ALPHA / DIGIT / ; Any character except controls,
	#                         "!" / "#" /     ;  SP, and specials.
	#                         "$" / "%" /     ;  Used for atoms
	#                         "&" / "'" /
	#                         "*" / "+" /
	#                         "-" / "/" /
	#                         "=" / "?" /
	#                         "^" / "_" /
	#                         "`" / "{" /
	#                         "|" / "}" /
	#                         "~"
	# atom            =       [CFWS] 1*atext [CFWS]

	$atext		= "(?:$alpha|$digit|[\\x21\\x23-\\x27\\x2a\\x2b\\x2d\\x2f\\x3d\\x3f\\x5e\\x5f\\x60\\x7b-\\x7e])";
	$atom		= "(?:$cfws?(?:$atext)+$cfws?)";

	# qtext           =       NO-WS-CTL /     ; Non white space controls
	#                         %d33 /          ; The rest of the US-ASCII
	#                         %d35-91 /       ;  characters not including "\"
	#                         %d93-126        ;  or the quote character
	# qcontent        =       qtext / quoted-pair
	# quoted-string   =       [CFWS]
	#                         DQUOTE *([FWS] qcontent) [FWS] DQUOTE
	#                         [CFWS]
	# word            =       atom / quoted-string

	$qtext		= "(?:$no_ws_ctl|[\\x21\\x23-\\x5b\\x5d-\\x7e])";
	$qcontent	= "(?:$qtext|$quoted_pair)";
	$quoted_string	= "(?:$cfws?\\x22(?:$fws?$qcontent)*$fws?\\x22$cfws?)";

	# changed the '*' to a '+' to require that quoted strings are not empty

	$quoted_string	= "(?:$cfws?\\x22(?:$fws?$qcontent)+$fws?\\x22$cfws?)";
	$word		= "(?:$atom|$quoted_string)";

	# obs-local-part  =       word *("." word)
	# obs-domain      =       atom *("." atom)

	$obs_local_part	= "(?:$word(?:\\x2e$word)*)";
	$obs_domain	= "(?:$atom(?:\\x2e$atom)*)";

	# dot-atom-text   =       1*atext *("." 1*atext)
	# dot-atom        =       [CFWS] dot-atom-text [CFWS]

	$dot_atom_text	= "(?:$atext+(?:\\x2e$atext+)*)";
	$dot_atom	= "(?:$cfws?$dot_atom_text$cfws?)";
	
	# domain-literal  =       [CFWS] "[" *([FWS] dcontent) [FWS] "]" [CFWS]
	# dcontent        =       dtext / quoted-pair
	# dtext           =       NO-WS-CTL /     ; Non white space controls
	# 
	#                         %d33-90 /       ; The rest of the US-ASCII
	#                         %d94-126        ;  characters not including "[",
	#                                         ;  "]", or "\"

	$dtext		= "(?:$no_ws_ctl|[\\x21-\\x5a\\x5e-\\x7e])";
	$dcontent	= "(?:$dtext|$quoted_pair)";
	$domain_literal	= "(?:$cfws?\\x5b(?:$fws?$dcontent)*$fws?\\x5d$cfws?)";

	# local-part      =       dot-atom / quoted-string / obs-local-part
	# domain          =       dot-atom / domain-literal / obs-domain
	# addr-spec       =       local-part "@" domain

	$local_part	= "(($dot_atom)|($quoted_string)|($obs_local_part))";
	$domain		= "(($dot_atom)|($domain_literal)|($obs_domain))";
	$addr_spec	= "$local_part\\x40$domain";

	# see http://www.dominicsayers.com/isemail/ for details, but this should probably be 254

	if (strlen($email) > 256) return 0;

	# we need to strip nested comments first - we replace them with a simple comment

	$email = rfc3696_strip_comments($outer_comment, $email, "(x)");

	# now match what's left

	if (!preg_match("!^$addr_spec$!", $email, $m)){
		return 0;
	}

	$bits = array(
		'local'				=> isset($m[1]) ? $m[1] : '',
		'local-atom'		=> isset($m[2]) ? $m[2] : '',
		'local-quoted'		=> isset($m[3]) ? $m[3] : '',
		'local-obs'			=> isset($m[4]) ? $m[4] : '',
		'domain'			=> isset($m[5]) ? $m[5] : '',
		'domain-atom'		=> isset($m[6]) ? $m[6] : '',
		'domain-literal'	=> isset($m[7]) ? $m[7] : '',
		'domain-obs'		=> isset($m[8]) ? $m[8] : '',
	);

	# we need to now strip comments from $bits[local] and $bits[domain],
	# since we know they're i the right place and we want them out of the
	# way for checking IPs, label sizes, etc

	$bits['local']	= rfc3696_strip_comments($comment, $bits['local']);
	$bits['domain']	= rfc3696_strip_comments($comment, $bits['domain']);

	# length limits on segments

	if (strlen($bits['local']) > 64) return 0;
	if (strlen($bits['domain']) > 255) return 0;

	# restrictuions on domain-literals from RFC2821 section 4.1.3

	if (strlen($bits['domain-literal'])){
		$Snum			= "(\d{1,3})";
		$IPv4_address_literal	= "$Snum\.$Snum\.$Snum\.$Snum";
		$IPv6_hex		= "(?:[0-9a-fA-F]{1,4})";
		$IPv6_full		= "IPv6\:$IPv6_hex(:?\:$IPv6_hex){7}";
		$IPv6_comp_part		= "(?:$IPv6_hex(?:\:$IPv6_hex){0,5})?";
		$IPv6_comp		= "IPv6\:($IPv6_comp_part\:\:$IPv6_comp_part)";
		$IPv6v4_full		= "IPv6\:$IPv6_hex(?:\:$IPv6_hex){5}\:$IPv4_address_literal";
		$IPv6v4_comp_part	= "$IPv6_hex(?:\:$IPv6_hex){0,3}";
		$IPv6v4_comp		= "IPv6\:((?:$IPv6v4_comp_part)?\:\:(?:$IPv6v4_comp_part\:)?)$IPv4_address_literal";

		# IPv4 is simple

		if (preg_match("!^\[$IPv4_address_literal\]$!", $bits['domain'], $m)){
			if (intval($m[1]) > 255) return 0;
			if (intval($m[2]) > 255) return 0;
			if (intval($m[3]) > 255) return 0;
			if (intval($m[4]) > 255) return 0;
		}
		else{
			# this should be IPv6 - a bunch of tests are needed here :)

			while (1){

				if (preg_match("!^\[$IPv6_full\]$!", $bits['domain'])){
					break;
				}

				if (preg_match("!^\[$IPv6_comp\]$!", $bits['domain'], $m)){
					list($a, $b) = explode('::', $m[1]);
					$folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
					$groups = explode(':', $folded);
					if (count($groups) > 6) return 0;
					break;
				}

				if (preg_match("!^\[$IPv6v4_full\]$!", $bits['domain'], $m)){
					if (intval($m[1]) > 255) return 0;
					if (intval($m[2]) > 255) return 0;
					if (intval($m[3]) > 255) return 0;
					if (intval($m[4]) > 255) return 0;
					break;
				}

				if (preg_match("!^\[$IPv6v4_comp\]$!", $bits['domain'], $m)){
					list($a, $b) = explode('::', $m[1]);
					$b = substr($b, 0, -1); # remove the trailing colon before the IPv4 address
					$folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
					$groups = explode(':', $folded);
					if (count($groups) > 4) return 0;
					break;
				}

				return 0;
			}
		}			
	}
	else
	{

		# the domain is either dot-atom or obs-domain - either way, it's
		# made up of simple labels and we split on dots

		$labels = explode('.', $bits['domain']);

		# this is allowed by both dot-atom and obs-domain, but is un-routeable on the
		# public internet, so we'll fail it (e.g. user@localhost)

		if (count($labels) == 1) return 0;

		# checks on each label

		foreach ($labels as $label){
			if (strlen($label) > 63) return 0;
			if (substr($label, 0, 1) == '-') return 0;
			if (substr($label, -1) == '-') return 0;
		}

		# last label can't be all numeric
		if (preg_match('!^[0-9]+$!', array_pop($labels))) return 0;
		
	}
	return 1;
}
 
?>