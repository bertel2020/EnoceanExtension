<?php
	class EltakoFWS61 extends IPSModule
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
			if (!IPS_VariableProfileExists('Illumination.klx.ENOEXT')) {
				IPS_CreateVariableProfile('Illumination.klx.ENOEXT', 1);
				IPS_SetVariableProfileIcon('Illumination.klx.ENOEXT', '');
				IPS_SetVariableProfileText('Illumination.klx.ENOEXT', '', ' klx');
			}

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

			$this->RegisterVariableFloat('Temperature', $this->Translate('Temperature'), "~Temperature");
			$this->RegisterVariableFloat('Wind', $this->Translate('Wind'), "~WindSpeed.kmh");
			$this->RegisterVariableInteger('Dawn', $this->Translate('Dawn'), "~Illumination.FWS61");
			$this->RegisterVariableInteger('SunWest', $this->Translate('SunWest'), "Illumination.klx.ENOEXT");
			$this->RegisterVariableInteger('SunSouth', $this->Translate('SunSouth'), "Illumination.klx.ENOEXT");
			$this->RegisterVariableInteger('SunEast', $this->Translate('SunEast'), "Illumination.klx.ENOEXT");
			$this->RegisterVariableBoolean('Rain', $this->Translate('Rain'), "~Raining");

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
					if($data->DataByte0 == "24" OR $data->DataByte0 == "26") { 
						$this->SetValue('Dawn', (int)$data->DataByte3 * (1000/255));
						$temp = ((int)$data->DataByte2 * (120/255));
						if ($temp > 40) {
							$temp = round(($temp - 40)*2)/2;
							$this->SetValue('Temperature', $temp); 
						}
						else {
							$temp = round((-40 + $temp)*2)/2;
							$this->SetValue('Temperature', $temp); 
						}
						$this->SetValue('Wind', round((int)$data->DataByte3 * (70/255) * 3.6, 1));
					}
					switch($data->DataByte0) {
						case "24":
							$this->SetValue('Rain', false);
							break;
						case "26":	
							$this->SetValue('Rain', true);
							break;
						case "40":
							$this->SetValue('SunWest', (int)$data->DataByte3 * (150/255));
							$this->SetValue('SunSouth', (int)$data->DataByte2 * (150/255));
							$this->SetValue('SunEast', (int)$data->DataByte1 * (150/255));
							break;
						default:
					}
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

