<?php
namespace frdl\OidplusTools;
use OIDplusObject;
use OIDplus;
use OIDplusAltId;
use OIDplusException;

trait AltIdsHandlerTrait /* using classes can implement AltIdsHandlerInterface */
{
   public function getAltIdsInfo($id){
	try{
	   $obj = OIDplusObject::parse($id);
	 }catch(\Exception $e){
		$obj = false; 
	 }
	   
	   if($obj !== false){
		  $alt_ids = $obj->getAltIds();
		  $alt=[];
		  foreach($alt_ids as $a){
			   $alt[] = [
				     'id'=>$obj->nodeId(true),
				     'alt' =>$a->getId(),
				     'ns' => $a->getNamespace(),
				     'description' => $a->getDescription(),
				     
				  ];
		  }
		 //  die(print_r($alt_ids,true));
		  $res_alt_ids = [];
		  $res_alt=[];
			  $res = OIDplus::db()->query("select * from ".OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', 'oidplus_')."alt_ids where id = ?", [$obj->nodeId(true)]);
			
		   while ($row = $res->fetch_array()) {
		 	//$res_alt_ids[]=$row;;
			  $res_alt_ids[]= new OIDplusAltId($row['ns'], $row['alt'],  $row['description']);
			  $res_alt[] = [
				     'id' => $row['id'],
				     'alt' => $row['alt'],
				     'ns' => $row['ns'],
				     'description' => $row['description'],
				     
				  ];
		  }   
		   
		   sort($alt);
		   sort($res_alt);
		   
		   $diff = array_udiff($alt, $res_alt, function($a, $b){
			 
			   
			  if(//0===count(array_diff($a, $b))  &&
				  $a['id'] === $b['id']
				  && $a['alt'] === $b['alt']
				  && $a['ns'] === $b['ns']
				//  && $a['description'] === $b['description']
				){
				 return 0;
			  }else if(//0 < count(array_diff($a, $b))   || 
				$a['id'] !== $b['id']
				  || $a['alt'] !== $b['alt']
				  || $a['ns'] !== $b['ns']
				 // || $a['description'] !== $b['description']
				){ /*
				     print_r(
			   '<pre>'.print_r(array_diff($a, $b),true).'</pre>' );
			   
				    */
				   return -1;
			  }else{ /*
				     print_r(
			   '<pre>'.print_r(array_diff($a, $b),true).'</pre>' );
			   
				   */
				return 1;  
			  }
		   });
		   
		 /*
		   die($obj->nodeId(true)
			   .'<pre>'.print_r($diff,true).'</pre>'
			   .'<pre>'.print_r($res_alt,true).'</pre>'
			   .'<pre>'.print_r($alt,true).'</pre>');
			     */
	   }//obj not false
	   
	   sort($diff);
	  return [
		  'altIds' => $alt,
		  'notInDB'=> $diff,
		  'inDB'=> $res_alt,
	  ];
   }
	
	 public function handleAltIds($id, $insertMissing = false){
		 	   try{
	             $obj = OIDplusObject::parse($id);
	           }catch(\Exception $e){
	                	$obj = false; 
	           }
		 $info = (false===$obj) ? $obj : $this->getAltIdsInfo($id);
		 if(false!==$obj && true === $insertMissing && 0<count($info['notInDB']) ){
			 
			// foreach(array_unique($info['notInDB']) as $num => $_inf){
			 foreach($info['notInDB'] as $num => $_inf){
				// die($obj->nodeId(true));
			  try{	 
				 $res = OIDplus::db()->query("insert into ".OIDplus::baseConfig()->getValue('TABLENAME_PREFIX', 'oidplus_')."alt_ids set id = ?, alt = ?,ns = ?,description = ?", [
					 $obj->nodeId(true),
					 $_inf['alt'],
					 $_inf['ns'],
					 $_inf['description'],					 
				 ]); 
	           }catch(\Exception $e){
	            //   die(print_r($e->getMessage(),true));   
				  throw new OIDplusException($e->getMessage());
	           }
				// die(print_r($res,true));
			 }
			 $info = $this->getAltIdsInfo($id);
		 }
		return $info;
	 }
	
}
