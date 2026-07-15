--TEST--
Modules: `internal(set)` inheritance never re-homes, and its declaration guards (static / non-module / trait) hold
--DESCRIPTION--
Like an `internal` method, an `internal(set)` property's module boundary is fixed at
declaration and never re-homes through inheritance. Inside the declaring module a child
may keep `internal(set)` or widen to `public(set)`; from outside the module the property
may not be overridden except by dropping the restriction (a plain public redeclaration is
a widening). A non-internal write contract may not be narrowed to `internal(set)`. And
`internal(set)` is rejected on a static property, outside any module, and on a public
trait (it counts as an internal member, so the trait must itself be `internal`).
--FILE--
<?php
function bad(string $label, string $needle, string $code): void {
    $f = tempnam(sys_get_temp_dir(), 'ais') . '.php';
    file_put_contents($f, "<?php\n" . $code);
    $out = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -n ' . escapeshellarg($f) . ' 2>&1');
    @unlink($f);
    echo $label, ": ", str_contains($out, $needle) ? "rejected" : ("UNEXPECTED: " . trim($out)), "\n";
}
function ok(string $label, string $code): void {
    $f = tempnam(sys_get_temp_dir(), 'ais') . '.php';
    file_put_contents($f, "<?php\n" . $code . "\necho 'OK';");
    $out = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -n ' . escapeshellarg($f) . ' 2>&1');
    @unlink($f);
    echo $label, ": ", trim($out) === 'OK' ? "ok" : ("UNEXPECTED: " . trim($out)), "\n";
}

echo "-- reject --\n";
bad('cross-module override', 'from outside its module',
    'module M { public class Base { public internal(set) int $v = 1; } } module N { public class Child extends M::Base { public internal(set) int $v = 1; } }');
bad('public parent -> internal(set)', 'may only override an internal(set)',
    'class Base { public int $v = 1; } module M { public class Child extends \Base { public internal(set) int $v = 1; } }');
bad('narrow to private(set)', 'must be public(set) or internal(set)',
    'module M { public class Base { public internal(set) int $v = 1; } public class Child extends Base { public private(set) int $v = 1; } }');
bad('static internal(set)', 'asymmetric visibility',
    'module M { public class C { public static internal(set) int $x = 0; } }');
bad('outside a module', 'only be used on a property of a module class',
    'class C { public internal(set) int $x = 0; }');
bad('public trait carrier', 'declares an internal(set) property',
    'module M { public trait T { public internal(set) int $x = 0; } }');

echo "-- allow --\n";
ok('same-module keep',        'module M { public class Base { public internal(set) int $v = 1; } public class Child extends Base { public internal(set) int $v = 1; } }');
ok('same-module widen (set)', 'module M { public class Base { public internal(set) int $v = 1; } public class Child extends Base { public public(set) int $v = 1; } }');
ok('cross-module widen',      'module M { public class Base { public internal(set) int $v = 1; } } module N { public class Child extends M::Base { public int $v = 1; } }');
ok('internal trait carrier',  'module M { internal trait T { public internal(set) int $x = 0; } public class U { use T; } }');
?>
--EXPECT--
-- reject --
cross-module override: rejected
public parent -> internal(set): rejected
narrow to private(set): rejected
static internal(set): rejected
outside a module: rejected
public trait carrier: rejected
-- allow --
same-module keep: ok
same-module widen (set): ok
cross-module widen: ok
internal trait carrier: ok
