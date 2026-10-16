--TEST--
Modules: a preloaded class with an undecided public property type is checked per request
--DESCRIPTION--
The class keeps ZEND_ACC_CONSTANTS_UPDATED clear so its first use in each request runs the check;
preloading must neither mark it updated nor loop resolving it.
--SKIPIF--
<?php
if (!extension_loaded('Zend OPcache')) die('skip requires opcache');
if (substr(PHP_OS, 0, 3) == 'WIN') die('skip not for Windows');
?>
--FILE--
<?php
$php = getenv('TEST_PHP_EXECUTABLE');
$dir = __DIR__ . '/m326_tmp';
@mkdir($dir);
file_put_contents("$dir/def.php", "<?php\nmodule W { public Api; public Fine; }\n");
file_put_contents("$dir/mem.php", "<?php\nmodule W;\nclass Impl {}\nclass Api { public ?Impl \$p = null; }\nclass Fine { public ?Other \$o = null; }\n");
file_put_contents("$dir/other.php", "<?php\nnamespace W;\nclass Other {}\n");
file_put_contents("$dir/preload.php", "<?php\nrequire __DIR__ . '/def.php';\nrequire __DIR__ . '/other.php';\nrequire __DIR__ . '/mem.php';\n");
file_put_contents("$dir/run.php", <<<'PHP'
<?php
foreach (['W\Api', 'W\Api', 'W\Fine'] as $c) {
    try { echo get_class(new $c), "\n"; } catch (Error $e) { echo $e->getMessage(), "\n"; }
}
PHP);
foreach (['', ' -d opcache.jit=tracing -d opcache.jit_buffer_size=64M'] as $jit) {
    echo shell_exec("$php -n -d opcache.enable_cli=1 -d opcache.preload=" . escapeshellarg("$dir/preload.php")
        . "$jit " . escapeshellarg("$dir/run.php") . ' 2>&1');
}
?>
--CLEAN--
<?php
$dir = __DIR__ . '/m326_tmp';
foreach (['def', 'mem', 'other', 'preload', 'run'] as $f) { @unlink("$dir/$f.php"); }
@rmdir($dir);
?>
--EXPECT--
the type of property W\Api::$p references "W\Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
the type of property W\Api::$p references "W\Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
W\Fine
the type of property W\Api::$p references "W\Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
the type of property W\Api::$p references "W\Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
W\Fine
