--TEST--
Modules: "module::Name" outside any module is a compile error
--FILE--
<?php
class Foo {}
$x = new module::Foo();
?>
--DESCRIPTION--
`module::` names a member of the enclosing module. Outside a module a bare `Module` is an ordinary
class name (`Module::CONST`, `Module::create()`), but "module::Foo" in a class-name position names
a module member, which needs a module.
--EXPECTF--
Fatal error: Cannot use "module::Foo" outside a module in %s on line %d
