<?php
/**
https://www.viathinksoft.com/codelib/67
*/
class URLFiletypeProcessor
{
	
protected $_URL;
protected $_file;
public function __construct($file){
	$this->_file=$_file;
	$this->_URL = self::getURL($file);
}
public function __get($name){
	if(isset($this->{'_'.$name})){
	   return  $this->{'_'.$name};
	}
}
public static function getURL($file) {
        if (!file_exists($file)) return false;
        if (!is_readable($file)) return false;

        $raw = file_get_contents($file);
        $raw = str_replace("\r", "\n", $raw);
        $ary = explode("\n", $raw);

        $in_section = false;
        foreach ($ary as $val) {
                if ($in_section) {
                        $bry = explode('=', $val);
                        if (trim(strtolower($bry[0])) == strtolower('URL')) {
                                array_shift($bry);
                                return implode('=', $bry);
                        }
                }

                if (substr(trim($val), 0, 1) == '[') {
                        $in_section = false;
                }
                if (str_replace(' ', '', strtolower($val)) == strtolower('[InternetShortcut]')) {
                        $in_section = true;
                }
        }
        unset($in_section);
        unset($val);

        return false;
}
	
}