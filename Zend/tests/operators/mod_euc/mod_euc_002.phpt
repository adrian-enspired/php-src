--TEST--
%% euclidean modulo: errors (modulo by zero, invalid operand types)
--FILE--
<?php
// Runtime modulo by zero throws DivisionByZeroError
$z = 0;
try {
    var_dump(5 %% $z);
} catch (DivisionByZeroError $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}

// Non-numeric string operand throws TypeError
try {
    $s = "banana";
    var_dump($s %% 3);
} catch (TypeError $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}

// Float with fractional part: deprecated implicit conversion, then truncated (like %)
$f = 1.5;
var_dump($f %% 2);
?>
--EXPECTF--
DivisionByZeroError: Modulo by zero
TypeError: Unsupported operand types: string %% int

Deprecated: Implicit conversion from float 1.5 to int loses precision in %s on line %d
int(1)
