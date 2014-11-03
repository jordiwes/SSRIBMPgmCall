<?php
/**
 * Module.php, Program Call Class
 * 
 * This class creates an instance of the toolkit and has a function which allows 
 * you to call an RPG program from ZF2.
 *
 * @author Stephanie Rabbani <stephanie@excelsystems.com>
 * @version 1.0
 * @since Version 1.0
 */


namespace PgmCall;
//use Zend\Mvc\ModuleRouteListener;
//use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

// set special path if the Toolkit is not in your classpath
$path = '/usr/local/zendsvr/share/ToolkitApi'; // development custom path
set_include_path($path . PATH_SEPARATOR .get_include_path() ); // prepend the custom path before the default path

require_once('ToolkitService.php');

class Module
{
	public $tkobj;
	
	/*
	Usage:
	
	$this->tkObj = $sm->get('tkconn');
	
	$ds[] = array('ATTR'=>'1A', 'VAL'=>$ACCTSTATUS, 'RETURN'=>'ACCTSTATUS', 'TYPE'=>'both');
	$ds[] = array('ATTR'=>'2S0', 'VAL'=>$UNTILMONTH, 'RETURN'=>'UNTILMONTH', 'TYPE'=>'both');
	
	$parms[] = array('ATTR'=>'DS', 'DSOBJ'=>$ds, 'DSNAME'=>'myDataStruct');
	$parms[] = array('ATTR'=>'10A', 'VAL'=>'value', 'RETURN'=>'returnfield', 'TYPE'=>'in');
	$parms[] = array('ATTR'=>'7P2', 'VAL'=>'0.00',  'RETURN'=>'errcode', 'TYPE'=>'in');
	
	$returnValues = $this->tkObj->callProgram('MYPGM', '', $parms);
	
	$myReturnVal = $returnValues['returnfield'];
	*/
	
	/**
	 * Get the instance of the Toolkit and attach the database connection
	 * @param $tbdb The database connection
	*/
	function __construct($dbAdapter)
	{
		//get the Toolkit instance and pass it the db connection
		$namingMode = DB2_I5_NAMING_ON;
		$tkobj = \ToolkitService::getInstance($dbAdapter, $namingMode);
		
		$tkobj->setToolkitServiceParams(array('stateless'=>true));
		$this->tkobj = $tkobj;
	}
	
	
	/**
	 * Call an RPG or COBOL program and pass parms
	 * @param $program The program to call
	 * @param $lib     The program library
	 * @param $parms   An array of parameters (see format above)
	 * @return array   An array of return parameters
	*/
	function callProgram($program,$lib,$parms)
	{
		
		foreach ($parms as $parm)
		{
			
			if ($parm['ATTR'] == 'DS')
			{
				$param[] = $this->addDataStructure($parm);
			}
			else
			{
				if (!isset($parm['VARYING']))
				{
					$parm['VARYING'] = 'off';
				}
				$param[] = $this->addParm($parm['ATTR'], $parm['VAL'], $parm['RETURN'], $parm['TYPE'], $parm['VARYING']);
			}
		}
		
		$result = $this->tkobj->PgmCall($program, $lib,$param, null, null);
		
		if($result){
			//var_dump($result);
			return $result['io_param'];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Add a data structure to the toolkit call
	 * @param $parms  The array of parms for the data structure
	 * @return object Parms added to object
	*/
	function addDataStructure($parms)
	{
		$ds = array();
		foreach ($parms['DSOBJ'] as $subparm)
		{
			if (!isset($subparm['VARYING']))
			{
				$subparm['VARYING'] = 'off';
			}
			if ($subparm['ATTR'] == 'DS')
			{
				$ds[] = $this->addDataStructure($subparm);
			}
			else
			{
				$ds[] = $this->addParm($subparm['ATTR'], $subparm['VAL'], $subparm['RETURN'], $subparm['TYPE'], $subparm['VARYING']);
			}
		}
		return $this->tkobj->AddDataStruct($ds, $parms['DSNAME']);
	}
	
	/**
	 * Add a parameter to the toolkit call
	 * @param $parmDesc  The description of the parm (ie. 10A or 9P0)
	 * @param $parmValue The value of the parm
	 * @param $rtnField  The name of the field you wish to return the value into
	 * @param $type      Input or output type - IN/OUT/BOTH
	 * @param $varying   Whether the parm is varying - on/off/2/4
	*/
	protected function addParm($parmDesc, $parmValue, $rtnField, $type, $varying='off')
	{
		//alpha parm
		$idx = strpos($parmDesc, 'A');
		if ($idx)
		{
			$parmlength = substr($parmDesc, 0, $idx);
			return $this->tkobj->AddParameterChar($type, $parmlength, $rtnField, $rtnField, $parmValue, $varying); 
		}
		
		//packed dec parm
		$idx = strpos($parmDesc, 'P');
		if ($idx)
		{
			$length = substr($parmDesc, 0, $idx);
			$dec = substr($parmDesc, $idx);
			return $this->tkobj->AddParameterPackDec($type,$length,$dec,$rtnField, $rtnField, $parmValue);
		}
		
		//signed or zoned numeric parm
		$idx = strpos($parmDesc, 'S');
		if ($idx)
		{
			$length = substr($parmDesc, 0, $idx);
			$dec = substr($parmDesc, $idx);
			return $this->tkobj->AddParameterZoned($type,$length,$dec,$rtnField, $rtnField, $parmValue);
		}
		
		//binary parm
		$idx = strpos($parmDesc, 'B');
		if ($idx)
		{
			$parmlength = substr($parmDesc, 0, $idx);
			//echo $parmlength;
			return $this->tkobj->AddParameterBin($type, $parmlength, $rtnField, $rtnField, $parmValue); 
			
		}
		
		//dateparm
		if (preg_match('/D/', $parmDesc) > 0)
		{
			
		}
		
		//timestamp parm
		if (preg_match('/Z/', $parmDesc) > 0)
		{
			
		}
		
	}
	
	/**
	 * Use the Toolkit to run a CL command
	*/
	public function runcmd($cmd)
	{
		$this->tkobj->ClCommand($cmd);
	}
	
	
	/**
	 * Destruction code
	 *
	 * If the connection hasn't been disconnected, do it now when the object is destroyed.
		*/
	public function __destruct()
	{
		//if($this->tkobj)
		//{
		//	$this->tkobj->disconnect();
		//}
		
		//echo "destroyed";
	}
	
	
}


?>
