--TEST--
Modules (C): preloaded SPLIT member is reachable by all three names and gated, registry-free
--EXTENSIONS--
opcache
--INI--
opcache.enable_cli=1
opcache.preload={PWD}/module_106_preload.inc
--SKIPIF--
<?php
if (substr(PHP_OS, 0, 3) == 'WIN') die('skip not for Windows');
?>
--FILE--
<?php
/* Ven2\Usr and its split member file are PRELOADED. This request never requires them, so the
 * runtime ZEND_DECLARE_MODULE / alias ops never run and the per-request module registry is empty.
 * Reachability by canonical, member name, and namespaced name — plus internal gating — therefore
 * all run off the persisted class entries and aliases. */
echo class_exists("Ven2\\Usr") ? "present\n" : "absent\n";
echo (new Ven2\Usr\Api\Check())->t(), "\n";   // namespaced name (persisted alias)
echo (new Ven2\Usr::Check())->t(), "\n";       // member name / handle (persisted alias)
echo (new Ven2\Usr::Api\Check())::class, "\n"; // canonical (the persisted class itself)

$n = "Ven2\\Usr\\Api\\Tok";                    // dynamic -> runtime gate applies
try { new $n(); echo "LEAK\n"; } catch (\Throwable $e) { echo "gated\n"; }
?>
--EXPECT--
present
chk
chk
Ven2\Usr::Api\Check
gated
