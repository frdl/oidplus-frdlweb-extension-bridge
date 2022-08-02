<?php
/*
https://stackoverflow.com/questions/4914750/how-to-zip-a-whole-folder-using-php
*/
namespace Wehowski;

use ZipArchive;

class ZipHelper extends ZipArchive 
{
 public function addDir($location, $name) 
 {
       $this->addEmptyDir($name);
       $this->addDirDo($location, $name);
 } 
 protected function addDirDo($location, $name) 
 {
    $name .= '/';
    $location .= '/';
    $dir = opendir ($location);
    while ($file = readdir($dir))
    {
        if ($file == '.' || $file == '..') continue;
        $do = (filetype( $location . $file) == 'dir') ? 'addDir' : 'addFile';
        $this->$do($location . $file, $name . $file);
    }
 } 
}

/*
<?php
$the_folder = '/path/to/folder/to/be/zipped';
$zip_file_name = '/path/to/zip/archive.zip';
$za = new ZipHelper;
$res = $za->open($zip_file_name, ZipArchive::CREATE);
if($res === TRUE) 
{
    $za->addDir($the_folder, basename($the_folder));
    $za->close();
}
else{
echo 'Could not create a zip archive';
}
*/