<?php
namespace Wehowski\Oidplus\Bootstrap;

use Wehowski\WEID;
use OIDplus;

set_time_limit(90);


require __DIR__ .'/../../../../../../vendor/autoload.php';
require __DIR__ 
	.\DIRECTORY_SEPARATOR 
	.'..'.\DIRECTORY_SEPARATOR 
	.'src'.\DIRECTORY_SEPARATOR 
    . '__autoload.php';

 
\header_remove('X-Powered-By');
 
require __DIR__.\DIRECTORY_SEPARATOR.'deprecated.php';