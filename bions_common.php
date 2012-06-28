<?php
// +----------------------------------------------------------------------+
// | BIONS -believe it or not , snort-  Version 0.3                       |
// +----------------------------------------------------------------------+
// | Author: Ryo Nakano <ryo@ryonkn.com>                                  |
// +----------------------------------------------------------------------+
//

/**
 * Generate Graph for snort
 */
class BionsGraph extends BionsCommon {

    /**
     * Graph title ( Daily | Weekly | Monthly | Yearly )
     * @var    string
     * @access private
     */
    var $_mode;

    /**
     * Array containing the times
     * @var     array
     * @access  private
     */
    var $_times;

    /**
     * Graph scale first time
     * @var    string
     * @access private
     */
    var $_fromtime;

    /**
     * Graph scale end time
     * @var    string
     * @access private
     */
    var $_totime;

    /**
     * Class constructor
     * @param    string $title
     * @access   public
     */
    function BionsGraph($title)
    {
        BionsCommon::BionsCommon();
        $this->_mode                = $title;
        $this->_times['year']['s']  = 0;
        $this->_times['year']['e']  = 0;
        $this->_times['month']['s'] = 0;
        $this->_times['month']['e'] = 0;
        $this->_times['day']['s']   = 0;
        $this->_times['day']['e']   = 0;
        $this->_times['hour']['s']  = 0;
        $this->_times['hour']['e']  = 0;
        $this->_fromtime            = '';
        $this->_totime              = '';
        return true;
    } // end constructor

    /**
     * Sets the Start and End time
     * @param   string $mode ( year | month | day | hour)
     * @param   int    $start
     * @param   int    $end
     * @access  private
     */
    function _SetTime($mode, $start, $end = '')
    {
        $this->_times[$mode]['s'] = $start;

        if ($end == '') {
            $this->_times[$mode]['e'] = $start;
        } else {
            $this->_times[$mode]['e'] = $end;
        }
        return true;
    } // end func _SetTime

    /**
     * Sets the Start and End time for YEAR
     * @param   int    $start
     * @param   int    $end
     * @access  public
     */
    function SetYear($start= 0, $end = '')
    {
        $this->_Settime('year', $start ,$end);
        return true;
    } // end func SetYear

    /**
     * Sets the Start and End time for MONTH
     * @param   int    $start
     * @param   int    $end
     * @access  public
     */
    function SetMonth($start= 0, $end = '')
    {
        $this->_Settime('month', $start ,$end);
        return true;
    } // end func SetMonth

    /**
     * Sets the Start and End time for DAY
     * @param   int    $start
     * @param   int    $end
     * @access  public
     */
    function SetDay($start= 0, $end = '')
    {
        $this->_Settime('day', $start ,$end);
        return true;
    } // end func SetDay

    /**
     * Sets the Start and End time for HOUR
     * @param   int    $start
     * @param   int    $end
     * @access  public
     */
    function SetHour($start= 0, $end = '')
    {
        $this->_Settime('hour', $start ,$end);
        return true;
    } // end func SetHour

    /**
     * Alert count from DB
     * @param   object  $db
     * @param   string  $var   ( year | month | day | hour)
     * @param   int     $count
     * @param   string  $timelabel for date()
     * @access  public
     */
    function DbQuery($db, $var, $count, $timelabel)
    {
        $times  = $this->_times;

        $times[$var]['s'] = $times[$var]['s'] -$count;
        $times[$var]['e'] = $times[$var]['e'] -$count + 1;

        for ($i = 0; $i <= $count; $i++)
        {
            $starttime  = date("Y/m/d H:i:s",mktime($times['hour']['s'] , 0 , 0 , $times['month']['s'], $times['day']['s'], $times['year']['s']));
            $endtime    = date("Y/m/d H:i:s",mktime($times['hour']['e'] , 0 , 0 , $times['month']['e'], $times['day']['e'], $times['year']['e']));

            if ($i == 0) {
                $this->_fromtime = $starttime;
            } elseif ($i == $count) {
                $this->_totime   = date("Y/m/d H:i:s", strtotime($endtime) - 1 );
            }

            $sql  = "SELECT count(*) FROM event WHERE timestamp >= '".$starttime."' AND timestamp  < '".$endtime."'";

            if ($this->_signature > 0) {
                $sql .= " AND signature = ".$this->_signature;
            }

            if ($this->_sensor> 0) {
                $sql .= " AND sid = ".$this->_sensor;
            }

            $result = $db->getOne($sql);

            if (DB::isError($result)) {
              die ($result->getMessage() ."<br>".$result->getDebugInfo());
            }

            $label = date($timelabel,mktime($times['hour']['s'], 0,0 , $times['month']['s'], $times['day']['s'], $times['year']['s']));

            $_SESSION[$this->_mode]['alerts'][] = $result;
            $_SESSION[$this->_mode]['times'][]  = $label;

            if (DEBUG) {
                echo $label.",".$result."=".$sql."<br />\n";
            }

            $times[$var]['s']++;
            $times[$var]['e']++;
        }

        $_SESSION['graphcount']++;

        $this->_html['graph'] = '<img src="'.GRAPH_PHP.'?mode='.$this->_mode.'" alt="'.$this->_mode.' Graph" width="'.WIDTH.'" height="'.HEIGHT.'" />';
        $this->_html['from'] = $this->_fromtime;
        $this->_html['to'] = $this->_totime;

        return true;
    } // end func DbQuery

} // end class BionsGraph


/**
 * Generate AlertsList for snort
 */
class AlertsList extends BionsCommon {

    /**
     * Current Signature name
     * @var    string
     * @access private
     */
    var $_signame;

    /**
     * Class constructor
     * @param    none
     * @access   public
     */
    function AlertsList()
    {
        BionsCommon::BionsCommon();
        $this->_signame = '';
        return true;
    } // end constructor

    /**
     * Get Current Signature name
     * @access  public
     * @return  string
     */
    function GetCurrentSigname()
    {
        return $this->_signame;
    } // end func GetCurrentSigname

    /**
     * Generate Alerts List HTML from DB
     * @param   object  $db
     * @access  public
     */
    function DbQuery($db)
    {
        $sql  = "SELECT sig_id, sig_name, sig_sid ,coalesce(sig_class_name, 'unclassified'), count(*) as cnt ".
                "FROM  ( signature NATURAL LEFT JOIN sig_class ), event ".
                "WHERE signature.sig_id = event.signature AND ".
                "timestamp > '".date("Y/m/d H:i:s",$this->_time - 86400)."' AND timestamp <= '".date("Y/m/d H:i:s",$this->_time)."' ";

        if ($this->_sensor > 0) {
            $sql .= "AND sid = ".$this->_sensor." ";
        }

        $sql .= "GROUP BY signature.sig_id, signature.sig_name, signature.sig_sid, sig_class.sig_class_name ".
                "ORDER BY cnt DESC;";

        $result = $db->getAll($sql);

        if (DEBUG) {
            var_dump($result);
            echo "<br />".$sql."<br />\n";
        }

        if (!DB::isError($result)) {

            $result[-1] = array(0, 'All Alerts', '&nbsp;', 'All Classification', '&nbsp;');
            $count = count($result) - 1;

            for ($i = -1 ; $i < $count ; $i++)
            {
                $result[$i][1] = htmlspecialchars($result[$i][1], ENT_QUOTES);
                $result[$i][3] = htmlspecialchars($result[$i][3], ENT_QUOTES);

                $this->_html .= '<tr>';

                if ($result[$i][2] > 0 and $result[$i][2] < 1000000) {
                    $this->_html      .= $this->_GenerateTD('<a href = "'.SNORTORG.$result[$i][2].'">Snort:'.$result[$i][2].'</a>');
                } else {
                    $this->_html      .= $this->_GenerateTD('&nbsp;');
                }

                if ($this->_signature == $result[$i][0]) {
                    $this->_html      .= $this->_GenerateTD($result[$i][1],'currentalert');
                    $this->_signame    = $result[$i][1];
                } else {
                    $linkTD            = $this->_GenerateLink($result[$i][1] ,$result[$i][0] ,$this->_sensor, $this->_time, $this->_now);
                    $this->_html      .= $this->_GenerateTD($linkTD);
                }

                $this->_html          .= $this->_GenerateTD($result[$i][3]);
                $this->_html          .= $this->_GenerateTD($result[$i][4]);

                $this->_html .= "</tr>\n";
            }

        } else {
           die ($result->getMessage() ."<br>".$result->getDebugInfo());
        }
        return true;
    } // end func DbQuery

    /**
     * Generate Table TD
     * @param   string  $contents
     * @param   string  $class
     * @access  private
     * @return  string
     */
    function _GenerateTD($contents,$class = 'list')
    {
      $td = '<td class = "'.$class.'">'.$contents.'</td>';
      return $td;
    } // end func _GenerateTD
} // end class AlertsList


/**
 * Generate Sensors List for snort
 */
class SensorsList extends BionsCommon {

    /**
     * Class constructor
     * @param    none
     * @access   public
     */
    function SensorsList()
    {
        BionsCommon::BionsCommon();
        return true;
    } // end constructor

    /**
     * Generate Alerts List HTML from DB
     * @param   object  $db
     * @access  public
     */
    function DbQuery($db)
    {
        if (SENSORS) {

            $sql = "SELECT sid,hostname,interface FROM sensor ORDER BY sid;";
            $result = $db->getAll($sql);

            if (DEBUG) {
                var_dump($result);
                echo "<br />".$sql."<br />\n";
            }

            if (!DB::isError($result)) {

                $result[-1] = array(0, 'All Sensors', 'All');
                $count = count($result) - 1;

                for ($i = -1 ; $i < $count ; $i++) {

                    $name = htmlspecialchars( $result[$i][1].'%'.$result[$i][2] , ENT_QUOTES);

                    $this->_html .= '[';
                    if ($this->_sensor == $result[$i][0]) {
                        $this->_html .= '<span class="currentsensor">'.$name.'</span>';
                    } else {
                        $this->_html .= $this->_GenerateLink($name, $this->_signature, $result[$i][0], $this->_time, $this->_now);
                    }
                    $this->_html .= ']&nbsp;';

                }
            } else {
                die ($result->getMessage() ."<br>".$result->getDebugInfo());
            }

        } else {
            $this->_html = "All Sensors";
        }
        return true;
    } // end func DbQuery
} // end class SensorsList


/**
 * Generate Time List for snort
 */
class TimeList extends BionsCommon {

    /**
     * Class constructor
     * @param    none
     * @access   public
     */
    function TimeList()
    {
        BionsCommon::BionsCommon();
        return true;
    } // end constructor

    /**
     * Return Currenttime
     * @param    none
     * @access   public
     * @return   string
     */
    function NowTime()
    {
        return date("Y/m/d H:i:s", $this->_time);
    } // end func NowTime

    /**
     * GenerateTD
     * @param    string   $mode
     * @param    int      $var
     * @param    bool     $now
     * @access   private
     * @return   string
     */
    function _GenerateTD($mode, $val, $now)
    {
        $time = getdate($this->_time);
        $time[$mode] += $val;

        $epoch = mktime($time['hours'], $time['minutes'], $time['seconds'], $time['mon'], $time['mday'], $time['year']);

        if ($now) {
             $linkname  = 'now';
        } else {
            $linkname = date("Y/m/d H:i:s", $epoch);
        }

        return '<td class="center">'.$this->_GenerateLink($linkname, $this->_signature, $this->_sensor, $epoch, $now)."</td>";;
    } // end func _GenerateTD

    /**
     * Generate Alerts List HTML from DB
     * @access  public
     */
    function Generate()
    {
        $time = getdate($this->_time);

        $this->_html  = '<tr>';
        $this->_html .= $this->_GenerateTD('mday', -1, false);
        $this->_html .= $this->_GenerateTD('mday',  0, true);
        $this->_html .= $this->_GenerateTD('mday',  1, false);
        $this->_html .= "</tr>\n";

        $this->_html .= '<tr>';
        $this->_html .= $this->_GenerateTD('mday', -7, false);
        $this->_html .='<td class="center">&lt;- 1 Week -&gt;</td>';
        $this->_html .= $this->_GenerateTD('mday',  7, false);
        $this->_html .= "</tr>\n";

        $this->_html .= '<tr>';
        $this->_html .= $this->_GenerateTD('mon', -1, false);
        $this->_html .='<td class="center">&lt;-1 Month -&gt;</td>';
        $this->_html .= $this->_GenerateTD('mon',  1, false);
        $this->_html .= "</tr>\n";

    } // end func Generate
} // end class TimeList


/**
 * Parent CLASS of BionsGraph/AlertsList/SensorsList
 */
class BionsCommon {

    /**
     * Signature number
     * @var    int
     * @access private
     */
    var $_signature;

    /**
     * Sensor number
     * @var    int
     * @access private
     */
    var $_sensor;

    /**
     * Epoch of current time
     * @var    int
     * @access private
     */
    var $_time;

    /**
     * Current time is now
     * @var    bool
     * @access private
     */
    var $_now;

    /**
     * BaseURL of Link
     * @var    string
     * @access private
     */
    var $_url;

    /**
     * HTML data
     * @var    string
     * @access private
     */
    var $_html;

    /**
     * Class constructor
     * @param    none
     * @access   public
     */
    function BionsCommon()
    {
        $this->_signature = 0;
        $this->_sensor    = 0;
        $this->_time      = 0;
        $this->_now       = true;
        $this->_url       = $_SERVER['SCRIPT_NAME'];
        $this->_html      = '';
        return true;
    } // end constructor

    /**
     * Set Current signature number
     * @param   int    $signature
     * @access  public
     */
    function SetCurrentSignature($signature = 0)
    {
        $this->_signature = $signature;
        return true;
    } // end func SetCurrentSignature

    /**
     * Set Current sensor number
     * @param   int    $sensor
     * @access  public
     */
    function SetCrrentSensor($sensor = 0)
    {
        $this->_sensor    = $sensor;
        return true;
    } // end func SetCrrentSensor

    /**
     * Set epoch of Current time
     * @param   int    $current
     * @param   bool   $now
     * @access  public
     */
    function SetCurrentTime($current = 0, $now = true)
    {
        $this->_time      = $current;
        $this->_now       = $now;
        return true;
    } // end func SetCurrentTime

    /**
     * Generate HTML link
     * @param   string  $link_name
     * @param   int     $signature
     * @param   int     $sensor
     * @param   int     $time
     * @param   bool    $now
     * @access  private
     * @return  string
     */
    function _GenerateLink($link_name , $signature, $sid ,$time = 0, $now = true )
    {
        $url = $this->_url;
        $first = true;

        if ($signature > 0) {
            $url   .= '?signature='.$signature;
            $first  = false;
        }

        if ($sid > 0 and $first) {
            $url   .= '?sid='.$sid;
            $first  = false;
        } elseif ($sid > 0) {
            $url .= '&sid='.$sid;
        }

        if (!$now and $first) {
            $url   .= '?time='.$time;
            $first  = false;
        } elseif (!$now) {
            $url .= '&time='.$time;
        }

        $link = '<a href = "'.$url.'">'.$link_name.'</a>';

        return $link;
    } // end func _GenerateLink

    /**
     * Return HTML data
     * @access  public
     * @return  string
     */
    function GetHTML($mode = NULL)
    {
        if (isset($mode)) {
            return $this->_html[$mode];
        } else {
            return $this->_html;
        }
    } // end func GetHTML

} // end class BionsCommon


/**
 * HTML Template Class
 */
class HTMLtmp {

    /**
     * Html data
     * @var    string
     * @access private
     */
    var $_html;

    /**
     * Class constructor
     * @param    string  $filename
     * @access   public
     */
    function HTMLtmp($filename)
    {
        if (file_exists($filename)) {
            $this->_html = implode("", file($filename));
        } else {
            die ("HTML Templete file is not exist.");
        }
        return true;
    } // end constructor

    /**
     * Replace from template to HTML data
     * @param   array  $htmldata
     * @access  public
     */
    function Replace($htmldata)
    {
        foreach ($htmldata  as $key => $val) {
            $this->_html = str_replace("{".$key."}", $val, $this->_html);
        }
        return true;
    } // end func Replace

    /**
     * Put HTML
     * @access  public
     */
    function PutHTML()
    {
        echo $this->_html;
        return true;
    } // end func PutHTML
} // end class HTMLtmp
?>