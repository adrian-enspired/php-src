--TEST--
unless is a statement, not an expression
--FILE--
<?php
$x = unless (true) {};
?>
--EXPECTF--
Parse error: syntax error, unexpected token "unless" in %s on line %d
