# Model

Copyright 2013, Markus Hedlund <markus@snowfire.net>, Snowfire AB, snowfire.net
Licensed under the MIT License.
Redistributions of files must retain the above copyright notice.



# Creating a model

## Database naming convention

Example table structure:

	order: id, person_id*, message
	order_products: order_id*, product_id*
	products: id, name
	people: id, first_name, last_name


Foreign keys use the foreign singular, and appends `_id`. Everything is lowercase and underscore.



## Options

None of the following options are required.

	protected static $_table = 'addresses';
	protected static $_singular = 'address';
	protected static $_plural = 'addresses';
	protected static $_special_fields = array('create_date' => 'created_on', 'datetime' => array('updated_on'));
	protected static $_active_condition = array('state' => 'ACTIVE');
	protected static $_many_default_parameters = array('columns' => 'id, property_id, name', 'order_by' => 'order');
	protected static $_foreign = array(
		array('name' => 'categories', 'type' => 'many_to_many'),
		array('name' => 'areas', 'type' => 'many_to_many'),
		array('name' => 'addresses', 'type' => 'one_to_one'),
		array('name' => 'addresses', 'type' => 'one_to_one', 'singular' => 'billing_address'),
		array('name' => 'images', 'type' => 'one_to_one', 'singular' => 'logo_image'),
		array('name' => 'images', 'type' => 'one_to_one', 'singular' => 'person_image')
	);


### `$_table`

Defaults to the class name of the model.


### `$_singular`

Needs to be set to use foreign keys.


### `$_plural`

Defaults to the table name.


### `$_special_fields`

An array with keys:

- `create_date`. The column name that contains the created date. Will set this column.
- `datetime`. Columns that should be converted to MySQL datetime. Converts from unix timestamp on inserts, and 
  to unix timestamps on retrieval.


### `$_active_condition`

Can be used to make `Model::one()` and `Model::many()` only find active records, for example.
The variable `$_active_condition` will be sent to `Database_Query::where()`.


### `$_many_default_parameters`

The defaults parameters for `Model::many()`. Since `Model::one()` uses `Model::many()`,
these will also affect that method.


### `$_foreign`

An array defining the foreign keys. Every array item is an array of the following keys:

- `name`. Foreign resources name / table name.
- `type`. `many_to_many` / `one_to_many` / `one_to_one`.
- `singular` (optional). Will be used for the column name.

#### Many to many

Example table structure:

	order: id, person_id*
	order_products: order_id*, product_id*
	products: id, name


#### One to many

Example table structure:

	user: id, username
	user_notes: id, user_id*, note


#### One to one

*Please note* that the foreign object will also be deleted, if a one to one relationship is defined.

Example table structure:

	order: id, person_id*
	people: id, first_name, last_name



## Triggers

These will be triggered before (`_pre`) or after (`_post`) a CRUD method has executed. Useful to manipulate
the in-data or to do some post processing.

	protected function _create_pre(array &$fields, &$foreign_models = array()) {}
	protected function _delete_pre(&$rows, &$conditions, &$foreign_models = array()) {}
	protected function _delete_post($rows, $conditions, $foreign_models = array()) {}
	protected function _edit_pre(&$conditions, array &$fields) {}



## Raw queries

Use these methods when you need to write raw queries.

	Model::_query_raw()
	Model::_query_select([optional select parameters])
	Model::_query_update()
	Model::_query_insert()
	Model::_query_delete()

`Model::_query_select()` return a `Database_Query` with "select" and "from" set.
`Model::_query_update()` return a `Database_Query` with "update" set.
`Model::_query_insert()` return a `Database_Query` with "insert" set.
`Model::_query_delete()` return a `Database_Query` with "delete" set.



## Transactions

Use `Model::_transaction_begin()` and `Model::_transaction_end()` for transactions. See `Database`.






# Using

## Intro

A model needs to instantiated.

	$orders_model = new Orders();

The model provides CRUD functionality:

	public function create(array $fields, $foreign_models = array());
	public function edit($conditions, array $fields);
	public function delete($conditions, $foreign_models = array());

Reading from the DB consists of two methods, `Model::one()` and `Model::many()`. 
`Model::one()` fetches the first row, `Model::many()` fetches all rows from the result set.

	public function one($p = array());
	public function many($p = array());

There is also a method to check existence.

	public function exists($conditions = array());

Parameter `$conditions` is passed to `Model::one()` as option `conditions`.



## Foreign

If a model have foreigns defined, you must provide instances of these models as well, when using
the CRUD methods.	



## Create

	$orders_model->create(array(
		'message' => 'Some message'
	));

Will create an order and set the message column.

	$orders_model->create(array(
		'message' => 'Some message',
		'person' => array('first_name' => 'Will', 'last_name' = > 'Smith')
	), array('people' => new People());

Will create an order, and also a person using the People model. This works for one to one relationships.

For many to many relationships, use:

	$orders_model->create(array(
		'message' => 'Some message',
		'product_ids' => array(4, 9)
	), array('products' => new Products());



## Edit

Edit doesn't affect foreigns.



## Delete

For one to one relationships, this will delete the foreign object.

For many to many, this will delete the relation.



## Fetch

`Model::one()` uses `Model::many()`.



### Options

- `columns`. Passed to `Database_Query::select()`.
- `conditions`. Passed to `Database_Query::where()`.
- `limit`. Passed to `Database_Query::limit()`. Limit=1 will return only the first row.
- `order_by`. Passed to `Database_Query::order_by()`.
- `id_indexed`. Sets the array index of every row to the value of the "id" column.
- `value_column`. Replaces the row with just one column value.
- `ignore_active_condition`. Ignores any `$_active_condition`.
- `single_column`. See `Database::one()` / `Database::many()` options.
- `foreign_models`. Foreign model instances.



### Foreigns

If `foreign_models` are provided, and foreigns are setup, the resultset will be populated with foreigns.

Exemple database structure:

	order: id, person_id*, message
	order_notes: id, order_id*, message
	order_products: order_id*, product_id*
	products: id, name
	people: id, first_name, last_name


#### One to one

Fetching using the Order model, would return something like:

	{ id: 1, person: { first_name: 'A', last_name: 'B' } }


#### One to many

Fetching using the Order model, would return something like:

	{ id: 1, notes: [{ id: '14', message: 'A' }, { id: '25', name: 'B' }] }


#### Many to many

Fetching using the Order model, would return something like:

	{ id: 1, products: [{ id: '44', name: 'A' }, { id: '45', name: 'B' }] }

