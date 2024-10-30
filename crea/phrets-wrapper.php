<?php

/**
   *  Used to wrap the inner class of phRETS to support echo, logging as well as configuration variables
   */
   
include('mylib.php');
require_once dirname(__FILE__).'/phrets-1.0rc2.php';

class phRETSWrapper {
        
	private $loginURL = 'http://sample.data.crea.ca/Login.svc/Login';
	public $userId = 'CXLHfDVrziCfvwgCuL8nUahC';
	public $pass = 'mFqMsCSPdnb5WO1gpEEtDCHH';
	private $logFileLocation = 'log.txt';
	
	private $rets;
	private $log;
		
	private function Init()
	{
		$this->rets->SetParam('catch_last_response', true);
		$this->rets->SetParam('compression_enabled', true);
		$this->rets->AddHeader('RETS-Version', 'RETS/1.7.2');
		$this->rets->AddHeader('Accept', '/');		
		$this->log = new Logging();
		$this->log->lfile($this->logFileLocation);
                
               
	}
	
	public function phRETSWrapper() 
	{ 
		$this->rets = new phRETS();
		$this->Init();
	}
	
	public function Connect() 
	{ 
		$this->DisplayLog('Connecting to sample.data.crea.ca');
		$connect = $this->rets->Connect($this->loginURL, $this->userId, $this->pass);

		if ($connect === true) 
		{
			$this->DisplayLog('Connection Successful');
		}
		else 
		{
			$this->DisplayLog('Connection FAILED');
			if ($error = $this->rets->Error()) 
			{
				$this->DisplayLog('ERROR type ['.$error['type'].'] code ['.$error['code'].'] text ['.$error['text'].']');
			}
			return false;
		}
		return true;
	}
	
	public function LogServerInfo()
	{	
		$this->DisplayHeader('Server Info');
		$this->DisplayLog('Server Details: ' . implode($this->rets->GetServerInformation()));
		$this->DisplayLog('RETS version: ' . $this->rets->GetServerVersion());
		
		//$this->OutputAssociativeArray($this->rets->GetServerInformation());
		//$this->DisplayLog($this->rets->GetServerVersion());
	}
	
	public function LogTypeInfo()
	{	
		$this->DisplayHeader('RETS Type Info');
		$this->DisplayLog(var_export($this->rets->GetMetadataTypes(), true));
		$this->DisplayLog(var_export($this->rets->GetMetadataResources(), true));
		
		//$this->DisplayLog(var_dump($this->rets->GetMetadataClasses("Property")));
		//$this->DisplayLog(var_dump($this->rets->GetMetadataClasses("Office")));
		//$this->DisplayLog(var_dump($this->rets->GetMetadataClasses("Agent")));
		
		//$this->DisplayLog(var_dump($this->rets->GetMetadataTable("Property", "Property")));
		//$this->DisplayLog(var_dump($this->rets->GetMetadataTable("Office", "Office")));
		//$this->DisplayLog(var_dump($this->rets->GetMetadataTable("Agent", "Agent")));
		
		//$this->DisplayLog(var_dump($this->rets->GetAllLookupValues("Property")));
		//$this->DisplayLog(var_dump($this->rets->GetAllLookupValues("Office")));
		//$this->DisplayLog(var_dump($this->rets->GetAllLookupValues("Agent")));
		
		/*$this->DisplayLog(var_dump($this->rets->GetMetadataObjects("Property")));
		$this->DisplayLog(var_dump($this->rets->GetMetadataObjects("Office")));
		$this->DisplayLog(var_dump($this->rets->GetMetadataObjects("Agent")));*/
	
	}
	
	public function SearchResidentialPropertiesUpdatedSince($days)
	{
		date_default_timezone_set('UTC');
		$date = new DateTime();
		$date->sub(new DateInterval('P' . $days . 'D'));				
		
		return $this->SearchResidentialProperty("(LastUpdated=" . $date->format('Y-m-d') . ")", false);		
	}
	
	public function SearchResidentialProperty($crit, $urlEncode = true)
	{	
		/*$search = $this->rets->SearchQuery("Property","Property","(ID=832356)");
		while ($listing = $this->rets->FetchRow($search)) {
				echo "Address: {$listing['StreetNumber']} {$listing['StreetName']}, ";
				echo "{$listing['City']}, ";
				echo "{$listing['State']} {$listing['ZipCode']} listed for ";
				echo "\$".number_format($listing['ListPrice'])."\n";
		}
		$this->rets->FreeResult($search);*/
			
		$this->DisplayHeader('Search Residential Property');
		
		if($urlEncode)
		{
			$results = $this->rets->SearchQuery("Property","Property",urlencode($crit));
		}
		else
		{
			$results = $this->rets->SearchQuery("Property","Property",$crit);
		}
		
		$this->DisplayLog(var_export($results, true));
		$this->LogLastRequest();
	}
	
	public function GetPropertyObject($id, $type)
	{
		$this->DisplayHeader('GetPropertyObject');
		$record = $this->rets->GetObject("Property", $type, $id);
		
		//We won't log this due to data size potential (could be a large image)
		//$this->DisplayLog(var_dump($record));		
		
		$this->LogLastRequest(false);
	}		
	
	public function LogLastRequest($logResponse = true)
	{	
		if ($last_request = $this->rets->LastRequest()) 
		{
			$this->DisplayLog('Reply Code '.$last_request['ReplyCode'].' ['.$last_request['ReplyText'].']');
		}
		$this->DisplayLog('LastRequestURL: '.$this->rets->LastRequestURL().PHP_EOL);
		
		if($logResponse)
		{
			$this->DisplayLog($this->rets->GetLastServerResponse());
		}
	}	
	
	public function Disconnect() 
	{ 
		$this->DisplayLog('Disconnect');
		$this->rets->Disconnect();
		$this->log->lclose();
	}		
	
	private function DisplayLog($text) 
	{
		echo $text . "\n";
		$this->log->lwrite($text.PHP_EOL);
	}
	
	function DisplayHeader($text) 
	{
		echo "\n\n";
		echo PHP_EOL.str_pad('## '.trim($text).' ', 80, '#').PHP_EOL;
		
		$this->log->lwrite("");
		$this->log->lwrite("");
		$this->log->lwrite(str_pad('## '.trim($text).' ', 80, '#').PHP_EOL);
	}	
}


?>