GeoFox Web Service
==================

Service Input Standards
-----------------------

- The server should accept raw JSON data passed directly. ```file_get_contents("php://input")```
- The server should accept cleartext data passed directly. ```$_REQUEST```
- This is a work in progress and has not been fully tested.
- To access secure actions, you must pass the parameter "auth_key" as returned by the login action.

Service Output Standards
------------------------

The server will always respond with the following array payload, regardless of action requested,
error encountered, or resulting completion state.

```
array(
	"error" => [true|false],
	"code" => [(int)server_status_code], 
	"message" => ["verbose description of response"],
	"execution_time" => [execution time in seconds]
)
```

If no error occured, then the array should also contain the following information.

```
array(
	"result" => result of action call, typically an array but sometimes not
)
```

If a lower level error occured (within the service dispatcher), then the array should also contain
a full stack trace of what caused the error. This will only be included when the debug flag is set.

```
array(
	"trace" => array()
}
```

The error_trace entry will be a multi-level deep (no limit) array that contains full details of 
where and why the error occured. 

Handling Errors
---------------

Errors should occur in a way that does not modify the service input or output constraints.

In order to ease error handling, the service uses exceptions to pass errors up to the parent
container. Exception handling must be recursive - child functions must be able to pass unhandled
errors up to their parents.

When the service controller encounters an unhandled exception, it will read retrieve the full stack
trace and then include it in the payload response. 

PHP standard 'Exceptions' should not be used. Instead, developers should throw an instance of 
'chain_exception'. 

```
throw new chain_exception(string $verbose_error_string, int $error_code [, chain_exception $child]);
```

Where the child exception (parameter 3) is the exception that caused the current exception to 
occur. I've included an example of how to use this below. 

IMPORTANT! Throwing an exception to the service controller will generate a 'server side error' 
HTTP response. It should only be done when the error cannot be handled locally. All input errors 
(and other errors that are the fault of the client) should be handled internally.

```
service_handler( $input ) {
	// call child object
	try {
		child( $input );
	} catch( chain_exception $e ) {
		// show any errors
		$response["error"] = true;
		$response["error_string"] = $e->getMessage();
		$response["error_trace"] = $e->fullTrace();
		// INTERNAL SERVER ERROR GENERATED!
	}
}

child( $input ) {
	// call nested child
	try {
		nested_child( $input );
	} catch ( chain_exception $e ) {
		// pass exception up to parent
		// notice the caught exception included as the third parameter
		throw new chain_exception("An error occured.",1,$e);
	}
}

nested_child ( $input ) {
	// throw a new exception
	// there is not chile exception to pass up, so the third parameter is empty
	throw new chain_exception("Nested child encountered a problem.",1);
}
```

Available Methods
-----------------

```
{input_parameters}=>{success_output|error_output}
```

S = string, I = int, B = boolean, F = float

**PUBLIC METHODS**

-	login						
	```
	{(S)email,(S)password} => {(S)auth_key|(B)false}
	```			
- 	user_create					
	```
	{(S)password,(S)fname,(S)lname,(S)email,(S)phone} => {(I)user_id|null}
	```			
- 	password_reset				
	```
	{(S)email} => {(B)true|null}
	```
	
**SECURE METHODS**

- 	user_details				
	```
	{(S)auth_key} => {(I)user_id,(S)fname,(S)lname,(S)email,(S)phone,(B)email_valid|null}
	```							
- 	user_update					
	```
	{(S)auth_key,(S)password,(S)fname,(S)lname,(S)email,(S)phone} => {(B)true|null}
	```							
- 	email_verify				
	```
	{(S)auth_key} => {(B)true|null}
	```							
- 	email_confirm				
	```
	{(S)auth_key,(S)code} => {(B)true|null}
	```
- 	place_search_near			
	```
	{(S)auth_key,(F)lat,(F)lon} => {[][---business information---]|null}
	```
- 	place_search_category		
	```
	{(S)auth_key,(F)lat,(F)lon,(S)category}	=> {[][---business information---]|null}
	```
- 	place_search_neighborhood	
	```
	{(S)auth_key,(F)lat,(F)lon}	=> {[(S)category][---business information---]|null}
	```
- 	place_get_details			
	```
	{(S)auth_key,(S)place_id} => {[---business information---]|null}
	```							
- 	place_get_details_live		
	```
	{(S)auth_key,(S)place_id} => {[---business information---]|null}
	```
- 	place_more_like				
	```
	{(S)auth_key,(S)place_id} => {[][---business information---]|null}
	```
- 	place_more_near				
	```
	{(S)auth_key,(S)place_id} => {[][---business information---]|null}
	```
- 	place_categories_get_all	
	```
	{(S)auth_key} => {[[(S)short][(S)long]]|null}
	```
- 	place_categories_get_short	
	```
	{(S)auth_key,(S)long} => {(S)short|null}
	```
- 	place_categories_get_long	
	```
	{(S)auth_key,(S)short} => {(S)long|null}
	```
- 	checkin_add					
	```
	{(S)auth_key,(S)place_id,(S)note} => {(I)checkin_id|null}
	```
- 	checkin_remove				
	```
	{(S)auth_key,(I)checkin_id} => {(B)true|null}
	```
- 	checkin_history				
	```
	{(S)auth_key} => {[(I)checkin_id,(S)note,(I)timestamp,(S)place_id,(S)place_name]|null}
	```
- 	checkin_history_date		
	```
	{(S)auth_key,(I)date_start,(I)date_end} => {[(I)checkin_id,(S)note,(I)timestamp,(S)place_id,(S)place_name]|null}
	```							
- 	rec_get_all					
	```
	{(S)auth_key} => {[][---business information---]|null}
	```

Error Codes
-----------

**SUCCESS STATES** (0000-0099) [no problem here]

-	0 - Success. No errors encountered.

**NOTICE STATUS** (0100-0199) [no problems, but with additional information]

-	100 - I was supposed to tell you something, but I forgot.

**SERVICE ERROR STATUS** (0200-0299) [it's my fault]

-	200 - An unknown service error has occurred.
-	201 - An internal database error has occurred.

**INPUT ERRORS** (0300-0399) [it's your fault]

-	300 - An unknown input error has occurred.
-	301 - Missing a required parameter. Check your syntax.
-	302 - Problem with one or more parameters. Check your data.
-	303 - Invalid or missing authentication code. You need to run login again.
-	304 - Invalid or missing developer code. Please contact support.
-	305 - Invalid action. Check the documentation.
-	306 - You're not allowed to perform this action.
-	307 - Access is denied. Your use of this service has been restricted. Please contact support.
-	308 - Bad password email combination.

**THIRD PARTY PROBLEMS** (0400-0499) [it's ____'s fault]

-	400 - An unknown third party error has occurred.
	
Legal
-----

Copyright (C) 2011, Matt Colf, Rusty Dekema, Mike Billau, Mike Brown, Adam Budde

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.