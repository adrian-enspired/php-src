--TEST--
JSON POST: content-type parameters are ignored; raw body stays readable via php://input
--POST_RAW--
Content-Type: application/json; charset=UTF-8
{"key":"value"}
--FILE--
<?php
var_dump($_POST);
var_dump(file_get_contents("php://input"));
?>
--EXPECT--
array(1) {
  ["key"]=>
  string(5) "value"
}
string(15) "{"key":"value"}"
