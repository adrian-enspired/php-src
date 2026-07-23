--TEST--
Modules (B-core): a namespaced member-file class projects its namespace name as a class alias
--DESCRIPTION--
Under Decision B a member's canonical name is module-rooted on its SIMPLE name (Widgets::Gadget),
and its file namespace supplies a separate outward *projection* alias (Acme\Gadget) pointing at
the same class. The projection is registered by a runtime op (ZEND_DECLARE_MODULE_MEMBER_ALIAS)
so it survives opcache/preload. This smoke-tests the mechanism in isolation, using a simple
claim name (Gadget) for visibility so it does not depend on the claim-alias / ordering work.
--FILE--
<?php
$dir = __DIR__ . '/proj_tmp';
@mkdir($dir);

file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
module Widgets {
    public Acme\Gadget;     // claim keys on the tail -> Widgets::Gadget, public; projects Acme\Gadget
}
PHP);
file_put_contents("$dir/member.php", <<<'PHP'
<?php
module Widgets;
namespace Acme;
class Gadget {
    public function tag(): string { return "gadget"; }
}
PHP);

require "$dir/manifest.php";
require "$dir/member.php";

// Canonical, module-rooted on the SIMPLE name (no "Widgets::Acme\Gadget" nesting):
var_dump(class_exists('Widgets::Gadget'));
var_dump(class_exists('Widgets::Acme\Gadget'));   // the old nested name must NOT exist
// Outward projection alias resolves to the same class:
var_dump(class_exists('Acme\Gadget'));

$c = new \Acme\Gadget();               // reachable via the projection (public)
echo $c::class, "\n";                  // canonical name is reported
echo $c->tag(), "\n";
var_dump($c instanceof Widgets::Gadget);   // identity holds through both names
?>
--CLEAN--
<?php
$dir = __DIR__ . '/proj_tmp';
@unlink("$dir/manifest.php");
@unlink("$dir/member.php");
@rmdir($dir);
?>
--EXPECT--
bool(true)
bool(false)
bool(true)
Widgets::Gadget
gadget
bool(true)
