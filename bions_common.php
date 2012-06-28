<?php
// +----------------------------------------------------------------------+
// | BIONS -believe it or not , snort-  Version 0.1                       |
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
        unset($this->_times);

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
                $sql .= " and signature = ".$this->_signature;
            }

            if ($this->_sensor> 0) {
                $sql .= " and sid = ".$this->_sensor;
            }

            $result = $db->getOne($sql);

            if (DB::isError($result)) {
              die ($result->getMessage());
            }

            $label = date($timelabel,mktime($times['hour']['s'], 0,0 , $times['month']['s'], $times['day']['s'], $times['year']['s']));

            $data['alerts'][] = $result;
            $data['times'][]  = $label;

            if (DEBUG) {
                echo $label.",".$result."=".$sql."<br>\n";
            }

            $times[$var]['s']++;
            $times[$var]['e']++;
        }

        $_SESSION[$this->_mode] = $data;
        $_SESSION['graphcount']++;
        unset($data);

        $this->_html[0] = '<img src="'.GRAPH_PHP.'?mode='.$this->_mode.'" alt="'.$this->_mode.' Graph" width="'.WIDTH.'" height="'.HEIGHT.'" />';
        $this->_html[1] = $this->_fromtime;
        $this->_html[2] = $this->_totime;

        return true;
    } // end func DbQuery

} // end class BionsGraph


/**
 * Generate AlertsList for snort
 */
class AlertsList extends BionsCommon {

    /**
     * Epoch of current time
     * @var    int
     * @access private
     */
    var $_time = '';

    /**
     * Current Signature name
     * @var    string
     * @access private
     */
    var $_signame = '';

    /**
     * Class constructor
     * @param    none
     * @access   public
     */
    function AlertsList()
    {
        BionsCommon::BionsCommon();
        $this->_html      = '<table summary="Alerts List" class="alertlist">'.
                            '<thead><tr>'.
                            '<th class="header">Signature Link</th>'.
                            '<th class="header">Alert Message</th>'.
                            '<th class="header">Classification</th>'.
                            '<th class="header">Last 24 hours</th>'.
                            '</tr></thead>'.
                            '<tbody>';
        $this->_time      = 0;
        return true;
    } // end constructor

    /**
     * Set epoch of Current time
     * @param   int    $current
     * @access  public
     */
    function SetCurrentTime($current = 0)
    {
        $this->_time      = $current;
        return true;
    } // end func SetCurrentTime

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
        $sql = "SELECT sig_id,sig_name,sig_sid,coalesce(sig_class_name,'unclassified'),count(*) ".
               "From signature ".
               "LEFT JOIN sig_class ON signature.sig_class_id = sig_class.sig_class_id ".
               "INNER JOIN event on signature.sig_id = event.signature ".
               "where timestamp > '".date("Y/m/d H:i:s",$this->_time - 86400)."' and timestamp <= '".date("Y/m/d H:i:s",$this->_time)."' ".
               "group by signature.sig_id,signature.sig_name,signature.sig_sid,sig_class.sig_class_id,sig_class.sig_class_name";

        $result = $db->getAll($sql);

        if(!DB::isError($result)) {
 
            $result[-1] = array(0, 'All Alerts', '&nbsp;', '&nbsp;', '&nbsp;');
            $count = count($result) - 1;

            for ($i = -1 ; $i < $count ; $i++)
            {
                $this->_html .= '<tr>';

                if( $result[$i][2] > 0 and $result[$i][2] < 1000000 ) {
                    $this->_html      .= $this->_GenerateTD('<a href = "'.SNORTORG.$result[$i][2].'">Snort:'.$result[$i][2].'</a>');
                } else {
                    $this->_html      .= $this->_GenerateTD('&nbsp;');
                }

                if( $this->_signature == $result[$i][0]) {
                    $this->_html      .= $this->_GenerateTD($result[$i][1],'currentalert');
                    $this->_signame    = $result[$i][1];
                } else {
                    $linkTD            = $this->_GenerateLink($result[$i][1] ,$result[$i][0] ,$this->_sensor);
                    $this->_html      .= $this->_GenerateTD($linkTD);
                }

                $this->_html          .= $this->_GenerateTD($result[$i][3]);
                $this->_html          .= $this->_GenerateTD($result[$i][4]);

                $this->_html .= "</tr>\n";
            }

        } else {
           die ($result->getMessage());
        }

        $this->_html .= "</tbody></table>";
        return true;
    } // end func DbQuery

    /**
     * Generate Table TD
     * @param   string  $contents
     * @param   string  $class
     * @access  private
     * @return  string
     */
    function _GenerateTD($contents,$class = 'alertlist')
    {
      $td = '<td class =  "'.$class.'">'.$contents.'</td>';
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
        if(SENSORS) {

            $sql = "SELECT sid,hostname,interface FROM sensor order by sid;";
            $result = $db->getAll($sql);

            if(!DB::isError($result)) {

                $result[-1] = array(0, 'All Sensors', 'All');
                $count = count($result) - 1;

                for ($i = -1 ; $i < $count ; $i++) {

                    $name = $result[$i][1].'%'.$result[$i][2];

                    if($this->_sensor == $result[$i][0]) {
                        $this->_html .= '[ <span class="currentsensor">'.$name.'</span> ]&nbsp;&nbsp;';
                    } else {
                        $this->_html .= '[ '.$this->_GenerateLink($name, $this->_signature, $result[$i][0]).' ]&nbsp;&nbsp;';
                    }

                }
            } else {
                die ($result->getMessage());
            }

        } else {
            $this->_html = "All Sensors";
        }
        return true;
    } // end func DbQuery
} // end class SensorsList


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
     * Generate HTML link
     * @param   string  $link_name
     * @param   int     $signature
     * @param   int     $sensor
     * @access  private
     * @return  string
     */
    function _GenerateLink($link_name , $signature, $sid)
    {
        $url = $this->_url;
        $first = true;

        if( $signature > 0 ) {
            $url   .= '?signature='.$signature;
            $first  = false;
        }

        if( $sid > 0 ) {

            if($first) {
                $url   .= '?sid='.$sid;
                $first  = false;
            } else {
                $url .= '&sid='.$sid;
            }
        }
        $link = '<a href = "'.$url.'">'.$link_name.'</a>';

        return $link;
    } // end func _GenerateLink

    /**
     * Return HTML data
     * @access  public
     * @return  string
     */
    function GetHTML()
    {
        return $this->_html;
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
    var $_html = '';

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
