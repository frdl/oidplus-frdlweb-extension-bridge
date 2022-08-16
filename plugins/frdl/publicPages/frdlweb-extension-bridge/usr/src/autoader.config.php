<?php

 
$config = [
	'__cache_dir' =>  __DIR__.\DIRECTORY_SEPARATOR.'cache'.\DIRECTORY_SEPARATOR,
	'__include_dirs' =>  [],
        '__exclude_dirs' =>  [],
	
	
];

 foreach([	
	 	  __DIR__.\DIRECTORY_SEPARATOR.'to-classmap'.\DIRECTORY_SEPARATOR, 
                  __DIR__.\DIRECTORY_SEPARATOR.'psr4'.\DIRECTORY_SEPARATOR,
	 
	 ] as $dir){
	 $config['__include_dirs'][] = $dir;
	 foreach(array_merge(glob(str_replace(\DIRECTORY_SEPARATOR, '/', $dir)."**/test**/*.php"), glob(str_replace(\DIRECTORY_SEPARATOR, '/', $dir)."**/Test**/*.php") ) as $testDirFile){
		 $config['__exclude_dirs'][] = dirname($testDirFile);
	 }
 }


return $config;