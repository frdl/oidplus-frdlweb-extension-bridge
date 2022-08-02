<?php
namespace Webfan\is;




abstract class TypeCheckOf implements ValidatorInterface, \Serializable
{
	protected $value;
	protected $valid = false;
	
	/* final */
	final public function __construct(){
		$args = func_get_args();
		
		$this->valid = false;
		
		if(0===count($args)){
		   $this->value = $this->set($this->example());	
		}elseif(1===count($args)){
			$this->value = call_user_func_array([$this, 'set'], [$args[0]]);  	
		}else{
			$this->valid = false;
		    $this->value = new \InvalidArgumentException('InvalidArguments in '.__METHOD__);
			throw $this->value;
		}
        
	}
	
	
	/* abstract */
	abstract protected function check($value) : bool;
		
	
	
	/* abstract */
	abstract public function getStrictTypeCheck() :callable;
		
	/* abstract */
	abstract protected function example();
	
	/* abstract */
	abstract public function __toString() : string;

	/* abstract */
	abstract public function getType() : string;
	
	/* abstract */
	abstract public function getAliasNames();
		
	/* abstract */
	abstract public function serialize();
	
	/* abstract */
	abstract public function unserialize($serialized);
	
	protected function typeError() : \Throwable {
		return new \TypeError('Invalid '.$this->getType());
	}
	/*	
    public function serialize() {
		if(!($this->valid && (!is_object($this->value) || true !== $this->value instanceof \Throwable)) ){
		  //return $this->value;	
			return '';
			
		}
        return (is_object($this->value) || is_array($this->value) ) ? serialize($this->value) : (string)$this->value;
    }
    public function unserialize($data) {
		if(''===$data){
			$this->valid = false;
		    $this->value = $this->typeError();
			$this->validate();  	
			return;
		}
		
		
        $this->valid = false;
        $this->value = $this->set($data);
	    if(is_callable([$this, '__wakeup'])){
			$this->__wakeup();
		}
    }

    public function __sleep() {
		if(!($this->valid && (!is_object($this->value) || true !== $this->value instanceof \Throwable)) ){
		  return [];	
		}		
		
        return array("\0*\0value");
    }
	
    public function __wakeup() {
        $this->valid = false;
		$this->validate();  	
    }	
	
    public function __debugInfo() {
        return (true===$this->valid) 
			? [$this->getType()=>serialize($this->value)] 
			: [get_class($this->value)=>$this->value->getMessage()];
    }
*/	
	   
	public function __debugInfo() {
        return (true!==$this->valid) 
			? ((is_object($this->value) && $this->value instanceof \Throwable)
			            ? [get_class($this->value)=>$this->value->getMessage()] 
			            :  [get_class($this->typeError())=>null]   
			  ) 
			: [$this->getType()=>serialize($this->value)];
    }
	
	/* final */
	final protected function typeCheck(){
	   return call_user_func_array($this->getStrictTypeCheck(), [$this->value]);	
	}


	/* final */
	final protected function set(){
         $args = func_get_args();
		 if(1!==count($args)){			
			 $this->valid = false;
			 $this->value = new \InvalidArgumentException('undefined in '.__METHOD__);
		//	 throw $this->value;
			 return  $this->value;
		 }
			
		
		$this->value = $args[0];					
		if(!($this->valid = $this->validate()) ){
			 $this->value = $this->typeError();
		//	 throw $this->value;			
		}
		
		return $this->value;
	}

	
	
	/* final */
	final public function __invoke(){
		$args = func_get_args();
		
		$r = $this->typeError();
		
		 if(0===count($args)){
			$r =  ($this->validate()) ? $this->value : $this->typeError();  
		 }elseif(1===count($args)){
			 call_user_func_array([$this, 'set'], [$args[0]]);
			 $r =  ($this->validate()) ? $this->value : $this->typeError();  
		 }elseif(1<count($args)){
			$r =  new \InvalidArgumentException('InvalidArguments in '.__METHOD__);
		}
		
		return $r;
	}
	
	
	/* final */
	final public function validate(){
		$args = func_get_args();
		
		if(1===count($args)){			 
			return (new self($args[0]))->validate();			 
		 }elseif(1<count($args)){
			$this->value = new \InvalidArgumentException('InvalidArguments in '.__METHOD__);
			 $valid = false;
		}
		
		try{
		   $valid = $this->value === call_user_func_array([$this, 'typeCheck'], [$this->value])
			         && call_user_func_array([$this, 'check'], [$this->value]);			
		//}catch(\TypeError $e){
		}catch(\Exception $e){	
           $valid = false;
	   }				
		  
		if(!$valid){
		  // throw new \TypeError('Invalid type in '.__METHOD__);
			$this->value = $this->typeError();
		}
		
		
		$this->valid = $valid;
		return $valid;
	}
	
	

	
		
	

	
	
	/* inherit */
	/*
	public function StrictTypeCheckString(string $str) : string{
	   return $str;	
	}	
	*/
	
	/* inherit */
	/*
	public function StrictTypeCheckInt(int $str) : int {
	   return $str;	
	}
	*/
}



/*

function base64Toggle($str) {
    if (!preg_match('~[^0-9a-zA-Z+/=]~', $str)) {
        $check = str_split(base64_decode($str));
        $x = 0;
        foreach ($check as $char) if (ord($char) > 126) $x++;
        if ($x/count($check)*100 < 30) return true;
    }
    return false;
}



function is_base64_string($s) {
  // first check if we're dealing with an actual valid base64 encoded string
  if (($b = base64_decode($s, TRUE)) === FALSE) {
    return FALSE;
  }

  // now check whether the decoded data could be actual text
  $e = mb_detect_encoding($b);
  if (in_array($e, array('UTF-8', 'ASCII'))) { // YMMV
    return TRUE;
  } else {
    return FALSE;
  }
}


function validBase64($string)
{
    $decoded = base64_decode($string, true);

    // Check if there is no invalid character in string
    if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) return false;

    // Decode the string in strict mode and send the response
    if (!base64_decode($string, true)) return false;

    // Encode and compare it to original one
    if (base64_encode($decoded) != $string) return false;

    return true;
}
			*/