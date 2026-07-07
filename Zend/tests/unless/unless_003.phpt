--TEST--
unless is semi-reserved: still usable as method and class constant name
--FILE--
<?php
class Query {
    const unless = 42;

    public static function unless(bool $cond): string {
        return "method unless(" . var_export($cond, true) . ")";
    }

    public static function callUnless(): string {
        return static::unless(false);
    }
}

$q = new Query();
echo $q->unless(true), "\n";
var_dump(Query::unless);
echo Query::callUnless(), "\n";
?>
--EXPECT--
method unless(true)
int(42)
method unless(false)
