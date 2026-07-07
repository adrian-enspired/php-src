--TEST--
JSON POST: enable_post_data_reading=0 disables $_POST population for JSON like any other content type
--INI--
enable_post_data_reading=0
--POST_RAW--
Content-Type: application/json
{"key":"value"}
--FILE--
<?php
var_dump($_POST);
var_dump(file_get_contents("php://input"));
?>
--EXPECT--
array(0) {
}
string(15) "{"key":"value"}"
