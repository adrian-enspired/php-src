--TEST--
Modules: partial function application respects internal methods and module functions
--DESCRIPTION--
`f(?)` creates a closure from a call. Creating one for an internal method, internal static
method or internal module function is gated like a direct call. Inside the module every form
works, including a module function reached bare (`f(?)`): the generated closure runs as module code.
--FILE--
<?php
module M {
    public class C {
        internal function secret(int $a, int $b) { return $a + $b; }
        internal static function ssecret(int $a) { return $a * 10; }
        public function fromInside() {
            return [($this->secret(?, 2))(1), (self::ssecret(?))(2), (module::f(?))(3), (\M\f(?))(4), (f(?))(5)];
        }
    }
    internal function f(int $a) { return $a + 100; }
}
function t($n, $f) { try { $r = json_encode($f()); echo "$n: $r\n"; } catch (Error $e) { echo "$n: ", $e->getMessage(), "\n"; } }
$c = new M\C;
t('method', fn() => ($c->secret(?, 2))(1));
t('static', fn() => (M\C::ssecret(?))(1));
t('module fn', fn() => (M\f(?))(1));
t('inside', fn() => $c->fromInside());
?>
--EXPECT--
method: Cannot call internal method M\C::secret() from outside its module
static: Cannot call internal method M\C::ssecret() from outside its module
module fn: Cannot call internal module function M\f() from outside its module
inside: [3,20,103,104,105]
