--TEST--
Modules (C): a public member is reachable COLD via its namespaced name, which loads the module
--DESCRIPTION--
`new Vendor\User\Auth\PasswordChecker` — the member's namespaced name — accessed before the
module has been loaded must (a) autoload the member file via an ordinary PSR-4-style loader and
(b) cause the member file's `module Vendor\User;` directive to load the module definition at
runtime (the ensure-load), so the member resolves with its CLAIMED public visibility and its flat
member name (`Vendor\User::PasswordChecker`) rather than defaulting to internal / going unnamed.

The loader maps the namespaced name (Vendor\User\Auth\PasswordChecker) and the module name
(Vendor\User) onto files — no module-specific logic. The FIRST access is the cold namespaced
name; nothing has loaded the module yet. Canonical identity is Vendor\User::Auth\PasswordChecker.
--FILE--
<?php
$dir = __DIR__ . '/mod096';
@mkdir($dir);

file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
namespace Vendor;
module User {
    public Auth\PasswordChecker;   // canonical Vendor\User::Auth\PasswordChecker; member name Vendor\User::PasswordChecker
}
PHP);

file_put_contents("$dir/member.php", <<<'PHP'
<?php
module Vendor\User;
namespace Auth;
class PasswordChecker {
    public function tag(): string { return "checker"; }
}
PHP);

spl_autoload_register(function (string $name) use ($dir): void {
    if ($name === 'Vendor\\User\\Auth\\PasswordChecker') { require "$dir/member.php";   return; }
    if ($name === 'Vendor\\User')                        { require "$dir/manifest.php"; return; }
});

// COLD: nothing has loaded module Vendor\User. Reach the public member by its namespaced name.
$c = new Vendor\User\Auth\PasswordChecker();
echo $c->tag(), "\n";                                       // checker
echo $c::class, "\n";                                       // canonical identity is reported
var_dump(class_exists('Vendor\User::PasswordChecker'));     // member name exists (cold handle)
var_dump($c instanceof Vendor\User::PasswordChecker);       // identity holds through the member name
var_dump($c instanceof Vendor\User::Auth\PasswordChecker);  // and through the canonical name
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod096';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
checker
Vendor\User::Auth\PasswordChecker
bool(true)
bool(true)
bool(true)
