--TEST--
Modules: an unclaimed member is reachable from its module by every spelling
--DESCRIPTION--
Claims decide visibility, not existence. An unclaimed member (declared in a member file but not
listed in the definition) is internal; from inside the module it can be named as module::X,
\M::X, M::X::K / ::m() chains and bare X -- loaded by autoload on first use. From outside it is
denied. A name that is no member at all is reported as "not a member" when used.
--FILE--
<?php
$dir = __DIR__ . '/m329_tmp';
@mkdir($dir);
file_put_contents("$dir/def.php", "<?php\nmodule F { public Api; }\n");
file_put_contents("$dir/impl.php", "<?php\nmodule F;\nclass Impl { const K = 'k'; static function s() { return 's'; } }\n");
file_put_contents("$dir/api.php", <<<'PHP'
<?php
module F;
class Api {
    public static function run(): void {
        foreach ([
            'new module::Impl'  => fn() => get_class(new module::Impl),
            'module::Impl::K'   => fn() => module::Impl::K,
            'module::Impl::s()' => fn() => module::Impl::s(),
            '\F\Impl::K'       => fn() => \F\Impl::K,
            '\F\Impl::s()'     => fn() => \F\Impl::s(),
            'new \F\Impl'      => fn() => get_class(new \F\Impl),
            'Impl::K'           => fn() => Impl::K,
            'module::Typo::K'   => fn() => module::Typo::K,
        ] as $k => $f) {
            try { echo "$k: ", $f(), "\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
        }
    }
}
PHP);
spl_autoload_register(function ($c) use ($dir) {
    $m = ['f' => 'def', 'f\impl' => 'impl', 'f\api' => 'api'];
    if (isset($m[strtolower($c)])) { require "$dir/{$m[strtolower($c)]}.php"; }
});
require "$dir/def.php";
F\Api::run();
foreach (['new F\Impl' => fn() => new F\Impl, 'F\Impl::K' => fn() => F\Impl::K] as $k => $f) {
    try { $f(); echo "$k: LEAK\n"; } catch (Error $e) { echo "outside $k: ", $e->getMessage(), "\n"; }
}
?>
--CLEAN--
<?php
$dir = __DIR__ . '/m329_tmp';
foreach (['def', 'impl', 'api'] as $f) { @unlink("$dir/$f.php"); }
@rmdir($dir);
?>
--EXPECT--
new module::Impl: F\Impl
module::Impl::K: k
module::Impl::s(): s
\F\Impl::K: k
\F\Impl::s(): s
new \F\Impl: F\Impl
Impl::K: k
module::Typo::K: "Typo" is not a member of module "F"
outside new F\Impl: Cannot access internal module member "F\Impl" from outside its module
outside F\Impl::K: Cannot access internal module member "F\Impl" from outside its module
