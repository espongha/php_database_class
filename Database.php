<?php

include dirname(__FILE__) . "/DB.php";

function __autoload($classname){
	include dirname(__FILE__) . "/driver/" . $classname . ".php";
}

class Database {

	public $driver;

	public function __construct($database){
		$this->driver = new $database;
	}
	
}