--TEST--
Modules: module functions may not use magic method names
--DESCRIPTION--
Reserved so a future module constructor (or other magic) cannot change the meaning of existing
code. Applies to module functions only: methods of module classes, and global functions, are
unaffected.
--FILE--
<?php
$names = ['__construct', '__destruct', '__call', '__callStatic', '__get', '__set', '__isset',
    '__unset', '__sleep', '__wakeup', '__serialize', '__unserialize', '__toString', '__invoke',
    '__set_state', '__clone', '__debugInfo', '__CONSTRUCT'];
$php = getenv('TEST_PHP_EXECUTABLE') ?: PHP_BINARY;
$f = __DIR__ . '/module_319_case.inc';
$run = function (string $code) use ($php, $f): string {
    file_put_contents($f, "<?php\n$code\n");
    $r = shell_exec(escapeshellarg($php) . ' -n ' . escapeshellarg($f) . ' 2>&1') ?? '';
    return preg_match('/Fatal error: (.*?) in \//', $r, $m) ? $m[1] : trim($r);
};
foreach ($names as $n) {
    echo $run("module M { public function $n() {} }"), "\n";
}
echo $run("module M; function __invoke() {}"), "\n";                       // member file
echo $run("module M { public class C { public function __construct() {} public function __invoke() { return 'ok'; } } } echo (new M\C)(), \"\\n\";"), "\n";
echo $run("module M { public function __helper() { return 'ok'; } } echo M::__helper(), \"\\n\";"), "\n";
@unlink($f);
?>
--EXPECT--
Module function cannot be named __construct(): magic method names are reserved
Module function cannot be named __destruct(): magic method names are reserved
Module function cannot be named __call(): magic method names are reserved
Module function cannot be named __callStatic(): magic method names are reserved
Module function cannot be named __get(): magic method names are reserved
Module function cannot be named __set(): magic method names are reserved
Module function cannot be named __isset(): magic method names are reserved
Module function cannot be named __unset(): magic method names are reserved
Module function cannot be named __sleep(): magic method names are reserved
Module function cannot be named __wakeup(): magic method names are reserved
Module function cannot be named __serialize(): magic method names are reserved
Module function cannot be named __unserialize(): magic method names are reserved
Module function cannot be named __toString(): magic method names are reserved
Module function cannot be named __invoke(): magic method names are reserved
Module function cannot be named __set_state(): magic method names are reserved
Module function cannot be named __clone(): magic method names are reserved
Module function cannot be named __debugInfo(): magic method names are reserved
Module function cannot be named __CONSTRUCT(): magic method names are reserved
Module function cannot be named __invoke(): magic method names are reserved
ok
ok
