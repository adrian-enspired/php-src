--TEST--
unless statement: condition uses full expression grammar; side effects evaluated exactly once
--FILE--
<?php
function sideEffect(): bool {
    echo "evaluated\n";
    return true;
}
unless (sideEffect()) {
    echo "never printed\n";
}

$arr = ['key' => null];
unless ($arr['key'] ?? true) {
    echo "coalesce works in condition\n";
}

$i = 0;
unless ($i++) {
    echo "post-increment: i was falsy\n";
}
var_dump($i);
?>
--EXPECT--
evaluated
post-increment: i was falsy
int(1)
