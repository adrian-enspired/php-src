--TEST--
Modules (C): nested module — members reachable by canonical Outer::Inner::Member; visibility per level
--DESCRIPTION--
A nested module's members are canonically Outer::Inner::Member and reachable by that name. An
internal nested member is gated from outside the containing module. (Inline nested modules;
member-file nested membership with a sub-namespace is a separate increment.)
--FILE--
<?php
namespace X\Y;
module Z {
    public module Inner {
        public class C {
            public function w(): string { return 'inner-c'; }
            public function reachSecret(): string { return module::Secret::TAG; }  // module = Inner
        }
        internal class Secret { public const TAG = 'sec'; }
    }
    public class Top {
        public function w(): string { return 'top'; }
    }
}

echo "-- top-level and nested members by canonical name --\n";
echo (new X\Y\Z::Top)->w(), "\n";              // top
echo (new X\Y\Z::Inner::C)->w(), "\n";         // inner-c
echo (new X\Y\Z::Inner::C)::class, "\n";       // X\Y\Z::Inner::C

echo "-- module:: from a nested member reaches an Inner sibling (internal) --\n";
echo (new X\Y\Z::Inner::C)->reachSecret(), "\n";   // sec

echo "-- an internal nested member is gated from outside --\n";
try { new X\Y\Z::Inner::Secret; echo "LEAK\n"; } catch (\Error $e) { echo "gated\n"; }
?>
--EXPECT--
-- top-level and nested members by canonical name --
top
inner-c
X\Y\Z::Inner::C
-- module:: from a nested member reaches an Inner sibling (internal) --
sec
-- an internal nested member is gated from outside --
gated
