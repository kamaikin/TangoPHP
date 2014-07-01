<?php
if (!defined("DOCUMENT_ROOT")) {
	$file=substr(__DIR__, 0, -8);
	define("DOCUMENT_ROOT", $file);
}
class Tango{
	/**
 	 * @package Tango
 	 *
 	 * @author Камайкин Владимир Анатольевич <kamaikin@gmail.com>
 	 *
 	 * @version 0.1
 	 * @since since 2013-01-11
 	 */
	private static $_config;
	private static $_session;
	private static $_cache;
	private static $_image;
	private static $_fileStorage;
	private static $_registry;
	private static $_sql;
	private static $_plugins;
	private function __construct(){}
	private function __clone(){}

	public static function plugins($name){
		if (!isset(self::$_plugins[$name])) {
			$path=self::config()->get('Tangophp.plugins.plugins_patch', DOCUMENT_ROOT.'plugins/');
			$path.=$name.'/'.$name.'.class.php';
			if (file_exists($path)) {
				include_once $path;
				$name1 = 'Tango_'.$name;
				if (class_exists($name1)) {
					self::$_plugins[$name] = new $name1();
				}else{
					echo 'В файле: '.$path.' Не обранужен класс '.$name1.'<hr>'; exit;
				}
			}else{
				echo 'Отстутсвует файл - '.$path; exit;
			}
		}
		return self::$_plugins[$name];
	}

	/*
	 *	Реестр приложения.
	 */
	public static function registry($name, $data=NULL){
		if ($data===NULL) {
			if (isset(self::$_registry[$name])) {
				return self::$_registry[$name];
			} else {
				return NULL;
			}
		}else{
			self::$_registry[$name]=$data;
			return $data;
		}
	}
	/*
	 *	Инициализируем работу с базами данных поддерживающими стандарт SQL
	 *	Возвращаем класс
	 */
	public static function sql($key=0, $info=array()){
		if (!self::$_sql[$key]) {
			self::Load('sql');
			if ($info!=array()) {
				self::$_sql[$key]=new TSql($info);
			} else {
				self::$_sql[$key]=new TSql();
			}
		}
		return self::$_sql[$key];
	}

	/*
	 *	Возвращаем класс хранения файлов
	 */
	public static function fileStorage(){
		if (!self::$_fileStorage) {
			self::Load('fileStorage');
			self::$_fileStorage=new TFileStorage();
		}
		return self::$_fileStorage;
	}
	/*
	 *	Инициализируем работу с изображениями
	 *	Возвращаем класс изображений
	 */
	public static function image(){
		if (!self::$_image) {
			self::Load('image');
			self::$_cache=new TImage();
		}
		return self::$_cache;
	}

	public static function cache(){
		if (!self::$_cache) {
			self::Load('cache');
			self::$_cache=new TCache();
		}
		return self::$_cache;
	}
	/*
	 *	Инициализируем работу с сессией
	 *	Возвращаем класс сесии
	 */
	public static function session(){
		if (!self::$_session) {
			self::Load('session');
			self::$_session=new TSession();
		}
		return self::$_session;
	}
	/*
	 *	Инициализируем работу с системой конфигурации
	 *	Возвращаем класс конфигурации
	 */
	public static function config($config=''){
		if (!self::$_config) {
			self::Load('config');
			self::$_config=new TConfig($config);
		}else{
			if ($config!='') {self::$_config->fileInfo($config);}
		}
		return self::$_config;
	}

	/*
	 *	Пишем в лог файл.
	 *	Ничего не возвращаем.
	 */
	public static function Log($message, $file_name=''){
		$text = date("H:i:s").' '.$message."\n";
		$file=Tango::config()->get('tango.log.dir', DOCUMENT_ROOT.'tmp/log').'/log_'.date("Y_m_d").$file_name.'.log';
		@file_put_contents($file, $text, FILE_APPEND);
	}

	public static function Load($name){
		if (!defined("TANGO_FRAMEWORK_ROOT")) {
			$file=substr(__FILE__, 0, -9);
			define("TANGO_FRAMEWORK_ROOT", $file);
		}
		$name=explode("_", $name);
		foreach ($name as $key => $value) {
			$name[$key]=ucwords($value);
		}
		$class=ucwords($value);
		$name=implode("/", $name);
		$urls=array();
		$url=TANGO_FRAMEWORK_ROOT.$name.'/'.$class.'.class.php';
		//print_r($url);
		$urls[]=$url;
		if (file_exists($url)) {
			include_once $url;
			return TRUE;
		} else {
			//	В нутри фраймеверка такого файла нет....
			//	Получаем из конфига пути для поиска файлов и просматриваем по ним...
			$path=Tango::config()->get('tango.include.path', array());
			foreach ($path as $key => $value) {
				$url=$value.$name.'/'.$class.'.class.php';
				$urls[]=$url;
				if (file_exists($url)) {
					include_once $url;
					return TRUE;
				}
			}
		}
		//	Ничего не получилось, пишем в лог ошибку загрузки....
		self::Log('Не удалось загрузить класс по запросу - "'.implode(" ", $urls).'"');
		return FALSE;
	}

	public static function HPre($message){
		echo'<pre>'; echo $message; echo'</pre>'; exit;
	}
}

spl_autoload_register(function ($name) {
    Tango::Load($name);
});