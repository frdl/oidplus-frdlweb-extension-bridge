<?php

namespace Wehowski\Helpers;
/*
Example:
<?php
$a = [
    295 => "Hello",
    58 => "world",
];

$a = arrayInsert($a, 1, [123 => "little"]);

Output:
Array
(
    [295] => Hello
    [123] => little
    [58] => world
)
?>
 Despite PHP's amazing assortment of array functions and juggling maneuvers, I found myself needing a way to get the FULL array key mapping to a specific value. This function does that, and returns an array of the appropriate keys to get to said (first) value occurrence.

function array_recursive_search_key_map($needle, $haystack) {
    foreach($haystack as $first_level_key=>$value) {
        if ($needle === $value) {
            return array($first_level_key);
        } elseif (is_array($value)) {
            $callback = array_recursive_search_key_map($needle, $value);
            if ($callback) {
                return array_merge(array($first_level_key), $callback);
            }
        }
    }
    return false;
}

usage example:
-------------------

$nested_array = $sample_array = array(
    'a' => array(
        'one' => array ('aaa' => 'apple', 'bbb' => 'berry', 'ccc' => 'cantalope'),
        'two' => array ('ddd' => 'dog', 'eee' => 'elephant', 'fff' => 'fox')
    ),
    'b' => array(
        'three' => array ('ggg' => 'glad', 'hhh' => 'happy', 'iii' => 'insane'),
        'four' => array ('jjj' => 'jim', 'kkk' => 'kim', 'lll' => 'liam')
    ),
    'c' => array(
        'five' => array ('mmm' => 'mow', 'nnn' => 'no', 'ooo' => 'ohh'),
        'six' => array ('ppp' => 'pidgeon', 'qqq' => 'quail', 'rrr' => 'rooster')
    )
);

$search_value = 'insane';

$array_keymap = array_recursive_search_key_map($search_value, $nested_array);

var_dump($array_keymap);
// Outputs:
// array(3) {
// [0]=>
//  string(1) "b"
//  [1]=>
//  string(5) "three"
//  [2]=>
//  string(3) "iii"
//}

----------------------------------------------

But again, with the above solution, PHP again falls short on how to dynamically access a specific element's value within the nested array. For that, I wrote a 2nd function to pull the value that was mapped above.

function array_get_nested_value($keymap, $array)
{
    $nest_depth = sizeof($keymap);
    $value = $array;
    for ($i = 0; $i < $nest_depth; $i++) {
        $value = $value[$keymap[$i]];
    }

    return $value;
}

usage example:
-------------------
echo array_get_nested_value($array_keymap, $nested_array);   // insane
*/
class ArrayHelper
{

 protected	$arr = null;
 protected	$item = null;
 protected	$index = null;

 public function __construct(array $arr = null){
	 if(null===$arr){
		 $arr=[];
	 }
	 $this->arr=$arr;
	 $this->index=count($this->arr)-1;
 }
 public function __call($name, $params){
	 if(function_exists('array_'.$name)){
		 array_unshift($params, $this->data);
		 return call_user_func_aray('array_'.$name, $params);
	 }
 }	
	
	
 public function getByHash($keymap,$hashIndex = null){
	 
    $nest_depth = sizeof($keymap);
	 if(null===$hashIndex){
		 $hashIndex=max(0,$nest_depth-1);
	 }
	 if(is_int($hashIndex)){
		  $hashIndex=max($hashIndex,$nest_depth);
	 }
    $value =  $this->arr;
    for ($i = 0; $i < $nest_depth; $i++) {
        $value = $value[$keymap[$i]];
		if(is_int($hashIndex) && $hashIndex === $i || $hashIndex === $keymap[$i])break;
		
    }

    return $value;
 }	
 
 public function find($search_value, $data = null, $hashIndex = null) {
   return $this->getByHash($this->hash($search_value), $hashIndex); 
 }	
 public function hash($needle) {
    return self::getHash($needle, $this->arr) ;
 }	
 public static function getHash($needle, $haystack) {
    foreach($haystack as $first_level_key=>$value) {
        if ($needle === $value || preg_match('/^'.$needle.'$\/', $value)) {
            return array($first_level_key);
        } elseif (is_array($value)) {
            $callback = self::getHash($needle, $value);
            if ($callback) {
                return array_merge(array($first_level_key), $callback);
            }
        }
    }
    return false;
  }
	
	
	
	public static function before(array $src,array $in, $pos){
			$this->index= ((!is_int($pos)) ?  ArrayHelper::getHash($pos, $this->arr)[0] : $pos) -1;
		return $this;
   }
	
	public function after( $pos){
			$this->index= ((!is_int($pos)) ?  ArrayHelper::getHash($pos, $this->arr)[0] : $pos) + 1;
		return $this;
    }	
	public function add( $data ){
			$this->arr= self::insert($this->arr, $data,  $this->index);
		return $this;
    }		
	
	
	public function up($index, $up = 1) {
      $new_array = $this->arr;
     
	 while($up > 0){
		$up-- ;
       if((count($new_array)>$index) && ($index>0)){
                 array_splice($new_array, $index-1, 0, $input[$index]);
                 array_splice($new_array, $index+1, 1);
             }

	 }
       return $new_array;
    }

	public function down($index, $down=1) {
       $new_array = $this->arr;
        while($down > 0){
		$down--  ;
       if(count($new_array)>$index) {
                 array_splice($new_array, $index+2, 0, $input[$index]);
                 array_splice($new_array, $index, 1);
             }
		 }
       return $new_array;
     }	
	
	public static function insert(array $array, $insertArray,  $position = null)
	{
     $ret = [];
		$count = count($array);
		
		if(!is_int($position)){
			$position = ArrayHelper::getHash($position,$array)[0];
		}
		
      if(null===$position || (is_int($position) && $position > $count )){
	   $position = $count - 1;
     }
		
     if (is_int($position) && $position === $count) {
		  $ret = $array;
		 array_push($ret, $insertArray);
     //  // $ret = $array + $insertArray;
     } else {
        $i = 0;
		 $f=false;
        foreach ($array as $key => $value) {
            if ((is_int($position) && $position === $i )
				|| (is_string($position) && $position === $key) 
				|| (is_scalar($position) &&  $position === $value)
				|| (is_string($position) &&  preg_match('/^'.$position.'$\/', $value))
			   ) {
		      // 	array_push($ret, $insertArray);
               //  $ret += $insertArray;
				$ret[(is_numeric($key))?$i:((is_numeric($position)|| isset($array[$position]))?$i:$position)] = $insertArray; 
				 $f=true;
				$i++;
            }      
			$ret[(($f===true && is_numeric($key))|| isset($array[$key]))?$i:$key] = $value;     
			$i++;
		}
  
	}

   
		return $ret;
	}
	
}

