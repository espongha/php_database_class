<?php

class MySQL implements DB{
	
	private $hostname;
	private $username;
	private $password;
	private $database;
	private $connection;
	private $trans;
	private $sql;
	private $statement;
	private $result;
	private $bind_param = array();
	private $reserved_words = array('CURRENT_TIMESTAMP', 'CURRENT_TIME', 'CURRENT_USER');

	public function connect($hostname, $database, $username, $password, $port=3306){
		$this->connection = mysqli_connect($hostname, $username, $password, $database, $port);
		if(!$this->connection){
			throw new Exception(@mysqli_error());
		}
	}

	public function begin_trans(){
		$this->trans = mysqli_autocommit($this->connection, FALSE);
		if(!$this->trans){
			throw new Exception(@mysqli_error($this->connection));
		}
	}
	
	public function select($table, $column='*', $where=null, $order=null){
		if(!$this->statement){
			@mysqli_stmt_close($this->statement);
			$this->statement = '';
		}
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
		$this->statement = @mysqli_prepare($this->connection, $this->sql);
		if(!$this->statement){
			throw new Exception(@mysqli_error($this->connection));
		}
	}

	public function update($table, $data, $where=null){
		if(!$this->statement){
			@mysqli_stmt_close($this->statement);
			$this->statement = '';
		}
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
		$this->statement = @mysqli_prepare($this->connection, $this->sql);
		if(!$this->statement){
			throw new Exception(@mysqli_error($this->connection));
		}
	}

	public function insert($table, $data){
		if(!$this->statement){
			@mysqli_stmt_close($this->statement);
			$this->statement = '';
		}
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
		$this->statement = @mysqli_prepare($this->connection, $this->sql);
		if(!$this->statement){
			throw new Exception(@mysqli_error($this->connection));
		}
	}
	
	public function delete($table, $where=null){
		if(!$this->statement){
			@mysqli_stmt_close($this->statement);
			$this->statement = '';
		}
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
		$this->statement = @mysqli_prepare($this->connection, $this->sql);
		if(!$this->statement){
			throw new Exception(@mysqli_error($this->connection));
		}
	}

	public function query($sql){
		if(!$this->statement){
			@mysqli_stmt_close($this->statement);
			$this->statement = '';
		}
		$this->sql = $sql;
		$this->statement = @mysqli_prepare($this->connection, $this->sql);
		if(!$this->statement){
			throw new Exception(@mysqli_error($this->connection));
		}
	}
	
	public function bindParam($value){
		$this->bind_param[] = $value;
	}
	
	public function execute(){
		if(!empty($this->bind_param)){
			foreach($this->bind_param as $value){
				if(is_numeric($value)){
					if((int) $value == $value){
						$bind_type .= "i";
					}else{
						$bind_type .= "d";
					}
					
				}else {
					$bind_type .= "s";
				}
			}
			$bind_type = array($bind_type);
			@call_user_func_array(mysqli_stmt_bind_param, $bind_type, $this->bind_param);
		}
		
		$this->result = mysqli_stmt_execute($this->statement);
	
		if(!$this->result){
			throw new Exception(@mysqli_error($this->connection) . $this->sql);
		}
		$this->sql = null;
		$this->bind_param = array();
	}
	
	public function fetch_row(){
		$ctr = 0;
		$data = array();
		try{
			$this->result = mysqli_stmt_get_result($this->statement);
			while($row = @mysqli_fetch_row($this->result)){
				foreach($row as $key => $value){
					$data[$ctr][$key] = $value;
				}
				$ctr++;
			}
			@mysqli_free_result($this->result);
		}
		catch(Exception $e){
			throw new Exception($e);
		}
		return $data;
	}
	
	public function fetch_assoc(){
		$ctr = 0;
		$data = array();
		$this->result = mysqli_stmt_get_result($this->statement);
		while($row = mysqli_fetch_assoc($this->result)){
			foreach($row as $key => $value){
				$data[$ctr][$key] = $value;
			}
			$ctr++;
		}
		@mysqli_free_result($this->result);
		
		return $data;
	}

	public function fetch_object(){
		$ctr = 0;
		$data = array();
		$this->result = mysqli_stmt_get_result($this->statement);
		while($row = mysqli_fetch_assoc($this->result)){
			$data[$ctr] = new StdClass();
			foreach($row as $key => $value){
				$data[$ctr]->{$key} = $value;
			}
			$ctr++;
		}
		mysqli_free_result($this->result);
		
		return $data;
	}

	public function rollback(){
		if(!$this->trans){
			$this->result = @mysqli_rollback($this->connection);
		}else{
			$this->result = @mysqli_rollback($this->connection);
			mysqli_autocommit($this->connection, TRUE);
			$this->trans = null;
		}
		if(!$this->result){
			throw new Exception(@mysqli_error($this->connection));
		}
	}

	public function commit(){
		if(!$this->trans){
			$this->result = mysqli_commit($this->connection);
		}else{
			$this->result = mysqli_commit($this->connection);
			mysqli_autocommit($this->connection, TRUE);
			$this->trans = null;
		}
		if(!$this->result){
			throw new Exception(@mysqli_error($this->connection));
		}
	}

	public function close(){
		$this->result = mysqli_close($this->connection);
		if(!$this->result){
			throw new Exception(@mysqli_error($this->connection));
		}
	}

}
	
?>