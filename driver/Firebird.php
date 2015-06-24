<?php

class Firebird implements DB{
	
	private $hostname;
	private $username;
	private $password;
	private $database;
	private $connection;
	private $trans;
	private $sql;
	private $result;
	private $bind_param = array();
	private $reserved_words = array('CURRENT_TIMESTAMP', 'CURRENT_TIME', 'CURRENT_USER');

	public function connect($hostname, $database, $username, $password, $port=3050){
		$this->connection = ibase_connect($hostname . "/" . $port . ":" . $database, $username, $password);
		if(!$this->connection){
			throw new Exception(ibase_errmsg());
		}
	}
	
	public function begin_trans(){
		$this->trans = ibase_trans(IBASE_DEFAULT, $this->connection);
	}

	public function select($table, $column='*', $where=null, $order=null){
		$selectcolumnkeys = "";
		$selectcolumnwhere = "";
		$selectcolumnorder = "";
		if($where != null){
			$selectwhere = array();
			foreach($where as $key => $value){
				if(in_array($value, $this->reserved_words)){
					$selectwhere[] = $key . "=" . $value;
				}else if(is_null($value)){
					$selectwhere[] = $key . "=NULL";
				}else{
					$selectwhere[] = $key . "='" . $value . "'";
				}
			}
			$selectcolumnwhere = "WHERE " . implode(" AND ", $selectwhere);
		}
		if($column != "*"){
			$selectcolumnkeys = implode(",", $column);
		}else{
			$selectcolumnkeys = $column;
		}
		if($order != null){
			$selectorder = array();
			foreach($order as $key => $value){
				if($value == "NONE"){
					$selectorder[] = $key;
				}else{
					$selectorder[] = $key . " " .$value;
				}
			}
			$selectcolumnorder = "ORDER BY " . implode(",", $selectorder);
		}
		$this->sql = "SELECT {$selectcolumnkeys} FROM {$table} {$selectcolumnwhere} {$selectcolumnorder}";
	}

	public function update($table, $data, $where=null){
		$updatecolumndata = "";
		$updatecolumnwhere = "";
		$updatedata = array();
		foreach($data as $key => $value){
			if(in_array($value, $this->reserved_words)){
				$updatedata[] = $key . "=" . $value;
			}else if(is_null($value)){
				$updatedata[] = $key . "=NULL";
			}else{
				$updatedata[] = $key . "='" . $value . "'";
			}
		}
		$updatecolumndata = implode(",", $updatedata);
		if($where != null){
			$updatewhere = array();
			foreach($where as $key => $value){
				if(is_numeric($value)){
					$updatewhere[] = $key . "=" . $value;
				}else if(in_array($value, $this->reserved_words)){
					$updatewhere[] = $key . "=" . $value;
				}else if(is_null($value)){
					$updatewhere[] = $key . "=NULL";
				}else{
					$updatewhere[] = $key . "='" . $value . "'";
				}
			}
			$updatecolumnwhere = "WHERE " . implode(" AND ", $updatewhere);
		}
		$this->sql = "UPDATE {$table} SET {$updatecolumndata} {$updatecolumnwhere}";
	}

	public function insert($table, $data){
		$insertdata = array();
		foreach($data as $key => $value){
			if(in_array($value, $this->reserved_words)){
				$insertdata[$key] = $value;
			}else if(is_null($value)){
				$insertdata[$key] = "NULL";
			}else{
				$insertdata[$key] = "'" . $value . "'";
			}
		}
		$insertcolumnkeys = implode(",", array_keys($insertdata));
		$insertcolumndata = implode(",", $insertdata);
		$this->sql = "INSERT INTO {$table} ({$insertcolumnkeys}) VALUES({$insertcolumndata})";
	}
	
	public function delete($table, $where=null){
		$deletewhere = array();
		foreach($where as $key => $value){
			if(in_array($value, $this->reserved_words)){
				$deletewhere[] = $key . "=" . $value;
			}else if(is_null($value)){
				$deletewhere[] = $key . "=NULL";
			}else{
				$deletewhere[] = $key . "='" . $value . "'";
			}
		}
		$deletecolumnwhere = "WHERE " . implode(" AND ", $deletewhere);
		$this->sql = "DELETE FROM {$table} {$deletecolumnwhere}";
	}

	public function query($sql){
		$this->sql = $sql;
	}
	
	public function bindParam($value){
		$this->bind_param[] = $value;
	}
	
	public function execute(){
		if(!$this->trans){
			$this->result = @call_user_func_array(ibase_query, array_merge(array($this->connection, $this->sql), $this->bind_param));
		}else{
			$this->result = @call_user_func_array(ibase_query, array_merge(array($this->trans, $this->sql), $this->bind_param));
		}
		
		if(!$this->result){
			throw new Exception(ibase_errmsg() . $this->sql);
		}
		
		$this->sql = null;
		$this->bind_param = array();
	}
	
	public function fetch_row(){
		$ctr = 0;
		$data = array();
		try{
			while($row = @ibase_fetch_row($this->result)){
				foreach($row as $key => $value){
					$data[$ctr][$key] = $value;
				}
				$ctr++;
			}
			ibase_free_result($this->result);
		}
		catch(Exception $e){
			throw new Exception($e);
		}
		
		return $data;
	}
	
	public function fetch_assoc(){
		$ctr = 0;
		$data = array();
		try{
			while($row = ibase_fetch_assoc($this->result)){
				foreach($row as $key => $value){
					$data[$ctr][$key] = $value;
				}
				$ctr++;
			}
			ibase_free_result($this->result);
		}
		catch(Exception $e){
			throw new Exception($e);
		}
		
		return $data;
	}

	public function fetch_object(){
		$ctr = 0;
		$data = array();
		try{
			while($row = ibase_fetch_assoc($this->result)){
				$data[$ctr] = new StdClass();
				foreach($row as $key => $value){
					$data[$ctr]->{$key} = $value;
				}
				$ctr++;
			}
			ibase_free_result($this->result);
		}
		catch(Exception $e){
			throw new Exception($e);
		}
		
		return $data;
	}

	public function rollback(){
		if(!$this->trans){
			$this->result = ibase_rollback($this->connection);
		}else{
			$this->result = ibase_rollback($this->trans);
			$this->trans = null;
		}
		if(!$this->result){
			throw new Exception(ibase_errmsg());
		}
	}

	public function commit(){
		if(!$this->trans){
			$this->result = ibase_commit($this->connection);
		}else{
			$this->result = ibase_rollback($this->trans);
			$this->trans = null;
		}
		if(!$this->result){
			throw new Exception(ibase_errmsg());
		}
	}

	public function close(){
		$this->result = ibase_close($this->connection);
		if(!$this->result){
			throw new Exception(ibase_errmsg());
		}
	}

}
