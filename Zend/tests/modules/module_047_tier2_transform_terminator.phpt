--TEST--
Modules: autoloading a member file loads its module first (names the autoloader sees)
--DESCRIPTION--
A member is autoloaded by its own name; its membership directive then loads the module's
definition. (This test used to cover the removed "::"->"\" autoload-name transform.)
--FILE--
<?php
$dir = __DIR__ . '/al47_tmp';
@mkdir($dir);
file_put_contents("$dir/Depot.def.php", "<?php\nmodule Depot {\n    public Crate;\n}\n");
file_put_contents("$dir/Depot.Crate.php", "<?php\nmodule Depot;\nclass Crate { public function id(): string { return 'crate'; } }\n");

$GLOBALS['seen'] = [];
spl_autoload_register(function ($name) use ($dir) {
    $GLOBALS['seen'][] = $name;               // keep the transformed name alive past this call
    $map = [
        'Depot'        => "$dir/Depot.def.php",
        'Depot\\Crate' => "$dir/Depot.Crate.php",
    ];
    if (isset($map[$name]) && is_file($map[$name])) require $map[$name];
});

echo (new Depot\Crate)->id(), "\n";
foreach ($GLOBALS['seen'] as $n) {
    echo $n, " (", strlen($n), ")\n";
}
?>
--CLEAN--
<?php
$dir = __DIR__ . '/al47_tmp';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
crate
Depot\Crate (11)
Depot (5)
