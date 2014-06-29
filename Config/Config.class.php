<?php
/*
 *
 */
class TConfig{
	protected $_config = array();
	protected $_file = '';
	/**
	* 
	*/
	public function __construct($file) {
		$this->_file=$file;
		$this->init();
	}

	public function init(){
		if (file_exists($this->_file)) {
			$this->_config=json_decode(file_get_contents($this->_file), TRUE);
		}else{
			$this->_config=array();
		}
	}

	public function get($name, $default=FALSE){
		$name=$this->keyPrepare($name);
		//print_r($name); exit;
		if (count($name)==1) {return (isset($this->_config[$name[0]])) ? $this->_config[$name[0]] : $default;}
		if (count($name)==2) {return (isset($this->_config[$name[0]][$name[1]])) ? $this->_config[$name[0]][$name[1]] : $default;}
		if (count($name)==3) {return (isset($this->_config[$name[0]][$name[1]][$name[2]])) ? $this->_config[$name[0]][$name[1]][$name[2]] : $default;}
		if (count($name) > 3) {return (isset($this->_config[$name[0]][$name[1]][$name[2]][$name[3]])) ? $this->_config[$name[0]][$name[1]][$name[2]][$name[3]] : $default;}
	}

	public function set($name, $obj){
		$name=$this->keyPrepare($name);
		//print_r($name); exit;
		if (count($name)==1) {return $this->_config[$name[0]] = $obj;}
		if (count($name)==2) {return $this->_config[$name[0]][$name[1]] = $obj;}
		if (count($name)==3) {return $this->_config[$name[0]][$name[1]][$name[2]] = $obj;}
		if (count($name) > 3) {return $this->_config[$name[0]][$name[1]][$name[2]][$name[3]] = $obj;}
	}

	public function save(){
		@unlink($this->_file);
		file_put_contents($this->_file, json_encode($this->_config));
	}

	private function keyPrepare($key){
		return explode(".", $key);
	}
}