<?php
	/**
 	 * @package SQL
 	 *
 	 * @author Камайкин Владимир Анатольевич <kamaikin@gmail.com>
 	 *
 	 * @version 0.1
 	 * @since 2013-01-11
 	 */
	class TSql{
		/*
		 *	Массив с параметрами подключения к базе данных
		 */
		protected $_connect_data=array();
		/*
		 *	Объект PDO
		 */
		protected $_sql_pdo=FALSE;
		/*
		 *	Параметр показывает идет ли в данный момент какая либо транзакция.
		 */
		protected $_transaction=FALSE;
		public function __construct($connect_data=array()){
			$this->_connect_data=$connect_data;
		}

		/**
		 *	Запрос на вставку данных в базу данных
		 */
		public function insert($table, $data=array()){
			$this->getInit();
			if(strtolower(substr($table , 0, 6))!='insert'){
				$query="INSERT INTO ".$table." ";
				$names=array();
				$values=array();
				foreach($data as $k=>$v){
					$names[]="`".$k."`";
					$values[]=$this->_sql_pdo->quote($v);
				}
				$query.="( ".implode(", ", $names)." ) VALUES (".implode(", ", $values).");";
			}else{
				$query=$table;
			}
			$this->_sql_pdo->exec($query);
			$this->sqlError();
			return $this;
		}

		/**
		 *	Зпрос на множественную вставку данных в базу данных
		 */
		public function multiInsert($table, $data=array()){
			$this->getInit();
			//	INSERT INTO (id, name) VALUES (1, 'qwe'), (2, 'qwe'), ...
			if(strtolower(substr($table , 0, 6))!='insert'){
				$query="INSERT INTO ".$table." ";
				$names=array();
				$values=array();
				foreach($data as $k=>$v){
					if ($k==0) {
						foreach ($v as $key => $value) {
							$names[]="`".$key."`";
						}
					}
					foreach ($v as $key => $value) {
						$values[$k][]=$this->_sql_pdo->quote($value);
					}
				}
				foreach ($values as $key => $value) {
					$values[$key]='('.implode(", ", $value).')';
				}
				$query.="( ".implode(", ", $names)." ) VALUES ".implode(", ", $values).";";
			}else{
				$query=$table;
			}
			$this->_sql_pdo->exec($query);
			$this->sqlError();
			return $this;
		}

		/**
		 *	Запрос на изменение данных в базе данных
		 */
		public function update($table, $data=array(), $where='', $where_data=array()){
			$this->getInit();
			if(strtolower(substr($table , 0, 6))!='update'){
				//  Собираем запрос из компонентов
				$query="UPDATE ".$table." SET ";
				$array=array();
				foreach($data as $k=>$v){
					$array[]=" `".$k."` = ".$this->_sql_pdo->quote($v);
				}
				$query.=implode(", ", $array);
				$query.=" WHERE ".$where;
			}else{
				$query=$table;
			}
			if ($where_data!=array()) {
				$result=$this->_sql_pdo->prepare($query);
				$result = $this->bindValue($result, $where_data);
				$result->execute();
			} else {
				$this->_sql_pdo->exec($query);
			} 
			//print_r($query);
			$this->sqlError();
			return $this;
		}

		/**
		 *	Запрос на вставку или изменение данных, если данные с таким первичным ключем уже есть в базе
		 *	$array=array('insert'=>array('start_time'=>'time','last_time'=>'time'), 'update'=>array('user_id'=>'user_id','last_time'=>'time'))
		 */
		public function insertUpdate($table, $data=array()){
			$this->getInit();
			//	INSERT INTO table SET start_time=time, last_time=time ON DUPLICATE KEY UPDATE user_id = user_id, last_time=time
			if(strtolower(substr($table , 0, 6))!='insert'){
				$query="INSERT INTO ".$table." SET ";
				$array=array();
				foreach($data['insert'] as $k=>$v){
					$array[]=" `".$k."` = '".$this->_sql_pdo->quote($v)."'";
				}
				$query.=implode(", ", $array);
				$query=" ON DUPLICATE KEY UPDATE ";
				$array=array();
				foreach($data['update'] as $k=>$v){
					$array[]=" `".$k."` = '".$this->_sql_pdo->quote($v)."'";
				}
				$query.=implode(", ", $array);
			}else{
				$query=$table;
			}
			$this->_sql_pdo->exec($query);
			$this->sqlError();
			return $this;
		}

		/**
		 *	Принимает, или формирует запрос на удаление данных
		 */
		public function delete($table, $where='', $where_data=array()){
			$this->getInit();
			if(strtolower(substr($table , 0, 6))!='delete'){
				$query="DELETE FROM ".$table." WHERE ".$where;
				if ($where_data!=array()) {
					$result=$this->_sql_pdo->prepare($query);
					$result = $this->bindValue($result, $where_data);
					$result->execute();
				}else{
					$this->_sql_pdo->exec($query);
				}
			}else{
				$query=$table;
				$this->_sql_pdo->exec($query);
			}
			$this->sqlError();
			return $this;
		}

		/**
		 *	Делаем выборку из базы данных
		 */
		public function select($table, $data=array()){
			$this->getInit();
			if ($data!=array()) {
				$result=$this->_sql_pdo->prepare($table);
				$result = $this->bindValue($result, $data);
				$result->execute();
			} else {
				$result=$this->_sql_pdo->query($table);
			}
			$this->sqlError();
			return $result->fetchAll(PDO::FETCH_ASSOC);
		}

		/**
		 *	Возвращает последний уникальный идентификатор вставленной записи
		 */
		public function lastInsertId(){
			if(!$this->_sql_pdo){
				$this->init();
			}
			return $this->_sql_pdo->lastInsertId();
		}

		/*
		 *	ставит кавычки в строковых данных таким образом, что их становится безопасно использовать в запросах. 
		 */
		public function quote($value){
			if(!$this->_sql_pdo){
				$this->init();
			}
			return $this->_sql_pdo->quote($value);
		}

		/*
		 *	PDO лишь проверяет возможность транзакции на уровне драйвера. 
		 *	Если определенные условия времени выполнения и сложатся так, что транзакции будут не возможны, PDO:: beginTransaction () 
		 *	все еще возвратит TRUE без ошибки, если сервер базы данных разрешит, запросу запустить транзакцию. 
		 *	Но в итоге может произойти облом. В этом можно убедиться, запустив транзакцию к таблице MyISAM в базе данных MySQL. 
		 *	Если скрипт завершился, или вдруг оборвалось соединение с базой, если у вас есть открытые транзакции, PDO автоматически выполнит их откат. 
		 *	Это мера безопасности, чтобы избежать несогласованности в тех случаях, когда сценарий завершается неожиданно - 
		 *	если вы явно не фиксируете транзакцию, то предполагается, что что-то пошло не так, поэтому откат выполняется для безопасности ваших данных.
		 *	Автоматический откат происходит только, если Вы инициируете транзакцию явно через PDO:: beginTransaction (). 
		 *	В любом другом случае, PDO не знает о транзакции и не сможет откатить её, если что-то пошло не так.
		 */
		public function beginTransaction(){
			if (!$this->_transaction) {
				if(!$this->_sql_pdo){
					$this->init();
				}
				try{
					$this->_sql_pdo->beginTransaction();
					$this->_transaction=TRUE;
				} catch (Exception $e) {
					Tango::Log('Для данного драйвера транзакция недоступна. '.$e->getMessage());
					$this->_transaction=FALSE;
				}
			}
			return $this;
		}

		/*
		 *	Фиксация выполненной транзакции
		 */
		public function commit(){
			if ($this->_transaction) {
				$this->_sql_pdo->Commit();
				$this->_transaction=FALSE;
			}
			return $this;
		}

		/*
		 *	Откат транзакции
		 */
		public function rollback(){
			if ($this->_transaction) {
				$this->_sql_pdo->Rollback();
				$this->_transaction=FALSE;
			}
			return $this;
		}

		protected function bindValue($query, $data){
			if (isset($data[0])) {
				foreach ($data as $key => $value) {
					$k=$key+1;
					$query->bindValue($k, $value);
				}
			} else {
				foreach ($data as $key => $value) {
					$query->bindValue($key, $value);
				}
			}
			return $query;
		}

		protected function sqlError(){
			if($this->_sql_pdo->errorCode() != 0000){
				$error_array = $this->_sql_pdo->errorInfo();
				Tango::Log('Ошибка в запросе '.$error_array[2], '_SQL');
			}
		}

		/**
		 *	В скрипте отложенное соединение с базой данных, пока не будет необходимость выполнить первый запрос, 
		 *	соединение не устанавливается. метод getInit() как раз и вызывается для установки соединения.
		 */
		protected function getInit(){
			if(!$this->_sql_pdo){
				$this->init();
			}
		}

		/**
		 *	Инициализация подключения к базе данных.
		 *	Может проходить как из конфига так и из переданного массива. (если его передали конечно)
		 */
		protected function init(){
			if ($this->_connect_data!=array()) {
				if(isset($this->_connect_data['method'])){$method=$this->_connect_data['method'];}
				if(isset($this->_connect_data['sqlite_patch'])){$sqlite_patch=$this->_connect_data['sqlite_patch'];}
				if(isset($this->_connect_data['sql_host'])){$sql_host=$this->_connect_data['sql_host'];}
				if(isset($this->_connect_data['db_name'])){$db_name=$this->_connect_data['db_name'];}
				if(isset($this->_connect_data['db_user'])){$db_user=$this->_connect_data['db_user'];}
				if(isset($this->_connect_data['db_pass'])){$db_pass=$this->_connect_data['db_pass'];}
			} else {
				$method=Tango::config()->get('Tangophp.db.master.method', 'mysql');
				$sqlite_patch=Tango::config()->get('Tangophp.db.master.sqlite_patch', '');
				$sql_host = Tango::config()->get('Tangophp.db.master.host', '');
				$db_name=Tango::config()->get('Tangophp.db.master.base', '');
				$db_user=Tango::config()->get('Tangophp.db.master.user', '');
				$db_pass=Tango::config()->get('Tangophp.db.master.pass', '');
			}
			
			try {
				if($method=='mysql'){
					$this->_sql_pdo= new PDO("mysql:host=".$sql_host.";dbname=".$db_name, $db_user, $db_pass);
					$this->_sql_pdo->exec('SET NAMES utf8');
				}
				if($method=='pgsql'){
					$this->_sql_pdo= new PDO("pgsql:host=".$sql_host." dbname=".$db_name.' user='.$db_user.' password='.$db_pass);
				}
				if($method=='sqlite'){
					if ($sqlite_patch!='') {
						$this->_sql_pdo= new PDO("sqlite:".$sqlite_patch);
					}
				}
		    } catch (Exception $e) {
		      Tango::Log($e->getMessage());
		      exit;
		    }
		}
	}