<?php
class TFileStorage{
	private $class = array();
	public function __construct(){
		$data=Tango::config()->get('Tangophp.filestorage.type', 'local');
		$file=TANGO_FRAMEWORK_ROOT.'FileStorage/modules/'.$data.'.class.php';
		include_once($file);
		$class_name = 'TFileStorage'.$data;
		$this->class = new $class_name;
	}

	public function get($file_name, $dir='', $new_file_name=''){
		return $this->class->get($file_name, $dir, $new_file_name);
	}

	public function put($file_name, $copy=FALSE){
		return $this->class->put($file_name, $copy);
	}

	public function delete($file_name){
		return $this->class->delete($file_name);
	}

	public function testing($file_name){
		return $this->class->testing($file_name);
	}

	public function options($array=array()){
		return $this->class->options($array);
	}
}