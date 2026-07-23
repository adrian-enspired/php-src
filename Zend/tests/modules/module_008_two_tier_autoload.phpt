--TEST--
Modules: two-tier autoload (module manifest, then "::"->"\" sub-file) with a PSR-4-style loader
--FILE--
<?php
$dir = __DIR__ . '/mod008';
@mkdir($dir . '/src/User', 0777, true);

// Manifest: module Vendor\User maps to src/User.php; Profile is defined inline.
file_put_contents($dir . '/src/User.php', <<<'PHP'
<?php
namespace Vendor;
module User {
    public class Profile {
        public function __construct(public string $name = "anon") {}
    }
    public Auth\PasswordChecker;   // claim keys on the tail -> Vendor\User::PasswordChecker
}
PHP);

// Membership sub-file. The member is canonically Vendor\User::PasswordChecker (module-rooted
// on its simple tail), so its autoload name is "Vendor\User\PasswordChecker" -> src/User/
// PasswordChecker.php. Its own "namespace Auth" projects it outward as Auth\PasswordChecker.
file_put_contents($dir . '/src/User/PasswordChecker.php', <<<'PHP'
<?php
module Vendor\User;
namespace Auth;
class PasswordChecker {
    public function tag(): string { return "checker"; }
}
PHP);

// Standard PSR-4-style autoloader: maps "Vendor\..." (module OR backslash form)
// onto files. Needs no module-specific logic.
spl_autoload_register(function (string $name) use ($dir): void {
    if (!str_starts_with($name, 'Vendor\\')) return;
    $rel = str_replace('\\', '/', substr($name, strlen('Vendor\\')));
    $file = $dir . '/src/' . $rel . '.php';
    echo "[autoload] $name\n";
    if (is_file($file)) require $file;
});

// Tier 1: resolved from the inline manifest definition.
$p = new Vendor\User::Profile("adrian");
echo $p->name, " ", $p::class, "\n";

// Tier 2: resolved from the separate membership sub-file (canonical, module-rooted name).
$c = new Vendor\User::PasswordChecker();
echo $c->tag(), " ", $c::class, "\n";
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod008';
@unlink($dir . '/src/User/PasswordChecker.php');
@unlink($dir . '/src/User.php');
@rmdir($dir . '/src/User');
@rmdir($dir . '/src');
@rmdir($dir);
?>
--EXPECT--
[autoload] Vendor\User
adrian Vendor\User::Profile
[autoload] Vendor\User\PasswordChecker
checker Vendor\User::PasswordChecker
