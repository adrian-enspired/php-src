--TEST--
Modules: denial by an internal nested module names that module, not the public member
--FILE--
<?php
// A public member of an internal nested module is denied because its MODULE is internal.
module Outer {
    internal module Priv {
        public class Pub { const K = 1; public static function s() {} }
        internal class Hid {}
    }
    public module Open {
        internal class Hid {}
    }
}
foreach ([
    'new Outer\Priv\Pub;',
    'Outer\Priv\Pub::K;',
    'Outer\Priv\Pub::s();',
    'new Outer\Priv\Hid;',
    'new Outer\Open\Hid;',
    '$n = "Outer\Priv\Pub"; new $n;',
] as $code) {
    try { eval($code); echo "allowed\n"; } catch (Error $e) { echo $e->getMessage(), "\n"; }
}
?>
--EXPECT--
Cannot access "Outer\Priv\Pub": module "Outer\Priv" is internal to its parent module
Cannot access "Outer\Priv\Pub": module "Outer\Priv" is internal to its parent module
Cannot access "Outer\Priv\Pub": module "Outer\Priv" is internal to its parent module
Cannot access "Outer\Priv\Hid": module "Outer\Priv" is internal to its parent module
Cannot access internal module member "Outer\Open\Hid" from outside its module
Cannot access "Outer\Priv\Pub": module "Outer\Priv" is internal to its parent module
