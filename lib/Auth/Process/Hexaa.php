<?php

/**
 * Hexaa AA authproc filter.
 *
 * This class is the authproc filter of the HEXAA backend
 *
 * Example configuration in the config/config.php
 *
 *    authproc.aa = array(
 *       ...
 *       '60' => array(
 *            'class' => 'hexaa:Hexaa',
 *            'nameId_attribute_name' =>  'subject_nameid', // look at the aa authsource config
 *            'hexaa_api_url' =>          'https://www.hexaa.example.com/app.php/api',
 *            'hexaa_master_secret' =>    'you_can_get_it_from_the_hexaa_administrator'
 *       ),
 *
 * @author Gyula Szabó <gyufi@niif.hu>
 * @author Kristóf Bajnok<bajnokk@niif.hu>
 * @package
 */
class sspmod_hexaa_Auth_Process_Hexaa extends SimpleSAML_Auth_ProcessingFilter
{
    private $as_config;
    
    public function __construct($config, $reserved) {
        parent::__construct($config, $reserved);
        $params = array('hexaa_master_secret', 'hexaa_api_url', 'nameId_attribute_name');
        foreach ($params as $param) {
            if (!array_key_exists($param, $config)) {
                throw new SimpleSAML_Error_Exception('Missing required attribute: ' . $param);
            }
            $this->as_config[$param] = $config[$param];
        }
    }
    
    public function process(&$state) {
        assert('is_array($state)');
        $nameId = $state['Attributes'][$this->as_config['nameId_attribute_name']][0];
        $spid = $state['Destination']['entityid'];
        $state['Attributes'] = $this->getAttributes($nameId, $spid);

        // restore the nameId_attribute_name attribute in the state array because the $this->getAttributes(...) call destroys it
        if (empty($state['Attributes'][$this->as_config['nameId_attribute_name']])) {
            $state['Attributes'][$this->as_config['nameId_attribute_name']] = array($nameId);
        }
    }
    
    public function getAttributes($nameId, $spid, $attributes = array()) {
        // Generate API key
        $time = new \DateTime();
        date_timezone_set($time, new \DateTimeZone('UTC'));
        $stamp = $time->format('Y-m-d H:i');
        $apiKey = hash('sha256', $this->as_config['hexaa_master_secret'] . $stamp);
        
        // Make the call
        // The data to send to the API
        $postData = array("apikey" => $apiKey, "fedid" => $nameId, "entityid" => $spid);
        
        // Setup cURL
        $ch = curl_init($this->as_config['hexaa_api_url'] . '/attributes.json');
        curl_setopt_array($ch,
        	array(
        		CURLOPT_CUSTOMREQUEST => "POST",
        		CURLOPT_RETURNTRANSFER => TRUE,
        		CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        		CURLOPT_POSTFIELDS => json_encode($postData),
        		CURLOPT_FOLLOWLOCATION => TRUE,
        		CURLOPT_POSTREDIR => 3
        		)
        	);
        
        // Send the request
        $response = curl_exec($ch);
        $http_response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for error; not even redirects are allowed here
        if ($response === FALSE || !($http_response >= 200 && $http_response < 300)) {
            SimpleSAML_Logger::error('[aa] HEXAA API query failed: HTTP response code: ' . $http_response . ', curl error: "' . curl_error($ch)) . '"';
            SimpleSAML_Logger::debug('[aa] HEXAA API query failed: curl info: ' . var_export(curl_getinfo($ch),1));
            SimpleSAML_Logger::debug('[aa] HEXAA API query failed: HTTP response: ' . var_export($response,1));
            $data = array();
        } else {
            $data = json_decode($response, true);
            SimpleSAML_Logger::info('[aa] got reply from HEXAA API');
            SimpleSAML_Logger::debug('[aa] HEXAA API query postData: ' . var_export($postData, TRUE));
            SimpleSAML_Logger::debug('[aa] HEXAA API query result: ' . var_export($data, TRUE));
        }
        return $data;
    }
}

