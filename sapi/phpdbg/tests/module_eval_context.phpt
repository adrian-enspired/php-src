--TEST--
ev while stopped in module code runs as that module
--PHPDBG--
b module_eval_context.php:7
r
ev get_class(new module::Secret)
ev get_class(new \M\Secret)
c
ev get_class(new \M\Secret)
q
--EXPECTF--
[Successful compilation of %s]
prompt> [Breakpoint #0 added at %s:7]
prompt> [Breakpoint #0 at %s:7, hits: 1]
>00007:         return $x;
 00008:     }
 00009: }
prompt> M\Secret
prompt> M\Secret
prompt> [Script ended normally]
prompt> %AUncaught Error: Cannot access internal module member "M\Secret" from outside its module in eval()'d code:1
%A
--FILE--
<?php

module M {
    internal class Secret {}
    public function f() {
        $x = 1;
        return $x;
    }
}
M::f();
