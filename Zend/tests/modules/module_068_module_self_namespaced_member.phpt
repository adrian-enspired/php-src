--TEST--
Modules (B): "module::" self-reference reaches a member by its canonical (tail) name
--FILE--
<?php
$dir = __DIR__ . '/msn_tmp';
@mkdir($dir);
@mkdir("$dir/M");

// Module M with an inline Facade that reaches a split-file member (Thing) via "module::"
// from inside the boundary. Thing's file declares "namespace Ns", projecting it as Ns\Thing.
file_put_contents("$dir/M.php", "<?php\nmodule M {\n    public Ns\\Thing;\n    public class Facade {\n        public static function tag(): string { return module::Thing::TAG; }\n        public static function make(): object { return new module::Thing(); }\n    }\n}\n");
file_put_contents("$dir/M/Thing.php", "<?php\nmodule M;\nnamespace Ns;\nclass Thing { const TAG = 'thing'; public function who(): string { return 'thing-obj'; } }\n");

spl_autoload_register(function ($name) use ($dir) {
    $f = "$dir/" . str_replace('\\', '/', $name) . '.php';
    if (is_file($f)) require $f;
});

echo M::Facade::tag(), "\n";              // module::Thing::TAG  (self-ref const)
echo M::Facade::make()->who(), "\n";      // new module::Thing   (self-ref new)
echo M::Thing::TAG, "\n";                 // external canonical Module::Member
echo M::Thing::class, "\n";               // ::class -> canonical
echo \Ns\Thing::TAG, "\n";                // reachable via the projection too
?>
--CLEAN--
<?php
$dir = __DIR__ . '/msn_tmp';
@array_map('unlink', glob("$dir/M/*"));
@array_map('unlink', glob("$dir/*.php"));
@rmdir("$dir/M"); @rmdir($dir);
?>
--EXPECT--
thing
thing-obj
thing
M::Thing
thing
