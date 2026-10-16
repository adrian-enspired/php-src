--TEST--
Modules: a module definition block takes an unqualified name
--DESCRIPTION--
A definition block's namespace comes from a preceding `namespace` statement; a nested module is
defined inside its parent (or in a member file of it). A qualified name in a definition block is
a syntax error.
--FILE--
<?php
namespace X\Y;
module Outer\Inner { public class C {} }
?>
--EXPECTF--
Parse error: syntax error, unexpected token "{", expecting ";" in %s on line %d
