<?php
	class EltakoFCO2TF extends IPSModule
	{
		#=====================================================================================
		public function Create() 
		#=====================================================================================
		{
			//Never delete this line!
			parent::Create();
			$this->RegisterPropertyString("ReturnID", "00000000");

			#	ListenTimer
			$this->RegisterTimer('ListenTimer', 0, 'IPS_RequestAction($_IPS["TARGET"], "Listen", -1);');
			$this->SetBuffer('Listen', 0);

			//Connect to available enocean gateway
			$this->ConnectParent("{A52FEFE9-7858-4B8E-A96E-26E15CB944F7}");


			#	Fehlende Profile erzeugen

		}

		#=====================================================================================
		public function Destroy()
		#=====================================================================================
		{
		    //Never delete this line!
		    parent::Destroy();

		}
    
		#=====================================================================================
		public function ApplyChanges()
		#=====================================================================================
		{
			//Never delete this line!
			parent::ApplyChanges();

			$this->RegisterVariableInteger('Humidity', $this->Translate('Humidity'), "~Humidity");
			$this->RegisterVariableInteger('Concentration', $this->Translate('Concentration'), "~Occurence.CO2");
			$this->RegisterVariableFloat('Temperature', $this->Translate('Temperature'), "~Temperature.Room");

			#	Filter setzen
			$this->SetFilter();
		}
		
		#=====================================================================================
		public function ReceiveData($JSONString)
		#=====================================================================================
		{
			$this->SendDebug("Received", $JSONString, 0);
			$data = json_decode($JSONString);

			if($this->GetReturnID($data, 165))return;

	        switch($data->Device) {
	            case "165":
					$this->SetValue('Humidity', (int)$data->DataByte3 * 0.5);
					$this->SetValue('Concentration', (int)$data->DataByte2 * 10);
					$this->SetValue('Temperature', (int)$data->DataByte1 * 0.2);
	                break;
	            default:
					$this->LogMessage("Unknown Message", KL_ERROR);
	        }
		
		}
		
		#=====================================================================================
		public function RequestAction($Ident, $Value) 
		#=====================================================================================
		{
			switch($Ident) {
				case "Listen":
					$this->Listen($Value);
					break;
				case "SetReturnID":
					$this->UpdateFormField('ReturnID', 'value', $Value);
					break;
				default:
					throw new Exception("Invalid Ident");
			}
		}

		#=====================================================================================
		protected function SendDebug($Message, $Data, $Format)
		#=====================================================================================
		{
			if (is_array($Data))
			{
			    foreach ($Data as $Key => $DebugData)
			    {
						$this->SendDebug($Message . ":" . $Key, $DebugData, 0);
			    }
			}
			else if (is_object($Data))
			{
			    foreach ($Data as $Key => $DebugData)
			    {
						$this->SendDebug($Message . "." . $Key, $DebugData, 0);
			    }
			}
			else
			{
			    parent::SendDebug($Message, $Data, $Format);
			}
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
			$ID = hexdec($this->ReadPropertyString("ReturnID"));
			if(IPS_GetKernelVersion() < 6.3){
				if($ID & 0x80000000)$ID -=  0x100000000;
			}
			$filter = sprintf('.*\"DeviceID\":%s,.*', $ID);
			$this->SendDebug('Filter', $filter, 0);
			$this->SetReceiveDataFilter($filter);
		}
	}

