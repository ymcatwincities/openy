<?php

// @codingStandardsIgnoreFile
class MindBodyAPI {
	public $credentials;
	public $client;

	function GetApiHostname() {
		return "api.mindbodyonline.com";
	}

	function __construct($service, $debug = false) {
		$endpointUrl = "https://" . $this->GetApiHostname() . "/0_5/" . $service . ".asmx";
		$wsdlUrl = $endpointUrl . "?wsdl";
	
		$this->debug = $debug;
		$option = array();
		if ($debug)
		{
			$option = array('trace'=>1);
		}
		$this->client = new soapclient($wsdlUrl, $option);
		$this->client->__setLocation($endpointUrl);
	}

	function setCredentials($sourcename, $password, $siteIDs) {
		$this->credentials = array(
			'SourceName' => $sourcename,
			'Password' => $password,
			'SiteIDs' => $siteIDs,
		);
	}

	function call($endpoint, $params) {
		return $this->client->{$endpoint}($this->GetMindbodyParams($params));
	}

	function getLastRequest() {
		return $this->client->__getLastRequest();
	}

	protected function GetMindbodyParams($additions) {
		$params['SourceCredentials'] = $this->credentials;
		$params['XMLDetail'] = 'Full';
		$params['PageSize'] = null;
		$params['CurrentPageIndex'] = null;
		if (empty($additions['Fields'])) {
			$params['Fields'] = null;
		}

		// Add the additions array and wrap it in Request
		return array('Request' => array_merge($params, $additions));
	}
}
