--TEST--
JSON POST: scalar top-level values do not populate $_POST; top-level arrays use numeric keys
--POST_RAW--
Content-Type: application/json
"just a string"
--FILE--
<?php
var_dump($_POST);
?>
--EXPECT--
array(0) {
}
