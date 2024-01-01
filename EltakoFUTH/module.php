<?php
	class EltakoFUTH extends IPSModule
	{
		#================================================================================================
		public function Create()
		#================================================================================================
		{
			//Never delete this line!
			parent::Create();
			$this->RegisterPropertyInteger("DeviceID", 0);
			$this->RegisterPropertyString("ReturnID", "");
			$this->RegisterPropertyString("ReturnID2", "");

			$this->RegisterPropertyString("BaseData", '{
				"DataID":"{70E3075F-A35D-4DEB-AC20-C929A156FE48}",
				"Device":165,
				"Status":0,
				"DeviceID":0,
				"DestinationID":-1,
				"DataLength":4,
				"DataByte12":0,
				"DataByte11":0,
				"DataByte10":0,
				"DataByte9":0,
				"DataByte8":0,
				"DataByte7":0,
				"DataByte6":0,
				"DataByte5":0,
				"DataByte4":0,
				"DataByte3":0,
				"DataByte2":0,
				"DataByte1":0,
				"DataByte0":0
			}');

			#	ListenTimer
			$this->RegisterTimer('ListenTimer', 0, 'IPS_RequestAction($_IPS["TARGET"], "Listen", -1);');
			$this->SetBuffer('Listen', 0);

			#	UpdateTimer
			$this->RegisterTimer('UpdateTimer', 0, 'IPS_RequestAction($_IPS["TARGET"], "Update", "");');

			//Connect to available enocean gateway
			$this->ConnectParent("{A52FEFE9-7858-4B8E-A96E-26E15CB944F7}");

			#	Fehlende Profile erzeugen
			if (!IPS_VariableProfileExists('FUTH.SetTemp.ENOEXT')) {
				IPS_CreateVariableProfile('FUTH.SetTemp.ENOEXT', 1);
				IPS_SetVariableProfileValues("FUTH.SetTemp.ENOEXT", 19, 24, 1);
				IPS_SetVariableProfileIcon('FUTH.SetTemp.ENOEXT', '');
				IPS_SetVariableProfileText('FUTH.SetTemp.ENOEXT', '', ' °C');
			}

			if (!IPS_VariableProfileExists('FUTH.Temp.ENOEXT')) {
				IPS_CreateVariableProfile('FUTH.Temp.ENOEXT', 2);
				IPS_SetVariableProfileIcon('FUTH.Temp.ENOEXT', '');
				IPS_SetVariableProfileDigits('FUTH.Temp.ENOEXT', 1);
				IPS_SetVariableProfileText('FUTH.Temp.ENOEXT', '', ' °C');
			}

			if (!IPS_VariableProfileExists('FUTH.Humidity.ENOEXT')) {
				IPS_CreateVariableProfile('FUTH.Humidity.ENOEXT', 2);
				IPS_SetVariableProfileIcon('FUTH.Humidity.ENOEXT', '');
				IPS_SetVariableProfileDigits('FUTH.Humidity.ENOEXT', 0);
				IPS_SetVariableProfileText('FUTH.Humidity.ENOEXT', '', ' %');
			}
		}

		#================================================================================================
		public function Destroy()
		#================================================================================================
		{
		    //Never delete this line!
		    parent::Destroy();

		}

		#================================================================================================
		public function ApplyChanges()
		#================================================================================================
		{
			//Never delete this line!
			parent::ApplyChanges();

			$this->RegisterVariableInteger("SetTemp", $this->Translate("Set Temp"), "FUTH.SetTemp.ENOEXT");
			$this->RegisterVariableFloat("Temperature", $this->Translate("Temperature"), "FUTH.Temp.ENOEXT");
			$this->RegisterVariableFloat("Humidity", $this->Translate("Humidity"), "FUTH.Humidity.ENOEXT");

			$this->EnableAction("SetTemp");

			#	Solltemp merken
			#$this->SetBuffer('settemp', $this->GetValue('SetTemp'));

			#	Filter setzen
			$this->SetFilter();
		}

		#================================================================================================
		public function ReceiveData($JSONString) //Verarbeitet die Rückmeldung des Aktors
		#================================================================================================
		{
			$this->SendDebug("Receive", $JSONString, 0);
    	   	$data = json_decode($JSONString);
			$this->SetTimerInterval('UpdateTimer', 0); 
			$ID1 = $this->GetID();
			$ID2 = $this->GetID2();

			if($this->GetReturnID($data, 165))return;

    	    switch($data->DeviceID) {
    	        case $ID1:
					$this->SetValue('Temperature', round((255-(int)$data->DataByte1) * (40/255),1));
					$this->SetValue('SetTemp', round((int)$data->DataByte2 *(40/255),0));
					break;
    	        case $ID2:
					$this->SetValue('Humidity', round((int)$data->DataByte2 *(100/250),0));
					break;
    	    	default:
    	            throw new Exception("Invalid Ident");
    	    }      

    	}

    	#================================================================================================
    	public function RequestAction($Ident, $Value)
    	#================================================================================================
    	{
    	    switch($Ident) {
    	        case "FreeDeviceID":
    	            $this->UpdateFormField('DeviceID', 'value', $this->FreeDeviceID());
    	            break;
    	        case "Listen":
    	            $this->Listen($Value);
    	            break;
    	        case "SetReturnID":
    	            $this->UpdateFormField('ReturnID', 'value', $Value);
    	            break;
    	        case "SetReturnID2":
    	            $this->UpdateFormField('ReturnID2', 'value', $Value);
    	            break;
    	        case "SetTemp": 
					$this->SetTempFUTH($Value);
                    break;
    	        default:
    	            throw new Exception("Invalid Ident");
    	    }
    	}

		#================================================================================================
        public function SetTempFUTH(int $temp)
		#================================================================================================
		{
			#$temp = dec2hex02($temp);
			$data = json_decode($this->ReadPropertyString("BaseData"));
			$data->DeviceID = $this->ReadPropertyInteger("DeviceID");
			$data->DataByte3 = 0;
			$data->DataByte2 = $temp;
			$data->DataByte1 = 0;
			$data->DataByte0 = 8;
			$this->SendData(json_encode($data));
			return;
        }


		#================================================================================================
		public function TeachIn() //Sendet ein TeachIn als "GFVS" an den FUTH
		#================================================================================================
		{
			$data = json_decode($this->ReadPropertyString("BaseData"));
			$data->DeviceID = $this->ReadPropertyInteger("DeviceID");
			$data->DataByte3 = 64;
			$data->DataByte2 = 48;
			$data->DataByte1 = 13;
			$data->DataByte0 = 133;
			$data->DestinationID = $this->GetID();
			$this->SendData(json_encode($data));
		}
	
		#================================================================================================
		protected function SendData($data)
		#================================================================================================
		{
			$this->SendDataToParent($data);
			$this->SendDebug("Send", $data, 0);
		}

		#================================================================================================
		protected function SendDebug($Message, $Data, $Format)
		#================================================================================================
		{
			if (is_array($Data)){
				foreach ($Data as $Key => $DebugData){
					$this->SendDebug($Message . ":" . $Key, $DebugData, 0);
				}
			}else if (is_object($Data)){
				foreach ($Data as $Key => $DebugData){
					$this->SendDebug($Message . "." . $Key, $DebugData, 0);
				}
			}else{
				parent::SendDebug($Message, $Data, $Format);
			}
		}
	
		#================================================================================================
		protected function FreeDeviceID()
		#================================================================================================
		{
			$Gateway = @IPS_GetInstance($this->InstanceID)["ConnectionID"];
			if($Gateway == 0) return;
			$Devices = IPS_GetInstanceListByModuleType(3);             # alle Geräte
			$DeviceArray = array();
			foreach ($Devices as $Device){
				if(IPS_GetInstance($Device)["ConnectionID"] == $Gateway){
					$config = json_decode(IPS_GetConfiguration($Device));
					if(!property_exists($config, 'DeviceID'))continue;
					if(is_integer($config->DeviceID)) $DeviceArray[] = $config->DeviceID;
				}
			}
		
			for($ID = 1; $ID<=256; $ID++)if(!in_array($ID,$DeviceArray))break;
			return $ID == 256?0:$ID;
		}
		
		#=====================================================================================
		private function Listen($value) 
		#=====================================================================================
		{
			$this->SetReceiveDataFilter('');
			if($value > 0){
				$this->SetBuffer('DeviceIDs','[]');
				$this->UpdateFormField('FoundIDs', 'values', json_encode(array()));
			}
			$this->SetTimerInterval('ListenTimer', 1000);
			$remain = intval($this->GetBuffer('Listen')) + $value;
			if($remain == 0)$this->SetFilter();
			if($remain > 60) $remain = 60;
			$this->UpdateFormField('Remaining', 'current', $remain);
			$this->UpdateFormField('Remaining', 'caption', "$remain / 60s");
			$this->SetBuffer('Listen', $remain);
		}
				
		#=====================================================================================
		private function GetReturnID($data, $DataValues) 
		#=====================================================================================
		{
			if($this->GetTimerInterval('ListenTimer') == 0) return false;

			$values = json_decode($this->GetBuffer('DeviceIDs'));
			$Devices = $this->GetDeviceArray();
			if(in_array($data->Device, $DataValues)){
				$ID = $data->DeviceID;
				if($ID <= 0)return true;
				$DeviceID = sprintf('%08X',$ID);
				if(strpos($this->GetBuffer('DeviceIDs'), $DeviceID) === false){
					$values[] = array(
						"ReturnID" => $DeviceID, 
						"ReturnID2" => $DeviceID, 
						"InstanceID" => isset($Devices[$DeviceID])?$Devices[$DeviceID]:0 ,
						"rowColor"=>isset($Devices[$DeviceID])?"#C0FFC0":-1
					);
					$this->UpdateFormField('FoundIDs', 'values', json_encode($values));
					$this->SetBuffer('DeviceIDs', json_encode($values));
				}
			}
			return true;
		}

		#=====================================================================================
		private function GetDeviceArray()
		#=====================================================================================
		{
			$Gateway = @IPS_GetInstance($this->InstanceID)["ConnectionID"];
			if($Gateway == 0) return;
			$Devices = IPS_GetInstanceListByModuleType(3);             # alle Geräte
			$DeviceArray = array();
			foreach ($Devices as $Device){
				if(IPS_GetInstance($Device)["ConnectionID"] == $Gateway){
					$config = json_decode(IPS_GetConfiguration($Device));
					if(!property_exists($config, 'ReturnID'))continue;
					$DeviceArray[strtoupper(trim($config->ReturnID))] = $Device;
					if(!property_exists($config, 'ReturnID2'))continue;
					$DeviceArray[strtoupper(trim($config->ReturnID2))] = $Device2;
				}
			}
			return $DeviceArray;
		}

		#=====================================================================================
		private function SetFilter() 
		#=====================================================================================
		{
			#	ListenTimer ausschalten
			$this->SetTimerInterval('ListenTimer', 0);
	
			#	Filter setzen
			$ID = $this->GetID();
			$ID2 = $this->GetID2();
			$filter1 = sprintf('.*\"DeviceID\":%s,.*', $ID);
			$filter2 = sprintf('.*\"DeviceID\":%s,.*', $ID2);
			$filter = "(?:".$filter1."|".$filter2.")";
			$this->SendDebug('Filter', $filter, 0);
			$this->SetReceiveDataFilter($filter);
		}
	
		#=====================================================================================
		private function GetID() 
		#=====================================================================================
		{
			$ID = hexdec($this->ReadPropertyString("ReturnID"));
			if(IPS_GetKernelVersion() < 6.3){
				if($ID & 0x80000000)$ID -=  0x100000000;
			}
			return($ID);
		}

		#=====================================================================================
		private function GetID2() 
		#=====================================================================================
		{
			$ID2 = hexdec($this->ReadPropertyString("ReturnID2"));
			if(IPS_GetKernelVersion() < 6.3){
				if($ID2 & 0x80000000)$ID2 -=  0x100000000;
			}
			return($ID2);
		}
	
		#=====================================================================================
		private function dec2hex02($dec) 
		#=====================================================================================
		{
			return sprintf("%02X", $dec);
		}		

	}