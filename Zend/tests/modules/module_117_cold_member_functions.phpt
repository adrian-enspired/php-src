--TEST--
Module functions (C): COLD member file — fn VIS_UNKNOWN resolves from the registry, failing closed
--FILE--
<?php
$dir = __DIR__ . '/mod117_tmp';
@mkdir($dir);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
module ColdFn { public pf; public KPUB; }
PHP);

file_put_contents($dir . '/funcs.php', <<<'PHP'
<?php
module ColdFn;
const KPUB = "kp";
const KSEC = "ks";
function pf(): string { return "cold:" . sf() . KSEC; }   // claimed public
function sf(): string { return "s-"; }                    // unclaimed -> internal
PHP);

spl_autoload_register(function ($n) use ($dir) {
    if (strcasecmp($n, 'ColdFn') === 0) { require $dir . '/manifest.php'; }
});

/* Member file FIRST: every function/constant in it compiles + registers COLD
 * (manifest absent -> VIS_UNKNOWN, resolved from the registry at the gate). */
require $dir . '/funcs.php';

var_dump(ColdFn::pf());          // triggers manifest autoload; claim says public
try { ColdFn::sf(); echo "LEAK\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
var_dump(\ColdFn\pf());          // projection registered at member-file runtime
var_dump(ColdFn::KPUB);          // cold const, claimed public
try { $x = \ColdFn\KSEC; echo "LEAK\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod117_tmp';
@unlink($dir . '/manifest.php');
@unlink($dir . '/funcs.php');
@rmdir($dir);
?>
--EXPECT--
string(9) "cold:s-ks"
Cannot call internal module function ColdFn::sf() from outside its module
string(9) "cold:s-ks"
string(2) "kp"
Cannot access internal module constant ColdFn::KSEC from outside its module
