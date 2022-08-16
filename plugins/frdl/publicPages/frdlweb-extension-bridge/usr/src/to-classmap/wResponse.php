<?php


class wResponse
{


  public static function header($name, $value)
    {
       return header($name.': '.$value);
    }



  public static function status($code = 200)
    {
       if((int)$code == 200)return header('HTTP/1.1 200 Ok');
       if((int)$code == 201)return header('HTTP/1.1 201 Created');

       if((int)$code == 400)return header("HTTP/1.1 400 Bad Request");
       if((int)$code == 401)return header('HTTP/1.1 401 Unauthorized');
       if((int)$code == 403)return header("HTTP/1.1 403 Forbidden");
       if((int)$code == 404)return header("HTTP/1.1 404 Not Found");
       if((int)$code == 409)return header('HTTP/1.1 409 Conflict');
       
       if((int)$code == 422)return header('HTTP/1.1 422 Validation Failure');
       
       if((int)$code == 429)return header('HTTP/1.1 429 Too Many Requests');
       
       if((int)$code == 455)return header('HTTP/1.1 455 Blocked Due To Misbehavior');

       
       if((int)$code == 500)return header("HTTP/1.1 500 Internal Server Error");
       if((int)$code == 501)return header('HTTP/1.1 501 Not Implemented');
	  
	  if((int)$code == 503)return header('HTTP/1.1 503 Service Unavailable');
	  
	   if((int)$code == 511)return header('HTTP/1.1 511 Network Authentication Required');
	  
	  
       if(0===intval($code)){
		   return header('HTTP/1.1 501 Not Implemented');
	   }
	  
	  trigger_error('status code '.intval($code).' not implemented in \'' . get_class($this) . '\'   ' . __METHOD__. ' '.__LINE__, E_USER_ERROR);
    }





}
//EOF