--TEST--
Module functions: inline `function` in the module block — default public, module-only (no projection)
--FILE--
<?php
module Billing {
    public function tax(int $n): int { return rate() + $n; }   // bare call to sibling
    internal function rate(): int { return 10; }
    public class Svc {
        public function viaModule(): int { return module::rate(); }
        public function viaBare(): int { return rate(); }
    }
}

// public inline function: module-qualified spelling works from anywhere
var_dump(Billing::tax(5));
var_dump(Billing::tax(5));                    // second call: cached static-call path

// internal inline function: gated outside, usable inside
try { Billing::rate(); echo "LEAK\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
$s = new Billing::Svc();
var_dump($s->viaModule());
var_dump($s->viaBare());

// inline members are module-only: NO namespaced projection (parity with inline classes)
var_dump(function_exists('Billing\tax'));

// global fallback still works from inline bodies (module tier misses, falls back)
module Fallback {
    public function len(): int { return strlen("abcd"); }
}
var_dump(Fallback::len());
?>
--EXPECT--
int(15)
int(15)
Cannot call internal module function Billing::rate() from outside its module
int(10)
int(10)
bool(false)
int(4)
