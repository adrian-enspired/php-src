--TEST--
Module constants: member-file constants — canonical identity, projection, claims, gating
--FILE--
<?php
$dir = __DIR__ . '/mod121_tmp';
@mkdir($dir);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
module Cfg {
    public RATE;                       // claims the member-file constant below
    public const INLINE_RATE = 1;      // inline constants unchanged (backing-class const)
    public calc;
    public Box;
}
PHP);

file_put_contents($dir . '/consts.php', <<<'PHP'
<?php
module Cfg;

const RATE = 0.15;          // claimed public
const SECRET = "s3cr3t";    // unclaimed -> internal

function calc(float $n): float { return $n * RATE; }    // bare const, ns fallback
class Box {
    public function viaModule(): string { return module::SECRET; }
    public function viaBare(): string   { return SECRET; }
}
PHP);

require $dir . '/manifest.php';
require $dir . '/consts.php';

// public: all spellings from outside
var_dump(Cfg::RATE);
var_dump(\Cfg\RATE);
var_dump(constant('Cfg::RATE'));
var_dump(constant('Cfg\RATE'));
var_dump(Cfg::INLINE_RATE);

// in-module use
var_dump(Cfg::calc(100.0));
$b = new Cfg\Box();
var_dump($b->viaModule());
var_dump($b->viaBare());

// internal: gated from outside on every route
try { $x = Cfg::SECRET; echo "LEAK\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
try { $x = \Cfg\SECRET; echo "LEAK\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
try { constant('Cfg::SECRET'); echo "LEAK\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }

// observability: defined() is silent -> false outside for internal, true for public
var_dump(defined('Cfg\SECRET'));
var_dump(defined('Cfg\RATE'));
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod121_tmp';
@unlink($dir . '/manifest.php');
@unlink($dir . '/consts.php');
@rmdir($dir);
?>
--EXPECT--
float(0.15)
float(0.15)
float(0.15)
float(0.15)
int(1)
float(15)
string(6) "s3cr3t"
string(6) "s3cr3t"
Cannot access internal module constant Cfg::SECRET from outside its module
Cannot access internal module constant Cfg::SECRET from outside its module
Cannot access internal module constant Cfg::SECRET from outside its module
bool(false)
bool(true)
