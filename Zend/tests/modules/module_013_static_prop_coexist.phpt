--TEST--
Modules: module-qualified "::" references coexist with static-property class references
--FILE--
<?php
module App {
    public class Service {
        public function __construct(public string $tag = "svc") {}
    }
}

class Registry {
    public static $cls = 'App::Service';
}

// (1) Module member via "::" bareword
$a = new App::Service("a");
echo $a->tag, " ", $a::class, "\n";

// (2) Static-property-as-class-name via "::$prop" — must still parse and work,
//     including when the stored name is itself a module-qualified name.
$b = new Registry::$cls;
echo $b->tag, " ", $b::class, "\n";

// (3) instanceof with a module-qualified name
var_dump($a instanceof App::Service);

// (4) instanceof with a static-property class reference
var_dump($b instanceof Registry::$cls);
?>
--EXPECT--
a App::Service
svc App::Service
bool(true)
bool(true)
