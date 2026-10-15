--TEST--
Modules: `internal` constructor promotion
--DESCRIPTION--
`internal` and `internal(set)` are promotion modifiers, like on a declared property. Outside a
module class they are a compile error, as for properties.
--FILE--
<?php
module M {
    public class W {}
    public class A {
        public function __construct(
            internal W $a,
            internal $b,
            internal readonly ?W $c = null,
            public internal(set) int $d = 1,
            internal int|string $e = 'e',
        ) {}
        public static function mk(): static { return new static(new W, 'b'); }
        public function inside(): array { return [$this->a::class, $this->b, $this->c, $this->e]; }
    }
}
$a = M::A::mk();
var_dump($a->inside(), $a->d);
foreach (['a', 'b', 'c', 'd', 'e'] as $p) {
    $r = new ReflectionProperty(M::A::class, $p);
    printf("%s: %s%s %s%s\n", $p, $r->isModuleInternal() ? 'internal' : 'public',
        $r->isPromoted() ? ' promoted' : '', $r->getType() ?? 'untyped', $r->isReadOnly() ? ' readonly' : '');
}
foreach (['a', 'b'] as $p) {
    try { $a->$p; echo "LEAK\n"; } catch (Error $e) { echo $e->getMessage(), "\n"; }
}
try { $a->d = 2; echo "LEAK\n"; } catch (Error $e) { echo $e->getMessage(), "\n"; }
eval('class B { public function __construct(internal int $x) {} }');
?>
--EXPECTF--
array(4) {
  [0]=>
  string(4) "M::W"
  [1]=>
  string(1) "b"
  [2]=>
  NULL
  [3]=>
  string(1) "e"
}
int(1)
a: internal promoted M::W
b: internal promoted untyped
c: internal promoted ?M::W readonly
d: public promoted int
e: internal promoted string|int
Cannot access internal module property M::A::$a from outside its module
Cannot access internal module property M::A::$b from outside its module
Cannot modify internal(set) property M::A::$d from global scope

Fatal error: internal may only be used on a member of a module class in %s : eval()'d code on line %d
