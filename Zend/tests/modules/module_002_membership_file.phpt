--TEST--
Modules: file-level membership declaration, with and without an inner namespace
--FILE--
<?php
require __DIR__ . '/module_002_def.inc';         // claims (public) — loaded before the bodies
require __DIR__ . '/module_002_member_ns.inc';
require __DIR__ . '/module_002_member_root.inc';

// The member declared under "namespace Auth" is canonically Vendor\User::PasswordChecker
// (module-rooted on its simple tail) and ALSO projects its namespace name Auth\PasswordChecker.
var_dump(class_exists('Vendor\User::PasswordChecker'));
var_dump(class_exists('Vendor\User::GuestUser'));
var_dump(class_exists('Auth\PasswordChecker'));   // the outward projection alias
var_dump(class_exists('GuestUser'));               // GuestUser has no namespace -> no projection

echo (new ('Vendor\User::PasswordChecker'))->tag(), "\n";
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(false)
checker
