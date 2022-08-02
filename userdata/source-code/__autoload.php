<?php

require_once __DIR__ .\DIRECTORY_SEPARATOR.'patch_set_timezone.php';


require_once __DIR__.\DIRECTORY_SEPARATOR. 'LoaderBundle.php';

$dir = getcwd();


$cacheFile = __DIR__.\DIRECTORY_SEPARATOR.'cache'.\DIRECTORY_SEPARATOR.'classes-caches.php';
$cachedir = __DIR__.\DIRECTORY_SEPARATOR.'cache'.\DIRECTORY_SEPARATOR.'classmap-cache';	 
if(!is_dir($cachedir)){
  mkdir($cachedir, 0755, true);	
}
chmod($cachedir, 0755);


 $loader = new \Nette\Loaders\FrdlRobotLoader;
 $loader->setTempDirectory($cachedir);
 $loader->addFromDirectoriesListConfigFile(__DIR__   .\DIRECTORY_SEPARATOR  .'autoader.config.php');
  $loader->Global(true);
  $mapFile = $loader->getGlobalCacheFile();
  $refresh = !file_exists($mapFile);
  $time = (file_exists($mapFile))
	 ? max((int)filemtime($mapFile), (int)filectime($mapFile))
	 : 1;
 $isTimeToRefresh = ($refresh && $time < time() - 31 * 24 * 60 * 60)
	           || (max((int)filemtime($mapFile), (int)filectime($mapFile))
	              < max((int)filemtime(__FILE__), (int)filectime(__FILE__)));
 
$classes_map=[];
 
	if(true === $isTimeToRefresh 
            || !file_exists($mapFile)
            || true === $refresh){
             $loader->refresh();
             $loader->rebuild();
	}	
/*		
   $classes_map = (file_exists($mapFile))
		               ? array_merge($classes_map, require $mapFile)
		               : array_merge($classes_map, []);	
	 
   $classes = array_keys($classes_map);
   sort($classes);
*/
 $loader->register();

 $LocalAutoloader =  new \frdl\implementation\psr4\LocalAutoloader();
 $LocalAutoloader->addNamespace('\webfan\hps\patch\\', __DIR__.\DIRECTORY_SEPARATOR
								.'patch-hps-webfan'.\DIRECTORY_SEPARATOR
								.'patch'.\DIRECTORY_SEPARATOR
								, false);
 $LocalAutoloader->addNamespace('\\', __DIR__.\DIRECTORY_SEPARATOR.'psr4'.\DIRECTORY_SEPARATOR, false);
 

	 $RemoteLoader = \frdl\implementation\psr4\RemoteAutoloader::getInstance('03.webfan.de', 
																	   false,
																	   sha1_file(__FILE__).filemtime(__FILE__), 
																	   false,
																	   false, 
																	   false, 
																	   $cachedir 
																	     .\DIRECTORY_SEPARATOR 
																	   .'cache-psr4-remotes'.\DIRECTORY_SEPARATOR ,
																	   24 * 60 * 60);	
	
	 $RemoteLoader->withClassmap([		
		 \frdl\OidplusTools\ObjectsCache::class=>'https://raw.githubusercontent.com/frdl/frdl-oidplus-object-type-trait/main/oid-frdl-classes/ObjectsCache.php?cache_bust=${salt}',
		 
		 
		 \frdl\OidplusTools\Contracts\WeidWebfantizeExtensionInterface::class=>
		 'https://raw.githubusercontent.com/frdl/frdl-oidplus-object-type-trait/main/oid-frdl-classes/WeidWebfantizeExtensionInterface.php?cache_bust=${salt}',
		 //https://raw.githubusercontent.com/frdl/frdl-oidplus-object-type-trait/main/oid-frdl-classes/AbstractWeidSubContracts.php#
		 \frdl\OidplusTools\Contracts\AbstractWeidSubContracts::class=>
		 'https://raw.githubusercontent.com/frdl/frdl-oidplus-object-type-trait/main/oid-frdl-classes/AbstractWeidSubContracts.php?cache_bust=${salt}',
		 \frdl\OIDplus\CustomObjectType::class => 
'https://raw.githubusercontent.com/frdl/frdl-oidplus-object-type-trait/main/oid-frdl-classes/CustomObjectType.php?cache_bust=${salt}',
		 
		 \Wehowski\WEID::class => 'https://raw.githubusercontent.com/frdl/frdl-oidplus-modifications-pack/master/oid-frdl-classes/WEID.php?cache_bust=${salt}',
		 
		 \frdl\OIDplus\CustomObjectType::class=>'https://raw.githubusercontent.com/frdl/frdl-oidplus-object-type-trait/main/oid-frdl-classes/CustomObjectType.php?cache_bust=${salt}'
		 
	 ]);

           $RemoteLoader->register(false);
