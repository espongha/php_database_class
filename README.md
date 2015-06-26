## How to use it

Get new instance like this
```php
<?php
require 'Database.php';
$db = new Database('MySQL');
```
setting the connection configuration
```php
$db->driver->connect('hostname', 'database', 'username', 'password');
```

### Basic Methods

See below basic methods to call select, update, delete and query in the class.

#### Select
```php
$db_column = array(
				'column1',
				'column2'
			 );
			  
$db_where = array(
				'search_column_name' => 'search_value'
			);
			  
$db_order = array(
				'column_name_to_order' => 'desc'
			);
			  
$db->driver->select('tablename', $db_column, $db_where, $db_order);

$db->driver->execute();
```
#### Update
```php
$db_column = array(
				'column1' => 'column1_value',
				'column2' => 'column2_value'
			 );
			 
$db_where = array(
				'search_column_name' => 'search_value'
			)

$db->driver->update('tablename', $db_column, $db_where);

$db->driver->execute();
```
#### Delete
```php
$db_where = array(
				'search_column_name' => 'search_value'
			)

$db->driver->delete('tablename', $db_where);

$db->driver->execute();
```

### Fetching Data

See below methods for different ways in fething data using php database class.

#### Fetch Row
```php
$db_column = array(
				'column1',
				'column2'
			 );
			  
$db->driver->select('tablename', $db_column);

$db->driver->execute();

foreach($db->driver->fetch_row() as $value){
	echo $value[0];
	echo $value[1];
}
```
#### Fetch Associative
```php
$db_column = array(
				'column1',
				'column2'
			 );
			  
$db->driver->select('tablename', $db_column);

$db->driver->execute();

foreach($db->driver->fetch_assoc() as $value){
	echo $value['column1'];
	echo $value['column2'];
}
```
#### Fetch Object
```php
$db_column = array(
				'column1',
				'column2'
			 );
			  
$db->driver->select('tablename', $db_column);

$db->driver->execute();

foreach($db->driver->fetch_object() as $value){
	echo $value->column1;
	echo $value->column2;
}
```

### Other Database class methods

see below other useful methods of PHP Database Class.

#### Closing connection
```php
$db->driver->close();
```
#### Beginning transaction
```php
$db->driver->begin_trans();

$db_where = array(
				'search_column_name' => 'search_value'
			)

$db->driver->delete('tablename', $db_where);

$db->driver->execute();

$db->driver->commit();
```
#### Rollback transaction
```php
$db->driver->rollback();
```
#### Commiting transaction
```php
$db->driver->commit();
```