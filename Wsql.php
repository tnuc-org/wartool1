<?php
class Wsql
{
	protected $dbhost = "REMOVED";
	protected $dbuser = "REMOVED";
	protected $dbpass = "REMOVED";
	protected $dbname = "REMOVED";
	protected $dblink = null;
	protected $connected = false;
	
	protected $forumurl = "http://z6.invisionfree.com/REMOVED/";
	protected $forumacc = "REMOVED";
	protected $forumpw  = "REMOVED";
	
	public $magereq = 65;		//Minimum Level to be recognised for the magelist
	public $server = "Eternia";	//Server the wartool is monitoring (used to acquire onlinelist)
	
	protected $starttime = 3;	//Availability Storage and Display offset (+n hours after midnight)
	
	protected $wtoolmail = 'REMOVED@REMOVED';
	protected $adminmail = array('REMOVED@REMOVED');
	
	public $phonearray = array (    "1"   => "USA/Canada",
									"20"  => "Egypt",
									"31"  => "Netherlands",
									"32"  => "Belgium",
									"33"  => "France LOL",
									"34"  => "Spain",
									"43"  => "Austria",
									"44"  => "UK",
									"45"  => "Denmark",
									"46"  => "Sweden",
									"47"  => "Norway",
									"48"  => "Poland",
									"49"  => "Germany",
									"52"  => "Mexico",
									"54"  => "Argentina",
									"55"  => "Brazil",
									"56"  => "Chile",
									"58"  => "Venezuela",
									"61"  => "Australia",
									"351" => "Portugal",
									"353" => "Ireland",
									"358" => "Finland",
									"370" => "Lithuania",
									"371" => "Latvia",
									"372" => "Estonia",
									"385" => "Croatia",
									"386" => "Slovenia",
									"387" => "Bosnia",
									"421" => "Slovakia",
									"593" => "Ecuador",
									"?"   => "OTHER"
								);
	
	function __construct()
	{
		$this->dblink = mysql_connect($this->dbhost,$this->dbuser,$this->dbpass)
			or die('DATABASE FAILURE, COULD NOT CONNECT');
		mysql_select_db($this->dbname)
			or die('DATABASE FAILURE, COULD NOT SELECT DB');
		if(!mysql_error())
		{
			$this->connected = true;
		}
	}
	
	function __destruct()
	{
		mysql_close($this->dblink);
	}

	function is_connected()
	{
		return($this->connected);
	}

	function token_auth($c_name,$c_hash)
	{
		$query = sprintf("SELECT `perm_charlist`,`perm_vent`,`perm_stats`,`perm_usermgr`,`perm_ourchars`,`perm_traps`,`perm_refresh`,`perm_availability`,`isadmin`,`users`.`name`,`availability`,`country_code` AS `cc`,`availability_cur` as `cur`,`availability_na` as `na`,`acctype` FROM `tokens`,`users` WHERE `tokens`.`name`=`users`.`name` AND `tokens`.`name`='%s' AND `hash`='%s' AND `ip`='%s'",
			mysql_real_escape_string($c_name),
			mysql_real_escape_string($c_hash),
			mysql_real_escape_string($_SERVER['REMOTE_ADDR']));
		$result = mysql_query($query);
		if(!mysql_error())
			if($line = mysql_fetch_array($result, MYSQL_ASSOC))	return($line);
			else return(false);
		else return(false);
		mysql_free_result();
	}
}
?>