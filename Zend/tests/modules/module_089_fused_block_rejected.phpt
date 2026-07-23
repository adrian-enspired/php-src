--TEST--
Modules: the fused "module X\Y\Z { }" definition form is a syntax error
--DESCRIPTION--
A definition block's name is unqualified (grammar: `T_MODULE identifier '{' … '}'`). A
qualified name in that position no longer parses, so the old fused form is a plain syntax
error — the namespace must be a separate `namespace X\Y;` statement.
--FILE--
<?php
module X\Y\Z {
    public class C {}
}
?>
--EXPECTF--
Parse error: syntax error, unexpected %s in %s on line %d
