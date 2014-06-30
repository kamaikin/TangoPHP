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
	private $_flash=FALSE;

	public function __construct() {
		session_start();
		if (isset($_SESSION['__TANGOFLASH__'])) {
			$this->_flash=$_SESSION['__TANGOFLASH__'];
			unset($_SESSION['__TANGOFLASH__']);
		}
		return $this;
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
		return $this;
	}

	public function delete($key){
		if (isset($_SESSION['__TANGO__'][$key])) {
			unset($_SESSION['__TANGO__'][$key]);
		}
		return $this;
	}

	public function getFlash($key){
		if (isset($this->_flash[$key])) {
			return $this->_flash[$key];
		} else {
			return FALSE;
		}
	}

	public function setFlash($key, $value){
		$_SESSION['__TANGOFLASH__'][$key]=$value;
		$this->_flash[$key]=$value;
		return $this;
	}
}