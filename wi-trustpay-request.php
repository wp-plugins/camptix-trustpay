<?php

if(!class_exists('WI_TrustPay_Request'))
{
	class WI_TrustPay_Request extends WI_Trustpay
	{
		const GATEWAY_TEST = 'https://ib.test.trustpay.eu/mapi/pay.aspx';
		const GATEWAY_LIVE = 'https://ib.trustpay.eu/mapi/paymentservice.aspx';

		function __construct($data, $secure_key, $is_test)
		{
			$this->is_test = $is_test;
			$this->status = self::INIT;

			$this->required_fields = array('AID', 'AMT', 'CUR', 'REF'); // +SIG
			$this->optional_fields = array('URL', 'RURL', 'CURL', 'EURL', 'NURL', 'LNG', 'CNT', 'DSC', 'EMA');

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
			$this->sign($secure_key);

		}

		public function getRedirectUrl()
		{
			if($this->status!=self::SIGNED)
				return '';

			$params = '?';
			foreach($this->fields as $key=>$value)
			{
				if(!empty($value))
					$params .= $key.'='.urlencode($value).'&';
			}

			$params = substr($params, 0, strlen($params)-1);

			$base_url = self::GATEWAY_LIVE;
			if($this->is_test)
				$base_url = self::GATEWAY_TEST;

			return $base_url.$params;
		}

		protected function getSignatureBase()
		{
			 return $this->fields['AID'].$this->fields['AMT'].$this->fields['CUR'].$this->fields['REF'];
		}

		protected function sign($secret)
		{
			if(empty($secret))
				return;

	        if($this->status!=self::VALID)
				return;

			$sb = $this->getSignatureBase();

			$msg = pack('A*', $sb);
	        $key = pack('A*', $secret);

			$sign = hash_hmac('sha256', $msg, $key);
			$sign = strtoupper($sign);

			$this->fields[self::SIGN_FIELD] = $sign;
			$this->status = self::SIGNED;

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

			if(strlen($this->fields['AID'])>10)
				$this->status = self::NOT_VALID;

			if(!preg_match('/^[0-9]+(\\.[0-9]+)?$/', $this->fields['AMT']))
				$this->status = self::NOT_VALID;

			if(!in_array($this->fields['CUR'], self::get_valid_currencies()))
				$this->status = self::NOT_VALID;

			if(!isset($this->fields['REF']) /*|| !preg_match('/^[0-9]{1,10}?$/', $this->fields['REF'])*/)
				$this->status = self::NOT_VALID;

			if(isset($this->fields['DSC']))
	        {
				if(strlen($this->fields['DSC']) > 255)
					$this->fields['DSC'] = substr($this->fields['DSC'], 0, 255);
			}

			if(isset($this->fields['LNG']))
			{
	        	if(!in_array($this->fields['LNG'], self::get_valid_languages()))
					$this->status = self::NOT_VALID;
			}

			if($this->status==self::INIT)
				$this->status = self::VALID;
		}
	}
}
