--TEST--
JSON POST: top-level JSON array populates $_POST with numeric keys; numeric-string object keys behave like symtable keys
--POST_RAW--
Content-Type: application/json
[10, 20, {"0": "zero", "k": "v"}]
--FILE--
<?php
var_dump($_POST);
?>
--EXPECT--
array(3) {
  [0]=>
  int(10)
  [1]=>
  int(20)
  [2]=>
  array(2) {
    [0]=>
    string(4) "zero"
    ["k"]=>
    string(1) "v"
  }
}
