<?php 
namespace webfan\hps\patch{


use DirectoryIterator;
use SplFileInfo;


class Fs
{
protected static $cacheRootDir=null;
protected static $cacheDir=null;
public static function cacheRoot(string $name = null){
	   if(is_string($name)){
		self::$cacheRootDir=$name;
	   }
   if(null === self::$cacheRootDir){
       self:: $cacheRootDir= self::tempDir(null);
   }
 return self::$cacheRootDir;
}

public static function tempDir( $name = null){
	    if(is_string($name)){
		return sys_get_temp_dir()
                                    . \DIRECTORY_SEPARATOR
                                     .$name
                                    . \DIRECTORY_SEPARATOR ;   
	   }elseif(false === $name){
		return sys_get_temp_dir() . \DIRECTORY_SEPARATOR;   
	   }elseif(true === $name){
		return sys_get_temp_dir()
                                    . \DIRECTORY_SEPARATOR
                                     .get_current_user() 
                                    . \DIRECTORY_SEPARATOR ;   
	   }elseif(null === $name){
              return  sys_get_temp_dir()
                                    . \DIRECTORY_SEPARATOR;   
            }


}
public static function getCacheDir(string $name = null){
 
          $base = self::cacheRoot();

	   if(null === $name){
		$name = '';   
	   }
	
	  $name = \preg_replace("/[^A-Za-z0-9\.\-\_\:\@]/", "", $name);

	 
	 return (empty($name)) ? $base  : $base. \DIRECTORY_SEPARATOR.$name. \DIRECTORY_SEPARATOR ;
   }
	

	
  /* by https://www.php.net/manual/de/function.chmod.php#105570 */	
  public static function chmod($path, $filemode, $dirmode, $r = true, &$log = null){
	if(null === $log){
	   $log = [];	
	}
    if (is_dir($path) ) {
        if (!chmod($path, $dirmode)) {
            $dirmode_str=decoct($dirmode);
            $e =[\E_USER_ERROR,  new \Exception("Failed applying filemode '$dirmode_str' on directory '$path'\n"
                                  . "  `-> the directory '$path' will be skipped from recursive chmod\n")];
			$log[] = $e;
            return $e;
        }
	   if(true === $r){
        $dh = opendir($path);
        while (($file = readdir($dh)) !== false) {
            if($file != '.' && $file != '..') { 
                $fullpath = $path.'/'.$file;
                self::chmod($fullpath, $filemode,$dirmode, true, $log);
            }
        }
        closedir($dh);
	   }
		
            $dirmode_str=decoct($dirmode);
            $e =[\E_USER_NOTICE,  "Done applying filemode '$dirmode_str' on directory '$path'\n"];
			$log[] = $e;
            return $e;	  		
		
    } else {
        if (is_link($path)) {
            $e = [\E_USER_NOTICE, new \Exception("link '$path' is skipped\n")];
			$log[] = $e;
            return $e;				
        }
        if (!chmod($path, $filemode)) {
            $filemode_str=decoct($filemode);
            $e = [\E_USER_ERROR, new \Exception("Failed applying filemode '$filemode_str' on file '$path'\n")];
			$log[] = $e;
            return $e;						
        }
		
	        
		    $filemode_str=decoct($filemode);
            $e =[\E_USER_NOTICE,  "Done applying filemode '$filemode_str' on file '$path'\n"];
			$log[] = $e;
            return $e;		
    } 

} 
	
	
 public static function mostRecentModified(string $dirName,bool $doRecursive=null, array $extensions = null, array $excludeExtensions = null) {
    $d = dir($dirName);
    if(null === $extensions){
	$extensions=['*'];    
    }
    if(null === $excludeExtensions){
	$excludeExtensions=[];    
    }
    if(null===$doRecursive){
	$doRecursive=true;    
    }
    $lastModified = [0, null,null];
    while($entry = $d->read()) {
        if ($entry != "." && $entry != "..") {
            if (!is_dir($dirName."/".$entry)) {
		$fileInfo = new SplFileInfo($dirName."/".$entry);
                $currentModified = ['filemtime'=>$fileInfo->getMTime(), 'path'=>$fileInfo->getPathname(), 'extension'=>$fileInfo->getExtension()];
            } else if (true===$doRecursive && is_dir($dirName."/".$entry)) {
                $currentModified = self::mostRecentModified($dirName."/".$entry,true,$extensions,$excludeExtensions);
            }
            if ($currentModified['filemtime'] > $lastModified['filemtime']
	        && (in_array('*', $extensions) || in_array($currentModified['extension'], $extensions)) 
	        && !in_array($currentModified['extension'], $excludeExtensions)){
                $lastModified = $currentModified;
            }
        }
    }
    $d->close();
    return $lastModified;
 }
	
	
	
/*https://www.startutorial.com/articles/view/deployment-script-in-php*/	
 public static function recursiveCopyDir($srcDir, $destDir){
    foreach (new DirectoryIterator($srcDir) as $fileInfo) {
        if ($fileInfo->isDot()) {
            continue;
        }
 
        if (!file_exists($destDir)) {
           shell_exec('mkdir -p '.$destDir);
        }
 
        $copyTo = $destDir . '/' . $fileInfo->getFilename();
 
        copy($fileInfo->getRealPath(), $copyTo);
    }
 }
 
 public static function copyFileToDir($src, $desDir){
    if (!file_exists($desDir)) {
        shell_exec('mkdir -p '.$desDir);
    }
 
    $fileInfo = new SplFileInfo($src);
 
    $copyTo = $desDir . '/' . $fileInfo->getFilename();
 
    copy($fileInfo->getRealPath(), $copyTo);
 }
	
 public static function copy($src, $desDir){
     if(is_dir($src)){
		 self::copy($src, $desDir);
	 }elseif(is_file($src)){
		 self::copyFileToDir($src, $desDir);
	 }
 }
	
 public static function remove($dir){
    shell_exec('rm -rf '.$dir);
 }	
	
 public static function compress($buffer) {
        /* remove comments */
        $buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", "", $buffer);
        /* remove tabs, spaces, newlines, etc. */
        $buffer = str_replace(array("\r\n","\r","\t","\n",'  ','    ','     '), '', $buffer);
        /* remove other spaces before/after ) */
        $buffer = preg_replace(array('(( )+\))','(\)( )+)'), ')', $buffer);
        return $buffer;
  }
	
	
 public static function filePrune($filename,$maxfilesize = 4096, $pruneStart = true){
	 
	 if(filesize($filename) < $maxfilesize){
		return; 
	 }
	 
	 $maxfilesize = min($maxfilesize, filesize($filename));
     $maxfilesize = max($maxfilesize, 0);
	 
	 if(true!==$pruneStart){
		 $fp = fopen($filename, "r+");
         ftruncate($fp, $maxfilesize);
         fclose($fp);
		 return;
	 }
	 
        $size=filesize($filename);
        if ($size<$maxfilesize*1.0) return;
        $maxfilesize=$maxfilesize*0.5; //we don't want to do it too often...
        $fh=fopen($filename,"r+");
        $start=ftell($fh);
        fseek($fh,-$maxfilesize,SEEK_END);
        $drop=fgets($fh);
        $offset=ftell($fh);
        for ($x=0;$x<$maxfilesize;$x++){
            fseek($fh,$x+$offset);
            $c=fgetc($fh);
            fseek($fh,$x);
            fwrite($fh,$c);
        }
        ftruncate($fh,$maxfilesize-strlen($drop));
        fclose($fh);
 }
	
	
public static function getRootDir($path = null){
	if(null===$path){
		$path = $_SERVER['DOCUMENT_ROOT'];
	}

		
 if(''!==dirname($path) && '/'!==dirname($path) //&& @chmod(dirname($path), 0755) 
    &&  true===@is_writable(dirname($path))
    ){
 	return self::getRootDir(dirname($path));
 }else{
 	return $path;
 }

}
	
public static function getPathUrl($dir = null, $absolute = true){
	if(null===$dir){
	//	$dir = dirname($_SERVER['PHP_SELF']);
		$dir = getcwd();
	}elseif(is_file($dir)){
	  $dir = dirname($dir);	
	}

    $root = "";
    $dir = str_replace('\\', '/', realpath($dir));

    //HTTPS or HTTP
    $root .= ($absolute) ? (!empty($_SERVER['HTTPS']) ? 'https' : 'http') : '';

    //HOST
    $root .= ($absolute) ? '://' . $_SERVER['HTTP_HOST'] : '';

    //ALIAS
    if(!empty($_SERVER['CONTEXT_PREFIX'])) {
        $root .= $_SERVER['CONTEXT_PREFIX'];
        $root .= substr($dir, strlen($_SERVER[ 'CONTEXT_DOCUMENT_ROOT' ]));
    } else {
        $root .= substr($dir, strlen($_SERVER[ 'DOCUMENT_ROOT' ]));
    }

    $root .= '/';

    return $root;
}
	
	
public static function getRelativePath($from, $to){
    // some compatibility fixes for Windows paths
  //   $from = is_dir($from) ? rtrim($from, \DIRECTORY_SEPARATOR) .  \DIRECTORY_SEPARATOR : $from;
  //   $to   = is_dir($to)   ? rtrim($to,  \DIRECTORY_SEPARATOR) .  \DIRECTORY_SEPARATOR   : $to;
	
    $from = str_replace('\\',  \DIRECTORY_SEPARATOR, $from);
    $to   = str_replace('\\',  \DIRECTORY_SEPARATOR, $to);

    $from     = explode( \DIRECTORY_SEPARATOR, $from);
    $to       = explode( \DIRECTORY_SEPARATOR, $to);
    $relPath  = $to;

    foreach($from as $depth => $dir) {
        // find first non-matching dir
        if($dir === $to[$depth]) {
            // ignore this directory
            array_shift($relPath);
        } else {
            // get number of remaining dirs to $from
            $remaining = count($from) - $depth;
            if($remaining > 1) {
                // add traversals up to first matching dir
                $padLength = (count($relPath) + $remaining - 1) * -1;
                $relPath = array_pad($relPath, $padLength, '..');
                break;
            } else {
                $relPath[0] = '.'. \DIRECTORY_SEPARATOR . $relPath[0];
            }
        }
    }
    return implode( \DIRECTORY_SEPARATOR, $relPath);
}
	
	
public static function pruneDir($dir, $limit, $skipDotFiles = true, $remove = false){
 $iterator = new \DirectoryIterator($dir);
 $c = 0;
 $all = 0;	
 foreach ($iterator as $fileinfo) {
    if ($fileinfo->isFile()) {
		$c++;
		if(true===$skipDotFiles && '.'===substr($fileinfo->getFilename(),0,1))continue;
             // if($fileinfo->getMTime() < time() - $limit){
		if(filemtime($fileinfo->getPathname()) < time() - $limit){
			if(file_exists($fileinfo->getPathname()) && is_file($fileinfo->getPathname())
			    && strlen(realpath($fileinfo->getPathname())) > strlen(realpath($dir))
			  ){
				//  echo $fileinfo->getPathname();
			//  @chmod(dirname($fileinfo->getPathname()), 0775);	
			//  @chmod($fileinfo->getPathname(), 0775);
			    unlink($fileinfo->getPathname());
				$c=$c-1;
			}	
		}
    }elseif ($fileinfo->isDir()){
    	     $firstToken = substr(basename($fileinfo->getPathname()),0,1);	
		       if('.'===$firstToken)continue;

            $subdir = rtrim($fileinfo->getPathname(),'/ ') . DIRECTORY_SEPARATOR;
		    $all += self::pruneDir($subdir, $limit, $skipDotFiles, true);
	 
	}
 }//foreach ($iterator as $fileinfo) 
	
	if(true === $remove && 0 === max($c, $all)){
		 @rmdir($dir);
	}
	
	return $c;
}	
	
	
  public static function rglob($pattern, $flags = 0, $traversePostOrder = false) {
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
        $subDirContent = self::rglob($dir . '/**/' . implode('/**/', $patternParts), $flags, $traversePostOrder);

        if (!$traversePostOrder) {
            $files = array_merge($files, $subDirContent);
        } else {
            $files = array_merge($subDirContent, $files);
        }
    }

    return (!is_array($files)) ? [] : $files;
 }
	
public static function rglob2($root, $pattern = "*"){
$fname=$pattern;
$result=[];
foreach(self::globdirs($root) as $dir) {
    $match=glob($dir.'/'.$fname,GLOB_NOSORT);
    if(!$match) continue;
    $result=array_merge($result,$match);
}
	return $result;
}
	
	
public static function globdirs($start) {
    $dirStack=[$start];
    while($dir=array_shift($dirStack)) {
        $ar=glob($dir.'/*',\GLOB_ONLYDIR|\GLOB_NOSORT);
        if(!$ar) continue;

        $dirStack=array_merge($dirStack,$ar);
        foreach($ar as $DIR)
            yield $DIR;
    }
}

	
}

}



namespace Nette{
  
 
  /**
  * Static class.
   */
 trait StaticClass
 {
 
     /**
       * @throws \LogicException
      */
    final public function __construct()
     {
          throw new \LogicException('Class ' . get_class($this) . ' is static and cannot be instantiated.');
     }
 
 
     /**
     * Call to undefined static method.
     * @throws MemberAccessException
      */
      public static function __callStatic($name, $args)
      {
        Utils\ObjectHelpers::strictStaticCall(get_called_class(), $name);
     }
  }
}
namespace Nette{

use Nette\Utils\ObjectHelpers;


/**
 * Strict class for better experience.
 * - 'did you mean' hints
 * - access to undeclared members throws exceptions
 * - support for @property annotations
 * - support for calling event handlers stored in $onEvent via onEvent()
 */
trait SmartObject
{
	/**
	 * @throws MemberAccessException
	 */
	public function __call(string $name, array $args)
	{
		$class = static::class;

		if (ObjectHelpers::hasProperty($class, $name) === 'event') { // calling event handlers
			$handlers = $this->$name ?? null;
			if (is_iterable($handlers)) {
				foreach ($handlers as $handler) {
					$handler(...$args);
				}
			} elseif ($handlers !== null) {
				throw new UnexpectedValueException("Property $class::$$name must be iterable or null, " . gettype($handlers) . ' given.');
			}

		} else {
			ObjectHelpers::strictCall($class, $name);
		}
	}


	/**
	 * @throws MemberAccessException
	 */
	public static function __callStatic(string $name, array $args)
	{
		ObjectHelpers::strictStaticCall(static::class, $name);
	}


	/**
	 * @return mixed
	 * @throws MemberAccessException if the property is not defined.
	 */
	public function &__get(string $name)
	{
		$class = static::class;

		if ($prop = ObjectHelpers::getMagicProperties($class)[$name] ?? null) { // property getter
			if (!($prop & 0b0001)) {
				throw new MemberAccessException("Cannot read a write-only property $class::\$$name.");
			}
			$m = ($prop & 0b0010 ? 'get' : 'is') . $name;
			if ($prop & 0b0100) { // return by reference
				return $this->$m();
			} else {
				$val = $this->$m();
				return $val;
			}
		} else {
			ObjectHelpers::strictGet($class, $name);
		}
	}


	/**
	 * @param  mixed  $value
	 * @return void
	 * @throws MemberAccessException if the property is not defined or is read-only
	 */
	public function __set(string $name, $value)
	{
		$class = static::class;

		if (ObjectHelpers::hasProperty($class, $name)) { // unsetted property
			$this->$name = $value;

		} elseif ($prop = ObjectHelpers::getMagicProperties($class)[$name] ?? null) { // property setter
			if (!($prop & 0b1000)) {
				throw new MemberAccessException("Cannot write to a read-only property $class::\$$name.");
			}
			$this->{'set' . $name}($value);

		} else {
			ObjectHelpers::strictSet($class, $name);
		}
	}


	/**
	 * @return void
	 * @throws MemberAccessException
	 */
	public function __unset(string $name)
	{
		$class = static::class;
		if (!ObjectHelpers::hasProperty($class, $name)) {
			throw new MemberAccessException("Cannot unset the property $class::\$$name.");
		}
	}


	public function __isset(string $name): bool
	{
		return isset(ObjectHelpers::getMagicProperties(static::class)[$name]);
	}
}

}

namespace Nette\Utils{

use Nette;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


/**
 * Finder allows searching through directory trees using iterator.
 *
 * <code>
 * Finder::findFiles('*.php')
 *     ->size('> 10kB')
 *     ->from('.')
 *     ->exclude('temp');
 * </code>
 */
class Finder implements \IteratorAggregate, \Countable
{
	use Nette\SmartObject;
   
	/** @var callable  extension methods */
	private static $extMethods = [];

	private array $paths = [];

	/** filters */
	private array $groups = [];

	/** filter for recursive traversing */
	private array $exclude = [];

	private int $order = RecursiveIteratorIterator::SELF_FIRST;

	private int $maxDepth = -1;

	private ?array $cursor;


	/**
	 * Begins search for files and directories matching mask.
	 * @param  string|string[]  $masks
	 */
	public static function find( ): self
	{
		$masks=func_get_args();
		$masks = $masks && is_array($masks[0]) ? $masks[0] : $masks;
		return (new static)->select($masks, 'isDir')->select($masks, 'isFile');
	}


	/**
	 * Begins search for files matching mask.
	 * @param  string|string[]  $masks
	 */
	public static function findFiles(): self
	{
		$masks=func_get_args();
		$masks = $masks && is_array($masks[0]) ? $masks[0] : $masks;
		return (new static)->select($masks, 'isFile');
	}


	/**
	 * Begins search for directories matching mask.
	 * @param  string|string[]  $masks
	 */
	public static function findDirectories( ): self
	{

		$masks=func_get_args();
		$masks = $masks && is_array($masks[0]) ? $masks[0] : $masks;
		return (new static)->select($masks, 'isDir');
	}


	/**
	 * Creates filtering group by mask & type selector.
	 */
	private function select(array $masks, string $type): self
	{
		$this->cursor = &$this->groups[];
		$pattern = self::buildPattern($masks);
		$this->filter(fn(RecursiveDirectoryIterator $file): bool => !$file->isDot()
				&& $file->$type()
				&& (!$pattern || preg_match($pattern, '/' . strtr($file->getSubPathName(), '\\', '/'))));
		return $this;
	}


	/**
	 * Searches in the given folder(s).
	 * @param  string|string[]  $paths
	 */
 
	public function in(): self
	{
		$paths=func_get_args();
		$this->maxDepth = 0;
		return $this->from(...$paths);
	}


	/**
	 * Searches recursively from the given folder(s).
	 * @param  string|string[]  $paths
	
	public function from(...$paths): static */
	public function from(): self
	{
		$paths=func_get_args();
		if ($this->paths) {
			throw new Nette\InvalidStateException('Directory to search has already been specified.');
		}
		$this->paths = is_array($paths[0]) ? $paths[0] : $paths;
		$this->cursor = &$this->exclude;
		return $this;
	}


	/**
	 * Shows folder content prior to the folder.
	 */
	public function childFirst(): self
	{
		$this->order = RecursiveIteratorIterator::CHILD_FIRST;
		return $this;
	}


	/**
	 * Converts Finder pattern to regular expression.
	 */
	private static function buildPattern(array $masks): ?string
	{
		$pattern = [];
		foreach ($masks as $mask) {
			$mask = rtrim(strtr($mask, '\\', '/'), '/');
			$prefix = '';
			if ($mask === '') {
				continue;

			} elseif ($mask === '*') {
				return null;

			} elseif ($mask[0] === '/') { // absolute fixing
				$mask = ltrim($mask, '/');
				$prefix = '(?<=^/)';
			}
			$pattern[] = $prefix . strtr(
				preg_quote($mask, '#'),
				['\*\*' => '.*', '\*' => '[^/]*', '\?' => '[^/]', '\[\!' => '[^', '\[' => '[', '\]' => ']', '\-' => '-'],
			);
		}
		return $pattern ? '#/(' . implode('|', $pattern) . ')$#Di' : null;
	}


	/********************* iterator generator ****************d*g**/


	/**
	 * Get the number of found files and/or directories.
	 */
	public function count(): int
	{
		return iterator_count($this->getIterator());
	}


	/**
	 * Returns iterator.
	 */
	public function getIterator(): \Iterator
	{
		if (!$this->paths) {
			throw new Nette\InvalidStateException('Call in() or from() to specify directory to search.');

		} elseif (count($this->paths) === 1) {
			return $this->buildIterator((string) $this->paths[0]);
		}

		$iterator = new \AppendIterator;
		foreach ($this->paths as $path) {
			$iterator->append($this->buildIterator((string) $path));
		}
		return $iterator;
	}


	/**
	 * Returns per-path iterator.
	 */
	private function buildIterator(string $path): \Iterator
	{
		$iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::FOLLOW_SYMLINKS);

		if ($this->exclude) {
			$iterator = new \RecursiveCallbackFilterIterator($iterator, function ($foo, $bar, RecursiveDirectoryIterator $file): bool {
				if (!$file->isDot() && !$file->isFile()) {
					foreach ($this->exclude as $filter) {
						if (!$filter($file)) {
							return false;
						}
					}
				}
				return true;
			});
		}

		if ($this->maxDepth !== 0) {
			$iterator = new RecursiveIteratorIterator($iterator, $this->order);
			$iterator->setMaxDepth($this->maxDepth);
		}

		$iterator = new \CallbackFilterIterator($iterator, function ($foo, $bar, \Iterator $file): bool {
			while ($file instanceof \OuterIterator) {
				$file = $file->getInnerIterator();
			}

			foreach ($this->groups as $filters) {
				foreach ($filters as $filter) {
					if (!$filter($file)) {
						continue 2;
					}
				}
				return true;
			}
			return false;
		});

		return $iterator;
	}


	/********************* filtering ****************d*g**/


	/**
	 * Restricts the search using mask.
	 * Excludes directories from recursive traversing.
	 * @param  string|string[]  $masks
	 */
	public function exclude( ): self
	{
		$masks=func_get_args();
		$masks = $masks && is_array($masks[0]) ? $masks[0] : $masks;
		$pattern = self::buildPattern($masks);
		if ($pattern) {
			$this->filter(fn(RecursiveDirectoryIterator $file): bool => !preg_match($pattern, '/' . strtr($file->getSubPathName(), '\\', '/')));
		}
		return $this;
	}


	/**
	 * Restricts the search using callback.
	 * @param  callable  $callback  function (RecursiveDirectoryIterator $file): bool
	 */
	public function filter(callable $callback): self
	{
		$this->cursor[] = $callback;
		return $this;
	}


	/**
	 * Limits recursion level.
	 */
	public function limitDepth(int $depth): self
	{
		$this->maxDepth = $depth;
		return $this;
	}


	/**
	 * Restricts the search by size.
	 * @param  string  $operator  "[operator] [size] [unit]" example: >=10kB
	 */
	public function size(string $operator, int $size = null): self
	{
		if (func_num_args() === 1) { // in $operator is predicate
			if (!preg_match('#^(?:([=<>!]=?|<>)\s*)?((?:\d*\.)?\d+)\s*(K|M|G|)B?$#Di', $operator, $matches)) {
				throw new Nette\InvalidArgumentException('Invalid size predicate format.');
			}
			[, $operator, $size, $unit] = $matches;
			static $units = ['' => 1, 'k' => 1e3, 'm' => 1e6, 'g' => 1e9];
			$size *= $units[strtolower($unit)];
			$operator = $operator ?: '=';
		}
		return $this->filter(fn(RecursiveDirectoryIterator $file): bool => self::compare($file->getSize(), $operator, $size));
	}


	/**
	 * Restricts the search by modified time.
	 * @param  string  $operator  "[operator] [date]" example: >1978-01-23
	 */
	public function date(string $operator,/* string|int|\DateTimeInterface */$date = null): self
	{
		if (func_num_args() === 1) { // in $operator is predicate
			if (!preg_match('#^(?:([=<>!]=?|<>)\s*)?(.+)$#Di', $operator, $matches)) {
				throw new Nette\InvalidArgumentException('Invalid date predicate format.');
			}
			[, $operator, $date] = $matches;
			$operator = $operator ?: '=';
		}
		$date = DateTime::from($date)->format('U');
		return $this->filter(fn(RecursiveDirectoryIterator $file): bool => self::compare($file->getMTime(), $operator, $date));
	}


	/**
	 * Compares two values.
	 */
	public static function compare($l, string $operator, $r): bool
	{
		switch ($operator) {
			case '>':
				return $l > $r;
			case '>=':
				return $l >= $r;
			case '<':
				return $l < $r;
			case '<=':
				return $l <= $r;
			case '=':
			case '==':
				return $l == $r;
			case '!':
			case '!=':
			case '<>':
				return $l != $r;
			default:
				throw new Nette\InvalidArgumentException("Unknown operator $operator.");
		}
	}


	/********************* extension methods ****************d*g**/


	public function __call(string $name, array $args)
	{
		return isset(self::$extMethods[$name])
			? (self::$extMethods[$name])($this, ...$args)
			: Nette\Utils\ObjectHelpers::strictCall(static::class, $name, array_keys(self::$extMethods));
	}


	public static function extensionMethod(string $name, callable $callback): void
	{
		self::$extMethods[$name] = $callback;
	}
}

}




namespace Nette\Utils{

use Nette;


/**
 * File system tool.
 */
final class FileSystem
{
	use Nette\StaticClass;

	/**
	 * Creates a directory if it doesn't exist.
	 * @throws Nette\IOException  on error occurred
	 */
	public static function createDir(string $dir, int $mode = 0777): void
	{
		if (!is_dir($dir) && !@mkdir($dir, $mode, true) && !is_dir($dir)) { // @ - dir may already exist
			throw new Nette\IOException("Unable to create directory '$dir' with mode " . decoct($mode) . '. ' . Helpers::getLastError());
		}
	}


	/**
	 * Copies a file or a directory. Overwrites existing files and directories by default.
	 * @throws Nette\IOException  on error occurred
	 * @throws Nette\InvalidStateException  if $overwrite is set to false and destination already exists
	 */
	public static function copy(string $origin, string $target, bool $overwrite = true): void
	{
		if (stream_is_local($origin) && !file_exists($origin)) {
			throw new Nette\IOException("File or directory '$origin' not found.");

		} elseif (!$overwrite && file_exists($target)) {
			throw new Nette\InvalidStateException("File or directory '$target' already exists.");

		} elseif (is_dir($origin)) {
			static::createDir($target);
			foreach (new \FilesystemIterator($target) as $item) {
				static::delete($item->getPathname());
			}
			foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($origin, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
				if ($item->isDir()) {
					static::createDir($target . '/' . $iterator->getSubPathName());
				} else {
					static::copy($item->getPathname(), $target . '/' . $iterator->getSubPathName());
				}
			}

		} else {
			static::createDir(dirname($target));
			if (
				($s = @fopen($origin, 'rb'))
				&& ($d = @fopen($target, 'wb'))
				&& @stream_copy_to_stream($s, $d) === false
			) { // @ is escalated to exception
				throw new Nette\IOException("Unable to copy file '$origin' to '$target'. " . Helpers::getLastError());
			}
		}
	}


	/**
	 * Deletes a file or directory if exists.
	 * @throws Nette\IOException  on error occurred
	 */
	public static function delete(string $path): void
	{
		if (is_file($path) || is_link($path)) {
			$func = DIRECTORY_SEPARATOR === '\\' && is_dir($path) ? 'rmdir' : 'unlink';
			if (!@$func($path)) { // @ is escalated to exception
				throw new Nette\IOException("Unable to delete '$path'. " . Helpers::getLastError());
			}

		} elseif (is_dir($path)) {
			foreach (new \FilesystemIterator($path) as $item) {
				static::delete($item->getPathname());
			}
			if (!@rmdir($path)) { // @ is escalated to exception
				throw new Nette\IOException("Unable to delete directory '$path'. " . Helpers::getLastError());
			}
		}
	}


	/**
	 * Renames or moves a file or a directory. Overwrites existing files and directories by default.
	 * @throws Nette\IOException  on error occurred
	 * @throws Nette\InvalidStateException  if $overwrite is set to false and destination already exists
	 */
	public static function rename(string $origin, string $target, bool $overwrite = true): void
	{
		if (!$overwrite && file_exists($target)) {
			throw new Nette\InvalidStateException("File or directory '$target' already exists.");

		} elseif (!file_exists($origin)) {
			throw new Nette\IOException("File or directory '$origin' not found.");

		} else {
			static::createDir(dirname($target));
			if (realpath($origin) !== realpath($target)) {
				static::delete($target);
			}
			if (!@rename($origin, $target)) { // @ is escalated to exception
				throw new Nette\IOException("Unable to rename file or directory '$origin' to '$target'. " . Helpers::getLastError());
			}
		}
	}


	/**
	 * Reads the content of a file.
	 * @throws Nette\IOException  on error occurred
	 */
	public static function read(string $file): string
	{
		$content = @file_get_contents($file); // @ is escalated to exception
		if ($content === false) {
			throw new Nette\IOException("Unable to read file '$file'. " . Helpers::getLastError());
		}
		return $content;
	}


	/**
	 * Writes the string to a file.
	 * @throws Nette\IOException  on error occurred
	 */
	public static function write(string $file, string $content, ?int $mode = 0666): void
	{
		static::createDir(dirname($file));
		if (@file_put_contents($file, $content) === false) { // @ is escalated to exception
			throw new Nette\IOException("Unable to write file '$file'. " . Helpers::getLastError());
		}
		if ($mode !== null && !@chmod($file, $mode)) { // @ is escalated to exception
			throw new Nette\IOException("Unable to chmod file '$file' to mode " . decoct($mode) . '. ' . Helpers::getLastError());
		}
	}


	/**
	 * Fixes permissions to a specific file or directory. Directories can be fixed recursively.
	 * @throws Nette\IOException  on error occurred
	 */
	public static function makeWritable(string $path, int $dirMode = 0777, int $fileMode = 0666): void
	{
		if (is_file($path)) {
			if (!@chmod($path, $fileMode)) { // @ is escalated to exception
				throw new Nette\IOException("Unable to chmod file '$path' to mode " . decoct($fileMode) . '. ' . Helpers::getLastError());
			}
		} elseif (is_dir($path)) {
			foreach (new \FilesystemIterator($path) as $item) {
				static::makeWritable($item->getPathname(), $dirMode, $fileMode);
			}
			if (!@chmod($path, $dirMode)) { // @ is escalated to exception
				throw new Nette\IOException("Unable to chmod directory '$path' to mode " . decoct($dirMode) . '. ' . Helpers::getLastError());
			}
		} else {
			throw new Nette\IOException("File or directory '$path' not found.");
		}
	}


	/**
	 * Determines if the path is absolute.
	 */
	public static function isAbsolute(string $path): bool
	{
		return (bool) preg_match('#([a-z]:)?[/\\\\]|[a-z][a-z0-9+.-]*://#Ai', $path);
	}


	/**
	 * Normalizes `..` and `.` and directory separators in path.
	 */
	public static function normalizePath(string $path): string
	{
		$parts = $path === '' ? [] : preg_split('~[/\\\\]+~', $path);
		$res = [];
		foreach ($parts as $part) {
			if ($part === '..' && $res && end($res) !== '..' && end($res) !== '') {
				array_pop($res);
			} elseif ($part !== '.') {
				$res[] = $part;
			}
		}
		return $res === ['']
			? DIRECTORY_SEPARATOR
			: implode(DIRECTORY_SEPARATOR, $res);
	}


	/**
	 * Joins all segments of the path and normalizes the result.
	 */
	public static function joinPaths(string ...$paths): string
	{
		return self::normalizePath(implode('/', $paths));
	}
}

}

namespace Webfan\Nette\Loaders{

use Nette;
use SplFileInfo;


/**
 * Nette auto loader is responsible for loading classes and interfaces.
 *
 * <code>
 * $loader = new Nette\Loaders\RobotLoader;
 * $loader->addDirectory('app');
 * $loader->excludeDirectory('app/exclude');
 * $loader->setTempDirectory('temp');
 * $loader->register();
 * </code>
 */
class RobotLoader
{
	use Nette\SmartObject;

	private const RETRY_LIMIT = 3;

	/** @var string[] */
	public $ignoreDirs = ['.*', '*.old', '*.bak', '*.tmp', 'temp'];

	/** @var string[] */
	public $acceptFiles = ['*.php'];

	/** @var bool */
	private $autoRebuild = true;

	/** @var bool */
	private $reportParseErrors = true;

	/** @var string[] */
	private $scanPaths = [];

	/** @var string[] */
	private $excludeDirs = [];

	/** @var array of class => [file, time] */
	private $classes = [];

	/** @var bool */
	private $cacheLoaded = false;

	/** @var bool */
	private $refreshed = false;

	/** @var array of missing classes */
	private $missing = [];

	/** @var string|null */
	private $tempDirectory;


	public function __construct()
	{
		if (!extension_loaded('tokenizer')) {
			throw new Nette\NotSupportedException('PHP extension Tokenizer is not loaded.');
		}
	}


	/**
	 * Register autoloader.
	 */
	public function register(bool $prepend = false) 
	{
		spl_autoload_register([$this, 'tryLoad'], true, $prepend);
		return $this;
	}


	/**
	 * Handles autoloading of classes, interfaces or traits.
	 */
	public function tryLoad(string $type): void
	{
		$this->loadCache();
		$type = ltrim($type, '\\'); // PHP namespace bug #49143
		$info = $this->classes[$type] ?? null;

		if ($this->autoRebuild) {
			if (!$info || !is_file($info['file'])) {
				$missing = &$this->missing[$type];
				$missing++;
				if (!$this->refreshed && $missing <= self::RETRY_LIMIT) {
					$this->refreshClasses();
					$this->saveCache();
				} elseif ($info) {
					unset($this->classes[$type]);
					$this->saveCache();
				}

			} elseif (!$this->refreshed && filemtime($info['file']) !== $info['time']) {
				$this->updateFile($info['file']);
				if (empty($this->classes[$type])) {
					$this->missing[$type] = 0;
				}
				$this->saveCache();
			}
			$info = $this->classes[$type] ?? null;
		}

		if ($info) {
			(static function ($file) { require $file; })($info['file']);
		}
	}


	/**
	 * Add path or paths to list.
	 * @param  string  ...$paths  absolute path
	 */
	public function addDirectory(...$paths): self
	{
		if (is_array($paths[0] ?? null)) {
			trigger_error(__METHOD__ . '() use variadics ...$paths to add an array of paths.', E_USER_WARNING);
			$paths = $paths[0];
		}
		$this->scanPaths = array_merge($this->scanPaths, $paths);
		return $this;
	}


	public function reportParseErrors(bool $on = true): self
	{
		$this->reportParseErrors = $on;
		return $this;
	}


	/**
	 * Excludes path or paths from list.
	 * @param  string  ...$paths  absolute path
	 */
	public function excludeDirectory(...$paths): self
	{
		if (is_array($paths[0] ?? null)) {
			trigger_error(__METHOD__ . '() use variadics ...$paths to add an array of paths.', E_USER_WARNING);
			$paths = $paths[0];
		}
		$this->excludeDirs = array_merge($this->excludeDirs, $paths);
		return $this;
	}


	/**
	 * @return array of class => filename
	 */
	public function getIndexedClasses(): array
	{
		$this->loadCache();
		$res = [];
		foreach ($this->classes as $class => $info) {
			$res[$class] = $info['file'];
		}
		return $res;
	}


	/**
	 * Rebuilds class list cache.
	 */
	public function rebuild(): void
	{
		$this->cacheLoaded = true;
		$this->classes = $this->missing = [];
		$this->refreshClasses();
		if ($this->tempDirectory) {
			$this->saveCache();
		}
	}


	/**
	 * Refreshes class list cache.
	 */
	public function refresh(): void
	{
		$this->loadCache();
		if (!$this->refreshed) {
			$this->refreshClasses();
			$this->saveCache();
		}
	}


	/**
	 * Refreshes $classes.
	 */
	private function refreshClasses(): void
	{
		$this->refreshed = true; // prevents calling refreshClasses() or updateFile() in tryLoad()
		$files = [];
		foreach ($this->classes as $class => $info) {
			$files[$info['file']]['time'] = $info['time'];
			$files[$info['file']]['classes'][] = $class;
		}

		$this->classes = [];
		foreach ($this->scanPaths as $path) {
			$iterator = is_file($path) ? [new SplFileInfo($path)] : $this->createFileIterator($path);
			foreach ($iterator as $file) {
				$file = $file->getPathname();
				if (isset($files[$file]) && $files[$file]['time'] == filemtime($file)) {
					$classes = $files[$file]['classes'];
				} else {
					$classes = $this->scanPhp($file);
				}
				$files[$file] = ['classes' => [], 'time' => filemtime($file)];

				foreach ($classes as $class) {
					$info = &$this->classes[$class];
					if (isset($info['file'])) {
						throw new Nette\InvalidStateException("Ambiguous class $class resolution; defined in {$info['file']} and in $file.");
					}
					$info = ['file' => $file, 'time' => filemtime($file)];
					unset($this->missing[$class]);
				}
			}
		}
	}


	/**
	 * Creates an iterator scaning directory for PHP files, subdirectories and 'netterobots.txt' files.
	 * @throws Nette\IOException if path is not found
	 */
	private function createFileIterator(string $dir): Nette\Utils\Finder
	{
		if (!is_dir($dir)) {
			throw new Nette\IOException("File or directory '$dir' not found.");
		}

		if (is_string($ignoreDirs = $this->ignoreDirs)) {
			trigger_error(__CLASS__ . ': $ignoreDirs must be an array.', E_USER_WARNING);
			$ignoreDirs = preg_split('#[,\s]+#', $ignoreDirs);
		}
		$disallow = [];
		foreach (array_merge($ignoreDirs, $this->excludeDirs) as $item) {
			if ($item = realpath($item)) {
				$disallow[str_replace('\\', '/', $item)] = true;
			}
		}

		if (is_string($acceptFiles = $this->acceptFiles)) {
			trigger_error(__CLASS__ . ': $acceptFiles must be an array.', E_USER_WARNING);
			$acceptFiles = preg_split('#[,\s]+#', $acceptFiles);
		}

		$iterator = Nette\Utils\Finder::findFiles($acceptFiles)
			->filter(function (SplFileInfo $file) use (&$disallow) {
				return !isset($disallow[str_replace('\\', '/', $file->getRealPath())]);
			})
			->from($dir)
			->exclude($ignoreDirs)
			->filter($filter = function (SplFileInfo $dir) use (&$disallow) {
				$path = str_replace('\\', '/', $dir->getRealPath());
				if (is_file("$path/netterobots.txt")) {
					foreach (file("$path/netterobots.txt") as $s) {
						if (preg_match('#^(?:disallow\\s*:)?\\s*(\\S+)#i', $s, $matches)) {
							$disallow[$path . rtrim('/' . ltrim($matches[1], '/'), '/')] = true;
						}
					}
				}
				return !isset($disallow[$path]);
			});

		$filter(new SplFileInfo($dir));
		return $iterator;
	}


	private function updateFile(string $file): void
	{
		foreach ($this->classes as $class => $info) {
			if (isset($info['file']) && $info['file'] === $file) {
				unset($this->classes[$class]);
			}
		}

		$classes = is_file($file) ? $this->scanPhp($file) : [];
		foreach ($classes as $class) {
			$info = &$this->classes[$class];
			if (isset($info['file']) && @filemtime($info['file']) !== $info['time']) { // @ file may not exists
				$this->updateFile($info['file']);
				$info = &$this->classes[$class];
			}
			if (isset($info['file'])) {
				throw new Nette\InvalidStateException("Ambiguous class $class resolution; defined in {$info['file']} and in $file.");
			}
			$info = ['file' => $file, 'time' => filemtime($file)];
		}
	}


	/**
	 * Searches classes, interfaces and traits in PHP file.
	 * @return string[]
	 */
	private function scanPhp(string $file): array
	{
		$code = file_get_contents($file);
		$expected = false;
		$namespace = $name = '';
		$level = $minLevel = 0;
		$classes = [];

		try {
			$tokens = token_get_all($code, TOKEN_PARSE);
		} catch (\ParseError $e) {
			if ($this->reportParseErrors) {
				$rp = new \ReflectionProperty($e, 'file');
				$rp->setAccessible(true);
				$rp->setValue($e, $file);
				throw $e;
			}
			$tokens = [];
		}

		foreach ($tokens as $token) {
			if (is_array($token)) {
				switch ($token[0]) {
					case T_COMMENT:
					case T_DOC_COMMENT:
					case T_WHITESPACE:
						continue 2;

					case T_NS_SEPARATOR:
					case T_STRING:
						if ($expected) {
							$name .= $token[1];
						}
						continue 2;

					case T_NAMESPACE:
					case T_CLASS:
					case T_INTERFACE:
					case T_TRAIT:
						$expected = $token[0];
						$name = '';
						continue 2;
					case T_CURLY_OPEN:
					case T_DOLLAR_OPEN_CURLY_BRACES:
						$level++;
				}
			}

			if ($expected) {
				switch ($expected) {
					case T_CLASS:
					case T_INTERFACE:
					case T_TRAIT:
						if ($name && $level === $minLevel) {
							$classes[] = $namespace . $name;
						}
						break;

					case T_NAMESPACE:
						$namespace = $name ? $name . '\\' : '';
						$minLevel = $token === '{' ? 1 : 0;
				}

				$expected = null;
			}

			if ($token === '{') {
				$level++;
			} elseif ($token === '}') {
				$level--;
			}
		}
		return $classes;
	}


	/********************* caching ****************d*g**/


	/**
	 * Sets auto-refresh mode.
	 */
	public function setAutoRefresh(bool $on = true): self
	{
		$this->autoRebuild = $on;
		return $this;
	}


	/**
	 * Sets path to temporary directory.
	 */
	public function setTempDirectory(string $dir): self
	{
		Nette\Utils\FileSystem::createDir($dir);
		$this->tempDirectory = $dir;
		return $this;
	}


	/**
	 * Loads class list from cache.
	 */
	private function loadCache(): void
	{
		if ($this->cacheLoaded) {
			return;
		}
		$this->cacheLoaded = true;

		$file = $this->getCacheFile();

		// Solving atomicity to work everywhere is really pain in the ass.
		// 1) We want to do as little as possible IO calls on production and also directory and file can be not writable (#19)
		// so on Linux we include the file directly without shared lock, therefore, the file must be created atomically by renaming.
		// 2) On Windows file cannot be renamed-to while is open (ie by include() #11), so we have to acquire a lock.
		$lock = defined('PHP_WINDOWS_VERSION_BUILD')
			? $this->acquireLock("$file.lock", LOCK_SH)
			: null;

		$data = @include $file; // @ file may not exist
		if (is_array($data)) {
			[$this->classes, $this->missing] = $data;
			return;
		}

		if ($lock) {
			flock($lock, LOCK_UN); // release shared lock so we can get exclusive
		}
		$lock = $this->acquireLock("$file.lock", LOCK_EX);

		// while waiting for exclusive lock, someone might have already created the cache
		$data = @include $file; // @ file may not exist
		if (is_array($data)) {
			[$this->classes, $this->missing] = $data;
			return;
		}

		$this->classes = $this->missing = [];
		$this->refreshClasses();
		$this->saveCache($lock);
		// On Windows concurrent creation and deletion of a file can cause a error 'permission denied',
		// therefore, we will not delete the lock file. Windows is peace of shit.
	}


	/**
	 * Writes class list to cache.
	 */
	private function saveCache($lock = null): void
	{
		// we have to acquire a lock to be able safely rename file
		// on Linux: that another thread does not rename the same named file earlier
		// on Windows: that the file is not read by another thread
		$file = $this->getCacheFile();
		$lock = $lock ?: $this->acquireLock("$file.lock", LOCK_EX);
		$code = "<?php\nreturn " . var_export([$this->classes, $this->missing], true) . ";\n";

		if (file_put_contents("$file.tmp", $code) !== strlen($code) || !rename("$file.tmp", $file)) {
			@unlink("$file.tmp"); // @ file may not exist
			throw new \RuntimeException("Unable to create '$file'.");
		}
		if (function_exists('opcache_invalidate')) {
			@opcache_invalidate($file, true); // @ can be restricted
		}
	}


	private function acquireLock(string $file, int $mode)
	{
		$handle = @fopen($file, 'w'); // @ is escalated to exception
		if (!$handle) {
			throw new \RuntimeException("Unable to create file '$file'. " . error_get_last()['message']);
		} elseif (!@flock($handle, $mode)) { // @ is escalated to exception
			throw new \RuntimeException('Unable to acquire ' . ($mode & LOCK_EX ? 'exclusive' : 'shared') . " lock on file '$file'. " . error_get_last()['message']);
		}
		return $handle;
	}


	private function getCacheFile(): string
	{
		if (!$this->tempDirectory) {
			throw new \LogicException('Set path to temporary directory using setTempDirectory().');
		}
		return $this->tempDirectory . '/' . md5(serialize($this->getCacheKey())) . '.php';
	}


	protected function getCacheKey(): array
	{
		return [$this->ignoreDirs, $this->acceptFiles, $this->scanPaths, $this->excludeDirs];
	}
}

}



namespace Webfan\Traits{

trait Singleton {

    private static $__frdl__aInstance = array();


  //  private function __construct() {}

    public static function getInstance() {

       $args = func_get_args();

       $sClassName = get_called_class(); 

       if( !isset( self::$__frdl__aInstance[ $sClassName ] ) ) {
 		
		if( 
			isset($sClassName::$WEBFANTIZED_CLASS)
		&& is_array($sClassName::$WEBFANTIZED_CLASS) && isset($sClassName::$WEBFANTIZED_CLASS['onGetInstance'])
		&& is_callable([$oInstance,  $sClassName::$WEBFANTIZED_CLASS['onGetInstance']])){
          self::$__frdl__aInstance[ $sClassName ]  = call_user_func_array([$oInstance,
		                                     $sClassName::$WEBFANTIZED_CLASS['onGetInstance']], 
											 $args);
        }else{
		     if(\version_compare(PHP_VERSION, '5.6.0', '>=')){
                  self::$__frdl__aInstance[ $sClassName ]  = new $sClassName(...$args);
             } else {
                $reflect  = new \ReflectionClass($sClassName);
                self::$__frdl__aInstance[ $sClassName ]  = $reflect->newInstanceArgs($args);
              }
		}
		
	}	
		
		$oInstance = & self::$__frdl__aInstance[ $sClassName ];
       return $oInstance;
    }

   // final private function __clone() {}
}

}


namespace Nette\Loaders{

use Nette;
use SplFileInfo;

use Nette\Utils\FileSystem;
use Nette\Utils\Finder;

use Webfan\Nette\Loaders\RobotLoader;
/**
 * Nette auto loader is responsible for loading classes and interfaces.
 *
 * <code>
 * $loader = new Nette\Loaders\RobotLoader;
 * $loader->addDirectory('app');
 * $loader->excludeDirectory('app/exclude');
 * $loader->setTempDirectory('temp');
 * $loader->register();
 * </code>
 */
class FrdlRobotLoader extends RobotLoader
{
	//use Nette\SmartObject;

	protected const RETRY_LIMIT = 1;

	protected $useGlobal = false;
	/** @var string[] */
	public $ignoreDirs = ['.*', '*.old', '*.bak', '*.tmp', 'temp'];

	/** @var string[] */
	public $acceptFiles = ['*.php'];

	/** @var bool */
	protected $autoRebuild = true;

	/** @var bool */
	protected $reportParseErrors = true;

	/** @var string[] */
	protected $scanPaths = [];

	/** @var string[] */
	protected $excludeDirs = [];

	/** @var array of class => [file, time] */
	protected $classes = [];
	protected $global_classes_map = [];

	/** @var bool */
	protected $cacheLoaded = false;

	/** @var bool */
	protected $refreshed = false;

	/** @var array of missing classes */
	protected $missing = [];

	/** @var string|null */
	protected $tempDirectory;

	
	protected $isSorted = false;

	public function __construct(bool $useGlobal = null)
	{
		if (!extension_loaded('tokenizer')) {
			throw new Nette\NotSupportedException('PHP extension Tokenizer is not loaded.');
		}
		
		if(is_bool($useGlobal)){
		  $this->Global($useGlobal);
		}
		$this->isSorted = false;
	}






	public function Global(bool $useGlobal): RobotLoader
	{
		$this->useGlobal = $useGlobal;
		return $this;
	}
	/**
	 * Register autoloader.
	 */
	public function register(bool $prepend = false) 
	{
		//spl_autoload_register([$this, 'tryLoad'], true, $prepend);
		spl_autoload_register([$this, 'loadClass'], true, $prepend);
		return $this;
	}


	/**
	 * Handles autoloading of classes, interfaces or traits.
	 */
	public function loadClass(string $class):bool
	{
		$classFile = $this->getFile($class);
		if (false!==$classFile) {
			 (static function ($file) { 
				 require $file;
			 })($classFile);
			return class_exists($class, false);
		}else{
		  return false;	
		}
	}


	public function file(string $class)
	{
	   return $this->getFile($class);
	}
	
	
	public function getFile(string $type)
	{
      	
		$this->loadCache();
		
		$type = ltrim($type, '\\'); // PHP namespace bug #49143	
		
		
		if(true===$this->useGlobal
		//   && !isset($this->classes[$type]) 
		   && isset($this->global_classes_map[$type]) 
		   && file_exists($this->global_classes_map[$type]) 
		  ){
			//return $this->global_classes_map[$type];
			//$info = ['file' => $file, 'time' => filemtime($file)];
			$file = $this->global_classes_map[$type];
		//	$this->classes[$type] = ['file' => $file, 'time' => filemtime($file)];
			//$this->classes[$type] = ['file' => $file, 'time' => filemtime($file)];
			if(!isset($this->classes[$type]) ){
			  $this->updateFile($file);
			  $this->classes[$type] = ['file' => $file, 'time' => filemtime($file)];
			}
			
			//return $file;
			
		}
	
		$info = $this->classes[$type] ?? null;

		if ($this->autoRebuild) {
			if (!$info || !is_file($info['file'])) {
				$missing = &$this->missing[$type];
				$missing++;
				if (!$this->refreshed && $missing <= self::RETRY_LIMIT) {
					$this->refreshClasses();
					$this->saveCache();
				} elseif ($info) {
					unset($this->classes[$type]);
					$this->saveCache();
				}

			} elseif (!$this->refreshed && filemtime($info['file']) != $info['time']) {
				$this->updateFile($info['file']);
				if (empty($this->classes[$type])) {
					$this->missing[$type] = 0;
				}
				$this->saveCache();
			}
			$info = $this->classes[$type] ?? null;
		}

		if ($info) {
			 return (static function ($file) { return (file_exists($file))
				               ? $file
				               : false;
											 })($info['file']);
		}
		
		return false;
	}	
	
	
	/**
	 * Add path or paths to list.
	 * @param  string  ...$paths  absolute path
	 */
	public function addDirectory(...$paths): RobotLoader
	{
		if (is_array($paths[0] ?? null)) {
			trigger_error(__METHOD__ . '() use variadics ...$paths to add an array of paths.', E_USER_WARNING);
			$paths = $paths[0];
		}
		$this->scanPaths = array_merge($this->scanPaths, $paths);
		$this->isSorted = false;
		return $this;
	}

	public function addFromDirectoriesListConfigFile(string $DirectoriesListConfigFile): RobotLoader
	{
		if(file_exists($DirectoriesListConfigFile)){
			 $dirs = require $DirectoriesListConfigFile;
			 //foreach($dirs as $dir){
			//	$this->addDirectory($dir);
			// }
			if(is_array($dirs)){		
				
				if(isset($dirs['__cache_dir'])){
					$this->setTempDirectory($dirs['__cache_dir']);
				}
				
				if(isset($dirs['__include_dirs'])){
				 	$__include_dirs = $dirs['__include_dirs'];
				}else{
				    $__include_dirs = $dirs;
				}
								
			  $this->addDirectory(...$__include_dirs);
				
				if(isset($dirs['__exclude_dirs'])){
				 	  $this->excludeDirectory(...$dirs['__exclude_dirs']);
				}

				
				
			}
			
		}
		
		
		$this->isSorted = false;
		//$this->sort();
		return $this;
	}
	
	
	public function reportParseErrors(bool $on = true): RobotLoader
	{
		$this->reportParseErrors = $on;
		return $this;
	}


	/**
	 * Excludes path or paths from list.
	 * @param  string  ...$paths  absolute path
	 */
	public function excludeDirectory(...$paths): RobotLoader
	{
		if (is_array($paths[0] ?? null)) {
			trigger_error(__METHOD__ . '() use variadics ...$paths to add an array of paths.', E_USER_WARNING);
			$paths = $paths[0];
		}
		$this->excludeDirs = array_merge($this->excludeDirs, $paths);		
		$this->isSorted = false;
		return $this;
	}


	/**
	 * @return array of class => filename
	 */
	public function getIndexedClasses(): array
	{
	
		$this->loadCache();
		
	
		$res = [];
		foreach ($this->classes as $class => $info) {
			$res[$class] = $info['file'];
		}
		return $res;
	}


	/**
	 * Rebuilds class list cache.
	 */
	public function rebuild(): void
	{
		$this->cacheLoaded = true;
		$this->classes = $this->missing = [];
		$this->refreshClasses();
		if ($this->tempDirectory) {
			$this->saveCache();
		}
	}


	/**
	 * Refreshes class list cache.
	 */
	public function refresh(): void
	{
		$this->loadCache();
		if (!$this->refreshed) {
			$this->refreshClasses();
			$this->saveCache();
		}
	}


	/**
	 * Refreshes $classes.
	 */
	protected function refreshClasses(): void
	{
		$this->refreshed = true; // prevents calling refreshClasses() or updateFile() in tryLoad()
		$files = [];
		foreach ($this->classes as $class => $info) {
			$files[$info['file']]['time'] = $info['time'];
			$files[$info['file']]['classes'][] = $class;
		}

		$this->classes = [];
		foreach ($this->scanPaths as $path) {
		
			$iterator = is_file($path) ? [new SplFileInfo($path)] : $this->createFileIterator($path);		
			
			
			foreach ($iterator as $file) {
					$file = $file->getPathname();
					//print_r($file.' '.__METHOD__);
				
				//    $file = \webfan\hps\patch\Fs::getRelativePath(getenv('HOME'), $file);
			
				  $file = realpath($file);
				
				if (isset($files[$file]) 
					&& intval($files[$file]['time']) == filemtime($file)
				   
				   ) {
					$classes = $files[$file]['classes'];
				} else {
				//	try{
						
					$classes = $this->scanPhp($file);
					
				}
				//$files[$file] = ['classes' => [], 'time' => filemtime($file)];
               $files[$file] = ['classes' => $classes, 'time' => filemtime($file)];
		
				
				
				foreach ($classes as $class) {
					$info = &$this->classes[$class];
							
					
					if (isset($info['file'])) {
							//print_r($info);
						//throw new Nette\InvalidStateException("Ambiguous class $class resolution; defined in {$info['file']} and in $file.");
						 trigger_error("Ambiguous class $class resolution; defined in {$info['file']} and in $file.", \E_USER_WARNING);	
						 continue;
					}
					$info = ['file' => $file, 'time' => filemtime($file)];
					
					
					unset($this->missing[$class]);
				}
				
				
			}
		}
		
		
		
		
	}


	/**
	 * Creates an iterator scaning directory for PHP files, subdirectories and 'netterobots.txt' files.
	 * @throws Nette\IOException if path is not found
	 */
	protected function createFileIterator(string $dir): Nette\Utils\Finder
	{
		if (!is_dir($dir)) {
			throw new Nette\IOException("File or directory '$dir' not found.");
		}

		if (is_string($ignoreDirs = $this->ignoreDirs)) {
			trigger_error(__CLASS__ . ': $ignoreDirs must be an array.', E_USER_WARNING);
			$ignoreDirs = preg_split('#[,\s]+#', $ignoreDirs);
		}
		$disallow = [];
		foreach (array_merge($ignoreDirs, $this->excludeDirs) as $item) {
			if ($item = realpath($item)) {
				$disallow[str_replace('\\', '/', $item)] = true;
			}
		}

		if (is_string($acceptFiles = $this->acceptFiles)) {
			trigger_error(__CLASS__ . ': $acceptFiles must be an array.', E_USER_WARNING);
			$acceptFiles = preg_split('#[,\s]+#', $acceptFiles);
		}

		$iterator = Finder::findFiles($acceptFiles)
			->filter(function (SplFileInfo $file) use (&$disallow) {
				return !isset($disallow[str_replace('\\', '/', $file->getRealPath())]);
			})
			->from($dir)
			->exclude($ignoreDirs)
			->filter($filter = function (SplFileInfo $dir) use (&$disallow) {
				$path = str_replace('\\', '/', $dir->getRealPath());
				if (is_file("$path/netterobots.txt")) {
					foreach (file("$path/netterobots.txt") as $s) {
						if (preg_match('#^(?:disallow\\s*:)?\\s*(\\S+)#i', $s, $matches)) {
							$disallow[$path . rtrim('/' . ltrim($matches[1], '/'), '/')] = true;
						}
					}
				}
				return !isset($disallow[$path]);
			});

		$filter(new SplFileInfo($dir));
		return $iterator;
	}


	protected function updateFile(string $file): void
	{
		foreach ($this->classes as $class => $info) {
			if (isset($info['file']) && $info['file'] === $file) {
				unset($this->classes[$class]);
			}
		}

		$classes = is_file($file) ? $this->scanPhp($file) : [];
		foreach ($classes as $class) {
			$info = &$this->classes[$class];
			if (isset($info['file']) && filemtime($info['file']) != $info['time']) { // @ file may not exists
				$this->updateFile($info['file']);
				$info = &$this->classes[$class];
			}
			if (isset($info['file'])) {
				//throw new Nette\InvalidStateException("Ambiguous class $class resolution; defined in {$info['file']} and in $file.");
				 trigger_error("Ambiguous class $class resolution; defined in {$info['file']} and in $file.", \E_USER_WARNING);	
				 continue;
			}
			$info = ['file' => $file, 'time' => filemtime($file)];
		}
	}


	/**
	 * Searches classes, interfaces and traits in PHP file.
	 * @return string[]
	 */
	protected function scanPhp(string $file): array
	{
		$code = file_get_contents($file);
		
		
		
		$expected = false;
		$namespace = $name = '';
		$level = $minLevel = 0;
		$classes = [];

		try {
			$tokens = token_get_all($code, TOKEN_PARSE);
		} catch (\ParseError $e) {
		
			
			if ($this->reportParseErrors) {
				$rp = new \ReflectionProperty($e, 'file');
				$rp->setAccessible(true);
				$rp->setValue($e, $file);
			//	throw $e;
				 trigger_error($e->getMessage(), \E_USER_WARNING);
				return [];
			}
			$tokens = [];
		}

		
		
		
		foreach ($tokens as $token) {
			if (is_array($token)) {
				switch ($token[0]) {
					case T_COMMENT:
					case T_DOC_COMMENT:
					case T_WHITESPACE:
						continue 2;

					case T_NS_SEPARATOR:
					case T_STRING:
						if ($expected) {
							$name .= $token[1];
						}
						continue 2;

				    //testing functions:
					case T_FUNCTION: 	
					 //	die(__METHOD__.' '.__LINE__.' '.print_r($token,true));
					 //	trigger_error(__METHOD__.' '.__LINE__.' '.print_r($token,true), \E_USER_NOTICE); 
						$expected = $token[0];
						$name = '';
						continue 2;						
						
					case T_NAMESPACE:
					case T_CLASS:
					case T_INTERFACE:
					case T_TRAIT:
						$expected = $token[0];
						$name = '';
						continue 2;
					case T_CURLY_OPEN:
					case T_DOLLAR_OPEN_CURLY_BRACES:
						$level++;
				}
			}

			if ($expected) {
				switch ($expected) {
					
				    //testing functions:
					case T_FUNCTION: 
						
					case T_CLASS:
					case T_INTERFACE:
					case T_TRAIT:
						if ($name && $level === $minLevel) {
							$classes[] = $namespace . $name;
						}
						break;

					case T_NAMESPACE:
						$namespace = $name ? $name . '\\' : '';
						$minLevel = $token === '{' ? 1 : 0;
				}

				$expected = null;
			}

			if ($token === '{') {
				$level++;
			} elseif ($token === '}') {
				$level--;
			}
		}
		
			
		
		return $classes;
	}


	/********************* caching ****************d*g**/


	/**
	 * Sets auto-refresh mode.
	 */
	public function setAutoRefresh(bool $on = true): RobotLoader
	{
		$this->autoRebuild = $on;
		return $this;
	}

	public function pruneTempDirectory(int $limit = null): RobotLoader
	{
		if(0===$limit || !is_dir($this->tempDirectory) ){
		  return $this;	
		}
		
		
		   $lastFile = $this->getCacheFile();
		   $lastFile_2 = $this->getGlobalCacheFile();
		
		   $limit_1 = (file_exists($lastFile))
			        ? filemtime($lastFile) - 1
			        : time() - 1;
		
		   $lastFile_2 = (file_exists($lastFile_2))
			        ? filemtime($lastFile_2) - 1
			        : time() - 1;
		
		
		   $limit_def = time() - min($limit_1, $lastFile_2) - 1;		                     

		
		if(!is_int($limit) || $limit < 0){
		   $limit = $limit_def;
		}else{
			$limit = max($limit, $limit_def);
		}
		                    
	    \webfan\hps\patch\Fs::pruneDir($this->tempDirectory, $limit, true, false);
		return $this;
	}
	
	
	/**
	 * Sets path to temporary directory.
	 */
	public function setTempDirectory(string $dir): RobotLoader
	{
		FileSystem::createDir($dir);
		$this->tempDirectory = $dir;
		return $this;
	}


	/**
	 * Loads class list from cache.
	 */
	private function loadCache(): void
	{
		
			
		
		
		if ($this->cacheLoaded) {
			return;
		}
		$this->cacheLoaded = true;

		
		if(true===$this->useGlobal && file_exists($this->getGlobalCacheFile())){
			$this->global_classes_map = require $this->getGlobalCacheFile();
		}
		
		
		
		$file = $this->getCacheFile();

		
		
		// Solving atomicity to work everywhere is really pain in the ass.
		// 1) We want to do as little as possible IO calls on production and also directory and file can be not writable (#19)
		// so on Linux we include the file directly without shared lock, therefore, the file must be created atomically by renaming.
		// 2) On Windows file cannot be renamed-to while is open (ie by include() #11), so we have to acquire a lock.
		$lock = defined('PHP_WINDOWS_VERSION_BUILD')
			? $this->acquireLock("$file.lock", LOCK_SH)
			: null;

		
		
		
		$data = (!file_exists($file))
			  ? null 
			  : include $file; // @ file may not exist
		if (is_array($data)) {
			[$this->classes, $this->missing] = $data;
			$this->isSorted = false;
			return;
		}

		if ($lock) {
			flock($lock, LOCK_UN); // release shared lock so we can get exclusive
		}
		$lock = $this->acquireLock("$file.lock", LOCK_EX);

		// while waiting for exclusive lock, someone might have already created the cache
		$data = (!file_exists($file))
			  ? null 
			  : include $file; // @ file may not exist
		if (is_array($data)) {
			[$this->classes, $this->missing] = $data;
			$this->isSorted = false;
			return;
		}

		
		
		$this->classes = $this->missing = [];		
		$this->isSorted = false;
		
		$this->refreshClasses();
		$this->saveCache($lock);
		// On Windows concurrent creation and deletion of a file can cause a error 'permission denied',
		// therefore, we will not delete the lock file. Windows is peace of shit.
	}


	/**
	 * Writes class list to cache.
	 */
	private function saveCache($lock = null): void
	{
		// we have to acquire a lock to be able safely rename file
		// on Linux: that another thread does not rename the same named file earlier
		// on Windows: that the file is not read by another thread
		$file = $this->getCacheFile();
		$lock = $lock ?: $this->acquireLock("$file.lock", LOCK_EX);
		$code = "<?php\n"
		//."\$HOME = getenv('HOME');\n"
		."return " . var_export([$this->classes, $this->missing], true) . ";\n";

		
		//$code = str_replace("'".getenv('HOME'), '$HOME.\'', $code);
		
		
		if (file_put_contents("$file.tmp", $code) !== strlen($code) || !rename("$file.tmp", $file)) {
			unlink("$file.tmp"); // @ file may not exist
			throw new \RuntimeException("Unable to create '$file'.");
		}
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($file, true); // @ can be restricted
		}
		
		$this->saveGlobal();
	}

	protected function saveGlobal(){
		if(true!==$this->useGlobal){
			return $this;
		}
		
                $mapFile = $this->getGlobalCacheFile();
		$classes_map = array_merge([], $this->getIndexedClasses());

                ksort($classes_map);
	 
		 $map=[];

		foreach($classes_map as $class => $file){
			$k=$this->shortkeys($class, 2, 2, '~', '.', $mapFile);
                        if(!isset($map[$k['metafile']])){
                             $map[$k['metafile']]=[];
                        }
			$map[$k['metafile']][$class]= $file;
		}
	
		 foreach($map as $metafile => $classesChunk){
                        $_chunk_export_map = var_export( $classesChunk, true);
                   	
		$_chunk_phpCode_map = <<<PHPCODE
<?php
 
return  $_chunk_export_map;
PHPCODE;
		    
                     file_put_contents($metafile, $_chunk_phpCode_map);	
                 }
		
		$export_map = var_export($classes_map, true);		
	
		$phpCode_map = <<<PHPCODE
<?php
return $export_map;
PHPCODE;
			
	//	$phpCode_map = str_replace("'".getenv('HOME'), '$HOME.\'', $phpCode_map);
		
		file_put_contents($mapFile, $phpCode_map);		
		return $this;
	}
	
	
	public function getGlobalCacheFile(){
		if (!$this->tempDirectory) {
			throw new \LogicException('Set path to temporary directory using setTempDirectory().');
		}
		return $this->tempDirectory . '/' . 'config.global.classmap' . '.php';	  	
	}
	

	private function acquireLock(string $file, int $mode)
	{
		$handle = fopen($file, 'w'); // @ is escalated to exception
		if (!$handle) {
			throw new \RuntimeException("Unable to create file '$file'. " . error_get_last()['message']);
		} elseif (!flock($handle, $mode)) { // @ is escalated to exception
			throw new \RuntimeException('Unable to acquire ' . ($mode & LOCK_EX ? 'exclusive' : 'shared') . " lock on file '$file'. " . error_get_last()['message']);
		}
		return $handle;
	}


        public function shortkeys($className, $size=4, $suffixSize=4, $padding='~', $delimiter='.', $mapFile=null){
                 if(!is_string($mapFile)){
                      $mapFile = $this->getGlobalCacheFile();
                 }

              
                $class = trim($className, \DIRECTORY_SEPARATOR.' /\\');
                $namespaceBase = explode('\\', $class, 2)[0];  

                $sha1= sha1($class);
                $md5= md5($class);
                $len=strlen($class);

               $nspfx = str_pad( substr($namespaceBase, 0, $size), $size, $padding, \STR_PAD_RIGHT);
               $pfx = str_pad( substr($class, 0, $size), $size, $padding, \STR_PAD_RIGHT);

                 $start = implode('',
				      array_merge(
					     preg_split("/\//", $nspfx, -1, \PREG_SPLIT_NO_EMPTY),
						 preg_split("/\//", $pfx, -1, \PREG_SPLIT_NO_EMPTY) 
					  )
				 );

               $sfx = substr($class, -1 * $suffixSize);
               $sfx = str_pad($sfx,  $suffixSize, $padding, \STR_PAD_LEFT);
               return [
                 'metafile'=>dirname($mapFile). \DIRECTORY_SEPARATOR . 
				 strtolower(substr(basename($mapFile),0,-4).'-'.$start.$delimiter.$sfx.'.php'), 
                 'pfx'=>$pfx, 
                 'sfx'=>$sfx,
                 'name'=>$class,
                 'sha1'=>$sha1,
                 'md5'=>$md5,
                 'hash'=>$sha1.$len,
               ];
        }


	public function getCacheFile(): string
	{
		if (!$this->tempDirectory) {
			throw new \LogicException('Set path to temporary directory using setTempDirectory().');
		}
		return $this->tempDirectory . '/' . \sha1(serialize($this->getCacheKey())) . '.php';
	}


	public function getCacheKey(): array
	{
		$this->sort();
		return [get_class($this), $this->ignoreDirs, $this->acceptFiles, $this->scanPaths, $this->excludeDirs];
	}
	
	
	protected function sort(){
		if(true===$this->isSorted){
		  return;	
		}
		$this->isSorted = true;
		ksort($this->global_classes_map);
		sort($this->ignoreDirs);
		sort($this->acceptFiles);
		sort($this->scanPaths);
		sort($this->excludeDirs);		
	}
	
	
	
	
}


}








namespace frdl\implementation\psr4{

/**
 * An example of a general-purpose implementation that includes the optional
 * functionality of allowing multiple base directories for a single namespace
 * prefix.
 *
 * Given a foo-bar package of classes in the file system at the following
 * paths ...
 *
 *     /path/to/packages/foo-bar/
 *         src/
 *             Baz.php             # Foo\Bar\Baz
 *             Qux/
 *                 Quux.php        # Foo\Bar\Qux\Quux
 *         tests/
 *             BazTest.php         # Foo\Bar\BazTest
 *             Qux/
 *                 QuuxTest.php    # Foo\Bar\Qux\QuuxTest
 *
 * ... add the path to the class files for the \Foo\Bar\ namespace prefix
 * as follows:
 *
 *      <?php
 *      // instantiate the loader
 *      $loader = new \Example\LocalAutoloader;
 *
 *      // register the autoloader
 *      $loader->register();
 *
 *      // register the base directories for the namespace prefix
 *      $loader->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/src');
 *      $loader->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/tests');
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\Quux class from /path/to/packages/foo-bar/src/Qux/Quux.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\Quux;
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\QuuxTest class from /path/to/packages/foo-bar/tests/Qux/QuuxTest.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\QuuxTest;
 */
class LocalAutoloader
{
    /**
     * An associative array where the key is a namespace prefix and the value
     * is an array of base directories for classes in that namespace.
     *
     * @var array
     */
    protected $prefixes = array();

    /**
     * Register loader with SPL autoloader stack.
     *
     * @return void
     */
    public function register($prepend = false)
    {
        spl_autoload_register([$this, 'loadClass'], true, $prepend);
    }

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix The namespace prefix.
     * @param string $base_dir A base directory for class files in the
     * namespace.
     * @param bool $prepend If true, prepend the base directory to the stack
     * instead of appending it; this causes it to be searched first rather
     * than last.
     * @return void
     */
    public function addNamespace($prefix, $base_dir, $prepend = false)
    {
        // normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';

        // normalize the base directory with a trailing separator
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';

        // initialize the namespace prefix array
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = array();
        }

        // retain the base directory for the namespace prefix
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $base_dir);
        } else {
            array_push($this->prefixes[$prefix], $base_dir);
        }
    }


    public function file($class)
    {
      return $this->loadClass($class,false);
    }


    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     * @return mixed The mapped file name on success, or boolean false on
     * failure.
     */
    public function loadClass($class, $load = true)
    {
        // the current namespace prefix
        $prefix = $class;

        // work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while (false !== $pos = strrpos($prefix, '\\')) {

            // retain the trailing namespace separator in the prefix
            $prefix = substr($class, 0, $pos + 1);

            // the rest is the relative class name
            $relative_class = substr($class, $pos + 1);

            // try to load a mapped file for the prefix and relative class
            $mapped_file = $this->loadMappedFile($prefix, $relative_class, $load);
            if ($mapped_file) {
                return $mapped_file;
            }

            // remove the trailing namespace separator for the next iteration
            // of strrpos()
            $prefix = rtrim($prefix, '\\');
        }

        // never found a mapped file
        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix The namespace prefix.
     * @param string $relative_class The relative class name.
     * @return mixed Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedFile($prefix, $relative_class, $load = true)
    {
        // are there any base directories for this namespace prefix?
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        // look through base directories for this namespace prefix
        foreach ($this->prefixes[$prefix] as $base_dir) {

            // replace the namespace prefix with the base directory,
            // replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $base_dir
                  . str_replace('\\', '/', $relative_class)
                  . '.php';

            // if the mapped file exists, require it
            if ((true===$load && true=== $this->requireFile($file))
                    || (false===$load && file_exists($file))
                ) {
                // yes, we're done
                return $file;
            }
        }

        // never found it
        return false;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     * @return bool True if the file exists, false if not.
     */
    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }
}

}





/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Process{

if(!class_exists(ExecutableFinder::class)){
/**
 * Generic executable finder.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ExecutableFinder
{
    private $suffixes = ['.exe', '.bat', '.cmd', '.com'];

    /**
     * Replaces default suffixes of executable.
     */
    public function setSuffixes(array $suffixes)
    {
        $this->suffixes = $suffixes;
    }

    /**
     * Adds new possible suffix to check for executable.
     */
    public function addSuffix(string $suffix)
    {
        $this->suffixes[] = $suffix;
    }

    /**
     * Finds an executable by name.
     *
     * @param string      $name      The executable name (without the extension)
     * @param string|null $default   The default to return if no executable is found
     * @param array       $extraDirs Additional dirs to check into
     *
     * @return string|null The executable path or default value
     */
    public function find(string $name, string $default = null, array $extraDirs = [])
    {
        if (ini_get('open_basedir')) {
            $searchPath = array_merge(explode(\PATH_SEPARATOR, ini_get('open_basedir')), $extraDirs);
            $dirs = [];
            foreach ($searchPath as $path) {
                // Silencing against https://bugs.php.net/69240
                if (@is_dir($path)) {
                    $dirs[] = $path;
                } else {
                    if (basename($path) == $name && @is_executable($path)) {
                        return $path;
                    }
                }
            }
        } else {
            $dirs = array_merge(
                explode(\PATH_SEPARATOR, getenv('PATH') ?: getenv('Path')),
                $extraDirs
            );
        }

        $suffixes = [''];
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $pathExt = getenv('PATHEXT');
            $suffixes = array_merge($pathExt ? explode(\PATH_SEPARATOR, $pathExt) : $this->suffixes, $suffixes);
        }
        foreach ($suffixes as $suffix) {
            foreach ($dirs as $dir) {
                if (@is_file($file = $dir.\DIRECTORY_SEPARATOR.$name.$suffix) && ('\\' === \DIRECTORY_SEPARATOR || @is_executable($file))) {
                    return $file;
                }
            }
        }

        return $default;
    }
}
}
}
 
 
 

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Process{
if(!class_exists(PhpExecutableFinder::class)){
/**
 * An executable finder specifically designed for the PHP executable.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PhpExecutableFinder
{
    private $executableFinder;

    public function __construct()
    {
        $this->executableFinder = new ExecutableFinder();
    }

    /**
     * Finds The PHP executable.
     *
     * @return string|false The PHP executable path or false if it cannot be found
     */
    public function find(bool $includeArgs = true)
    {
        if ($php = getenv('PHP_BINARY')) {
            if (!is_executable($php)) {
                $command = '\\' === \DIRECTORY_SEPARATOR ? 'where' : 'command -v';
                if ($php = strtok(exec($command.' '.escapeshellarg($php)), \PHP_EOL)) {
                    if (!is_executable($php)) {
                        return false;
                    }
                } else {
                    return false;
                }
            }

            return $php;
        }

        $args = $this->findArguments();
        $args = $includeArgs && $args ? ' '.implode(' ', $args) : '';

        // PHP_BINARY return the current sapi executable
        if (\PHP_BINARY && \in_array(\PHP_SAPI, ['cgi-fcgi', 'cli', 'cli-server', 'phpdbg'], true)) {
            return \PHP_BINARY.$args;
        }

        if ($php = getenv('PHP_PATH')) {
            if (!@is_executable($php)) {
                return false;
            }

            return $php;
        }

        if ($php = getenv('PHP_PEAR_PHP_BIN')) {
            if (@is_executable($php)) {
                return $php;
            }
        }

        if (@is_executable($php = \PHP_BINDIR.('\\' === \DIRECTORY_SEPARATOR ? '\\php.exe' : '/php'))) {
            return $php;
        }

        $dirs = [\PHP_BINDIR];
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $dirs[] = 'C:\xampp\php\\';
        }

        return $this->executableFinder->find('php', false, $dirs);
    }

    /**
     * Finds the PHP executable arguments.
     *
     * @return array The PHP executable arguments
     */
    public function findArguments()
    {
        $arguments = [];
        if ('phpdbg' === \PHP_SAPI) {
            $arguments[] = '-qrr';
        }

        return $arguments;
    }
}
}
}






/*
  (new \webfan\hps\patch\PhpBinFinder)->find()
*/

namespace webfan\hps\patch{

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\PhpProcess;


class PhpBinFinder
{
	public function find(){
		$binFile = (new PhpExecutableFinder)->find();
		if(empty($binFile)){
			$binFile = dirname(dirname(dirname(ini_get('extension_dir')))). \DIRECTORY_SEPARATOR .'bin'. \DIRECTORY_SEPARATOR .'php';	
		}

		$tmpfname = tempnam(\sys_get_temp_dir(), 'phpcheck');
		file_put_contents($tmpfname, "<?php echo 'php\n'; echo \PHP_VERSION.'\n';");
		exec(sprintf('cd %s && %s %s 2>&1 ',dirname($tmpfname), $binFile, $tmpfname), $out, $status); 
		unlink($tmpfname);

		if(isset($out[0]) && 'php' === $out[0]){
 
		}else{ 
			exec('which php 2>&1 ', $out, $status); 
			$binFile = (isset($out[0])) ? $out[0] : '/usr/bin/php';
		}		
	
		return $binFile;
	}	
}


}





namespace frdl\Lint{

class Php
{

 protected $cacheDir = null;	
	
 public function __construct($cacheDir = null){
    $this->cacheDir = $cacheDir;
 }
	
	
  public function setCacheDir($cacheDir = null){
	  $this->cacheDir = $cacheDir;
	  return $this;
 }
	
	
 public function getCacheDir(){
	 if((null!==$this->cacheDir && !empty($this->cacheDir)) && is_dir($this->cacheDir)){
	    return $this->cacheDir;
	 }
	 
   if(!isset($_ENV['FRDL_HPS_CACHE_DIR']))$_ENV['FRDL_HPS_CACHE_DIR']=getenv('FRDL_HPS_CACHE_DIR');	 

	  $this->cacheDir = 
		(  isset($_ENV['FRDL_HPS_CACHE_DIR']) && !empty($_ENV['FRDL_HPS_CACHE_DIR']))
		  ? $_ENV['FRDL_HPS_CACHE_DIR'] 
                   : \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . \get_current_user(). \DIRECTORY_SEPARATOR . 'cache' . \DIRECTORY_SEPARATOR ;	  
	  
	 $this->cacheDir = rtrim($this->cacheDir, '\\/'). \DIRECTORY_SEPARATOR.'lint';
	 
	 if(!is_dir($this->cacheDir)){
		mkdir($this->cacheDir, 0777, true); 
	 }
	 
	 
	  return $this->cacheDir;
 }
	
 public function lintString($source){
	 $cachedir =  $this->getCacheDir();
	 if(!is_writable($cachedir)){
		mkdir($cachedir, 0777, true); 
	 }	 
	 $tmpfname = tempnam($cachedir, 'frdl_lint_php');
	 if(empty($tmpfname))return false;
	 file_put_contents($tmpfname, $source);
	 $valid = $this->checkSyntax($tmpfname, false);
	 unlink($tmpfname);
	 return $valid;
 }

 public function lintFile($fileName, $checkIncludes = true){	   
	// $o = new self;
	// $o->setCacheDir($o->getCacheDir());
	 return call_user_func_array([$this, 'checkSyntax'], [$fileName, $checkIncludes]);
 }
	
 public static function lintStringStatic($source){
	 $o = new self;
	 $tmpfname = tempnam($o->getCacheDir(), 'frdl_lint_php');
	 file_put_contents($tmpfname, $source);
	 $valid = $o->checkSyntax($tmpfname, false);
	 unlink($tmpfname);
	 return $valid;
 }

 public static function lintFileStatic($fileName, $checkIncludes = true){	 	 
	 $o = new self;
	 $o->setCacheDir($o->getCacheDir());
	 return call_user_func_array([$o, 'checkSyntax'], [$fileName, $checkIncludes]);
 }   
	
	
 public static function __callStatic($name, $arguments){
	 $o = new self;
	 return call_user_func_array([$o, $name], $arguments);
 }	
	
	
 public function checkSyntax($fileName, $checkIncludes = false){
        // If it is not a file or we can't read it throw an exception
      // if(!is_file($fileName) || !is_readable($fileName))
	  if(!file_exists($fileName))
            throw new \Exception("Cannot read file ".$fileName);
       
        // Sort out the formatting of the filename
       $fileName = realpath($fileName);
       
        // Get the shell output from the syntax check command
        $output = shell_exec(sprintf('%s -l "%s"',  (new \webfan\hps\patch\PhpBinFinder())->find(), $fileName));
       
        // Try to find the parse error text and chop it off
        $syntaxError = preg_replace("/Errors parsing.*$/", "", $output, -1, $count);
       
        // If the error text above was matched, throw an exception containing the syntax error
        if($count > 0)
            //throw new \Exception(trim($syntaxError));
			return 'Errors parsing '.print_r([$output, $count],true);
       
        // If we are going to check the files includes
        if($checkIncludes)
        {
            foreach($this->getIncludes($fileName) as $include)
            {
                // Check the syntax for each include
				$tCheck = $this->checkSyntax($include, $checkIncludes);
               if(true!==$tCheck){
				 return $tCheck;   
			   }
            }
        }
	 
	  return true;
    }
   
   public function getIncludes($fileName)
    {
        // NOTE that any file coming into this function has already passed the syntax check, so
        // we can assume things like proper line terminations
           
        $includes = array();
        // Get the directory name of the file so we can prepend it to relative paths
        $dir = dirname($fileName);
       
        // Split the contents of $fileName about requires and includes
        // We need to slice off the first element since that is the text up to the first include/require
        $requireSplit = array_slice(preg_split('/require|include/i', file_get_contents($fileName)), 1);
       
        // For each match
        foreach($requireSplit as $string)
        {
            // Substring up to the end of the first line, i.e. the line that the require is on
            $string = substr($string, 0, strpos($string, ";"));
           
            // If the line contains a reference to a variable, then we cannot analyse it
            // so skip this iteration
            if(strpos($string, "$") !== false)
                continue;
           
            // Split the string about single and double quotes
            $quoteSplit = preg_split('/[\'"]/', $string);
           
            // The value of the include is the second element of the array
            // Putting this in an if statement enforces the presence of '' or "" somewhere in the include
            // includes with any kind of run-time variable in have been excluded earlier
            // this just leaves includes with constants in, which we can't do much about
            if($include = $quoteSplit[1])
            {
                // If the path is not absolute, add the dir and separator
                // Then call realpath to chop out extra separators
                if(strpos($include, ':') === FALSE)
                    $include = realpath($dir.\DIRECTORY_SEPARATOR.$include);
           
                array_push($includes, $include);
            }
        }
       
        return $includes;
    }
	
}


}


namespace Webfan\Traits {
if ( !trait_exists( WithOnShutdown::class ) ) {
	
 trait WithOnShutdown
 {
 
  public function onShutdown(){
	  return call_user_func_array($this->_getShutdowner(), func_get_args()); 
  }	
  public function _getShutdowner(){
	  $load = $this->_currentClassToAutoload !== \frdlweb\Thread\ShutdownTasks::class;
	  
		 return (class_exists(\frdlweb\Thread\ShutdownTasks::class, $load ))
					  ? \frdlweb\Thread\ShutdownTasks::mutex()
					  : function(){
						   call_user_func_array('register_shutdown_function', func_get_args());
						   register_shutdown_function(function($load){
							   $t = class_exists(\frdlweb\Thread\ShutdownTasks::class, $load);
						   }, $load);
					  };
  }	
 }
}
  
}  
  
/**
* https://gist.github.com/bubba-h57/32593b2b970366d24be7
*/
namespace Webfan\Connection {
	
use Webfan\Traits\WithOnShutdown as WithOnShutdown;


  class Browser
  {
  use WithOnShutdown;
  protected $options=[
    'ob_handler'=>true,
	'time_limit'=>3600,
	'terminate'=>true,
  ];
  public function __construct(array $options=null){
    if(!is_array($options)){
	  $options=[];
	}
	
	$this->options=array_merge($this->options,  $options);
	
	if(isset($this->options['ob_handler'])){
	    if(true===$this->options['ob_handler']){
		  ob_start();
		}elseif(is_callable($this->options['ob_handler'])){
		    ob_start($this->options['ob_handler']);
		}
	}
	
	
	  if(true===$this->options['terminate']){
	  
	  }
  }
  
  
 /**
 * Close the connection to the browser but continue processing the operation
 * @param $body
 */
 public function close($body = '', $responseCode = 200){
    // Cause we are clever and don't want the rest of the script to be bound by a timeout.
    // Set to zero so no time limit is imposed from here on out.
    set_time_limit($this->options['time_limit']);

    // Client disconnect should NOT  script execution
    ignore_user_abort(true);

    // Clean (erase) the output buffer and turn off outputabort our buffering
    // in case there was anything up in there to begin with.
  //  ob_end_clean();
        $body = ((ob_get_level())?ob_get_clean():'') . $body;
		 
		 
    // Turn on output buffering, because ... we just turned it off ...
    // if it was on.
    ob_start();

    echo $body;

    // Return the length of the output buffer
    $size = ob_get_length();

    // send headers to tell the browser to close the connection
    // remember, the headers must be called prior to any actual
    // input being sent via our flush(es) below.
    header("Connection: close\r\n");
    header("Content-Encoding: none\r\n");
    header("Content-Length: $size");

    // Set the HTTP response code
    // this is only available in PHP 5.4.0 or greater
    http_response_code($responseCode);

    // Flush (send) the output buffer and turn off output buffering
    ob_end_flush();

    // Flush (send) the output buffer
    // This looks like overkill, but trust me. I know, you really don't need this
    // unless you do need it, in which case, you will be glad you had it!
    @ob_flush();

    // Flush system output buffer
    // I know, more over kill looking stuff, but this
    // Flushes the system write buffers of PHP and whatever backend PHP is using
    // (CGI, a web server, etc). This attempts to push current output all the way
    // to the browser with a few caveats.
    flush();
   }  
  }
}

/**
	    if(\version_compare(PHP_VERSION, '5.6.0', '>=')){
              self::$__frdl__aInstance[ $sClassName ]  = new $sClassName(...$args);
         } else {
                $reflect  = new \ReflectionClass($sClassName);
                self::$__frdl__aInstance[ $sClassName ]  = $reflect->newInstanceArgs($args);
              }	  
			  */
			  
			  
namespace Webfan {  
if ( !trait_exists( WebfantizedClassTrait::class ) ) {
	
 trait WebfantizedClassTrait
 {
 
	  public static function Webfantized():\stdclass{
	      $sClassName = get_called_class(); 
	      $sClassNameClosureDefined ='\Webfan\App\\'.$sClassName; 
		  
		  $phpCode=<<<PHPCODE
class $sClassNameClosureDefined extends $sClassName{

}
PHPCODE;

		  $tmpfname = tempnam(\sys_get_temp_dir(), "php~code~.".sha1($phpCode));
		  file_put_contents($tmpfname, $phpCode);
		  require $tmpfname;
		  $reflect  = new \ReflectionClass($sClassNameClosureDefined);
		 
          
	  }
	  public static function webfantize(){
	  
	  }
	  public static function webfan(){

	  }
	  
	  
 }
 
}
}


namespace Webfan\Autoupdate {
if (!interface_exists(SVLClassInterface::class)) {
	///Single Version Line   
  interface SVLClassInterface
  {
	  public static  function __FRDL_SVLC_INTERVAL(int $interval = null) :int;
	  public static function __FRDL_SVLC_SOURCE_LOCATION(string $function_or_class_name = null ) :string;
	  public static  function __FRDL_SVLC_RACE(array $race = null):array;
	  public static  function __FRDL_SVLC_UPDATE();
  }
	
}

if ( !trait_exists( SVLClassTrait::class ) ) {
	
 trait SVLClassTrait// implements SVLClassInterface
 {
  
	// public static function __FRDL_SVLC_SOURCE_LOCATION(string $function_or_class_name = null ) :string;
	 // public static  function __FRDL_SVLC_RACE(array $race = null):array;
	 // public static  function __FRDL_SVLC_UPDATE();
	  
	  
 
	  public static function __FRDL_SVLC_SOURCE_LOCATION(string $function_or_class_name = null ) :string{
	       if(null === $function_or_class_name) {
		     $function_or_class_name=get_called_class();
		   }

           if( function_exists( $function_or_class_name ) ){
                $link = ( new \ReflectionFunction( $function_or_class_name ) )->getFileName();
           }elseif( class_exists( $function_or_class_name ) ){     
	           $link = ( new \ReflectionClass( $function_or_class_name ) )->getFileName();
           }elseif( ( $local = \frdl\implementation\psr4\RemoteAutoloader::getInstance()->file( $function_or_class_name )) 
		              && file_exists( $local ) ){              
		         $link = $local;
           }elseif(($remote = \frdl\implementation\psr4\RemoteAutoloader::getInstance()->resolve( $function_or_class_name )) 
		              && \frdl\implementation\psr4\RemoteAutoloader::getInstance()->exists($remote)){
               $link = $remote;
           }
           return $link;
       }


	  public static function __FRDL_SVLC_INTERVAL(int $interval = null) :int{
		   $sClassName = get_called_class(); 
		  
		   if(!isset($sClassName::$WEBFANTIZED_CLASS)){
		      $sClassName::$WEBFANTIZED_CLASS = [];
		   }
		  
		   if(!isset($sClassName::$WEBFANTIZED_CLASS['SVLC_INTERVAL'])){
		      $sClassName::$WEBFANTIZED_CLASS['SVLC_INTERVAL'] = 356 * 24 * 60 * 60;
		   }
		   
		  if(is_int($interval)){
			 $sClassName::$WEBFANTIZED_CLASS['SVLC_INTERVAL'] =  $interval;
		  }
		  
		  
		  
		  return $sClassName::$WEBFANTIZED_CLASS['SVLC_INTERVAL'];
	  }
	
	
	public static function __FRDL_SVLC_RACE( $race = null):array{
	      $sClassName = get_called_class(); 
	      
	  
	      if(!is_array(static::$__SVLCCLASS_UPDATE_RACE__)){
			 static::$__SVLCCLASS_UPDATE_RACE__=  [
			   'https://cdn.frdl.io/@webfan3/stubs-and-fixtures/classes/${class}?salt=${salt}&version=${version}',
			   'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=${class}',
			 ];
			 
			 array_unshift(static::$__SVLCCLASS_UPDATE_RACE__,
			   \frdl\implementation\psr4\RemoteAutoloader::getInstance()->resolve( get_called_class() )			 
			 );
		  }
	  
	       if(isset($sClassName::$WEBFANTIZED_CLASS)
		      && isset($sClassName::$WEBFANTIZED_CLASS['SVLCCLASS_UPDATE_RACE']) 
		      && is_array($sClassName::$WEBFANTIZED_CLASS['SVLCCLASS_UPDATE_RACE'])){
		          static::$__SVLCCLASS_UPDATE_RACE__=  array_merge(
				    static::$__SVLCCLASS_UPDATE_RACE__, 
				     $sClassName::$WEBFANTIZED_CLASS['SVLCCLASS_UPDATE_RACE']
				   );
		   }
	  
	  
	      if(is_string($race)){
			 array_unshift(static::$__SVLCCLASS_UPDATE_RACE__,
			   $race			 
			 );		  
		  }elseif(is_array($race)){
			 static::$__SVLCCLASS_UPDATE_RACE__=  array_merge($race, static::$__SVLCCLASS_UPDATE_RACE__);
		  }
		  return static::$__SVLCCLASS_UPDATE_RACE__;	  
	  }
	  
	  
	  
    public static function __FRDL_SVLC_UPDATE($force = false){
	  $interval = static::__FRDL_SVLC_INTERVAL();
	  
	  
	  if(true!==$force 
	  && file_exists( static::__FRDL_SVLC_SOURCE_LOCATION( get_called_class() ) )
	  && ( $interval<=0 || filemtime(  static::__FRDL_SVLC_SOURCE_LOCATION( get_called_class() ) ) > time() - $interval )
	){
				   return false;
				 }
		  $SourcesRaces=static::__FRDL_SVLC_RACE();		 
		  $loader =  \frdl\implementation\psr4\RemoteAutoloader::getInstance();
		  $loader->withClassmap($SourcesRaces);
		 
          $oldc = @file_get_contents(static::__FRDL_SVLC_SOURCE_LOCATION( get_called_class() ) );
		file_put_contents(static::__FRDL_SVLC_SOURCE_LOCATION( get_called_class() ).'.bak.php', $oldc);
        $links_hint=[];
    $trys=[];
   $code=false;
	while(($code===false || empty($code) ) && count($SourcesRaces) > 0){
		array_push($trys, array_shift($SourcesRaces) );
		$current=$trys[count($trys)-1];
		   try{
	                  $code =    @file_get_contents($current);		
		   }catch(\Exception $e){		   
			   $code=false; 
		   }
		if(($code === false || empty($code)) && $current!==__FILE__){
			array_push($links_hint,  $current );
		}
	}
  
   try{
	 
 if(false === $code || empty( $code) ){
     throw new \Exception(sprintf('Could not load %s from %s',
								  static::__FRDL_SVLC_SOURCE_LOCATION( get_called_class() ) ,  
								  print_r($links_hint,true)));
 }else{
        file_put_contents( static::__FRDL_SVLC_SOURCE_LOCATION( get_called_class() ), $code);
      //  return require  \frdl\implementation\psr4\RemoteAutoloader::getInstance()->file(static::class);
 }
	   
// return require __FILE__;

}catch(\Exception $e){
     file_put_contents(static::__FRDL_SVLC_SOURCE_LOCATION( get_called_class() ), $oldc);
     throw $e;
  }



	  }
 }
}
}





namespace frdl\Contract\Container{
if (!interface_exists(ContainerInterface::class)) {
/**
 * Describes the interface of a container that exposes methods to read its entries.
 */
interface ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get(string $id);

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $id): bool;
}
}	
}




namespace frdl\implementation\psr4{
 
use frdl\Contract\Container\ContainerInterface;
use Webfan\Autoupdate\SVLClassTrait as SVLClassTrait;
use Webfan\Traits\WithOnShutdown;
use Webfan\Autoupdate\SVLClassInterface;
use Webfan\Traits\Singleton as Singleton;
/*
 $loader = \frdl\implementation\psr4\RemoteAutoloader::getInstance('frdl.webfan.de', true, 'latest', true);
 
 
\frdl\implementation\psr4\RemoteAutoloader::getInstance($server = '03.webfan.de', $register = true, $version = 'latest', $allowFromSelfOrigin = false, $salted = false, $classMap = null, $cacheDirOrAccessLevel = self::ACCESS_LEVEL_SHARED, $cacheLimit = null, $password = null)
									 
									 
\frdl\implementation\psr4\RemoteAutoloader::getInstance()->url(\frdl\Proxy\Proxy::class, false, false) 	
https://raw.githubusercontent.com/frdl/proxy/master/src/frdl\Proxy\Proxy.php?cache_bust=123

\frdl\implementation\psr4\RemoteAutoloader::getInstance()->urlTemplate(\frdl\Proxy\Proxy::class)
https://03.webfan.de/install/?salt=${salt}&source=${class}&version=${version}

\frdl\implementation\psr4\RemoteAutoloader::getInstance()->url(\WeidDnsConverter::class)
https://raw.githubusercontent.com/frdl/oid2weid/master/src/WeidDnsConverter.php?cache_bust=123

\frdl\implementation\psr4\RemoteAutoloader::getInstance()->urlTemplate(\WeidDnsConverter::class)
https://raw.githubusercontent.com/frdl/oid2weid/master/src/WeidDnsConverter.php?cache_bust=${salt}

\frdl\implementation\psr4\RemoteAutoloader::getInstance()->resolve(\frdl\Proxy\Proxy::class, 123)
https://raw.githubusercontent.com/frdl/proxy/master/src/frdl\Proxy\Proxy.php?cache_bust=123

	 
*/
class RemoteAutoloader implements ContainerInterface, SVLClassInterface
{
	use SVLClassTrait, WithOnShutdown, Singleton;
	
	
	
	
	const HASH_ALGO = 'sha1';
	const ACCESS_LEVEL_SHARED = 0;
	const ACCESS_LEVEL_PUBLIC = 1;
	const ACCESS_LEVEL_OWNER = 2;
	const ACCESS_LEVEL_PROJECT = 4;
	const ACCESS_LEVEL_BUCKET = 8;
	const ACCESS_LEVEL_CONTEXT = 16;
	
	//WEBFANTIZED_CLASS['SVLCCLASS_UPDATE_RACE']
	protected $WEBFANTIZED_CLASS = [
	  'onGetInstance'=>'__frdl__setup',
	  'SVLCCLASS_UPDATE_RACE' =>  [
	           'https://cdn.webfan.de/@webfan3/stubs-and-fixtures/classes/frdl/implementation/psr4/RemoteAutoloader',
			   'https://raw.githubusercontent.com/frdl/remote-psr4/master/src/implementations/autoloading/RemoteAutoloader.php',
			   'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=${class}',
			   'https://cdn.frdl.io/@webfan3/stubs-and-fixtures/classes/${class}?salt=${salt}&version=${version}',
	   ],
	];

	
	const CLASSMAP_DEFAULTS = [
		'GuzzleHttp\\uri_template' => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\choose_handler',
		'GuzzleHttp\\describe_type' => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\choose_handler',
		'GuzzleHttp\\headers_from_lines' => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\choose_handler',
		'GuzzleHttp\\debug_resource' => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\choose_handler',
		'GuzzleHttp\\choose_handler' => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\choose_handler',
		'GuzzleHttp\\default_user_agent' => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\choose_handler',
		'GuzzleHttp\\default_ca_bundle' => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\choose_handler',
		'GuzzleHttp\\normalize_header_keys' => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\choose_handler',
		'GuzzleHttp\\is_host_in_noproxy' => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\choose_handler',
		'GuzzleHttp\\json_decode' => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\choose_handler',
		'GuzzleHttp\\json_encode' => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\choose_handler',
		
	\GuzzleHttp\LoadGuzzleFunctionsForFrdl::class => 'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\LoadGuzzleFunctionsForFrdl',
		 
		'GuzzleHttp\\Psr7\\uri_for' =>  'https://03.webfan.de/install/?salt=${salt}&version=${version}&source=GuzzleHttp\Psr7\uri_for',
		
	
		\Wehowski\Gist\Http\Response\Helper::class =>
'https://gist.githubusercontent.com/wehowski/d762cc34d5aa2b388f3ebbfe7c87d822/raw/5c3acdab92e9c149082caee3714f0cf6a7a9fe0b/Wehowski%255CGist%255CHttp%255CResponse%255CHelper.php?cache_bust=${salt}',
	\webfan\hps\Format\DataUri::class => 'https://03.webfan.de/install/?salt=${salt}&source=webfan\hps\Format\DataUri',
	'frdl\\Proxy\\' => 'https://raw.githubusercontent.com/frdl/proxy/master/src/${class}.php?cache_bust=${salt}',
	 \frdlweb\Thread\ShutdownTasks::class => 'https://raw.githubusercontent.com/frdl/shutdown-helper/master/src/ShutdownTasks.php?cache_bust=${salt}',

		
		'Fusio\\Adapter\\Webfantize\\' => 'https://raw.githubusercontent.com/frdl/fusio-adapter-webfantize/master/src/${class}.php?cache_bust=${salt}',
		
		//frdl\implementation\psr4 
		//Nette\Loaders\FrdlRobotLoader
		'@frdl\\implementation\\Autoload\\Local' =>  \Webfan\Autoload\ClassMapGenerator::class,
		/* \Nette\Loaders\FrdlRobotLoader::class, */
		'@frdl\\implementation\\Autoload\\Remote' => __CLASS__,
		'@frdl\\implementation\\Build\\ClassMap' => \Webfan\Autoload\ClassMapGenerator::class,
		'@frdl\\implementation\\Autoload' => __CLASS__,
		
		/*
		 \Webfan\Autoupdate\SVLClassInterface::class=>
		    'https://cdn.frdl.io/@webfan3/stubs-and-fixtures/classes/frdl/implementation/psr4/RemoteAutoloader',
		  \Webfan\Autoupdate\SVLClassTrait::class =>  
		     'https://cdn.frdl.io/@webfan3/stubs-and-fixtures/classes/frdl/implementation/psr4/RemoteAutoloader',
			 */
			 
    // NAMESPACES
    // Zend Framework components
    '@Zend\\AuraDi\\Config' => 'Laminas\\AuraDi\\Config',
    '@Zend\\Authentication' => 'Laminas\\Authentication',
    '@Zend\\Barcode' => 'Laminas\\Barcode',
    '@Zend\\Cache' => 'Laminas\\Cache',
    '@Zend\\Captcha' => 'Laminas\\Captcha',
    '@Zend\\Code' => 'Laminas\\Code',
    '@ZendCodingStandard\\Sniffs' => 'LaminasCodingStandard\\Sniffs',
    '@ZendCodingStandard\\Utils' => 'LaminasCodingStandard\\Utils',
    '@Zend\\ComponentInstaller' => 'Laminas\\ComponentInstaller',
    '@Zend\\Config' => 'Laminas\\Config',
    '@Zend\\ConfigAggregator' => 'Laminas\\ConfigAggregator',
    '@Zend\\ConfigAggregatorModuleManager' => 'Laminas\\ConfigAggregatorModuleManager',
    '@Zend\\ConfigAggregatorParameters' => 'Laminas\\ConfigAggregatorParameters',
    '@Zend\\Console' => 'Laminas\\Console',
    '@Zend\\ContainerConfigTest' => 'Laminas\\ContainerConfigTest',
    '@Zend\\Crypt' => 'Laminas\\Crypt',
    '@Zend\\Db' => 'Laminas\\Db',
    '@ZendDeveloperTools' => 'Laminas\\DeveloperTools',
    '@Zend\\Di' => 'Laminas\\Di',
    '@Zend\\Diactoros' => 'Laminas\\Diactoros',
    '@ZendDiagnostics\\Check' => 'Laminas\\Diagnostics\\Check',
    '@ZendDiagnostics\\Result' => 'Laminas\\Diagnostics\\Result',
    '@ZendDiagnostics\\Runner' => 'Laminas\\Diagnostics\\Runner',
    '@Zend\\Dom' => 'Laminas\\Dom',
    '@Zend\\Escaper' => 'Laminas\\Escaper',
    '@Zend\\EventManager' => 'Laminas\\EventManager',
    '@Zend\\Feed' => 'Laminas\\Feed',
    '@Zend\\File' => 'Laminas\\File',
    '@Zend\\Filter' => 'Laminas\\Filter',
    '@Zend\\Form' => 'Laminas\\Form',
    '@Zend\\Http' => 'Laminas\\Http',
    '@Zend\\HttpHandlerRunner' => 'Laminas\\HttpHandlerRunner',
    '@Zend\\Hydrator' => 'Laminas\\Hydrator',
    '@Zend\\I18n' => 'Laminas\\I18n',
    '@Zend\\InputFilter' => 'Laminas\\InputFilter',
    '@Zend\\Json' => 'Laminas\\Json',
    '@Zend\\Ldap' => 'Laminas\\Ldap',
    '@Zend\\Loader' => 'Laminas\\Loader',
    '@Zend\\Log' => 'Laminas\\Log',
    '@Zend\\Mail' => 'Laminas\\Mail',
    '@Zend\\Math' => 'Laminas\\Math',
    '@Zend\\Memory' => 'Laminas\\Memory',
    '@Zend\\Mime' => 'Laminas\\Mime',
    '@Zend\\ModuleManager' => 'Laminas\\ModuleManager',
    '@Zend\\Mvc' => 'Laminas\\Mvc',
    '@Zend\\Navigation' => 'Laminas\\Navigation',
    '@Zend\\Paginator' => 'Laminas\\Paginator',
    '@Zend\\Permissions' => 'Laminas\\Permissions',
    '@Zend\\Pimple\\Config' => 'Laminas\\Pimple\\Config',
    '@Zend\\ProblemDetails' => 'Mezzio\\ProblemDetails',
    '@Zend\\ProgressBar' => 'Laminas\\ProgressBar',
    '@Zend\\Psr7Bridge' => 'Laminas\\Psr7Bridge',
    '@Zend\\Router' => 'Laminas\\Router',
    '@Zend\\Serializer' => 'Laminas\\Serializer',
    '@Zend\\Server' => 'Laminas\\Server',
    '@Zend\\ServiceManager' => 'Laminas\\ServiceManager',
    '@ZendService\\ReCaptcha' => 'Laminas\\ReCaptcha',
    '@ZendService\\Twitter' => 'Laminas\\Twitter',
    '@Zend\\Session' => 'Laminas\\Session',
    '@Zend\\SkeletonInstaller' => 'Laminas\\SkeletonInstaller',
    '@Zend\\Soap' => 'Laminas\\Soap',
    '@Zend\\Stdlib' => 'Laminas\\Stdlib',
    '@Zend\\Stratigility' => 'Laminas\\Stratigility',
    '@Zend\\Tag' => 'Laminas\\Tag',
    '@Zend\\Test' => 'Laminas\\Test',
    '@Zend\\Text' => 'Laminas\\Text',
    '@Zend\\Uri' => 'Laminas\\Uri',
    '@Zend\\Validator' => 'Laminas\\Validator',
    '@Zend\\View' => 'Laminas\\View',
    '@ZendXml' => 'Laminas\\Xml',
    '@Zend\\Xml2Json' => 'Laminas\\Xml2Json',
    '@Zend\\XmlRpc' => 'Laminas\\XmlRpc',
    '@ZendOAuth' => 'Laminas\\OAuth',	
		
		
		//https://raw.githubusercontent.com/elastic/elasticsearch-php/v7.12.0/src/autoload.php
	'Elasticsearch\\' => 'https://raw.githubusercontent.com/elastic/elasticsearch-php/v7.12.0/src/${class}.php?cache_bust=${salt}',
		
		\WeidDnsConverter::class =>
		'https://raw.githubusercontent.com/frdl/oid2weid/master/src/WeidDnsConverter.php?cache_bust=${salt}',
		\WeidHelper::class =>
		'https://raw.githubusercontent.com/frdl/oid2weid/master/src/WeidHelper.php?cache_bust=${salt}',
		\WeidOidConverter::class =>
		'https://raw.githubusercontent.com/frdl/oid2weid/master/src/WeidOidConverter.php?cache_bust=${salt}',
	];
	
	
	protected $salted = false;
	
	protected $selfDomain;
	protected $server;
	protected $domain;
	protected $version;
	protected $allowFromSelfOrigin = false;
	
	protected $prefixes = [];
	protected $cacheDir;
	protected $cacheLimit = 0;
	protected static $instances = [];	
	protected $alias = [];
	protected static $classmap = [];

	protected $_currentClassToAutoload = null;

	
   protected function __construct($server = '03.webfan.de', 
							   $register = true,
							   $version = 'latest',
							   $allowFromSelfOrigin = false,
							   $salted = false, 
							   $classMap = null, 
							   $cacheDirOrAccessLevel = self::ACCESS_LEVEL_SHARED, 
							    $cacheLimit = null, 
							    $password = null){
	    
	  
	
	   
	    $defauoltcacheLimit = -1;
	    $bucketHash = $this->generateHash([
			                               self::class//,
			                             //  $version
										  ], 
										  '',
										  self::HASH_ALGO,
										 '-');
	   
	   
	
	   
	   switch($cacheDirOrAccessLevel){
		 
		   case self::ACCESS_LEVEL_PUBLIC : 
		        $bucket = \get_current_user().\DIRECTORY_SEPARATOR.'shared';
			   break;
		   case self::ACCESS_LEVEL_OWNER : 
		        $bucket = \get_current_user().\DIRECTORY_SEPARATOR 
					          .$this->generateHash([
								                       $bucketHash,
								                       $version,
								                     \get_current_user ( ),
													  //$_SERVER['SERVER_NAME']
								                    //  $_SERVER['SERVER_ADDR']
								  
								                        
												   ], 
										  $password,
										  self::HASH_ALGO,
										 '-');
			   break;
		   case self::ACCESS_LEVEL_PROJECT : 
			   		        $bucket = \get_current_user ( ).\DIRECTORY_SEPARATOR 
					          .$this->generateHash([
								                        $bucketHash,
								                           $version,
								                     \get_current_user ( ),
												 	 $_SERVER['SERVER_NAME'],
								                     $_SERVER['SERVER_ADDR'],
								                     basename(getcwd()),
								                     realpath(getcwd()),
								                  
												   ], 
										  $password,
										  self::HASH_ALGO,
										 '-');
			   
			   break;
		   case self::ACCESS_LEVEL_BUCKET : 
		        $bucket = \get_current_user ( ).\DIRECTORY_SEPARATOR .$this->generateHash([
								                    $bucketHash,
								                    $version
												   ], 
										  $password,
										  self::HASH_ALGO,
										 '-');
			   break;
		   case self::ACCESS_LEVEL_CONTEXT : 
		        $bucket =   \get_current_user ( ).\DIRECTORY_SEPARATOR 
					          .$this->generateHash([
								  					$bucketHash,
								                    $version,
								                     \get_current_user ( ),
												 	 $_SERVER['SERVER_NAME'],
								                     $_SERVER['SERVER_ADDR'],
								                     $_SERVER['REMOTE_ADDR'],
								                     basename(getcwd()),
								                     realpath(getcwd())
												   ], 
										  $password,
										  self::HASH_ALGO,
										 '-');
					
 
			        
			   break;
			    
		   case self::ACCESS_LEVEL_SHARED : 
			   default: 
		        $bucket = '_'.\DIRECTORY_SEPARATOR.'shared';
			   break;
	   }
	   
	    $this->cacheLimit = (is_int($cacheLimit)) ? $cacheLimit : ((isset($_ENV['FRDL_HPS_PSR4_CACHE_LIMIT']))? $_ENV['FRDL_HPS_PSR4_CACHE_LIMIT'] : $defauoltcacheLimit);   

	   
	   $this->cacheDir = (is_string($cacheDirOrAccessLevel) && is_dir($cacheDirOrAccessLevel) && is_readable($cacheDirOrAccessLevel) && is_writeable($cacheDirOrAccessLevel) ) 
		   ? $cacheDirOrAccessLevel 
			:  \sys_get_temp_dir().\DIRECTORY_SEPARATOR
			                      //   .'.frdl'.\DIRECTORY_SEPARATOR
			                         .$bucket.\DIRECTORY_SEPARATOR
			                         .'lib'.\DIRECTORY_SEPARATOR
			                         .'php'.\DIRECTORY_SEPARATOR
			                         .'src'.\DIRECTORY_SEPARATOR
			                         .'psr4'.\DIRECTORY_SEPARATOR; 
	   
	   
	    
	 
	   
	   	$valCacheDir;    
		$valCacheDir = (function($CacheDir, $checkAccessable = true, $checkNotIsSysTemp = true, $r = null) use(&$valCacheDir){
			if(null ===$r)$r=dirname($CacheDir);
			
			$checkRoot = substr($r,  0, strlen($CacheDir) );
			
			
			$checkSame = $r === $CacheDir;
			
			
			$checked = false === $checkNotIsSysTemp
				 || false === $checkSame
			|| (
				(
			       rtrim($CacheDir, \DIRECTORY_SEPARATOR.'/\\ ') !== rtrim(\sys_get_temp_dir(),\DIRECTORY_SEPARATOR.'/\\ ') 
			   && 'tmp' !== basename($CacheDir) 
			 //  && 'tmp' !== basename(dirname($CacheDir))
				)
																
			);
			
			return (
				  $checkAccessable === false
				||
				(
				 //  (//is_dir($CacheDir)					 
				//	|| is_dir(dirname($CacheDir))					
					//|| is_dir(dirname(dirname($CacheDir)))
					// ||
				//	$valCacheDir($r, false, false, $CacheDir)
				//	)
				//&&
				
			      is_writable($CacheDir) 
			   && is_readable($CacheDir) 
				)
			 )
			
			 && true === $checked
				 
				? true
				: false
			;
		});	
	

 if(!$valCacheDir($this->cacheDir,false,false) ){

	throw new \Exception('Bootstrap error in '.basename(__FILE__).' '.__LINE__.' for '.$this->cacheDir); 
 }
	
	  if(!is_dir($this->cacheDir)){
		 mkdir($this->cacheDir, 0777,true);
	  }
	   //die($this->cacheDir);
	   if(!is_array($classMap)){
		  $classMap = self::CLASSMAP_DEFAULTS;  
	   }else{
	      $classMap = array_merge(self::CLASSMAP_DEFAULTS, $classMap);  
	   }
	   
	        $this->withSalt($salted);
	        $this->withClassmap($classMap);
		$this->allowFromSelfOrigin = $allowFromSelfOrigin;
		$this->version=$version;
		$this->server = $server;	
		$_self = (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
		$h = explode('.', $_self);
		$dns = array_reverse($h);
		$this->selfDomain = $dns[1].'.'.$dns[0];
		
		$h = explode('.', $this->server);
		$dns = array_reverse($h);
		$this->domain = $dns[1].'.'.$dns[0];
		
		
		if(!$this->allowFromSelfOrigin && $this->domain === $this->selfDomain){
		  $register = false;	
		}
		

	
		if(true === $register){
		   $this->register();	
		}		
		
		 
	}
	
   public static function ik($server, $classMap){
	   if(is_array($classMap)){
		   ksort($classMap);
	   }
	   $hashingData=[$server, $classMap];
	  return sha1(var_export( $hashingData , true)); 
   }





	
	 
	 
  protected static function __frdl__setup($server = '03.webfan.de', $register = true, $version = 'latest', $allowFromSelfOrigin = false, $salted = false,
									 $classMap = null, $cacheDirOrAccessLevel = self::ACCESS_LEVEL_SHARED,       $cacheLimit = null, $password = null){
	  	
	   if(!is_array($classMap)){
		  $classMap = self::CLASSMAP_DEFAULTS;  
	   }else{
	      $classMap = array_merge(self::CLASSMAP_DEFAULTS, $classMap);  
	   }

	
	

	  $classMap = array_merge([
	    '@frdl\\implementation\\Load\\ClassMap' => 'Nette\\Loaders\\FrdlRobotLoader',
		'@frdl\\implementation\\Load\\RemoteClass' => static::class,
		 \Webfan\Autoupdate\SVLClassInterface::class=>
		    'https://cdn.frdl.io/@webfan3/stubs-and-fixtures/classes/frdl/implementation/psr4/RemoteAutoloader',
		  \Webfan\Autoupdate\SVLClassTrait::class =>  
		     'https://cdn.frdl.io/@webfan3/stubs-and-fixtures/classes/frdl/implementation/psr4/RemoteAutoloader',
		  
	  ], $classMap);  
	   	
	  
	  
	// $singleton = self::getInstance();
	  
	  if(is_array($server)){
	      $instance = [];
	      foreach($server as $indexOrServer => $_s){ 
			     $s=array_merge($_s, [
					     'server' => (is_string($indexOrServer))?$indexOrServer:$server,
					     'register'=>$register,
					     'version'=>$version,
					     'allowFromSelfOrigin'=>$allowFromSelfOrigin,
					     'salted'=>$salted,
					     'classmap'=>(is_string($indexOrServer)&&is_string($_s))?[$indexOrServer=>$_s]:$classmap,
					     'cacheDirOrAccessLevel'=>$cacheDirOrAccessLevel,
					     'cacheLimit'=>$cacheLimit,
					     'password'=>$password,
					 ]);
	 
			self::__frdl__setup(
				$s['server'],
				$s['register'], 
				$s['version'],
				$s['allowFromSelfOrigin'], 
				$s['salted'],
				$s['classmap'], 
				$s['cacheDirOrAccessLevel'],
				$s['cacheLimit'], 
				$s['password']
				);      
	      }

		  
		//$server = 'file://'.getcwd().\DIRECTORY_SEPARATOR;
		
	  }elseif(is_string($server)){
		   $key = static::ik($server, $classMap);
	         if(!isset(self::$instances[$key])){
		
			   // self::$instances[$key] =self::getInstance($server, 
			   self::$instances[$key] = new self($server,
					   $register,
					   $version,
					   $allowFromSelfOrigin,
					   $salted,
					   $classMap, 
					   $cacheDirOrAccessLevel,
					   $cacheLimit,
					   $password);
	        }		  
		   $instance = self::$instances[$key];
		   //$instance::__FRDL_SVLC_UPDATE();
	  }elseif(0===count(func_get_args())){
		  return self::$instances;
	  }
	  	

	  
	 return $instance;
  }		
	 

	
  public function pruneCache(){
	
	 if($this->cacheLimit !== 0
		   && $this->cacheLimit !== -1){
					 
            $this->onShutdown(function($CacheDir, $maxCacheTime){		
                   
						  \webfan\hps\patch\Fs::pruneDir($CacheDir, $maxCacheTime, true,  
														 (
															    'tmp' !== basename($CacheDir)										
															 && 'tmp' !== basename(dirname($CacheDir))											 
														 )
														);		
      
				  }, $this->cacheDir, $this->cacheLimit);                  	  
    }
  }
	
  public function get(string $class){
      $that = &$this;
      $r = new \stdclass;
	  $r->name = $class;
	  $r->class = [
		  'aliasOf'=>isset($this->alias[$class]) ? $this->alias[$class] : null,
		  'loaded'=>class_exists($class, false),
	  ];
	  $r->remote = (isset($that->alias[$class]) ) ? false :[
		  'link' => $that->resolve($class),
		  'exists' => $that->exists($this->resolve($class)),
	  ];
	  
	  $r->local = (isset($that->alias[$class]) ) ? false :[		  
		  'link' =>$that->file($class),
		  'cache' => (object)[
		        'hit'=>  file_exists($this->file($class)),
		        'expired'=> !file_exists($this->file($class))
				      || ($interval >= 0 &&  (false === filemtime($this->file($class)) > time() - $interval)), 
					  'filemtime' => (!file_exists($this->file($class))) ? 0 : filemtime($this->file($class)),
					  ]
	  ];
	  
	 $make = function(array $options = null) use (&$that, &$make, &$r, $class){ 
	  if(!is_array($options))$options=[];
	  $options['class']=$class;
	  if(!isset($options['interval'])){
	      $options['interval']=-1;
	  }
	  extract($options);
	  if(isset($classMap)){
	     $that->withClassmap($classMap);
	  }
	  $r->class = array_merge($r->class, [
		  'autoload' => $that->Autoload($class),
		  'aliasOf'=>isset($that->alias[$class]) ? $that->alias[$class] : null,
		  'loaded'=>class_exists($class, true),
	  ]);
	  $r->remote = (isset($that->alias[$class]) ) ? false : [
		  'link' => $that->resolve($class),
		  'exists' => $that->exists($this->resolve($class)),
	  ];
	  
	  $r->local = (isset($that->alias[$class]) ) ? false :[		  
		  'link' =>$that->file($class),
		  'cache' => (object)[
		        'hit'=>  file_exists($this->file($class)),
		        'expired'=> !file_exists($this->file($class))
				      || ($interval >= 0 &&  (false === filemtime($this->file($class)) > time() - $interval)), 
					  'filemtime' => (!file_exists($this->file($class))) ? 0 : filemtime($this->file($class)),
				 
		  ],
		
		  
	  ];
	 return $r;
	};
	
	 $r->load = $make;
	 $r->__construct = function() use (&$r){
	      return call_user_func_array($r->name().'::__construct', func_get_args());
	 };
	 return $r;
  }
	
  public function has(string $class):bool{
	 return isset($this->alias[$class]) || file_exists($this->file($class)) || is_string($this->urlTemplate($class));
  }	
  public function getCacheDir(){
	 return $this->cacheDir;  
  }
  public function file($class, $fallbackfile = false){

	 $file = rtrim($this->getCacheDir(),\DIRECTORY_SEPARATOR.'/\\ '). \DIRECTORY_SEPARATOR
		 . str_replace('\\', \DIRECTORY_SEPARATOR, $class). '.php';
	  
	   if($fallbackfile){
	    $file =\sys_get_temp_dir().\DIRECTORY_SEPARATOR. basename(__FILE__).'~fallback.'.sha1($file).strlen($file).'.php';	
		   if(!is_writable(dirname($file))){
			 $file =  __DIR__.\DIRECTORY_SEPARATOR.basename(__FILE__).'.'.basename($file);   
		   }
	  }
	  return $file;
  }
		
		
   public function str_contains($haystack, $needle, $ignoreCase = false) {
    if ($ignoreCase) {
        $haystack = strtolower($haystack);
        $needle   = strtolower($needle);
    }
    $needlePos = strpos($haystack, $needle);
    return ($needlePos === false ? false : ($needlePos+1));
  }
	

   public function str_parse_vars($string,$start = '[',$end = '/]', $variableDelimiter = '='){
     preg_match_all('/' . preg_quote($start, '/') . '(.*?)'. preg_quote($end, '/').'/i', $string, $m);
     $out = [];
     foreach($m[1] as $key => $value){
       $type = explode($variableDelimiter,$value);
       if(sizeof($type)>1){
          if(!is_array($out[$type[0]]))
             $out[$type[0]] = [];
             $out[$type[0]][] = $type[1];
       } else {
          $out[] = $value;
       }
     }

	return $out;
  }
	
	
   public function resolve($class, $salt = null){
     return $this->url($class, $salt, false);
   }		
	
   public function url($class, $salt = null, $skipCheck = true){
    if(true===$salt){
       $salt = sha1(mt_rand(1000,99999999).time());	  
    }
	   
	   $url =  $this->replaceUrlVars($this->urlTemplate($class, null, $skipCheck), $salt, $class, $this->version);
 
     return $url;
   }	
	
   public function urlTemplate($class, $salt = null, $skipCheck = true){	
      return $this->loadClass($class, $salt, $skipCheck);
   }
	

  protected function cacheRace($class){
	  $files = [
		     $this->file($class, false),
		     $this->file($class, true),
		     __DIR__.\DIRECTORY_SEPARATOR.basename(__FILE__).'.'.basename($this->file($class, true)),
		  ];
	  foreach($files as $file){
		if(file_exists($file)){
		   return $file;	
		}
	  }
	 return false;
  }
	
	
	
	
  public function Autoload($class){
  	   if( class_exists($class, false)){
	     $this->_currentClassToAutoload=null;
	     return true;
	   }
	   
	  if($this->_currentClassToAutoload === $class){
		 return;  
	  }
	  
    $this->_currentClassToAutoload=$class;
	$_cacheFile =$this->file($class, false);
	$fallbackFile =   $this->file($class, true);
	$cacheFileRaced =$this->cacheRace($class);
	  
 	$cacheFile =$_cacheFile;
  
	  
	if((!file_exists($cacheFile)  && !file_exists($fallbackFile) && !file_exists($cacheFileRaced) ) 
	   || (
		  // $this->cacheLimit !== 0
		//   && $this->cacheLimit !== -1
		   $this->cacheLimit > 0 
		   && (
			        (file_exists($cacheFile)  && filemtime($cacheFile) < time() - $this->cacheLimit)
		 	     || (file_exists($fallbackFile)  && filemtime($fallbackFile) < time() - $this->cacheLimit)
			     || (file_exists($cacheFileRaced)  && filemtime($cacheFileRaced) < time() - $this->cacheLimit)
			 )
		  )
	  ){
	  
	$code = $this->fetchCode($class, null);
		

		
    //if(true === $code){
	//	return true;
	//}else
	//if(false !==$code){		
	if(is_string($code)){		
		if(!is_dir(dirname($cacheFile))){			
		  @mkdir(dirname($cacheFile), 0777, true);
		}elseif(is_dir(dirname($cacheFile)) && !is_writable(dirname($cacheFile))){
			@chmod(dirname($cacheFile), 0777);
		}
		
    	if(!file_put_contents($cacheFile, $code)){
	    //  throw new \Exception('Cannot write source for class '.$class.' to '.$cacheFile);
			//$fallbackFile =   $this->file($class, true);
			trigger_error('Cannot write source for class '.$class.' to '.$cacheFile, \E_USER_NOTICE);
	
		    $cacheFile =$fallbackFile;				
		if(!file_put_contents($fallbackFile, $code)){	
			trigger_error('Cannot write source for class '.$class.' to '.$fallbackFile, \E_USER_NOTICE);
			
	   $cacheFile =$cacheFileRaced;				
		if(!file_put_contents($cacheFileRaced, $code)){				
			  trigger_error('Cannot write source for class '.$class.' to '.$cacheFileRaced, \E_USER_NOTICE);
		  $this->onShutdown(function($m){		
			     //  trigger_error($m,\E_USER_NOTICE);
			        throw new \Exception($m);
			  }, 'Cannot write source for class '.$class.' to '.$cacheFile);		
		  }
			
		}
			
	   }
	//  return $code;		
		//code === string
   }elseif(false ===$code){  
		//code !== string
		 // die($cacheFile);
	  return;	
	}	
  
  }//!file_exists
	
	  
		

		         if($class!==\frdlweb\Thread\ShutdownTasks::class){
                  $this->onShutdown(function($cacheFile, $cacheLimit){		
						 if(false !== $cacheFile && file_exists($cacheFile) 	 
							&& ($cacheLimit !== 0		  
								&& $cacheLimit !== -1		 
								&& (filemtime($cacheFile) < time() -$cacheLimit)		 
							   )){						
							 unlink($cacheFile); 						
							 clearstatcache(true, $cacheFile); 
					 }
				  }, $cacheFile, $this->cacheLimit);		
				 }	  
	  
	if(file_exists($cacheFile) ){
	    if(false === ($this->requireFile($cacheFile)) ){
				unlink($cacheFile);
				trigger_error('Cannot load source (require file) for class '.$class.' in '.$cacheFile,\E_USER_NOTICE);
		}
	}elseif(file_exists($fallbackFile) ){
	    if(false === ($this->requireFile($fallbackFile)) ){
				unlink($fallbackFile);
				trigger_error('Cannot load source (require file) for class '.$class.' in '.$fallbackFile,\E_USER_NOTICE);
		}
	}elseif(file_exists($cacheFileRaced) ){
	    if(false === ($this->requireFile($cacheFileRaced)) ){
				unlink($cacheFileRaced);
				trigger_error('Cannot load source (require file) for class '.$class.' in '.$cacheFileRaced,\E_USER_NOTICE);
		}
	}elseif(isset($code) && is_string($code)){
 
		//$tmpfile = tmpfile();
		$tmpfile = tempnam($this->cacheDir, 'autoloaded-file.'.sha1($code).strlen($code)); 	      
		file_put_contents($tmpfile, $code);
		
		         if($class!==\frdlweb\Thread\ShutdownTasks::class){
                  $this->onShutdown(function($tmpfile, $cacheLimit ){		
					 if(file_exists($tmpfile) && filemtime($cacheFile) < time() -$cacheLimit ){
						unlink($tmpfile); 
					 }
				  }, $tmpfile, $this->cacheLimit);		
				 }
			
		if(false === ($this->requireFile($tmpfile)) ){
			//return;	
			  trigger_error('Cannot load source (require file) for class '.$class.' in '.$tmpfile,\E_USER_NOTICE);
		}else{   
			//if( class_exists($class, false))return true;
		}
	}else{
	     // throw new \Exception('Cannot write/load source for class '.$class.' in '.$cacheFile);
		//  trigger_error('Cannot write/load source for class '.$class.' in '.$cacheFile,\E_USER_NOTICE);
		//  return;
	   }
	   
	   
	   if( class_exists($class, false)){
	     $this->_currentClassToAutoload=null;
	     return true;
	   }
	 
		$this->_currentClassToAutoload=null;	
  }
	
	
	

	
	

	
    public function generateHash( array $chunks = [], $key = null, $algo = 'sha1', $delimiter = '-', &$ctx = null ){ 
  
		$size = count($chunks);
    $initial = null === $ctx;
   $asString = serialize($chunks);//implode($delimiter, $chunks);
	$buffer = '';	
  $l = 0;
		$c = $size + ($initial);
  if(null===$key || empty($key)){
	  $c++;	
	  $key = \hash( $algo , serialize([$delimiter, [$algo,$size, $delimiter, $c, $asString]]) );
  }

   $MetaCtx = \hash_init ( $algo , \HASH_HMAC, $key ) ;	
   \hash_update($MetaCtx, $key);
		
 if( true === $initial || null === $ctx){
   $ctx = \hash_init ( $algo , \HASH_HMAC, $key ) ;
 }
	  
		while(count($chunks) > 0 && $data = array_shift($chunks)){	  
			
		   $buffer .=$data;
		   $l += strlen($data);
		   $c += count($chunks);	
			
            \hash_update($ctx, $data);		
			
			\hash_update($MetaCtx, $buffer);
			\hash_update($MetaCtx, $l);
			\hash_update($MetaCtx, $c);
		}

		
	  $c++;	
	  //$h2 = $this->generateHash([$algo,count($chunks),$l,$asString, $delimiter, $key, $c], $key, 'sha1', $delimiter, $ctx); 
      $h2 = \hash( $algo , $size .'.'. $l. '.' . $c. '.' .  strlen($buffer.$asString) ); 	 
	  \hash_update($ctx, $h2);	
	  \hash_update($MetaCtx, $h2);	
		
	$c++;	
	$h3 = hash_final($MetaCtx); 		
		
	\hash_update($ctx, $h3);	
		
	$hash = hash_final($ctx);    
	return implode($delimiter, [$size .'.'. $l. '.' . $c. '.' .  (strlen($h2.$buffer.$asString) * $c) % 20, 
								$h3, 
								$hash,
								$h2]);
  }
	
    public function withNamespace($prefix, $server, $prepend = false)
    {
        // normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';

        // normalize the base directory with a trailing separator
     //   $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';

        // initialize the namespace prefix array
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = [];
        }

        // retain the base directory for the namespace prefix
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $server);
        } else {
            array_push($this->prefixes[$prefix], $server);
        }
    }


	
   public function getUrl($class, $server, $salt = null, $parseVars = false){
	   if(!is_string($salt))$salt=mt_rand(1000,9999);
	  $url = false; 
			
		

	if(is_string($server) ){	   
     if(substr($server, 0, strlen('http://')) === 'http://' || substr($server, 0, strlen('https://')) === 'https://'){
	 //   $url = str_replace(['${salt}', '${class}', '${version}'], [$salt, $class, $this->version], $server);   
		  $url = $server;
     }elseif('~' === substr($server, 0, 1) || is_string($server) && '.' === substr($server, 0, 1) || substr($server, 0, strlen('file://')) === 'file://'){
	    $url =  \DIRECTORY_SEPARATOR.str_replace('\\', \DIRECTORY_SEPARATOR,
												 getcwd() .str_replace(['file://', '~'/*, '${salt}', '${class}', '${version}'*/],
																	   ['', (!empty(getenv('FRDL_HOME'))) ? getenv('FRDL_HOME') : getenv('HOME')/*, $salt, $class, $this->version*/],
																	   $server). '.php');   
     }elseif(preg_match("/^([a-z0-9]+)\.webfan\.de$/", $server, $m) && false === strpos($server, '/') ){
		 $url = 'https://'.$m[1].'.webfan.de/install/?salt=${salt}&source=${class}&version=${version}';
	 }elseif(preg_match("/^([\w\.^\/]+)(\/[.*]+)?$/", $server, $m) && false !== strpos($server, '.') ){
		 $url = 'https://'.$m[1].((!empty($m[2])) ? $m[2] : '/');
	 }//else{	  
	  //  $url = 'https://'.$server.'/install/?salt='.$salt.'&source='. $class.'&version='.$this->version;
		 
		 //$url = 'https://'.$server.'/install/?salt=${salt}&source=${class}&version=${version}';
   //  }
		if(!$this->str_contains($url, '${class}', false) && '.php' !== substr(explode('?', $url)[0], -4)){
			$url = rtrim($url, '/').'/${class}';
		}		
		
		if(!$this->str_contains($url, '${salt}', false)){
			$url .= (( $this->str_contains($url, '?', false) ) ? '&' : '?').'salt=${salt}';
		}	
		
	}elseif(is_callable($server)){
	    $url = call_user_func_array($server, [$class, $this->version, $salt]);	  
     }elseif(is_object($server)  && is_callable([$server, 'has']) && is_callable([$server, 'get']) && true === call_user_func_array([$server, 'has'], [$class]) ){
	    $url = call_user_func_array([$server, 'get'], [$class, $this->version, $salt]);	  
     }elseif(is_object($server) && is_callable([$server, 'get']) ){
	    $url = call_user_func_array([$server, 'get'], [$class, $this->version, $salt]);	  
     }
	   return  (true === $parseVars && is_string($url)) ?  $this->replaceUrlVars($url, $salt, $class, $version) : $url;
   }
	
	
	
	public function replaceUrlVars($_url, $salt, $class, $version){
		
		 $url = str_replace(['${salt}', '${class}', '${version}'], [$salt, $class, $version], $_url);
		
		 if(substr($_url,0, strlen('http'))==='http' 
			&& (
				   !$this->str_contains($_url, '=${class}', false)
			   || !preg_match("/".preg_quote('=${class}')."/",$_url)
			)
		   
		   ){
		  $url = preg_replace('/\\\\/',  '/', $url);
	    }
		
		return $url;
	}
	
	
    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     * @return mixed The mapped file name on success, or boolean false on
     * failure.
     */
    public function loadClass($class, $salt = null, $skipCheck = false)
    {
		
        // the current namespace prefix
        $prefix = $class;
	
	
        // work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while (false !== $pos = strrpos($prefix, '\\')) {


            // retain the trailing namespace separator in the prefix
            $prefix = substr($class, 0, $pos + 1);

            // the rest is the relative class name
            $relative_class = substr($class, $pos + 1);

	
            // try to load a mapped file for the prefix and relative class
            $mapped_file = $this->loadMappedSource($prefix, $relative_class, $salt, $skipCheck);
			
				 
            if ($mapped_file) {		
                return $mapped_file;
            }

            // remove the trailing namespace separator for the next iteration
            // of strrpos()
            $prefix = rtrim($prefix, '\\');
        }
		
		
        // never found a mapped file
        return $this->loadMappedSource('', $class, $salt, $skipCheck);
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix The namespace prefix.
     * @param string $relative_class The relative class name.
     * @return mixed Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedSource($prefix, $relative_class, $salt = null, $skipCheck = false)
    {
		
	    $url = false;
		$class = $prefix.$relative_class;
		
		//if(isset($this->alias[$class]) ){
		//	\webfan\hps\Format\DataUri
		//	die(__LINE__.$class.' Alias: '.$this->alias[$class]);
	//	}
		$pfx = !isset($this->alias[$prefix]) && substr($prefix,-1) === '\\' ? substr($prefix, 0, -1) : $prefix;
		
	
		
		if(isset($this->alias[$pfx]) ){
		//	\webfan\hps\Format\DataUri
			$originalClass = substr($this->alias[$pfx],-1) === '\\' ? substr($this->alias[$pfx], 0, -1) : $this->alias[$pfx];
			$originalClass .= '\\'.$relative_class;
			$alias = $class;
			
		//	die($classOrInterfaceExists.' <br />'.$alias.' <br />rc: '.$originalClass.'<br />'.$datUri);
			 $classOrInterfaceExistsAndNotEqualsAlias =( 
				    class_exists($originalClass, $originalClass !== $alias) 
				 || interface_exists($originalClass, $originalClass !== $alias) 
				 || (function_exists('trait_exists') && trait_exists($originalClass, $originalClass !== $alias))
					) && $originalClass !== $alias;	
			
			
            if($classOrInterfaceExistsAndNotEqualsAlias){	
			   \class_alias($originalClass, $alias);
			}
			
			return true;
			//return $classOrInterfaceExistsAndNotEqualsAlias;
		}	
		
		
	if (isset($this->prefixes[$prefix]) ) {
		
        // look through base directories for this namespace prefix
        foreach ($this->prefixes[$prefix] as $server) {
			
			$url = $this->getUrl($relative_class, $server, $salt);	
		 
			if(is_string($url) 
			   && (false === $skipCheck || $this->exists($url) )
			   //  && '\\' !== substr($class, -1) 
			   && (!isset(self::$classmap[$class]) ||  '\\' !== substr(self::$classmap[$class], -1) )
			  ){
				
					$url =  $this->replaceUrlVars($url, $salt, $relative_class, $this->version);
				
			    return $url;
			}
        }
	 }		
		if(
			isset(self::$classmap[$class])
			&& is_string(self::$classmap[$class]) 		 
		  // && '\\' !== substr($class, -1)  
			&& '\\' !== substr(self::$classmap[$class], -1)
		  ){
						
			return $this->getUrl($class, self::$classmap[$class], $salt);
		//	return self::$classmap[$class];
		}


        // never found it
       return $this->getUrl($class, $this->server, $salt);
   }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     * @return bool True if the file exists, false if not.
     */
  protected function requireFile($file){
        if (file_exists($file)) {
			try{
              if( include $file ){
		 
                return true;
			  }
			
			}catch(\Exception $e){
			    trigger_error($e->getMessage(), \E_USER_WARNING);
			     return false;				  
			  }
        }
        return false;
  }	
  public function withClassmap(array $classMap = null){
     if(null !== $classMap){
	   foreach($classMap as $class => $server){
		   if('@' === substr($class, 0, 1) && is_string($server)){
               $this->withAlias($class, $server);		
		   }elseif('\\' === substr($class, -1)){
               $this->withNamespace($class, $server, is_string($server));		
		   }else{
		        self::$classmap[$class] = $server;   
		   }

	   }
     }
	  
    return self::$classmap;	  
  }	
  public function withAlias(string $alias, string $rewrite){
       $this->alias[ltrim($alias, '@')] = $rewrite;
  }
	
  public function withSalt(bool $salted = null){
     if(null !== $salted){
	     $this->salted = $salted; 
     }
	  
    return $this->salted;	  
  }
	
	

	
  public static function __callStatic($name, $arguments){
	  $me = (count(self::$instances)) ? self::$instances[0] : self::getInstance();
	   return call_user_func_array([$me, $name], $arguments);	
  }
	
  public function __call($name, $arguments){
	   if(!in_array($name, ['fetch', 'fetchCode', '__invoke', 'register', 'getLoader', 'Autoload'])){
		  throw new \Exception('Method '.$name.' not allowed in '.__METHOD__);   
	   }
	   return call_user_func_array([$this, $name], $arguments);	
  }	
	
  protected function fetch(){
	  return call_user_func_array([$this, 'fetchCode'], func_get_args());	
  }
	
  protected function exists($source){
	if('http://'!==substr($source, 0, strlen('http://'))
	   && 'https://'!==substr($source, 0, strlen('https://'))
	   && is_file($source) && file_exists($source) && is_readable($source)){
		return true;
	}
	  
	$options = [
		'https' => [
           'method'  => 'HEAD',
            'ignore_errors' => true,        
  
		   ]
	];
    $context  = stream_context_create($options);
    $res = @file_get_contents($source, false, $context);
	return false !== $res;  
  }
	
  protected function fetchCode($class, $salt = null){	

	if(!is_string($salt) 
	   //&& true === $this->withSalt()
	  ){
		$salt = mt_rand(10000000,99999999);
	}
	  

	
	  $url = $this->resolve($class, $salt);
	
	  if(is_bool($url)){
		 return $url;  
	  }
	  
	  $withSaltedUrl = (true === $this->str_contains($url, '${salt}', false)) ? true : false;
	   
	$options = [
		'https' => [
           'method'  => 'GET',
            'ignore_errors' => true,        
  
		   ]
	];
    $context  = stream_context_create($options);
    $code = @file_get_contents($url, false, $context);
	    $statusCode = 0;  
	  //$code = file_get_contents($url);
   if( isset($http_response_header) ){
	foreach($http_response_header as $i => $header){
				
		if(0===$i){
			   preg_match('{HTTP\/\S*\s(\d{3})}', $header, $match);
               $statusCode = intval($match[1]);
		}
		
		$h = explode(':', $header);
		if('x-content-hash' === strtolower(trim($h[0]))){
			$hash = trim($h[1]);
		}		
		if('x-user-hash' === strtolower(trim($h[0]))){
			$userHash = trim($h[1]);
		}		
	}	  
   }  else{
	   
   }
	  
	  

		   
		   
      $errorsResults;

	  if(   200 !== $statusCode
		 || false===$code 
		 || !is_string($code) 
		 || (true === $withSaltedUrl && true === $this->withSalt() && (!isset($hash) || !isset($userHash)))

	       //|| false===$linted
		){	
		  return false;	
	  }

	
	  $oCode =$code;
   
     if(is_string($salt) && true === $withSaltedUrl && true === $this->withSalt() ){
	   $hash_check = strlen($oCode).'.'.sha1($oCode);
	   $userHash_check = sha1($salt .$hash_check);			 
		 
	   if($hash_check !== $hash || $userHash_check !== $userHash){
		  trigger_error('Invalid checksums while fetching source code for '.$class.' from '.$url, \E_USER_NOTICE);
		  return false;
	   }	   	
     }	
 
  $code = trim($code);
	  
    if(!$this->str_contains($code, '<?', false)){
		 trigger_error('Invalid source code for '.$class.' from '.$url.': '.base64_encode($code),\E_USER_NOTICE);
		 return false;
	}
	  
	  
  if('<?php' === substr($code, 0, strlen('<?php')) ){
	  $code = substr($code, strlen('<?php'), strlen($code));
  }
    $code = trim($code, '<?php> ');
  $codeWithStartTags = "<?php "."\n".$code;	
		
	  
	  
	  	  $linted =
			  $class === \webfan\hps\patch\Fs::class
		   || $class === \webfan\hps\patch\PhpBinFinder::class 
	       || $class===\frdl\Lint\Php::class 
		   || $class === \Symfony\Component\Process\PhpExecutableFinder::class
		   || $class === \Symfony\Component\Process\ExecutableFinder::class
		   || $class === \Symfony\Component\Process\PhpProcess::class
		   || __FILE__ === self::__FRDL_SVLC_SOURCE_LOCATION( get_class($this) )
		   || true === (new \frdl\Lint\Php($this->getCacheDir()))->lintString($code) 
		   ;
	  
	  if(true!== $linted){
		   trigger_error('Error php-linting '.$class.': '.$linted, \E_USER_NOTICE);
		   throw new \Exception('Error php-linting '.$class.': '.$linted);
		  return false;
	  }
	  
	  
    return $codeWithStartTags;
 }
	
	
/*	
	public function __invoke(){
	   return call_user_func_array($this->getLoader(), func_get_args());	
	}
*/

	public function register(/* $throw = true, */ $prepend = false){
		$args=func_get_args();
		$throw = true;
		if(count($args)>1){
		  $throw = array_shift($args);
		}
		if(count($args)>0){
		  $prepend = array_shift($args);
		}
		$res = false;
	
		
		if(!$this->allowFromSelfOrigin && $this->domain === $this->selfDomain){
		   throw new \Exception('You should not autoload from remote where you have local access to the source (remote server = host)');
		}		
		
		$aFuncs = \spl_autoload_functions();
		if(!is_array($aFuncs) || !in_array($this->getLoader(), $aFuncs) ){
			$res =  \spl_autoload_register($this->getLoader(), $throw, $prepend);
		}
		
		
			if( false !== $res  ){	             
                 $this->pruneCache();			
		    }else{
				 throw new \Exception(sprintf('Cannot register Autoloader of "%s" with cachedir "%s"', __METHOD__, $this->cacheDir));
			}
		
		
		
		return $res;
	}
	
	protected function getLoader(){
		return [$this, 'Autoload'];
	}
	


	
}

}
