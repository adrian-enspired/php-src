--TEST--
Modules (C): a COLD-loaded internal member is gated (visibility resolved at runtime, not defaulted public)
--DESCRIPTION--
When an internal member is reached COLD via its namespaced name — before its module has loaded —
its visibility cannot be baked at compile time. It is stamped UNKNOWN; the directive's runtime
ensure-load loads the module, and the access gate resolves the real (internal) visibility from the
registry. Without this, a cold internal member would default to public and leak. Identity stays
observable (class_exists succeeds); only USE across the boundary is denied.
--FILE--
<?php
$dir = __DIR__ . '/mod102';
@mkdir($dir);

file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
namespace Acme;
module Secret {
    internal Impl\Token;   // canonical Acme\Secret::Impl\Token; internal
}
PHP);

file_put_contents("$dir/token.php", <<<'PHP'
<?php
module Acme\Secret;
namespace Impl;
class Token {
    public function tag(): string { return "token"; }
}
PHP);

spl_autoload_register(function (string $name) use ($dir): void {
    if ($name === 'Acme\\Secret\\Impl\\Token') { require "$dir/token.php";    return; }
    if ($name === 'Acme\\Secret')              { require "$dir/manifest.php"; return; }
});

// COLD: nothing has loaded module Acme\Secret. The FIRST access is to the internal member,
// from outside the module. It must be gated, not leaked as public.
try {
    $t = new Acme\Secret\Impl\Token();
    echo "LEAK: ", $t->tag(), "\n";
} catch (\Error $e) {
    echo "gated\n";
}
// Identity is observable even for an internal member (only USE is gated).
var_dump(class_exists('Acme\Secret::Impl\Token'));
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod102';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
gated
bool(true)
