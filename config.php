<?php

define("DB_HOST", "89.216.97.244");
define("DB_USER", "doaj");
define("DB_PASS", "c30n#d0aJ");
define("DB_NAME", "Paper_Processor_Seesame");
define("DRIVER", "sqlsrv:server");
ini_set('memory_limit', '4G');
ini_set("xdebug.var_display_max_children", -1);
ini_set("xdebug.var_display_max_data", -1);
ini_set("xdebug.var_display_max_depth", -1);


if (PHP_SAPI === 'cli') {
    $break = "\n";
} else {
    $break = "<br>";
}
