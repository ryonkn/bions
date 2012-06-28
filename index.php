<?php
// +----------------------------------------------------------------------+
// | BIONS -believe it or not , snort-  Version 0.1a                      |
// +----------------------------------------------------------------------+
// | Author: Ryo Nakano <ryo@ryonkn.com>                                  |
// +----------------------------------------------------------------------+
//

// PEAR DB require
require_once "DB.php";

// Config file require
require_once "bions_conf.php";

// Common file require
require_once "bions_common.php";

// Get cureent time
$current = getdate();

// Get Signature ID and Signature name from $_GET
if ($_SERVER['REQUEST_METHOD'] == "GET" and ereg("^[0-9]+$", $_GET['signature']) ) {
    $signature = $_GET['signature'];
}

if ($_SERVER['REQUEST_METHOD'] == "GET" and ereg("^[0-9]+$", $_GET['sid']) ) {
    $sid = $_GET['sid'];
}

// Session Start
session_start();

// Set time to SESSION
$_SESSION['currenttime'] = $current;

// Connect DB
// dsn = DB-Type://DB-User:DB-Pass@DB-Host:DB-Port/DB-Name
$db = DB::connect(DB_TYPE."://".DB_USER.":".DB_PASS."@".DB_HOST.":".DB_PORT."/".DB_NAME);

if (DB::isError($db)) {
    die ($db->getMessage());
}

// Alerts list and Current Signature Name
$alerts = new AlertsList();
$alerts->SetCurrentSignature($signature);
$alerts->SetCrrentSensor($sid);
$alerts->SetCurrentTime($_SESSION['currenttime'][0]);
$alerts->DbQuery($db);
$htmldata['alertslist']   = $alerts->GetHTML();
$htmldata['currentalert'] = $alerts->GetCurrentSigname();
unset($alerts);

// Get Sensors Name & id from DB
$sensor = new SensorsList();
$sensor->SetCurrentSignature($signature);
$sensor->SetCrrentSensor($sid);
$sensor->DbQuery($db);
$htmldata['sensors'] = $sensor->GetHTML();
unset($sensor);

// Daily graph
$daily = new BionsGraph('Daily');
$daily->SetCurrentSignature($signature);
$daily->SetCrrentSensor($sid);
$daily->SetYear($_SESSION['currenttime']['year']);
$daily->SetMonth($_SESSION['currenttime']['mon']);
$daily->SetDay($_SESSION['currenttime']['mday']);
$daily->SetHour($_SESSION['currenttime']['hours']);
$daily->DbQuery($db, 'hour', 23, 'G');
$htmldata['dailygraph'] = $daily->GetHTML('graph');
$htmldata['dailyfrom']  = $daily->GetHTML('from');
$htmldata['dailyto']    = $daily->GetHTML('to');
unset($daily);

// Weekly graph
$weekly = new BionsGraph('Weekly');
$weekly->SetCurrentSignature($signature);
$weekly->SetCrrentSensor($sid);
$weekly->SetYear($_SESSION['currenttime']['year']);
$weekly->SetMonth($_SESSION['currenttime']['mon']);
$weekly->SetDay($_SESSION['currenttime']['mday']);
$weekly->Sethour($_SESSION['currenttime']['hours']);
$weekly->DbQuery($db, 'hour', 167, 'D');
$htmldata['weeklygraph'] = $weekly->GetHTML('graph');
$htmldata['weeklyfrom']  = $weekly->GetHTML('from');
$htmldata['weeklyto']    = $weekly->GetHTML('to');
unset($weekly);

// Monthly graph
$monthly = new BionsGraph('Monthly');
$monthly->SetCurrentSignature($signature);
$monthly->SetCrrentSensor($sid);
$monthly->SetYear($_SESSION['currenttime']['year']);
$monthly->SetMonth($_SESSION['currenttime']['mon']);
$monthly->SetDay($_SESSION['currenttime']['mday']);
$monthly->DbQuery($db, 'day', 29, 'j');
$htmldata['monthlygraph'] = $monthly->GetHTML('graph');
$htmldata['monthlyfrom']  = $monthly->GetHTML('from');
$htmldata['monthlyto']    = $monthly->GetHTML('to');
unset($monthly);

// Yearly graph
$yearly = new BionsGraph('Yearly');
$yearly->SetCurrentSignature($signature);
$yearly->SetCrrentSensor($sid);
$yearly->SetYear($_SESSION['currenttime']['year']);
$yearly->SetMonth($_SESSION['currenttime']['mon']);
$yearly->SetDay(1, 0);
$yearly->DbQuery($db, 'month', 11, 'M');
$htmldata['yearlygraph'] = $yearly->GetHTML('graph');
$htmldata['yearlyfrom']  = $yearly->GetHTML('from');
$htmldata['yearlyto']    = $yearly->GetHTML('to');
unset($yearly);

// DB disconnect
$db->disconnect();

$html = new HTMLtmp(TEMPLATE_HTML);
$html->Replace($htmldata);
$html->PutHTML();
unset($html);

// Session Write
session_write_close();
?>
