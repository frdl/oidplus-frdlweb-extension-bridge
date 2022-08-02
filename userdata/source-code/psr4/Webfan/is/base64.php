<?php
namespace Webfan\is;




class base64 extends TypeCheckOf
{

	

	/* abstract */
	protected function example(){
	   return base64_encode('');
	}
	
    public function serialize() {		
	//	return (true === $this->validate() && '' !== $this->value) 
	//		?  (\webfan\hps\Format\DataUri::create(\webfan\hps\Format\DataUri::DEFAULT_TYPE, $this->value, null) )->__toString()
	//		 : '';
		return ((string)(''.$this->__toString()));
    }	
    public function unserialize($data) {
		$this->value = $this->set($data);
    }	
	
	/* abstract */
	public function __toString() : string{
	   if(!$this->valid || $this->value === $this->typeError() ){
		  return '';   
	   }
	   return $this->value;	
	}
	
	
	
	/* inherit */
	protected function StrictTypeCheck(string $str) : string{
	   return $str;	
	}
	
	
	/* abstract 	*/
	public function getStrictTypeCheck() :callable{
		return [$this, 'StrictTypeCheck'];
	}		

	
	
	/* abstract 	*/
	public function getType() : string {
		return 'base64';
	}

	
	
	/* abstract */
	public function getAliasNames()  {
		return ['base64_encoded', 'b64'];
	}

	
	
	
	/* abstract */
	protected function check($value) : bool{
	   return  is_string($value) 
		    && $this->is_base64_chars($value) 
		    && $this->is_base64_string($value) 
		    && $this->is_base64_test($value)
		   // No: Recursive Redundant && \webfan\hps\Format\Validate::isbase64($value)
		   ;
	}
	
	
	
	
	
	
   public function is_base64_chars($str) {
    if (!preg_match('~[^0-9a-zA-Z+/=]~', $str)) {
        $check = str_split(base64_decode($str));
        $x = 0;
        foreach ($check as $char) if (ord($char) > 126) $x++;
        if ($x/count($check)*100 < 30) return true;
    }
	   
    return false;
   }
	
   public function is_base64_string($s) { 
	   // first check if we're dealing with an actual valid base64 encoded string  
	   if (($b = base64_decode($s, TRUE)) === FALSE) {   
		   return false;
	   }

	   // now check whether the decoded data could be actual text 
	   $e = mb_detect_encoding($b); 
	   if (in_array($e, array('UTF-8', 'ASCII'))) { // YMMV  
		   return true;  
	   } else {  
		   return false;  
	   }
 
    }	
	
   public function is_base64_test($string){
    $decoded = base64_decode($string, true);

    // Check if there is no invalid character in string
    if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) return false;

    // Decode the string in strict mode and send the response
    if (!base64_decode($string, true)) return false;

    // Encode and compare it to original one
    if (base64_encode($decoded) != $string) return false;

    return true;
 }	
	
	
}



