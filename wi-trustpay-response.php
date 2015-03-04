<?php

if(!class_exists('WI_TrustPay_Response'))
{
	class WI_TrustPay_Response extends WI_Trustpay
	{	
		function __construct($data, $secure_key)
		{
			$this->status = self::INIT;
			
			$this->required_fields = array('REF', 'RES');
			$this->optional_fields = array('AID', 'TYP', 'AMT', 'CUR', 'TID', 'PID', 'OID', 'TSS', 'SIG');
			
			$this->fields = array();
			
			// load req fields
			foreach($this->required_fields as $field)
			{
				if(isset($data[$field]))
					$this->fields[$field] = $data[$field];				
			}
			
			// load opt fields
			foreach($this->optional_fields as $field)
			{
				if(isset($data[$field]))
					$this->fields[$field] = $data[$field];
			}
			
			$this->validateFields();
			$this->validateSign($secure_key);
		}

		protected function getSignatureBase() 
		{		
			return $this->fields['AID'].$this->fields['TYP'].$this->fields['AMT'].$this->fields['CUR'].$this->fields['REF'].$this->fields['RES'].$this->fields['TID'].$this->fields['OID'].$this->fields['TSS'];
		}

		protected function validateFields()
		{
			foreach($this->required_fields as $field)
			{
				if(!isset($this->fields[$field]))
				{
					$this->status = self::INCOMPLETE;
					return;
				}
			}
			
			$valid_res = array('0', '1', '2', '3', '1001', '1002', '1003', '1004', '1005', '1006', '1100'); 
			if(isset($this->fields['RES']) && !in_array($this->fields['RES'], $valid_res))
				$this->status = self::NOT_VALID;
					
			if(isset($this->fields['TSS']) && $this->fields['TSS']!='Y')
				$this->status = self::NOT_VALID;
					
			if($this->status==self::INIT)
				$this->status = self::VALID;
			
		}

		protected function validateSign($secret)
		{
			if(empty($secret))
				return;
				
	        if($this->status!=self::VALID)
	        	return;
			
			if(empty($this->fields['SIG']))
	        {
				$this->status = self::SIGN_NOT_SIGNED;
				$this->result = self::RES_NOTSIGNED;
	        }
			else
			{
				$sb = $this->getSignatureBase();
						
				$msg = pack('A*', $sb);
		        $key = pack('A*', $secret);
				
				$sign = hash_hmac('sha256', $msg, $key);		
				$sign = strtoupper($sign);
				
				if($this->fields['SIG']!=$sign)
				{
					$this->status = self::SIGN_NOT_VALID;
					$this->result = self::RES_FAILED;
				}
			}
			
			// 0 - success, 1 - pending, 2 - announced, 3 - authorized, 1001 - invalid request, 1002 - unknown account, 1003 - merchant disabled, 1004 - invalid sign, 1005 - user cancel, 1006 invalid authentication, 1100 - general error
				
			if($this->fields['RES']=='0')
			{
				if($this->status==self::SIGN_NOT_SIGNED)
					return;

				$this->result = self::RES_OK;
			}
			else if($this->fields['RES']=='1' || $this->fields['RES']=='2' || $this->fields['RES']=='3' || $this->fields['RES']=='4')
			{
				if($this->status==self::SIGN_NOT_SIGNED)
					return;

				$this->result = self::RES_TO;
			}
			else
				$this->result = self::RES_FAILED;
		}
	}
}