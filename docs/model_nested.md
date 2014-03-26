
## Nested set model

Copyright 2013, Snowfire AB, snowfireit.com
Licensed under the MIT License.
Redistributions of files must retain the above copyright notice.

http://en.wikipedia.org/wiki/Nested_set_model



## `Database_Model_Nested::create()`

If you omit fields `left` and `right`, they will be added automatically. The node will then be added
next to the (last) root node.

Use `$field['next_to_node']` to put it after that node. Should contain the DB row.  
Use `$field['inside_node']` to make it the first child of that node. Should contain the DB row.




## `Database_Model_Nested::parent_path()`

Get the path from the node (exclusive) to the node's root (inclusive) as an array.





## `Database_Model_Nested::nested()`

Returns a nested array with `children` keys.

Options:

- `parent_id`. Get child nodes.





## `Database_Model_Nested::import()`

Work in progress.