SSRIBMPgmCall
=============

Program Call IBM i module for ZF2

Place the "PgmCall" directory in the vendor directory of your ZF2 application.


I have set up my application.config like so:

```php
'service_manager' => array(
		
	 	'factories'    => array(
			//database adapter to use later
			'dbadapter' =>  function($sm) {
                    $options = array("i5_naming" => DB2_I5_NAMING_ON, "i5_libl" => 'LIB1 LIB2 LIB3');
					$adapter = new \Zend\Db\Adapter\Adapter(array(    
					'driver' => 'IbmDb2',
					'platform' => 'IbmDb2',  
					'platform_options' => array('quote_identifiers' => false),  
					'database' => '*LOCAL',    
					'username' => 'MYNAME',    
					'password' => 'MYPASS',
					'driver_options' => $options
					 ));           
                    return $adapter;
            },
			//Toolkit (program call) object to use later
			'tkconn'    => function ($sm) {
				$dbAdapter = $sm->get('dbadapter');
			
				$dbAdapter->getDriver()->getConnection()->connect();
				
				$conn = $dbAdapter->getDriver()->getConnection()->getResource();

				$tk = new PgmCall\Module($conn);
				return $tk;
			}
	    ),
	),
```	
	
	
	
And I call the program call module like so:


```php
class MyTable
{

	protected $dbAdapter;
	protected $tkObj;

	/**
	 * construct the Table object and pass the database and toolkit connections
	*/
	public function __construct($conn, $tkObj)
	{
		
		$this->dbAdapter = $conn;
		$this->tkObj = $tkObj;
		
	}

  public function deleteRecord($id)
  {
    //etc etc etc
    
    //call a program to delete the record
    $parms[] = array('ATTR'=>'9P0', 'VAL'=>$id, 'RETURN'=>'id', 'TYPE'=>'both');
		$parms[] = array('ATTR'=>'1A', 'VAL'=>'', 'RETURN'=>'delOK', 'TYPE'=>'both');
		$parms[] = array('ATTR'=>'2A', 'VAL'=>'', 'RETURN'=>'errmsg', 'TYPE'=>'both');
	
		
		
		$returnValues = $this->tkObj->callProgram('MYPGM', '', $parms);
	}
	
}
```  

