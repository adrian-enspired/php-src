--TEST--
Module functions: member-file functions — canonical identity, projection, claims, gating
--FILE--
<?php
$dir = __DIR__ . '/mod111_tmp';
@mkdir($dir);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
namespace Vendor\Package;
module Feature {
    public x;          // claims the member-file function below
    public Widget;
}
PHP);

file_put_contents($dir . '/functions.php', <<<'PHP'
<?php
module Vendor\Package\Feature;

// unclaimed -> internal; canonical Vendor\Package\Feature::f; namespaced ...\Feature\f
function f(): string { return "f:" . x(); }          // bare same-module call (ns fallback)
// claimed public
function x(): string { return "x"; }
PHP);

file_put_contents($dir . '/widget.php', <<<'PHP'
<?php
module Vendor\Package\Feature;
class Widget {
    public function viaBare(): string  { return f(); }          // internal, same module
    public function viaModule(): string { return module::f(); } // module:: self-call
    public function viaCanonical(): string { return \Vendor\Package\Feature::f(); }
}
PHP);

require $dir . '/manifest.php';
require $dir . '/functions.php';
require $dir . '/widget.php';

// public function, all four spellings from outside
var_dump(Vendor\Package\Feature::x());
var_dump(\Vendor\Package\Feature\x());
$w = new Vendor\Package\Feature\Widget();

// internal function, reachable from inside by every route
var_dump($w->viaBare());
var_dump($w->viaModule());
var_dump($w->viaCanonical());

// internal function, gated from outside on every route
try { Vendor\Package\Feature::f(); echo "LEAK\n"; }
catch (\Error $e) { echo $e->getMessage(), "\n"; }
try { \Vendor\Package\Feature\f(); echo "LEAK\n"; }
catch (\Error $e) { echo $e->getMessage(), "\n"; }

// observable but gated
var_dump(function_exists('Vendor\Package\Feature\f'));
var_dump(function_exists('Vendor\Package\Feature\x'));
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod111_tmp';
@unlink($dir . '/manifest.php');
@unlink($dir . '/functions.php');
@unlink($dir . '/widget.php');
@rmdir($dir);
?>
--EXPECT--
string(1) "x"
string(1) "x"
string(3) "f:x"
string(3) "f:x"
string(3) "f:x"
Cannot call internal module function Vendor\Package\Feature::f() from outside its module
Cannot call internal module function Vendor\Package\Feature::f() from outside its module
bool(true)
bool(true)
