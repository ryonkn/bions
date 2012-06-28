<?php
// +----------------------------------------------------------------------+
// | BIONS -believe it or not , snort-  Version 0.1a                      |
// +----------------------------------------------------------------------+
// | Author: Ryo Nakano <ryo@ryonkn.com>                                  |
// +----------------------------------------------------------------------+
//

// DB setup
define("DB_TYPE",            "pgsql");      /* MySQL = mysql , PostgreSQL = pgsql */
define("DB_HOST",            "127.0.0.1");
define("DB_PORT",            "5432");       /* default : MySQL = 3306 , PostgreSQL = 5432 */
define("DB_USER",            "snort");
define("DB_PASS",            "snort");
define("DB_NAME",            "snort");

// Jpgraph path
define("JPGRAPH_PATH",       "jpgraph-1.14/src");

// Many sensors
define("SENSORS",            true);

// Debug mode ( debug on = true , debug off = false )
define("DEBUG",              false);

define("GRAPH_PHP",          "bions_graph.php");
define("TEMPLATE_HTML",      "bions_temp.html");

// Snort Signature Database Link
define("SNORTORG",           "http://www.snort.org/snort-db/sid.html?sid=");

// Graph style
define("COLOR",              "navy@0.7");
define("FILLCOLOR",          "skyblue@0.5");
define("WIDTH",              500);
define("HEIGHT",             135);

// Margin set
define("LEFT_MARGIN",        50);
define("RIGHT_MARGIN",       10);
define("TOP_MARGIN",         10);
define("BOTTOM_MARGIN",      20);

/*
 * Anti-aliasing for Graph ( on = true ,off = false )
 * Anti-aliased line drawing is roughly 8 times slower then lines without anti-aliasing
 *
 *   See http://www.aditus.nu/jpgraph/jpg_antialias.php
*/
define("ANTIALIAS",          false);

?>
