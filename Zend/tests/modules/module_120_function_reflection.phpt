--TEST--
Module functions: reflection surface — getFunctions, getSymbolVisibility, ReflectionFunction identity
--FILE--
<?php
$dir = __DIR__ . '/mod120_tmp';
@mkdir($dir);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
module Refl {
    public x;
    public function inl(): int { return 1; }
}
PHP);

file_put_contents($dir . '/funcs.php', <<<'PHP'
<?php
module Refl;
const RATE = 5;
function x(): int { return 2; }
function hidden(): int { return 3; }
PHP);

require $dir . '/manifest.php';
require $dir . '/funcs.php';

$r = new ReflectionModule('Refl');
$fns = $r->getFunctions();
sort($fns);
print_r($fns);

var_dump($r->getSymbolVisibility('Refl::x'));
var_dump($r->getSymbolVisibility('Refl::hidden'));
var_dump($r->getSymbolVisibility('Refl::inl'));
var_dump($r->getSymbolVisibility('Refl::RATE'));

// ReflectionFunction: reachable by projection or canonical string, reports canonical
$rf = new ReflectionFunction('Refl\x');
var_dump($rf->name);

// member-file constants appear in getConstants alongside inline ones
print_r($r->getConstants());
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod120_tmp';
@unlink($dir . '/manifest.php');
@unlink($dir . '/funcs.php');
@rmdir($dir);
?>
--EXPECT--
Array
(
    [0] => Refl::hidden
    [1] => Refl::inl
    [2] => Refl::x
)
string(6) "public"
string(8) "internal"
string(6) "public"
string(8) "internal"
string(7) "Refl::x"
Array
(
    [RATE] => 5
)
