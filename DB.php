<?php
interface DB{
	
	//Trasaction connection
	public function connect($hostname, $database, $username, $password, $port);
	public function begin_trans();
	
	//Query builder for SQL trasaction
	public function select($table, $column, $where, $order);
	public function update($table, $data, $where);
	public function insert($table, $data);
	public function delete($table, $where);
	
	//Custom query and with binding variables
	public function query($sql);
	public function bindParam($value);
	
	//Executing query
	public function execute();
	
	//Fetching data
	public function fetch_row();
	public function fetch_assoc();
	public function fetch_object();
	
	//Trasaction status
	public function rollback();
	public function commit();
	
	//Trasaction connection
	public function close();
	
}