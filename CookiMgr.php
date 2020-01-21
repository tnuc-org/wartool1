<?php
class CookiMgr
{
	private $validfor=3600;
	private $rep1=array('0','1','2','3','4','5','6','7','8','9');
	private $rep2=array('U','T','S','X','Z','V','Q','Y','W','R');
	private $allset=false;
	
	function get_user()
	{
		return($_COOKIE['_acu']);
	}
	
	function get_hash()
	{
		$str = array($_COOKIE['_ap0'],$_COOKIE['_ap1'],$_COOKIE['_ap2'],$_COOKIE['_ap3']);
		for($i=0;$i<4;$i+=1)
		{
			$str[$i] = str_replace($this->rep2,$this->rep1,$str[$i]);
			$str[$i] = strtolower($str[$i]);
			$str[$i] = base_convert($str[$i],26,16);
			while(strlen($str[$i])<8) $str[$i] = "0" . $str[$i];
		}
		$ret=$str[0] . $str[1] . $str[2] . $str[3];
		return($ret);
	}
	
	function allset()
	{
		if(isset($_COOKIE['_acu'])&&isset($_COOKIE['_ap0'])&&isset($_COOKIE['_ap1'])&&isset($_COOKIE['_ap2'])&&isset($_COOKIE['_ap3'])) 
			$this->allset=true;
		else $this->allset=false;
		return($this->allset);
	}

	function create($cuser,$chash)
	{
		$str=str_split($chash,8);
		for($i=0;$i<4;$i+=1)
		{
			$str[$i] = base_convert($str[$i],16,26);
			$str[$i] = strtoupper($str[$i]);
			$str[$i] = str_replace($this->rep1,$this->rep2,$str[$i]);
		}
		setcookie('_acu',$cuser,time()+$this->validfor);
		setcookie('_ap0',$str[0],time()+$this->validfor);
		setcookie('_ap1',$str[1],time()+$this->validfor);
		setcookie('_ap2',$str[2],time()+$this->validfor);
		setcookie('_ap3',$str[3],time()+$this->validfor);
	}
	function delete()
	{
		setcookie('_acu',0,time()-9999);
		setcookie('_ap0',0,time()-9999);
		setcookie('_ap1',0,time()-9999);
		setcookie('_ap2',0,time()-9999);
		setcookie('_ap3',0,time()-9999);
	}
}
