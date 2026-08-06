--TEST--
Modules: ReflectionMethod::invoke()/invokeArgs() bypass an `internal` method
--DESCRIPTION--
Calling `internal function credit()` with ordinary `->` syntax from outside the module
is denied. ReflectionMethod::invoke() and invokeArgs() are not subject to the gate,
mirroring their existing bypass of private/protected. The object here was itself
constructed via reflection (module_200), so nothing in this test ever touches ordinary
`new` -- the whole round trip (construct, call, read the result) stays inside
Reflection's API.
--FILE--
<?php
module Bank {
    internal class Ledger {
        public function __construct(public int $balance = 0) {}
        internal function credit(int $amount): int { return $this->balance += $amount; }
    }
}

$rc = new ReflectionClass('Bank::Ledger');
$ledger = $rc->newInstanceArgs([10]);

echo "ordinary method call: ";
try { $ledger->credit(5); echo "LEAKED\n"; }
catch (\Error $e) { echo $e->getMessage(), "\n"; }

echo "ReflectionMethod::invoke: ";
$rm = $rc->getMethod('credit');
var_dump($rm->invoke($ledger, 5));

echo "ReflectionMethod::invokeArgs: ";
var_dump($rm->invokeArgs($ledger, [5]));
?>
--EXPECT--
ordinary method call: Cannot call internal method Bank::Ledger::credit() from outside its module
ReflectionMethod::invoke: int(15)
ReflectionMethod::invokeArgs: int(20)
