<?php
// +----------------------------------------------------------------------+
// | BIONS -believe it or not , snort-  Version 0.3a                      |
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

// Session Start
session_start();

// Get Signature ID and Signature name and Now from $_GET
if (ereg("^[0-9]+$", $_GET['signature']) and $_GET['signature'] > 0 and $_GET['signature'] <= 2147483647) {
    $signature = $_GET['signature'];
}

if (ereg("^[0-9]+$", $_GET['sid']) and $_GET['sid'] > 0 and $_GET['sid'] <= 2147483657) {
    $sid = $_GET['sid'];
}

if (ereg("^[0-9]+$", $_GET['time']) and $_GET['time'] > 0 and $_GET['time'] <= 2147483657) {
    $_SESSION['currenttime'] = getdate($_GET['time']);
    $now                     = false;
} else {
    $_SESSION['currenttime'] = getdate();
    $now                     = true;
}

// Connect DB
$dsn = array( 'phptype'    => DB_TYPE,
              'dbsyntax'   => DB_SYNT,
              'protocol'   => DB_PROT,
              'database'   => DB_NAME,
              'username'   => DB_USER,
              'password'   => DB_PASS,
              'hostspec'   => DB_HOST,
              'proto_opts' => DB_OPTS );

$db = DB::connect($dsn);

if (DB::isError($db)) {
    die ($db->getMessage() ."<br>".$db->getDebugInfo());
}

// Alerts list and Current Signature Name
$alerts = new AlertsList();
$alerts->SetCurrentSignature($signature);
$alerts->SetCrrentSensor($sid);
$alerts->SetCurrentTime($_SESSION['currenttime'][0], $now);
$alerts->DbQuery($db);
$htmldata['alertslist']   = $alerts->GetHTML();
$htmldata['currentalert'] = $alerts->GetCurrentSigname();
$alerts->Destructor();

// Get Sensors Name & id from DB
$sensor = new SensorsList();
$sensor->SetCurrentSignature($signature);
$sensor->SetCrrentSensor($sid);
$sensor->SetCurrentTime($_SESSION['currenttime'][0], $now);
$sensor->DbQuery($db);
$htmldata['sensors'] = $sensor->GetHTML();
$sensor->Destructor();

// Get TimeList
$timelist = new TimeList();
$timelist->SetCurrentSignature($signature);
$timelist->SetCrrentSensor($sid);
$timelist->SetCurrentTime($_SESSION['currenttime'][0], $now);
$timelist->Generate();
$htmldata['timelist'] = $timelist->GetHTML();
$htmldata['nowtime'] = $timelist->NowTime();
$timelist->Destructor();

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
$daily->Destructor();

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
$weekly->Destructor();

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
$monthly->Destructor();

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
$yearly->Destructor();

// DB disconnect
$db->disconnect();

$html = new HTMLtmp(TEMPLATE_HTML);
$html->Replace($htmldata);
$html->PutHTML();
unset($html);

// Session Write
session_write_close();
?>
