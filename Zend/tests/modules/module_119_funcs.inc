<?php
/* Member file for module_119_functions_preload.phpt */
module PreFn;
function x(): string { return "x:" . sec(); }
function sec(): string { return "s"; }
