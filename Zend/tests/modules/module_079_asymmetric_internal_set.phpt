--TEST--
Modules: `public internal(set)` -- a property readable anywhere but writable only within its module (asymmetric module visibility)
--DESCRIPTION--
`internal(set)` is the set-path analogue of `internal`: the property reads at its
class-level (public) visibility from anywhere, but may be written only by code inside
the declaring module. It reuses the asymmetric-visibility write machinery, so it works
through methods, sibling classes, property hooks, and interface-declared properties;
Reflection's setValue bypasses the gate exactly as it does for `private(set)`.
--FILE--
<?php
module M {
    public class Counter {
        public internal(set) int $n = 0;
        public function bump(): void { $this->n++; }                 // in-module write (own method)
    }
    public class Factory {
        public function make(int $start): Counter { $c = new Counter(); $c->n = $start; return $c; }  // sibling write
    }
}
$c = (new M::Factory)->make(5);
echo $c->n, "\n";                                                    // public read from global scope
$c->bump();
echo $c->n, "\n";
try { $c->n = 100; } catch (\Error $e) { echo $e->getMessage(), "\n"; }   // write denied from global
echo $c->n, "\n";                                                    // unchanged

module N { public class Poker { public function poke(M::Counter $x): void { $x->n = 99; } } }
try { (new N::Poker)->poke($c); } catch (\Error $e) { echo $e->getMessage(), "\n"; }  // cross-module denied

$r = new ReflectionProperty("M::Counter", "n");
var_dump($r->isPublic());                                            // read side is public
$r->setValue($c, 77);                                               // Reflection bypasses, like private(set)
echo $c->n, "\n";

/* Hooked internal(set): the set hook runs for in-module writes, and is gated outside. */
module H {
    public class Box {
        private int $b = 0;
        public internal(set) int $x { get => $this->b; set { $this->b = $value * 2; } }
        public function put(int $v): void { $this->x = $v; }
    }
}
$box = new H::Box();
$box->put(10); echo $box->x, "\n";
try { $box->x = 1; } catch (\Error $e) { echo $e->getMessage(), "\n"; }

/* An interface may declare an internal(set) property; the implementor enforces it. */
module I {
    public interface Stated { public internal(set) int $s { get; set; } }
    public class Impl implements Stated {
        private int $bk = 0;
        public internal(set) int $s { get => $this->bk; set { $this->bk = $value; } }
        public function drive(int $v): void { $this->s = $v; }
    }
}
$o = new I::Impl();
$o->drive(3); echo $o->s, "\n";
try { $o->s = 8; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECT--
5
6
Cannot modify internal(set) property M::Counter::$n from global scope
6
Cannot modify internal(set) property M::Counter::$n from scope N::Poker
bool(true)
77
20
Cannot modify internal(set) property H::Box::$x from global scope
3
Cannot modify internal(set) property I::Impl::$s from global scope
