## How to use it

Get new instance like this
```php
<?php
require 'Database.php';
$db = new Database('MySQL');
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