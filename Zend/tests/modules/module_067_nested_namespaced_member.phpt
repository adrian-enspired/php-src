--TEST--
Modules (B): static access reaches a member of a NESTED module by its canonical (tail) name
--FILE--
<?php
$dir = __DIR__ . '/nnm_tmp';
@mkdir($dir);
@mkdir("$dir/Outer");
@mkdir("$dir/Outer/Inner");

// A nested module (Outer::Inner) whose split-file members are declared under "namespace Auth":
// each is canonically Outer::Inner::<tail> and projects as Auth\<tail>.
file_put_contents("$dir/Outer.php", "<?php\nmodule Outer {\n    public module Inner {\n        public Auth\\Checker;\n        internal Auth\\Secret;\n    }\n}\n");
file_put_contents("$dir/Outer/Inner/Checker.php", "<?php\nmodule Outer::Inner;\nnamespace Auth;\nclass Checker { const OK = 'nested-ns-ok'; public static int \$count = 7; public static function run(): string { return 'ran'; } }\n");
file_put_contents("$dir/Outer/Inner/Secret.php", "<?php\nmodule Outer::Inner;\nnamespace Auth;\nclass Secret { const KEY = 'k'; }\n");

spl_autoload_register(function ($name) use ($dir) {
    $f = "$dir/" . str_replace('\\', '/', $name) . '.php';
    if (is_file($f)) require $f;
});

echo Outer::Inner::Checker::OK, "\n";
echo Outer::Inner::Checker::run(), "\n";
echo Outer::Inner::Checker::$count, "\n";
echo Outer::Inner::Checker::class, "\n";
echo (new Outer::Inner::Checker()) instanceof Outer::Inner::Checker ? "instanceof-ok\n" : "no\n";

// Internal member of the nested module: identity ungated, use denied.
echo Outer::Inner::Secret::class, "\n";
try { echo Outer::Inner::Secret::KEY; }
catch (\Error $e) { echo $e->getMessage(), "\n"; }
?>
--CLEAN--
<?php
$dir = __DIR__ . '/nnm_tmp';
@array_map('unlink', glob("$dir/Outer/Inner/*"));
@array_map('unlink', glob("$dir/*.php"));
@rmdir("$dir/Outer/Inner"); @rmdir("$dir/Outer"); @rmdir($dir);
?>
--EXPECT--
nested-ns-ok
ran
7
Outer::Inner::Checker
instanceof-ok
Outer::Inner::Secret
Cannot access internal module member "Outer::Inner::Secret" from outside its module
