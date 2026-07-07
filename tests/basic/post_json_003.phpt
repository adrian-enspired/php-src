--TEST--
JSON POST: invalid JSON leaves $_POST empty, body still available via php://input
--POST_RAW--
Content-Type: application/json
{"broken": tru
--FILE--
<?php
var_dump($_POST);
var_dump(file_get_contents("php://input"));
?>
--EXPECT--
array(0) {
}
string(14) "{"broken": tru"
