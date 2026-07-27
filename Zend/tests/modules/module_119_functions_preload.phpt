--TEST--
Module functions: preloaded module functions — canonical + aliases + gating, registry-free
--EXTENSIONS--
opcache
--INI--
opcache.enable_cli=1
opcache.preload={PWD}/module_119_preload.inc
--SKIPIF--
<?php
if (substr(PHP_OS, 0, 3) == 'WIN') die('skip not for Windows');
?>
--FILE--
<?php
/* PreFn was PRELOADED; this request never requires its files, so the alias-registration ops
 * never re-run. Every call resolves off the persisted function-table entries; visibility is
 * baked on fn_flags, so gating works with an EMPTY per-request module registry. */
var_dump(PreFn::x());               // member-file fn, claimed public, via M::
var_dump(\PreFn\x());               // projected namespaced name (persisted alias key)
var_dump(PreFn::inl());             // inline fn (canonical key only)
var_dump(function_exists('PreFn\inl'));   // inline: module-only, no projection

try { PreFn::sec(); echo "LEAK\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
try { \PreFn\sec(); echo "LEAK\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
try { PreFn::inlsec(); echo "LEAK\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }

$fns = (new ReflectionModule('PreFn'))->getFunctions();
sort($fns);
print_r($fns);
?>
--EXPECT--
string(3) "x:s"
string(3) "x:s"
string(3) "inl"
bool(false)
Cannot call internal module function PreFn::sec() from outside its module
Cannot call internal module function PreFn::sec() from outside its module
Cannot call internal module function PreFn::inlsec() from outside its module
Array
(
    [0] => PreFn::inl
    [1] => PreFn::inlsec
    [2] => PreFn::sec
    [3] => PreFn::x
)
