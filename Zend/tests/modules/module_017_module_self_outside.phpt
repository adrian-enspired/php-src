--TEST--
Modules: "module::" outside any module is an ordinary class reference
--FILE--
<?php
class Foo {}
$x = new module::Foo();
?>
--EXPECTF--
Fatal error: Uncaught Error: Class "module::Foo" not found in %s:%d
Stack trace:
#0 {main}
  thrown in %s on line %d
