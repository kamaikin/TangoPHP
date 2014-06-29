<?php
/*
	Кладем файл в хранилище... возвращает или имя файла в хранилище при удаче или FALSE в случае если не удалось
	$file=Tango::fileStorage()->put(DOCUMENT_ROOT.'tmp/src.jpg');
	Получаем файл с заданым именем, файл будет поожен во временную папку с именем переданным в запросе.
	Tango::fileStorage()->get($file);
	Удаляем файл с переданным именем из хранилища
	Tango::fileStorage()->delete($file);
*/
class TFileStoragelocal{
	private $config=FALSE;
	public function __construct(){
		$this->config=Tango::config()->get('Tangophp.filestorage', array('type'=>'local', 
			'dir_temp'=>DOCUMENT_ROOT.'/tmp/upload', 
			'local_patch'=>DOCUMENT_ROOT.'cms/file_upload', 
			'nesting_depth'=>2));
	}

	public function options($options=array()){
		foreach ($options as $key => $value) {
			$this->config[$key]=$value;
		}
		return $this;
	}

	public function testing($file_name){
		//	Посмотреть есть в хранилище нужный файл или нет.
		$patch=$this->_stoagePath($file_name);
		$patch=$patch.'/'.$file_name;
		//print_r($patch);
		if (file_exists($patch)) {
			return TRUE;
		}else{
			return FALSE;
		}
	}

	//	Прочитать из хранилища
	public function get($file_name, $dir='', $new_file_name='') {
    	$patch=$this->_stoagePath($file_name);
		$patch=$patch.'/'.$file_name;
        //	Проверить находится ли файл в хранилище
		if (file_exists($patch)) {
			//	Копируем файл во временную папку.
			if ($dir=='') {
				$newfile=$this->config['dir_temp'];
			}else{
				$newfile=$dir;
			}
			if ($new_file_name=='') {
				$newfile=$newfile.$file_name;
			} else {
				$newfile=$newfile.$new_file_name;
			}
			if (!copy($patch, $newfile)) {
				return FALSE;
			}else{
				return $newfile;
			}
		}else{
			return FALSE;
		}
    }

	//	Загрузить в хранилише
	public function put($file_name, $copy=FALSE) {
        //	Проверяем наличие файла
		if (file_exists($file_name)) {
			//	Получаем расширение
			$file=explode(".", $file_name);
			$ext=$file[count($file)-1];
			//	Формируем имя файла в хранилище
			$md_name=md5_file($file_name);
			$md_name=$md_name.'.'.$ext;
			$patch=$this->_stoagePath($md_name);
			$patch=$patch.'/'.$md_name;
			if ($copy) {
				//	Копируем файл в хранилище под новым именем
				copy($file_name, $patch);
			} else {
				//	Перемещаем файл в хранилище под новым именем
				rename($file_name, $patch);
			}
			return $md_name;
		} else {
			return FALSE;
		}
    }

	//	Удалить из хранилища
	public function delete($file_name){
		$patch=$this->_stoagePath($file_name);
		$patch=$patch.'/'.$file_name;
		if (file_exists($patch)) {
			//	удаляем файл
			@unlink($patch);
			return TRUE;
		}else{
			return FALSE;
		}
	}

	private function _stoagePath($file_name){
		$patch=$this->config['local_patch'];
		for ($i=0; $i < $this->config['nesting_depth']; $i++) { 
			$start=$i*2;
			$patch=$patch.'/'.substr($file_name, $start, 2);
			@mkdir($patch);
		}
		return $patch;
	}
}