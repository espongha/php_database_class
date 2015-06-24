<?php
class Oracle implements DB{
	
	private $hostname;
	private $username;
	private $password;
	private $database;
	private $connection;
	private $sql;
	private $result;
	private $bind_param = array();
	private $reserved_words = array('CURRENT_TIMESTAMP', 'CURRENT_TIME', 'CURRENT_USER');

	public function connect($hostname, $database, $username, $password, $port){
		$port = ($port == null ? 1521 : $port);
		$this->connection = @oci_connect($username, $password, $hostname . ':' . $port . '/' . $database);
		if(!$this->connection){
			$e = @oci_error();
			throw new Exception($e['message']);
		}
	}

	public function select($table, $column, $where, $order){
		$selectcolumnkeys = "";
		$selectcolumnwhere = "";
		$selectcolumnorder = "";
		if($where != null){
			$selectwhere = array();
			foreach($where as $key => $value){
				$value = str_replace("'", "''", $value);
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
		$this->result = @oci_parse($this->connection, $this->sql);
		if(!$this->result){
			$e = @oci_error($this->connection);
			throw new Exception($e['message']);
		}
	}

	public function update($table, $data, $where){
		$updatecolumndata = "";
		$updatecolumnwhere = "";
		$updatedata = array();
		foreach($data as $key => $value){
			$value = str_replace("'", "''", $value);
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
				$value = str_replace("'", "''", $value);
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
		$this->result = @oci_parse($this->connection, $this->sql);
		if(!$this->result){
			$e = @oci_error($this->connection);
			throw new Exception($e['message']);
		}
	}

	public function insert($table, $data){
		$insertdata = array();
		foreach($data as $key => $value){
			$value = str_replace("'", "''", $value);
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
		$this->result = @oci_parse($this->connection, $this->sql);
		if(!$this->result){
			$e = @oci_error($this->connection);
			throw new Exception($e['message']);
		}
	}
	
	public function delete($table, $where){
		$deletewhere = array();
		foreach($where as $key => $value){
			$value = str_replace("'", "''", $value);
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
		$this->result = @oci_parse($this->connection, $this->sql);
		if(!$this->result){
			$e = @oci_error($this->connection);
			throw new Exception($e['message']);
		}
	}

	public function query($sql){
		$this->sql = $sql;
		$this->result = @oci_parse($this->connection, $this->sql);
		if(!$this->result){
			$e = @oci_error($this->connection);
			throw new Exception($e['message']);
		}
	}
	
	public function bindParam($value){
		foreach($value as $bind_key => $bind_value){
			$result = @oci_bind_by_name($this->result, $bind_key, $bind_value);
			if(!$result){
				$e = @oci_error($this->connection);
				throw new Exception($e['message']);
			}
		}
	}
	
	public function execute($commit){
		if($commit == true){
			$result = @oci_execute($this->result);
			if(!$result){
				$e = @oci_error($this->connection);
				throw new Exception($e['message']);
			}
		}else{
			$result = @oci_execute($this->result, OCI_NO_AUTO_COMMIT);
			if(!$result){
				$e = @oci_error($this->connection);
				throw new Exception($e['message']);
			}
		}
	}
	
	public function fetch_row(){
		$ctr = 0;
		$data = array();
		try{
			while($row = @oci_fetch_row($this->result)){
				foreach($row as $key => $value){
					$data[$ctr][$key] = $value;
				}
				$ctr++;
			}
			@oci_free_statement($this->result);
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
			while($row = @oci_fetch_assoc($this->result)){
				foreach($row as $key => $value){
					$data[$ctr][$key] = $value;
				}
				$ctr++;
			}
			@oci_free_statement($this->result);
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
			while($row = @oci_fetch_assoc($this->result)){
				$data[$ctr] = new StdClass();
				foreach($row as $key => $value){
					$data[$ctr]->{$key} = $value;
				}
				$ctr++;
			}
			@oci_free_statement($this->result);
		}
		catch(Exception $e){
			throw new Exception($e);
		}
		
		return $data;
	}
	
	public function rollback(){
		$result = @oci_rollback($this->connection);
		if(!$result){
			$e = @oci_error($this->connection);
			throw new Exception($e['message']);
		}
	}
	
	public function commit(){
		$result = @oci_commit($this->connection);
		if(!$result){
			$e = @oci_error($this->connection);
			throw new Exception($e['message']);
		}
	}
	
	public function close(){
		$result = oci_close($this->connection);
		if(!$result){
			$e = @oci_error($this->connection);
			throw new Exception($e['message']);
		}
	}

}

?>