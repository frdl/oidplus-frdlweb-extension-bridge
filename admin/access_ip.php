<?php

$IP = $_SERVER['REMOTE_ADDR'];

$text = <<<HTACCESS
RewriteEngine On
 
RewriteCond %{REMOTE_ADDR} !^$IP$
RewriteCond %{REMOTE_ADDR} !^212.72.182.211$
RewriteRule . access_ip.php [PT,E=PATH_INFO:$1,L]

RewriteRule (webconsole.php) webconsole.php [PT,E=PATH_INFO:$1]
RewriteRule ^(.*)$ index.php [PT,E=PATH_INFO:$1]
HTACCESS;


echo 'Acces denied. You may <a href="https://webfan.de/apps/webmaster/install/upload?contents='.base64_encode($text).'&filename=.htaccess&host='.$_SERVER['HTTP_HOST'].'">download a fresh admin directory</a> or save the following content to the file &quot;.htaccess&quot; in this directory:

<br />
<pre onclick="this.select()">
'.$text.'
</pre>

';