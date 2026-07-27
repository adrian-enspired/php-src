--TEST--
Module functions: use function imports, string callables, first-class callables, dynamic calls
--FILE--
<?php
use function Pkg::pub;

module Pkg {
    public function pub(): string { return "pub"; }
    internal function priv(): string { return "priv"; }
    public class Maker {
        public function firstClassInternal(): callable { return priv(...); }
    }
}

// use function M::f -> bare call
var_dump(pub());

// first-class callable of a public module function
$fc = Pkg::pub(...);
var_dump($fc());

// string callables
var_dump(call_user_func('Pkg::pub'));
$f = 'Pkg::pub';
var_dump($f());

// internal: every dynamic route is gated from outside
var_dump(is_callable('Pkg::priv'));
try { call_user_func('Pkg::priv'); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
try { $g = 'Pkg::priv'; $g(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
try { array_map('Pkg::priv', [1]); } catch (\TypeError $e) { echo get_class($e), "\n"; }

// ... but a first-class callable CREATED inside the module works anywhere
$mk = new Pkg::Maker();
$h = $mk->firstClassInternal();
var_dump($h());
?>
--EXPECT--
string(3) "pub"
string(3) "pub"
string(3) "pub"
string(3) "pub"
bool(false)
Cannot call internal module function Pkg::priv() from outside its module
Cannot call internal module function Pkg::priv() from outside its module
TypeError
string(4) "priv"
