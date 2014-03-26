
# Query

Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
Licensed under the MIT License.
Redistributions of files must retain the above copyright notice.

Create SQL queries.




## Basic usage

To create a query like

	SELECT `id` FROM pages WHERE `id` IN (1, 2, 3) AND is_active = 1

use this code

	$ids = array(1, 2, 3);
	$query = new Lib\Database_Query($db);
	$query->select('id')->from('pages')->where(array(
		'id' => $ids,
		'is_active' => 1
	));





## Reset

To reset a parameter, call it with `null`, like

	$query->where(null);

To reset the query, use `Database_Query::clear()`.





## Debug

You can easily echo the query.

	echo $query;





## Execute

`Database_Query::execute()` runs the query on the provided database adapter 
(provided to `Database_Query::__construct()`).

`$options` are passed to `Database::many()` and `Database::one()`.

If the query is a `SELECT` query, and `LIMIT 1` is set, then `Database::one()` 
is run. Otherwise `Database::many()` is run, or `Database::execute()` if not a
`SELECT` query.






## Query parameters



### `Database_Query::set()`

	$query->set('column', 'value');
	$query->set(array(
		'column1' => 'value1',
		'column2' => 'value2'
	));



### `Database_Query::values()`

	$query->values(array('first_name' => 'Jason', 'last_name' => 'Bourne'));
	$query->values(array(
		array('first_name' => 'Jason1', 'last_name' => 'Bourne1'),
		array('first_name' => 'Jason2', 'last_name' => 'Bourne2'),
		array('first_name' => 'Jason3', 'last_name' => 'Bourne3')
	));




### `Database_Query::where()`

	$query->where('raw = "query"');
	$query->where('raw = ?', array('query'));
	$query->where('raw', 'query');
	$query->where('raw !=', 'query');
	$query->where('col <', 10);
	$query->where(array('raw' => 'query'));
	$query->where(array(
		'col1' => 'val1',
		'col2' => array(1, 2, 3),
		'col3 !=' => 'val3',
		'col4' => null
	));


#### `null`

Query `$query->where('column', null)` generates `WHERE column IS NULL`.


#### Array

Query `$query->where('column', array(1, 2))` generates `WHERE column IN (1, 2)`.  
Query `$query->where('column', array())` generates `WHERE column AND FALSE`.  
Query `$query->where('column !=', array())` generates `WHERE column OR TRUE`.
