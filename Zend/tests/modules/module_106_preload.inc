<?php
/* Preload entry: manifest FIRST (so the split member file compiles with the module's backing
 * class already present — warm-baked, never cold), then the split member file. Preload snapshots
 * the whole class table into SHM, including the member's namespaced-name and member-name aliases,
 * so they must survive to real requests where the per-request module registry is empty. */
require __DIR__ . '/module_106_manifest.inc';
require __DIR__ . '/module_106_member.inc';
