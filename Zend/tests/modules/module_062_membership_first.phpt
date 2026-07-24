--TEST--
Modules: a membership declaration ("module X;") must be the first statement in the file
--DESCRIPTION--
A `module X;` membership declaration owns the rest of the file, so it must come first (only a
leading declare() may precede it). Decision C: a membership file is MODULE-FIRST — the directive
establishes the file's base namespace, so a `namespace` BEFORE it is rejected while a relative
`namespace N;` AFTER it is the intended form. Definition blocks, code, or a namespace preceding
the directive are all rejected; `module X; namespace N;` and `module X; module Inner { … }` are
valid.
--SKIPIF--
<?php if (!function_exists('shell_exec')) die('skip shell_exec disabled'); ?>
--FILE--
<?php
function compiles(string $code): bool {
    $f = tempnam(sys_get_temp_dir(), 'memfirst') . '.php';
    file_put_contents($f, "<?php\n" . $code);
    $out = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -n ' . escapeshellarg($f) . ' 2>&1');
    @unlink($f);
    return stripos($out, 'error') === false;
}

echo "-- rejected: membership may not follow a block / code / namespace --\n";
var_dump(compiles('module M { W; } module M; class W {}'));           // block, then membership (same name)
var_dump(compiles('module M { W; } module N; class Q {}'));           // block, then membership (other name)
var_dump(compiles('echo "x"; module M; class Y {}'));                 // code, then membership
var_dump(compiles('namespace A {} module M; class Z {}'));            // bracketed namespace, then membership
var_dump(compiles('namespace A; module M; class Y {}'));              // Decision C: namespace BEFORE membership

echo "-- allowed: membership first, then relative namespace / nested block --\n";
var_dump(compiles('module M; class Y {}'));
var_dump(compiles('declare(strict_types=1); module M; class Y {}'));
var_dump(compiles('module M; namespace A; class Y {}'));              // Decision C: relative namespace AFTER the directive
var_dump(compiles('module M; module Inner { class G {} }'));
?>
--EXPECT--
-- rejected: membership may not follow a block / code / namespace --
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
-- allowed: membership first, then relative namespace / nested block --
bool(true)
bool(true)
bool(true)
bool(true)
