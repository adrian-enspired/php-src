--TEST--
Modules: `internal` constructor promotion; module visibility requires a type
--DESCRIPTION--
`internal` and `internal(set)` are promotion modifiers, like on a declared property, and like
them require a type. Directly before the variable `internal` is the type Internal: a lone
`Internal $f` is a plain parameter of that type, `internal Internal $g` an internal promoted
property of it. Outside a module class `internal` is a compile error.
--FILE--
<?php
module M {
    public class W {}
    public class Internal {}
    public class A {
        public function __construct(
            internal W $a,
            internal mixed $b,
            internal readonly ?W $c = null,
            public internal(set) int $d = 1,
            internal int|string $e = 'e',
            ?Internal $f = null,
            internal ?Internal $g = null,
            public ?Internal $h = null,
            final ?Internal $i = null,
        ) {}
        public static function mk(): static { return new static(new W, 'b', null, 1, 'e', new Internal, new Internal); }
        public function inside(): array { return [$this->a::class, $this->b, $this->c, $this->e, $this->g::class]; }
    }
}
$a = M::A::mk();
var_dump($a->inside(), $a->d);
foreach (['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'] as $p) {
    if (!property_exists(M::A::class, $p)) { echo "$p: not promoted\n"; continue; }
    $r = new ReflectionProperty(M::A::class, $p);
    printf("%s: %s%s %s%s%s\n", $p, $r->isModuleInternal() ? 'internal' : 'public',
        $r->isPromoted() ? ' promoted' : '', $r->getType(), $r->isReadOnly() ? ' readonly' : '',
        $r->isFinal() ? ' final' : '');
}
foreach (['a', 'b', 'g'] as $p) {
    try { $a->$p; echo "LEAK\n"; } catch (Error $e) { echo $e->getMessage(), "\n"; }
}
try { $a->d = 2; echo "LEAK\n"; } catch (Error $e) { echo $e->getMessage(), "\n"; }
$php = getenv('TEST_PHP_EXECUTABLE') ?: PHP_BINARY;
$f = __DIR__ . '/module_316_case.inc';
foreach ([
    'module M { public class A { public function __construct(public internal(set) $x) {} } }',
    'class B { public function __construct(internal int $x) {} }',
] as $code) {
    file_put_contents($f, "<?php\n$code\n");
    $r = shell_exec(escapeshellarg($php) . ' -n ' . escapeshellarg($f) . ' 2>&1') ?? '';
    echo preg_match('/Fatal error: (.*?) in \//', $r, $m) ? $m[1] : $r, "\n";
}
@unlink($f);
?>
--EXPECT--
array(5) {
  [0]=>
  string(4) "M::W"
  [1]=>
  string(1) "b"
  [2]=>
  NULL
  [3]=>
  string(1) "e"
  [4]=>
  string(11) "M::Internal"
}
int(1)
a: internal promoted M::W
b: internal promoted mixed
c: internal promoted ?M::W readonly
d: public promoted int
e: internal promoted string|int
f: not promoted
g: internal promoted ?M::Internal
h: public promoted ?M::Internal
i: public promoted ?M::Internal final
Cannot access internal module property M::A::$a from outside its module
Cannot access internal module property M::A::$b from outside its module
Cannot access internal module property M::A::$g from outside its module
Cannot modify internal(set) property M::A::$d from global scope
Property with asymmetric visibility M::A::$x must have type
internal may only be used on a member of a module class
