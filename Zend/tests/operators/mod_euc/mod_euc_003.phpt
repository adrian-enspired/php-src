--TEST--
%% operator: tokenization (T_MOD_EUC) and parse error for stray %%%
--EXTENSIONS--
tokenizer
--FILE--
<?php
foreach (token_get_all('<?php 5 %% 2;') as $t) {
    if (is_array($t) && $t[0] === T_MOD_EUC) {
        echo token_name($t[0]), " ", var_export($t[1], true), "\n";
    }
}
// %%% tokenizes as %% then %, which cannot parse
try {
    eval('return 5 %%% 2;');
} catch (ParseError $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
?>
--EXPECT--
T_MOD_EUC '%%'
ParseError: syntax error, unexpected token "%"
