--TEST--
Module functions: closures and arrow functions keep the module identity of their birthplace
--FILE--
<?php
$dir = __DIR__ . '/mod115_tmp';
@mkdir($dir);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
module Clo { public make; public makeArrow; public makeNested; }
PHP);

file_put_contents($dir . '/funcs.php', <<<'PHP'
<?php
module Clo;

function secret(): string { return "s"; }          // internal

function make(): callable  { return function (): string { return secret(); }; }
function makeArrow(): callable { return fn (): string => module::secret(); }
function makeNested(): callable {
    return function (): callable {
        return function (): string { return secret(); };   // closure inside closure
    };
}
PHP);

require $dir . '/manifest.php';
require $dir . '/funcs.php';

// closures born inside the module keep access to its internals, even invoked outside
$c = Clo::make();
var_dump($c());
$a = Clo::makeArrow();
var_dump($a());
$n = Clo::makeNested();
var_dump($n()());

// a closure born OUTSIDE has no module identity
$out = function () { return \Clo\secret(); };
try { $out(); echo "LEAK\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod115_tmp';
@unlink($dir . '/manifest.php');
@unlink($dir . '/funcs.php');
@rmdir($dir);
?>
--EXPECT--
string(1) "s"
string(1) "s"
string(1) "s"
Cannot call internal module function Clo::secret() from outside its module
