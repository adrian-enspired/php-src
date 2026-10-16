--TEST--
Modules: an autoload miss looks for a module among the name's prefixes, each prefix once
--DESCRIPTION--
A member without a file of its own (defined inline in its module) is found by loading its module
after the miss on its own name. The enclosing names are tried longest first, each at most once per
request -- a prefix whose autoload failed isn't retried on later misses, until the autoloader list
changes.
--FILE--
<?php
$seen = [];
spl_autoload_register(function ($c) use (&$seen) { $seen[] = $c; });

var_dump(class_exists('Shop\Cart\Missing1'), class_exists('Shop\Cart\Missing2'));
echo implode(', ', $seen), "\n";

// a loader that knows the module: the next miss under it tries the prefixes again
$seen = [];
spl_autoload_register(function ($c) {
    if ($c === 'Shop') { eval('module Shop { public class Cart {} public class Inline {} }'); }
});
var_dump(class_exists('Shop\Inline'), get_class(new Shop\Cart));
echo implode(', ', $seen), "\n";
?>
--EXPECT--
bool(false)
bool(false)
Shop\Cart\Missing1, Shop\Cart, Shop, Shop\Cart\Missing2
bool(true)
string(9) "Shop\Cart"
Shop\Inline, Shop
