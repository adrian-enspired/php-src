--TEST--
Modules: module/internal tokenize as keywords only in keyword positions
--EXTENSIONS--
tokenizer
--FILE--
<?php
$code = <<<'PHP'
<?php
class Module {}
module M { internal class C {} public internal(set) int $x; internal function f() {} }
use module::X;
function internal() {}
$a = M::Module;
$b = M::internal();
const INTERNAL = 1;
new Module;
PHP;
foreach (PhpToken::tokenize($code) as $t) {
    if (in_array(strtolower($t->text), ['module', 'internal', 'internal(set)'], true)) {
        echo $t->line, ' ', $t->getTokenName(), ' ', $t->text, "\n";
    }
}
?>
--EXPECT--
2 T_STRING Module
3 T_MODULE module
3 T_INTERNAL internal
3 T_INTERNAL_SET internal(set)
3 T_INTERNAL internal
4 T_MODULE module
5 T_STRING internal
6 T_STRING Module
7 T_STRING internal
8 T_STRING INTERNAL
9 T_STRING Module
