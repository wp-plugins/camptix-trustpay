<?php

if(!class_exists('WI_Trustpay'))
{ 		
	class WI_Trustpay
	{
		const INIT = 'INIT';
		const VALID = 'VALID';
		const NOT_VALID = 'NOT_VALID';
		const INCOMPLETE = 'INCOMPLETE';
		const SIGNED = 'SIGNED';
		
		const SIGN_NOT_SIGNED = 'SIGN_NOT_SIGNED';
		const SIGN_NOT_VALID = 'SIGN_NOT_VALID';
		
		const RES_NOTSIGNED = 'RES_NOTSIGNED';
		const RES_FAILED = 'RES_FAILED';
		const RES_TO = 'RES_TO';
		const RES_OK = 'RES_OK';

		const SIGN_FIELD = 'SIG';
		
		protected $required_fields;
	    protected $optional_fields;
	    
	    protected $store_key;
	    protected $is_test;
	    protected $fields;
	    protected $status;
	    protected $result;
	    
		function getResult()
		{
		    return $this->result;
		}

		function getStatus()
		{
		    return $this->status;
		}
		
		public function __get($name)
	    {
	        if(isset($this->fields[$name]))
	            return $this->fields[$name];

	        return null;
	    }

	    public function __isset($name)
	    {
	        return isset($this->fields[$name]);
	    }

	    function toArray()
	    {
	        return $this->fields;
	    }

	    static function get_valid_currencies()
	    {
	    	return array(
				'BGN' => 'BGN',
				'CZK' => 'CZK',
				'EUR' => 'EUR',
				'GBP' => 'GBP',
				'HRK' => 'HRK',
				'HUF' => 'HUF',
				'NOK' => 'NOK',
				'RON' => 'RON',
				'TRY' => 'TRY',
				'USD' => 'USD'
	    	);
	    }

	    static function get_valid_languages()
	    {
			return array(
				'bg' => 'bg',
				'bs' => 'bs',
				'cs' => 'cs',
				'de' => 'de',
				'en' => 'en',
				'es' => 'es',
				'et' => 'et',
				'hr' => 'hr',
				'hu' => 'hu',
				'it' => 'it',
				'lt' => 'lt',
				'lv' => 'lv',
				'pl' => 'pl',
				'ro' => 'ro',
				'ru' => 'ru',
				'sk' => 'sk',
				'sl' => 'sl',
				'sr' => 'sr',
				'uk' => 'uk'		
			);
		}
	}
}