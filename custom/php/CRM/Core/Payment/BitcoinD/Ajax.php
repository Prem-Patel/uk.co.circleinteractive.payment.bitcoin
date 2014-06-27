<?php

/**
 * Ajax service class
 * @author  andyw@circle
 * @package com.uk.andyw.payment.bitcoin
 */
class CRM_Core_Payment_BitcoinD_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		return $this->request($_POST);
	}

	/**
	 * Request handler
	 * @param array $param input parameters for request
	 */
	private function request(&$param) {

		CRM_Utils_System::civiExit();
		
	}

}