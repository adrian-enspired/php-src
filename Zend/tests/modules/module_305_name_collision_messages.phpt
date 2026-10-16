--TEST--
Modules: name collisions between modules and class-likes name the module, not its backing class
--FILE--
<?php
// The name of a module and a class-like may not collide; errors name the module.
foreach ([
    'module M1 {} module M1 {}',
    'module M2 {} class M2 {}',
    'module M3 {} interface M3 {}',
    'interface M4 {} module M4 {}',
    'trait M5 {} module M5 {}',
    'enum M6 {} module M6 {}',
    'module M7 { public module N {} public module N {} }',
] as $i => $code) {
    $f = __DIR__ . "/module_305_$i.inc";
    file_put_contents($f, "<?php\n$code\n");
    $out = shell_exec((getenv('TEST_PHP_EXECUTABLE') ?: PHP_BINARY) . ' -n ' . escapeshellarg($f) . ' 2>&1');
    @unlink($f);
    echo preg_replace('/ in \/.*| \(declared in .*\)/', '', strtok(trim($out), "\n")), "\n";
}
?>
--EXPECT--
Fatal error: Cannot declare M1: the name is already in use by module M1
Fatal error: Cannot declare M2: the name is already in use by module M2
Fatal error: Cannot declare M3: the name is already in use by module M3
Fatal error: Cannot declare module "M4": an interface with that name already exists
Fatal error: Cannot declare module "M5": a trait with that name already exists
Fatal error: Cannot declare M6: the name is already in use by module M6
Fatal error: Cannot declare M7\N: the name is already in use by module M7\N
