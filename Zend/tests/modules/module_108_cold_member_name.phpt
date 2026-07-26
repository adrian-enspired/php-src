--TEST--
Modules (C): cold access by flat MEMBER NAME resolves via the n-tier member-name map
--DESCRIPTION--
A sub-namespaced member's flat member name (Vendor\User::Checker) drops the sub-namespace, so the
naive "::"->"\" transform (Vendor\User\Checker) would miss the member file (at
Vendor\User\Auth\Checker). After tier-1 loads the module, the n-tier consults the module's
member-name -> namespaced-name map to autoload the right file. A root member (member name ==
canonical) needs no map (the naive transform already lands right). Internal members stay gated.
--FILE--
<?php
$dir = __DIR__ . '/mod108';
@mkdir($dir);

file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
namespace Vendor;
module User {
    public Auth\Checker;      // member name Vendor\User::Checker; namespaced Vendor\User\Auth\Checker
    internal Auth\Hidden;     // member name Vendor\User::Hidden; internal
    public Root;              // root: member name == canonical Vendor\User::Root
}
PHP);

file_put_contents("$dir/checker.php", <<<'PHP'
<?php
module Vendor\User;
namespace Auth;
class Checker { public function tag(): string { return "chk"; } }
class Hidden {}
PHP);

file_put_contents("$dir/root.php", <<<'PHP'
<?php
module Vendor\User;
class Root { public function tag(): string { return "root"; } }
PHP);

spl_autoload_register(function (string $n) use ($dir): void {
    if ($n === 'Vendor\\User')                { require "$dir/manifest.php"; return; }
    if ($n === 'Vendor\\User\\Auth\\Checker') { require "$dir/checker.php";  return; }
    if ($n === 'Vendor\\User\\Auth\\Hidden')  { require "$dir/checker.php";  return; }
    if ($n === 'Vendor\\User\\Root')          { require "$dir/root.php";     return; }
});

// COLD by member name (sub-namespaced): resolves via the member-name -> namespaced-name map.
echo (new Vendor\User::Checker())->tag(), "\n";   // chk
echo (new Vendor\User::Checker())::class, "\n";    // Vendor\User::Auth\Checker (canonical identity)
// COLD by member name (root member): the naive transform already lands right.
echo (new Vendor\User::Root())->tag(), "\n";       // root
// An internal member reached by its member name is gated.
try { new Vendor\User::Hidden(); echo "LEAK\n"; } catch (\Error $e) { echo "gated\n"; }
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod108';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
chk
Vendor\User::Auth\Checker
root
gated
