<?php
/* Preload entry: manifest first, then the member file. Preload snapshots the function table
 * into SHM including the module functions' canonical entries AND their projected alias keys
 * (one shared op_array, dedup-walked by the optimizer, xlat-deduped by persistence), so all
 * of it must survive to real requests where the pseudo-mains never re-run. */
require __DIR__ . '/module_119_manifest.inc';
require __DIR__ . '/module_119_funcs.inc';
