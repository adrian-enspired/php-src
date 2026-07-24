--TEST--
Modules (C): a COLD root member (no sub-namespace) is reachable by its namespaced name; canonical == member name
--DESCRIPTION--
A member declared directly in the module root has canonical == member name (Acme\Lib::Helper) and
a namespaced name Acme\Lib\Helper. Reached COLD by its namespaced name, the directive's ensure-load
loads the manifest so it resolves public. The deferred-handle op is a no-op here: the member name
already equals the canonical (the class's own identity), so there is nothing extra to alias.
--FILE--
<?php
$dir = __DIR__ . '/mod103';
@mkdir($dir);

file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
namespace Acme;
module Lib {
    public Helper;   // canonical == member name: Acme\Lib::Helper
}
PHP);

file_put_contents("$dir/helper.php", <<<'PHP'
<?php
module Acme\Lib;
class Helper {
    public function tag(): string { return "help"; }
}
PHP);

spl_autoload_register(function (string $name) use ($dir): void {
    if ($name === 'Acme\\Lib\\Helper') { require "$dir/helper.php";   return; }
    if ($name === 'Acme\\Lib')         { require "$dir/manifest.php"; return; }
});

// COLD: reach the public root member by its namespaced name before the module loads.
$h = new Acme\Lib\Helper();
echo $h->tag(), "\n";                          // help
echo $h::class, "\n";                          // Acme\Lib::Helper (canonical == member name)
var_dump(class_exists('Acme\Lib::Helper'));    // the class's own name
var_dump($h instanceof Acme\Lib::Helper);      // reachable by canonical / member name
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod103';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
help
Acme\Lib::Helper
bool(true)
bool(true)
