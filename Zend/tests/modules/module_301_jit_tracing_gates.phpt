--TEST--
Modules: tracing JIT keeps every module gate on hot paths (calls, static calls, constants, properties)
--DESCRIPTION--
The JIT duplicates parts of the VM handlers (function lookup, static-method lookup, constant
fetch, typed/asymmetric property writes, statically-bound property offsets). Each of those
copies must apply the same module gates, and a module function reached through its
module's backing class has no class scope (it used to crash the JIT helper).
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.jit=tracing
opcache.jit_hot_loop=1
opcache.jit_hot_func=1
opcache.jit_hot_return=1
opcache.jit_hot_side_exit=1
opcache.jit_buffer_size=64M
opcache.file_update_protection=0
--EXTENSIONS--
opcache
--SKIPIF--
<?php if (ini_get('opcache.jit') === false) die('skip PHP is compiled without JIT'); ?>
--FILE--
<?php
module M {
    public class C {
        public internal(set) int $n = 0;
        internal int $p = 5;
        internal function m() { return 1; }
        internal const K = 7;
        public function bump(): int { $this->n++; return $this->p + $this->n; }
    }
    internal class I { const Z = 1; }
    internal function f() { return "f"; }
    public function g() { return "g:" . f(); }
}
module N {
    public class Poker { public function poke(\M::C $x): void { $x->n = 99; } }
}

class Out {
    static function writeSet(M::C $o) { $o->n = 3; }
    static function writeInternal(M::C $o) { $o->p = 4; }
    static function readInternal(M::C $o) { return $o->p; }
    static function callInternal(M::C $o) { return $o->m(); }
    static function constInternal() { return M::C::K; }
    static function fnInternal() { return M::f(); }
    static function fnStatic() { return M::g(); }
    static function newInternal() { return new M::I; }
}

$o = new M::C;
for ($i = 0; $i < 50; $i++) foreach (['writeSet', 'writeInternal', 'readInternal', 'callInternal', 'constInternal', 'fnInternal', 'newInternal'] as $m) {
    try { Out::$m($o); echo "$m: BYPASSED\n"; } catch (Error $e) { if ($i == 49) echo "$m: ", $e->getMessage(), "\n"; }
}
try { (new N::Poker)->poke($o); echo "poke: BYPASSED\n"; } catch (Error $e) { echo "poke: ", $e->getMessage(), "\n"; }

// public paths keep working
echo Out::fnStatic(), "\n";
echo $o->bump(), "\n";
?>
--EXPECT--
writeSet: Cannot modify internal(set) property M::C::$n from scope Out
writeInternal: Cannot access internal module property M::C::$p from outside its module
readInternal: Cannot access internal module property M::C::$p from outside its module
callInternal: Cannot call internal method M::C::m() from outside its module
constInternal: Cannot access internal module constant M::C::K from outside its module
fnInternal: Cannot call internal module function M::f() from outside its module
newInternal: Cannot access internal module member "M::I" from outside its module
poke: Cannot modify internal(set) property M::C::$n from scope N::Poker
g:f
6
