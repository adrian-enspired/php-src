--TEST--
Module functions: sub-namespace member files, `as` handles, member-name resolution
--FILE--
<?php
$dir = __DIR__ . '/mod113_tmp';
@mkdir($dir);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
module Subby {
    public Crypto\check as verify;   // member name Subby::verify
    public Crypto\sign;              // member name Subby::sign
}
PHP);

file_put_contents($dir . '/crypto.php', <<<'PHP'
<?php
module Subby;
namespace Crypto;

function check(): string { return "checked:" . helper(); }  // bare sub-ns sibling call
function sign(): string { return "signed"; }
function helper(): string { return "h"; }                   // unclaimed -> internal
PHP);

require $dir . '/manifest.php';
require $dir . '/crypto.php';

// flat member names (handle from the claim)
var_dump(Subby::verify());
var_dump(Subby::sign());
// canonical (as a dynamic string) and namespaced names
var_dump(call_user_func('Subby::Crypto\check'));
var_dump(\Subby\Crypto\check());
// internal helper: in-module only
try { \Subby\Crypto\helper(); echo "LEAK\n"; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
// identity is canonical
var_dump((new ReflectionFunction('Subby\Crypto\check'))->name);
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod113_tmp';
@unlink($dir . '/manifest.php');
@unlink($dir . '/crypto.php');
@rmdir($dir);
?>
--EXPECT--
string(9) "checked:h"
string(6) "signed"
string(9) "checked:h"
string(9) "checked:h"
Cannot call internal module function Subby::Crypto\helper() from outside its module
string(19) "Subby::Crypto\check"
