--TEST--
Modules (B-order): a member file may be namespace-first (namespace A\B; module M;)
--DESCRIPTION--
Decision B decouples a member file's namespace from its module membership. The file may lead
with its own `namespace`, then declare membership: `namespace Acme; module Widgets;`. The
member is canonically module-rooted on its tail (Widgets::Thing) and projects its file
namespace name (Acme\Thing). The membership claim is NOT prefixed by the file namespace.
--FILE--
<?php
$dir = __DIR__ . '/nf_tmp';
@mkdir($dir);

file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
module Widgets {
    public Acme\Thing;   // namespaced claim matches the member file's projection
}
PHP);
file_put_contents("$dir/member.php", <<<'PHP'
<?php
namespace Acme;
module Widgets;
class Thing { public function tag(): string { return "thing"; } }
PHP);

require "$dir/manifest.php";
require "$dir/member.php";

var_dump(class_exists('Widgets::Thing'));   // canonical, module-rooted on the tail
var_dump(class_exists('Acme\Thing'));        // outward projection from the file namespace
echo (new Widgets::Thing)->tag(), "\n";      // via canonical
echo (new \Acme\Thing)->tag(), "\n";         // via projection
echo (new Widgets::Thing)::class, "\n";      // canonical name is reported
?>
--CLEAN--
<?php
$dir = __DIR__ . '/nf_tmp';
@unlink("$dir/manifest.php");
@unlink("$dir/member.php");
@rmdir($dir);
?>
--EXPECT--
bool(true)
bool(true)
thing
thing
Widgets::Thing
