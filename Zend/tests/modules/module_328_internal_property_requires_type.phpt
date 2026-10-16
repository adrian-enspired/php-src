--TEST--
Modules: an internal property must have a type; before a variable `internal` is the type Internal
--DESCRIPTION--
Like readonly and asymmetric visibility, module visibility requires a type. That keeps `internal`
unambiguous: directly before a variable it is always the class name Internal. `internal $x;` alone
is therefore an error (not a public property), and `static Internal $x` is a static property of
type Internal. An untyped internal property is written `internal mixed $x`.
--FILE--
<?php
$php = getenv('TEST_PHP_EXECUTABLE') ?: PHP_BINARY;
$f = __DIR__ . '/module_328_case.inc';
$run = function (string $code) use ($php, $f): string {
    file_put_contents($f, "<?php\n$code\n");
    $r = shell_exec(escapeshellarg($php) . ' -n ' . escapeshellarg($f) . ' 2>&1') ?? '';
    return preg_match('/(?:Fatal|Parse) error: (.*?) in \//', $r, $m) ? $m[1] : trim($r);
};
foreach ([
    'module M { public class C { internal $x; } }',
    'module M { public class C { internal static $x; } }',
    'module M { public class C { internal $x { get => 1; } } }',
    'module M { public class C { public internal(set) $x; } }',
] as $code) {
    echo $run($code), "\n";
}
module M {
    public class Internal {}
    public class C {
        internal mixed $any = 1;
        internal ?int $n = null;
        internal static mixed $s = 2;
        internal Internal $own;
        public Internal $pub;
        static ?Internal $st = null;
        final ?Internal $fin = null;
        public readonly Internal $ro;
        public function __construct() { $this->own = new Internal; $this->pub = new Internal; $this->ro = new Internal; }
    }
}
$r = new ReflectionClass('M::C');
foreach ($r->getProperties() as $p) {
    printf("%s: %s%s%s %s\n", $p->getName(), $p->isModuleInternal() ? 'internal' : 'public',
        $p->isStatic() ? ' static' : '', $p->isFinal() ? ' final' : '', $p->getType());
}
echo $run('class Internal {} class D { static Internal $x; final ?Internal $y = null; } echo (new ReflectionProperty("D", "x"))->getType(), "\n";'), "\n";
@unlink($f);
?>
--EXPECT--
An internal property must have a type: before a variable, "internal" is the type Internal (write "internal mixed $x" for an untyped internal property, or "public Internal $x")
Internal property M::C::$x must have type
An internal property must have a type: before a variable, "internal" is the type Internal (write "internal mixed $x" for an untyped internal property, or "public Internal $x")
Property with asymmetric visibility M::C::$x must have type
any: internal mixed
n: internal ?int
s: internal static mixed
own: internal M::Internal
pub: public M::Internal
st: public static ?M::Internal
fin: public final ?M::Internal
ro: public M::Internal
Internal
