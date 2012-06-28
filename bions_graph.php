<?php
// +----------------------------------------------------------------------+
// | BIONS -believe it or not , snort-  Version 0.3                       |
// +----------------------------------------------------------------------+
// | Author: Ryo Nakano <ryo@ryonkn.com>                                  |
// +----------------------------------------------------------------------+
//

// Config file require
require_once "bions_conf.php";

// Jpgraph require
require_once JPGRAPH_PATH."/jpgraph.php";
require_once JPGRAPH_PATH."/jpgraph_line.php";

// Get mode (Daily ,Weekly, Monthly ,Yearly)
if ($_SERVER['REQUEST_METHOD'] == 'GET' and ereg("^(Daily|Weekly|Monthly|Yearly)$", $_GET['mode'])) {
    $mode = $_GET['mode'];
}

// Session start
session_start();

$times  = $_SESSION[$mode]['times'];
$alerts = $_SESSION[$mode]['alerts'];
$hour = $_SESSION['currenttime']['hours'];

// Unset $_SESSION
unset($_SESSION[$mode]);

// Graphcount = Graphcount - 1
$_SESSION['graphcount']--;

// Graphcount == 0 are session destroy
if ($_SESSION['graphcount'] > 0) {
    session_write_close();
} else {
    session_destroy();
}

// Create New Graph OBJ
$graph = new Graph(WIDTH, HEIGHT, "auto", 60);
$graph->SetScale("textlin");

// Set Grid
$graph->ygrid->SetLineStyle('dashed');
$graph->ygrid->SetColor('gray');
$graph->ygrid->SetFill(true, 'gainsboro@0.5', 'silver@0.5');
$graph->ygrid->Show(true);
$graph->xgrid->SetLineStyle('dashed');
$graph->xgrid->SetColor('gray');
$graph->xgrid->Show(true);

// Margin set
$graph->img->SetMargin(LEFT_MARGIN, RIGHT_MARGIN, TOP_MARGIN, BOTTOM_MARGIN);
$graph->SetMarginColor('white');

// Set Anti-Aliasing
if (ANTIALIAS) {
    $graph->img->SetAntiAliasing();
}

// Graph draw and set color
$plot = new LinePlot($alerts);
$plot->SetColor(COLOR);
$plot->SetFillColor(FILLCOLOR);

// Weekly Graph is 1 label/day ... I think that there is a better way.
if ($mode == 'Weekly') {
    $graph->xaxis->SetTextTickInterval(24, 23 - $hour);
    $times = array_slice($times,23 - $hour);
}

// Time label output
$graph->xaxis->SetTickLabels($times);

// $plot Plot
$graph->Add($plot);

// Graph output
$graph->Stroke();

?>
