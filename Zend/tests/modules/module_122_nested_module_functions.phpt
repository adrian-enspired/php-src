--TEST--
Module functions: nested modules — composition, and outermost-first gating for functions
--FILE--
<?php
namespace App;
module Core {
    public function ping(): string { return "core"; }
    public module Open {
        public function g(): string { return "open-g"; }
        internal function guts(): string { return "guts"; }
    }
    internal module Secret {
        public function w(): string { return "w"; }    // public leaf, internal container
    }
    public class Bridge {
        public function callSecret(): string { return \App\Core::Secret::w(); }  // parent may see Secret
    }
}

/* From OUTSIDE App\Core: */
echo \App\Core::ping(), "\n";
echo \App\Core::Open::g(), "\n";                 // both boundaries public

// internal leaf of a public nested module: denied at the INNER boundary
try { \App\Core::Open::guts(); echo "LEAK\n"; } catch (\Error $e) { echo "gated-inner\n"; }

// PUBLIC function of an INTERNAL nested module: denied at the OUTER boundary
try { \App\Core::Secret::w(); echo "LEAK\n"; } catch (\Error $e) { echo "gated-outer\n"; }

// ... but reachable from a direct member of the parent module
echo (new \App\Core::Bridge())->callSecret(), "\n";
?>
--EXPECT--
core
open-g
gated-inner
gated-outer
w
