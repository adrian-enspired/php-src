--TEST--
Modules (B): static-access chains reach a member by its canonical (tail) name; its namespace projects outward
--FILE--
<?php
$dir = __DIR__ . '/nsm_tmp';
@mkdir($dir);
@mkdir("$dir/Vendor");
@mkdir("$dir/Vendor/User");

// Module claims two members (public + internal). Their bodies live in split files under
// "namespace Auth", so each is canonically Vendor\User::<tail> AND projects as Auth\<tail>.
file_put_contents("$dir/Vendor/User.php", "<?php\nnamespace Vendor;\nmodule User {\n    public Auth\\Checker;\n    internal Auth\\Secret;\n}\n");
file_put_contents("$dir/Vendor/User/Checker.php", "<?php\nmodule Vendor\\User;\nnamespace Auth;\nclass Checker { const OK = 'checker-ok'; public static int \$count = 41; public static function run(): string { return 'ran'; } }\n");
file_put_contents("$dir/Vendor/User/Secret.php", "<?php\nmodule Vendor\\User;\nnamespace Auth;\nclass Secret { const KEY = 'secret'; }\n");

spl_autoload_register(function ($name) use ($dir) {
    $f = "$dir/" . str_replace('\\', '/', $name) . '.php';
    if (is_file($f)) require $f;
});

// Cold static-access chains reach the member by its canonical, module-rooted name.
echo Vendor\User::Checker::OK, "\n";        // member constant
echo Vendor\User::Checker::run(), "\n";     // member static method
echo Vendor\User::Checker::$count, "\n";    // member static property
echo Vendor\User::Checker::class, "\n";     // ::class -> canonical
echo (new Vendor\User::Checker()) instanceof Vendor\User::Checker ? "instanceof-ok\n" : "no\n";

// The member also projects its namespace name (registered when its file loaded above).
echo \Auth\Checker::OK, "\n";               // reachable via the projection too
var_dump((new Vendor\User::Checker()) instanceof \Auth\Checker);   // identity through both names

// Internal member: identity (::class) is ungated; use (::KEY) is denied from outside.
echo Vendor\User::Secret::class, "\n";
try { echo Vendor\User::Secret::KEY; }
catch (\Error $e) { echo $e->getMessage(), "\n"; }
?>
--CLEAN--
<?php
$dir = __DIR__ . '/nsm_tmp';
@array_map('unlink', glob("$dir/Vendor/User/*.php"));
@array_map('unlink', glob("$dir/Vendor/*.php"));
@rmdir("$dir/Vendor/User"); @rmdir("$dir/Vendor"); @rmdir($dir);
?>
--EXPECT--
checker-ok
ran
41
Vendor\User::Checker
instanceof-ok
checker-ok
bool(true)
Vendor\User::Secret
Cannot access internal module member "Vendor\User::Secret" from outside its module
