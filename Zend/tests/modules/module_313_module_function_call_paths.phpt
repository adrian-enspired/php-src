--TEST--
Modules: M::f() compiles to a direct call and "M::f" strings resolve from the function table; gates still apply
--FILE--
<?php
// "M::f()" with M known at compile time compiles to a plain function call, and a "M::f"
// string callable is resolved straight from the function table. Both keep the gate.
module M {
    public function pub(int $x): int { return $x + 1; }
    internal function priv(int $x): int { return $x + 2; }
    public module N { internal function deep(): string { return 'deep'; } public function open(): string { return deep(); } }
    internal module H { public function hidden(): string { return 'hidden'; } }
    public class C {
        public static function viaDirect(): int { return module::priv(1) + \M\priv(1); }
        public static function viaString(): int { $f = 'M\priv'; return $f(1); }
    }
}
class Outside { static function call(string $f) { return $f(); } }

var_dump(M\pub(1), (function () { $f = '\M\pub'; return $f(1); })());
var_dump(M\C::viaDirect(), M\C::viaString(), M\N\open());
foreach ([fn() => M\priv(1), fn() => ('M\priv')(1), fn() => M\N\deep(), fn() => ('M\N\deep')(),
          fn() => M\H\hidden(), fn() => ('M\H\hidden')()] as $i => $f) {
    try { $f(); echo "$i: BYPASSED\n"; } catch (Error $e) { echo "$i: ", $e->getMessage(), "\n"; }
}
// an ordinary "Class::method" string callable is unaffected
class K { static function m() { return 'K::m'; } }
$s = 'K::m'; var_dump($s());
?>
--EXPECT--
int(2)
int(2)
int(6)
int(3)
string(4) "deep"
0: Cannot call internal module function M\priv() from outside its module
1: Cannot call internal module function M\priv() from outside its module
2: Cannot call internal module function M\N\deep() from outside its module
3: Cannot call internal module function M\N\deep() from outside its module
4: Cannot call M\H\hidden(): module "M\H" is internal to its parent module
5: Cannot call M\H\hidden(): module "M\H" is internal to its parent module
string(4) "K::m"
