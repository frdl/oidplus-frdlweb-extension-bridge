<?php

function getRootDir($path = null){
if(null===$path){
$path = $_SERVER['DOCUMENT_ROOT'];
}


 if(''!==dirname($path) && '/'!==dirname($path) //&& @chmod(dirname($path), 0755)
    &&  true===@is_writable(dirname($path))
    ){
  return getRootDir(dirname($path));
 }else{
  return $path;
 }

}


function rglob($pattern, $flags = 0, $traversePostOrder = false) {
  // Keep away the hassles of the rest if we don't use the wildcard anyway
    if (strpos($pattern, '/**/') === false) {
        $files = glob($pattern, $flags);
		 return (!is_array($files)) ? [] : $files;
    }

    $patternParts = explode('/**/', $pattern);

    // Get sub dirs
    $dirs = glob(array_shift($patternParts) . '/*', \GLOB_ONLYDIR | \GLOB_NOSORT);

    // Get files for current dir
    $files = glob($pattern, $flags);
	
    $files =  (!is_array($files)) ? [] : $files;
	
    foreach ($dirs as $dir) {
        $subDirContent = rglob($dir . '/**/' . implode('/**/', $patternParts), $flags, $traversePostOrder);

        if (!$traversePostOrder) {
            $files = array_merge($files, $subDirContent);
        } else {
            $files = array_merge($subDirContent, $files);
        }
    }

    return (!is_array($files)) ? [] : $files;
 }