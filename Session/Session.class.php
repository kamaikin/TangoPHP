<?php
/**
 * @package Tango
 *
 * @author Камайкин Владимир Анатольевич <kamaikin@gmail.com>
 *
 * @version 0.1
 * @since since 2014-29-06
 */
class TSession{
	/**
	* 
	*/
	rivate $_flash=FALSE;

	public function __construct() {
		session_start();
		if (isset($_SESSION['__TANGOFLASH__'])) {
			$this->_flash=$_SESSION['__TANGOFLASH__'];
			unset($_SESSION['__TANGOFLASH__']);
		}
	}

	public function get($key){
		if (isset($_SESSION['__TANGO__'][$key])) {
			return $_SESSION['__TANGO__'][$key];
		} else {
			return FALSE;
		}
		
	}

	public function set($key, $value){
		$_SESSION['__TANGO__'][$key]=$value;
	}

	public function getFlash($key){

	}

	public function setFlash($key, $value){
		$_SESSION['__TANGOFLASH__'][$key]=$value;
	}
}