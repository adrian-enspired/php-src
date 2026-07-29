--TEST--
Modules: an undecidable public return type is checked at first return, on the DECLARATION
--DESCRIPTION--
A bare reference in a membership file resolves to the projection form, which is deliberately
ambiguous: it may name an unclaimed member (internal) or an ordinary class in a namespace that
matches the module name (public). Only the resolved class entry separates them, so the
publication check is deferred to first return.

The check inspects the DECLARED return type and nothing else. Returning an internal instance
through a public supertype stays legal -- the value's own type is verified separately.
--FILE--
<?php
$dir = __DIR__ . '/mod137_tmp';
@mkdir($dir);
$php = escapeshellarg(getenv('TEST_PHP_EXECUTABLE'));

// (1) unclaimed member, named bare in a public return type -> deferred, then caught
file_put_contents($dir . '/a.php', "<?php\nmodule F { public Api; }\n");
file_put_contents($dir . '/b.php',
    "<?php\nmodule F;\nclass Impl {}\nclass Api { public function leak(): Impl { return new Impl(); } }\n");
file_put_contents($dir . '/r.php', "<?php\nrequire __DIR__.'/a.php'; require __DIR__.'/b.php';\n"
    . "echo \"compiles\\n\";\ntry { (new F::Api)->leak(); echo \"LEAKED\\n\"; }\n"
    . "catch (\\Error \$e) { echo \"caught at return\\n\"; }\n");
echo shell_exec($php . ' -n ' . escapeshellarg($dir . '/r.php') . ' 2>&1');

// (2) an ordinary class in a matching namespace is NOT a member -> must pass
file_put_contents($dir . '/c.php', "<?php\nnamespace G;\nclass External {}\n");
file_put_contents($dir . '/d.php', "<?php\nmodule G { public Api; }\n");
file_put_contents($dir . '/e.php',
    "<?php\nmodule G;\nclass Api { public function fine(): External { return new External(); } }\n");
file_put_contents($dir . '/r2.php', "<?php\nrequire __DIR__.'/c.php'; require __DIR__.'/d.php'; require __DIR__.'/e.php';\n"
    . "echo \"external ok: \", get_class((new G::Api)->fine()), \"\\n\";\n");
echo shell_exec($php . ' -n ' . escapeshellarg($dir . '/r2.php') . ' 2>&1');

// (3) the value's type is a separate concern: an internal instance may escape
file_put_contents($dir . '/f.php', "<?php\nmodule K { public Iface; public Api; internal Impl; }\n");
file_put_contents($dir . '/g.php',
    "<?php\nmodule K;\ninterface Iface {}\nclass Impl implements Iface {}\n"
    . "class Api { public function open(): Iface { return new Impl(); } }\n");
file_put_contents($dir . '/r3.php', "<?php\nrequire __DIR__.'/f.php'; require __DIR__.'/g.php';\n"
    . "echo \"escaped internal: \", get_class((new K::Api)->open()), \"\\n\";\n");
echo shell_exec($php . ' -n ' . escapeshellarg($dir . '/r3.php') . ' 2>&1');
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod137_tmp';
foreach (glob($dir . '/*.php') as $f) { @unlink($f); }
@rmdir($dir);
?>
--EXPECT--
compiles
caught at return
external ok: G\External
escaped internal: K::Impl
