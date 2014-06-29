<?php
/*
	Кладем файл в хранилище... возвращает или имя файла в хранилище при удаче или FALSE в случае если не удалось
	$file=Tango::fileStorage()->put(DOCUMENT_ROOT.'tmp/src.jpg');
	Получаем файл с заданым именем, файл будет поожен во временную папку с именем переданным в запросе.
	Tango::fileStorage()->get($file);
	Удаляем файл с переданным именем из хранилища
	Tango::fileStorage()->delete($file);
*/
class TFileStoragesqlite{
	private $config=FALSE;
	public function __construct(){
		$this->config=Tango::config()->get('tango.file.storage', array('dir_temp'=>DOCUMENT_ROOT.'tmp', 
			'db_patch'=>DOCUMENT_ROOT.'cms/file_upload', 
			'storage_nesting_depth'=>2));
	}

	//	Прочитать из хранилища
	public function get($file_name){
		//	Проверить находится ли файл в хранилище
		$patch=$this->_stoagePath($file_name);
		$patch=$patch.'/'.$file_name;
		if (file_exists($patch)) {
			//	Копируем файл во временную папку.
			$newfile=Tango::config()->get('dir_temp');
			$newfile=$newfile.$file_name;
			if (!copy($patch, $newfile)) {
			}else{
				return $newfile;
			}
		}else{
			return FALSE;
		}
	}

	//	Загрузить в хранилише
	public function put($file_name){
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
			//	Перемещаем файл в хранилище подновым именем
			rename($file_name, $patch);
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
		$patch=$this->config['storage_local_patch'];
		for ($i=0; $i < $this->config['storage_nesting_depth']; $i++) { 
			$start=$i*2;
			$patch=$patch.'/'.substr($file_name, $start, 2);
			@mkdir($patch);
		}
		return $patch;
	}

	private function _data_base($file){
		//	Проверить существование базы даных И если нет то создать ее)))))
		$file=Tango::config()->get('tango.file.storage.db_patch', DOCUMENT_ROOT.'cms/file_upload').substr($file, 0, 2).'.sqlite';
		$db = new PDO('sqlite:'.$file);
		if (!$db) exit("Не удалось создать базу данных!");
	}
}