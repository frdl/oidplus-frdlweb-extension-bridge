<?php

 
function setTimezone($default = null) {
	if(null===$default){
		try{
			 $default = date_default_timezone_get ( );
		}catch(\Exception $e){
			 $default = null;
		}
		
	}	
	
	
	if(null===$default){
		try{
			 $default = ini_get('date.timezone');
		}catch(\Exception $e){
			 $default = null;
		}
		
	}
	
	if(null===$default){
		 $default = 'Europe/Berlin';
	}
	
    $timezone = "";
   
    // On many systems (Mac, for instance) "/etc/localtime" is a symlink
    // to the file with the timezone info
    if (!strlen($timezone) && @is_readable("/etc/localtime") 
		&& is_link("/etc/localtime")) {
       
        // If it is, that file's name is actually the "Olsen" format timezone
        $filename = readlink("/etc/localtime");
       
        $pos = strpos($filename, "zoneinfo");
        if ($pos) {
            // When it is, it's in the "/usr/share/zoneinfo/" folder
            $timezone = substr($filename, $pos + strlen("zoneinfo/"));
        } else {
            // If not, bail
            $timezone = $default;
        }
    }
    elseif (!strlen($timezone) && @is_readable("/etc/timezone") ) {
        // On other systems, like Ubuntu, there's file with the Olsen time
        // right inside it.
        $timezone = file_get_contents("/etc/timezone");

    }
	       
	if (!strlen($timezone)) {
            $timezone = $default;
    }
    date_default_timezone_set($timezone);
}

//ini_set('date.timezone', 'Europe/Berlin');
setTimezone( );
ini_set('date.timezone', date_default_timezone_get ( ) );
 