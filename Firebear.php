<?php
/**
 * FireBear Screen PHP5 Library
 *
 * Планируется в следующей версии:
 * 1. структурирование файлов по папкам
 * 2. работа с базой данных
 * 3. работа с различными типами изображений
 * 
 * @author NonRealDeveloper
 * @version 0.3.0
 */
class Firebear
{
	/** шаблон пути к скриншоту */
	public $filepattern = 'screens/<FILE>.jpg';
	
	/** путь к файлу ошибки */
	public $badfilepath = 'bad.jpg';
	
	/**
	 * Размер оптимизированого изображения.
	 * Формат: width:height
	 *
	 * Если указать вместо какого-либо из параметров 0, 
	 * то во время оптимизации он не изменится.
	 *
	 * если original size = 500x500 то:
	 * 458:343 	- width 458, height 343
	 * 458:0 	- width 458, height 500
	 * 0:400 	- width 500, height 400
	 * 0:0 		- width 500, height 500
	 */
	public $screenssize = '458:343';
	
	/** использовать оптимизатор true/false */
	public $gdoptimizer = false;
	
	/** tools path */
	public $pathtotools = 'tools';
	
	/**
	 * Конструктор класса
	 * @param array $config конфигурация класса
	 */
	public function __construct ($config = false)
	{
		if (is_array($config))
		{
			foreach ($config as $key => $value)
			{
				$this->$key = $value;
			}
		}
	}
	
	/**
	 * Получить путь к файлу скриншота, основываясь на filepattern
	 * @param string $text текст для поиска скриншота
	 * @return string
	 */
	public function Path ($text)
	{
		return str_replace('<FILE>', md5($text), $this->filepattern);
	}
	
	/**
	 * Создать скриншот (если нету) и вывести его в браузер
	 * @param string $text текст для поиска скриншота
	 * @param string $fname имя файла, если false то используется шаблон
	 */
	public function ShowScreen ($text, $fname = false)
	{
		$fname = ($fname) ? $fname : $this->Path($text);
		$this->MakeScreen($text, $fname);
		header("Location: $fname", true, 302);
		exit;
	}
	
	/**
	 * Создать скриншот (если нету)
	 * @param string $text текст для поиска скриншота
	 * @param string $fname имя файла, если false то используется шаблон
	 */
	public function MakeScreen ($text, $fname = false)
	{
		$fname = ($fname) ? $fname : $this->Path($text);
		if (! file_exists($fname)) {
			if (! $result = $this->GetImageFromGoogle($text, $fname)) {
				$this->NoScreenShotAvailable($fname);
			}	$this->InitImageOptimizer($fname);
		}
	}
	
	/**
	 * Оптимизировать скриншот, подогнав его под нужные размеры
	 * @param string $fname имя файла
	 */
	public function InitImageOptimizer ($fname)
	{
		if ($this->gdoptimizer && function_exists('imagetypes'))
		{
			list($width, $height, $type, $attr) = getimagesize($fname);
			$needwidth = intval(strtok($this->screenssize, ':'));
			$needheight = intval(strtok(':'));
			if ($needwidth === 0) $needwidth = $width;
			if ($needheight === 0) $needheight = $height;
			if (!(($width === $needwidth) && ($height === $needheight)))
			{
				$at = array('jpeg','png','gif');
				$imgext = image_type_to_extension($type, false);
				if (!in_array($imgext, $at)) return false;
				$saving = 'image'.$imgext;
				$create = 'imagecreatefrom'.$imgext;
				$source = $create($fname);
				$to = imagecreatetruecolor($needwidth, $needheight);
				imagecopyresized($to, $source, 0, 0, 0, 0, $needwidth, $needheight, $width, $height);
				unlink($fname); $saving($to, $fname); imagedestroy($to); imagedestroy($source);
			}
		}
	}
	
	/**
	 * Создать скриншот используя badfilepath
	 * @param string $fname имя файла
	 */
	public function NoScreenShotAvailable ($fname)
	{
		if ($src = @fopen($this->badfilepath, 'r'))
		{
			$write = fopen($fname, 'w');
			stream_copy_to_stream($src, $write);
			fclose($fname);
		}
		else
		{
			header("HTTP/1.1 404 Not Found");
			exit;
		}
	}
	
	/**
	 * Создать скриншот используя Google Images
	 * @param string $text текст для поиска скриншота
	 * @param string $fname имя файла, если false то используется шаблон
	 * @return bool
	 */
	public function GetImageFromGoogle ($text, $fname = false)
	{
		$fname = ($fname) ? $fname : $this->Path($text);
		$request = file_get_contents("http://images.google.com/images?hl=ru&source=imghp&safe=off&tbs=isch:1,isz:m&source=lnt&q=".urlencode($text));
		preg_match_all("/imgurl\S+(http\:\/\/\S+jpe?g)\S+imgrefurl/i", $request, $out);
		if	(	($src = @fopen($out[1][0], 'r')) 
				or (isset($out[1][1]) and $src = @fopen($out[1][1], 'r')) 
				or (isset($out[1][2]) and $src = @fopen($out[1][2], 'r'))
			)
		{
			$write = fopen($fname, 'w');
			stream_copy_to_stream($src, $write);
			fclose($fname);
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Сделать скриншот окна
	 * 
	 * Для Windows требуется .NET Framework 4.
	 * Под linux требуется scrot (debian/ubuntu: apt-get install scrot)
	 * @param string $filename имя файла
	 * @param string $windowname заголовок окна
	 * @param bool $optimize оптимизировать ли изображение?
	 * @param string $mimetype тип миме изображения (auto, image/png, image/jpeg, image/gif...)
	 * @return bool
	 */
	public function PrintScreen($filename, $windowname = '', $optimize = true, $mimetype = 'auto')
	{
		$mimetype = ($mimetype === 'auto') ? 
			self::_imagename_to_mime($filename) : $mimetype;
			
		if (PHP_OS == 'WINNT')
		{
			clearstatcache(true, $filename);
			if (file_exists($filename)) unlink($filename);
			shell_exec($this->pathtotools.'\\scrmake.exe "'.iconv("UTF-8", "Windows-1251", $windowname).'" "'.$filename.'" "'.$mimetype.'"');
			list($width, $height) = getimagesize($filename);
			if (intval($width.$height) === 11) return false;
			if ($optimize) $this->InitImageOptimizer($filename);
			return true;
		}
		elseif (PHP_OS == 'Linux')
		{
			clearstatcache(true, $filename);
			if (file_exists($filename)) unlink($filename);
			shell_exec("scrot $filename -b");
			if ($optimize) $this->InitImageOptimizer($filename);
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Запустить фоновую задачу
	 * 
	 * Для Windows требуется COM+ и Windows Script Host
	 * @param string $process название процесса
	 * @param integer $sleeptime время ожидания запуска, по умолчанию 2 секунды
	 * @return self|false
	 */
	public function InitDaemon ($process, $sleeptime = 2)
	{
		if (PHP_OS == 'WINNT')
		{
			$WshShell = new COM('WScript.Shell');
			$WshShell->Run($process, 1, false);
			sleep($sleeptime);
			return $this;
		}
		elseif (PHP_OS == 'Linux')
		{
			popen($process, 'r');
			sleep($sleeptime);
			return $this;
		}
		else
		{
			return false;
		}
	}
	
	private static function _imagename_to_mime ($image_filename) {
		return ($pos = strrpos($image_filename, '.')) ? 'image/'.str_replace(array('jpg', 'pjpg', 'pjpeg'), 'jpeg', substr($image_filename, $pos + 1)) : false;
	}
}