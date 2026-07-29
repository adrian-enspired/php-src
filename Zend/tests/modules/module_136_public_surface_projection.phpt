--TEST--
Modules: the public-surface check sees bare (projection-form) type names
--DESCRIPTION--
A bare reference inside a membership file resolves to the member's PROJECTION name ("F\Impl"),
not its canonical one ("F::Impl") -- that is deliberate, so a reference can fall through to an
external symbol. But the roster is keyed by canonical name, so a check that matches only on
"::" would silently pass on the majority of real code, which writes bare names. All three
spellings of the same member must be treated alike.
--FILE--
<?php
$dir = __DIR__ . '/mod136_tmp';
@mkdir($dir);
file_put_contents($dir . '/manifest.php', "<?php\nmodule F { public Api; internal Impl; }\n");

$php = getenv('TEST_PHP_EXECUTABLE');
foreach ([
    'bare'       => 'Impl',
    'canonical'  => '\F::Impl',
    'projection' => '\F\Impl',
] as $label => $type) {
    file_put_contents($dir . '/m.php',
        "<?php\nmodule F;\nclass Impl {}\nclass Api { public function leak(): $type { return new Impl(); } }\n");
    file_put_contents($dir . '/r.php',
        "<?php\nrequire __DIR__.'/manifest.php'; require __DIR__.'/m.php'; echo \"NOT CAUGHT\\n\";");
    $out = shell_exec(escapeshellarg($php) . ' -n ' . escapeshellarg($dir . '/r.php') . ' 2>&1');
    printf("%-11s: %s\n", $label,
        str_contains($out, 'not a public member of module "F"') ? 'caught' : trim($out));
}

// a public member is fine in all three spellings
file_put_contents($dir . '/m.php',
    "<?php\nmodule F;\nclass Impl {}\nclass Api { public function fine(): Api { return \$this; } }\n");
file_put_contents($dir . '/r.php',
    "<?php\nrequire __DIR__.'/manifest.php'; require __DIR__.'/m.php'; echo \"public ok\\n\";");
echo trim(shell_exec(escapeshellarg($php) . ' -n ' . escapeshellarg($dir . '/r.php') . ' 2>&1')), "\n";
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod136_tmp';
foreach (glob($dir . '/*.php') as $f) { @unlink($f); }
@rmdir($dir);
?>
--EXPECT--
bare       : caught
canonical  : caught
projection : caught
public ok
