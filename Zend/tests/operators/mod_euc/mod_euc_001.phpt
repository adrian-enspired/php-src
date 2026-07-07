--TEST--
%% euclidean modulo: basic semantics (result sign follows neither operand; always in [0, |divisor|))
--FILE--
<?php
// Compile-time constant folding path
var_dump(7 %% 3);
var_dump(-7 %% 3);
var_dump(7 %% -3);
var_dump(-7 %% -3);

// Runtime (CV) path
$a = -7; $b = 3;
var_dump($a %% $b);

// Contrast with truncated %
var_dump(-7 % 3);

// Precedence: same as * / %
var_dump(2 + 9 %% 4);
var_dump(-13 %% 4 * 2);

// Edge cases
var_dump(PHP_INT_MIN %% -1);
var_dump(0 %% 5);

// Usable in constant expressions
const WRAPPED = -1 %% 12;
var_dump(WRAPPED);
?>
--EXPECT--
int(1)
int(2)
int(1)
int(2)
int(2)
int(-1)
int(3)
int(6)
int(0)
int(0)
int(11)
