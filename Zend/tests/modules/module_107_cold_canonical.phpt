--TEST--
Modules (C): cold access by CANONICAL :: name loads the module then the member (n-tier)
--DESCRIPTION--
Reaching a member COLD by its canonical name (Vendor\User::Auth\PasswordChecker) goes through the
n-tier: tier-1 autoloads the module (Vendor\User); tier-2 autoloads the member by the canonical's
"::"->"\" transform (Vendor\User\Auth\PasswordChecker), which under Decision C IS the member's
namespaced name. The public member resolves with its canonical identity; an internal one is gated.
--FILE--
<?php
$dir = __DIR__ . '/mod107';
@mkdir($dir);

file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
namespace Vendor;
module User {
    public Auth\PasswordChecker;
    internal Auth\Secret;
}
PHP);

file_put_contents("$dir/checker.php", <<<'PHP'
<?php
module Vendor\User;
namespace Auth;
class PasswordChecker { public function tag(): string { return "checker"; } }
PHP);

file_put_contents("$dir/secret.php", <<<'PHP'
<?php
module Vendor\User;
namespace Auth;
class Secret {}
PHP);

spl_autoload_register(function (string $n) use ($dir): void {
    if ($n === 'Vendor\\User')                        { require "$dir/manifest.php"; return; }
    if ($n === 'Vendor\\User\\Auth\\PasswordChecker') { require "$dir/checker.php";  return; }
    if ($n === 'Vendor\\User\\Auth\\Secret')          { require "$dir/secret.php";   return; }
});

// COLD by canonical name: n-tier loads the module, then the member file.
$c = new Vendor\User::Auth\PasswordChecker();
echo $c->tag(), "\n";                          // checker
echo $c::class, "\n";                          // Vendor\User::Auth\PasswordChecker
// An internal member reached cold by canonical is gated.
try { new Vendor\User::Auth\Secret(); echo "LEAK\n"; } catch (\Error $e) { echo "gated\n"; }
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod107';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
checker
Vendor\User::Auth\PasswordChecker
gated
