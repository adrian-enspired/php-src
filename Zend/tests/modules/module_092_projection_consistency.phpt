--TEST--
Modules (B): a member's projection must match the module's claim (mismatch is a compile error)
--DESCRIPTION--
The manifest is authoritative for a member's outward projection. A claim `public Auth\Checker;`
says the member's projection is Auth\Checker. If a member file instead declares it under a
different namespace (projecting Xyz\Checker), the mismatch is rejected at compile time — a
same-tail member alone doesn't satisfy the claim.
--FILE--
<?php
$dir = __DIR__ . '/pc_tmp';
@mkdir($dir);

file_put_contents("$dir/manifest.php", "<?php\nmodule M {\n    public Auth\\Checker;\n}\n");
// The body declares Checker under the WRONG namespace (Xyz, not Auth):
file_put_contents("$dir/member.php", "<?php\nmodule M;\nnamespace Xyz;\nclass Checker {}\n");
file_put_contents("$dir/run.php",
    "<?php\nrequire '$dir/manifest.php';\nrequire '$dir/member.php';\n");

$php = getenv('TEST_PHP_EXECUTABLE');
echo shell_exec(escapeshellarg($php) . ' -n ' . escapeshellarg("$dir/run.php") . ' 2>&1');
?>
--CLEAN--
<?php
$dir = __DIR__ . '/pc_tmp';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECTF--
%aModule member "M::Checker" is declared with projection "Xyz\Checker", which module "M" does not claim%a
