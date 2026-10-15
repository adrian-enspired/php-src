--TEST--
Modules: trait aliases set and clear `internal` (as internal / as internal name / as public)
--DESCRIPTION--
`f as internal;` makes the imported method internal (it lexes like a name before ";" but is the
modifier, as `as public;` is). `as public` clears an internal method's internal state. A method
named internal is still reachable: `f as public internal;`. Outside a module, `as internal` is a
compile error like `internal` on any member.
--FILE--
<?php
module M {
    internal trait T {
        public function f() { return 'f'; }
        internal function g() { return 'g'; }
    }
    public class C {
        use T { f as internal; f as internal hidden; g as public; f as public internal; }
        public function inside(): string { return $this->f() . $this->hidden(); }
    }
}
$c = new M::C;
foreach (['f', 'hidden', 'g', 'internal'] as $m) {
    try { $r = $c->$m(); echo "$m: $r\n"; } catch (Error $e) { echo "$m: ", $e->getMessage(), "\n"; }
    echo "  isModuleInternal: ", var_export((new ReflectionMethod('M::C', $m))->isModuleInternal(), true), "\n";
}
echo "inside: ", $c->inside(), "\n";
$php = getenv('TEST_PHP_EXECUTABLE') ?: PHP_BINARY;
$f = __DIR__ . '/module_322_case.inc';
file_put_contents($f, "<?php\ntrait T { function f() {} }\nclass C { use T { f as internal; } }\n");
$r = shell_exec(escapeshellarg($php) . ' -n ' . escapeshellarg($f) . ' 2>&1') ?? '';
echo preg_match('/Fatal error: (.*?) in \//', $r, $m) ? $m[1] : $r, "\n";
@unlink($f);
?>
--EXPECT--
f: Cannot call internal method M::C::f() from outside its module
  isModuleInternal: true
hidden: Cannot call internal method M::C::hidden() from outside its module
  isModuleInternal: true
g: g
  isModuleInternal: false
internal: f
  isModuleInternal: false
inside: ff
internal may only be used on a member of a module class
