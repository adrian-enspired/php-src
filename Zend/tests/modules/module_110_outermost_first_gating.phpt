--TEST--
Modules (C): access gating walks boundaries outermost-first and short-circuits
--DESCRIPTION--
To reach A::B::C the accessor crosses each "::" boundary in order — "does A expose B?" is asked
BEFORE "does B expose C?", and the first denial wins (exactly like the n-tier autoload walk). So a
PUBLIC member of an INTERNAL nested module is unreachable from outside: it is denied at the OUTER
boundary and the public leaf is never consulted. A member of a PUBLIC nested module is gated only
by its own visibility. A nested module never decides its own visibility — its container does.
--FILE--
<?php
namespace App;
module Core {
    internal module Secret {
        public class Widget { public function w(): string { return "w"; } }
    }
    public module Open {
        public class Gadget { public function g(): string { return "g"; } }
        internal class Guts {}
    }
}

/* From OUTSIDE App\Core: */

// Open is PUBLIC in Core and Gadget is PUBLIC in Open -> reachable (both boundaries crossable).
echo (new App\Core::Open::Gadget())->g(), "\n";               // g

// Open is PUBLIC, but Guts is INTERNAL in Open -> denied at the INNER boundary.
try { new App\Core::Open::Guts(); echo "LEAK\n"; } catch (\Error $e) { echo "gated-inner\n"; }

// Secret is INTERNAL in Core and Widget is PUBLIC in Secret -> denied at the OUTER boundary,
// short-circuiting before the public leaf is ever consulted. This is the outermost-first rule.
try { new App\Core::Secret::Widget(); echo "LEAK\n"; } catch (\Error $e) { echo "gated-outer\n"; }

// Identity is still observable (only USE is gated).
var_dump(class_exists('App\Core::Secret::Widget'));
?>
--EXPECT--
g
gated-inner
gated-outer
bool(true)
