<?php
// +----------------------------------------------------------------------+
// | BIONS -believe it or not , snort-  Version 0.1                       |
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
if ($_SERVER['REQUEST_METHOD'] == "GET" and isset($_GET))
{
    // Parameter signature check
    if (ereg("^[1-9][0-9]{0,6}$", $_GET['signature'])) {
        $signature = $_GET['signature'];
    }

    // Parameter sensor id check
    if (ereg("^[1-9][0-9]{0,2}$", $_GET['sid'])) {
        $sid = $_GET['sid'];
    }
}

// Session Start
session_start();

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
$alerts->SetCurrentTime($current[0]);
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
$daily->SetYear($current[year]);
$daily->SetMonth($current[mon]);
$daily->SetDay($current[mday]);
$daily->SetHour($current[hours]);
$daily->DbQuery($db, 'hour', 23, 'G');
list($htmldata['dailygraph'], $htmldata['dailyfrom'], $htmldata['dailyto']) = $daily->GetHTML();
unset($daily);

// Weekly graph
$weekly = new BionsGraph('Weekly');
$weekly->SetCurrentSignature($signature);
$weekly->SetCrrentSensor($sid);
$weekly->SetYear($current[year]);
$weekly->SetMonth($current[mon]);
$weekly->SetDay($current[mday]);
$weekly->Sethour($current[hours]);
$weekly->DbQuery($db, 'hour', 167, 'D');
list($htmldata['weeklygraph'], $htmldata['weeklyfrom'], $htmldata['weeklyto']) = $weekly->GetHTML();
unset($weekly);

// Monthly graph
$monthly = new BionsGraph('Monthly');
$monthly->SetCurrentSignature($signature);
$monthly->SetCrrentSensor($sid);
$monthly->SetYear($current[year]);
$monthly->SetMonth($current[mon]);
$monthly->SetDay($current[mday]);
$monthly->DbQuery($db, 'day', 29, 'j');
list($htmldata['monthlygraph'], $htmldata['monthlyfrom'], $htmldata['monthlyto']) = $monthly->GetHTML();
unset($monthly);

// Yearly graph
$yearly = new BionsGraph('Yearly');
$yearly->SetCurrentSignature($signature);
$yearly->SetCrrentSensor($sid);
$yearly->SetYear($current[year]);
$yearly->SetMonth($current[mon]);
$yearly->SetDay(1, 0);
$yearly->DbQuery($db, 'month', 11, 'M');
list($htmldata['yearlygraph'], $htmldata['yearlyfrom'], $htmldata['yearlyto']) = $yearly->GetHTML();
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
