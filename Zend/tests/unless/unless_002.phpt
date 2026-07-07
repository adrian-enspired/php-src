--TEST--
unless statement: condition is a full expression, evaluated exactly once
--FILE--
<?php
function falsy() {
    echo "falsy() called\n";
    return null;
}
unless (falsy()) {
    echo "body ran\n";
}

$i = 0;
unless ($i++) {
    echo "post-increment saw falsy 0\n";
}
var_dump($i);

$x = null;
unless ($x ?? true) {
    echo "not printed (coalesce gave true)\n";
}
unless ($x ?? false) {
    echo "coalesce gave false\n";
}
?>
--EXPECT--
falsy() called
body ran
post-increment saw falsy 0
int(1)
coalesce gave false
