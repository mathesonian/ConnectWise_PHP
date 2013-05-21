<? if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Connectwise {
	// Using SSL?
	var $use_ssl = TRUE;
	// Is the SSL cert valid? 
	// (If there appear to be problems with SSL, you may want to set this to FALSE)
	var $valid_ssl_cert = FALSE;
	// Host to connect to
	var $cw_host = "connectwise.example.com";
	// This apparently can change with the connectwise version
	var $cw_release = "v4_6_release";

	// Company ID that you're connecting to
	var $co_id = "";
	// Integrator Username
	var $username = "";
	// Integrator Password
	var $password = "";

	/* You shouldn't need to edit anything beyond this point */
	var $CI;
	var $base_url_ext = "services/system_io/integration_io/processClientAction.rails";
	var $base_url = "";
	var $actionName = "";
	var $parms = array();

	public function __construct($init_vars=array()) {
		$this->CI =& get_instance();
		$this->base_url = ($this->use_ssl?"https://":"http://").$this->cw_host."/".$this->cw_release."/".$this->base_url_ext;
		if(isset($init_vars['cw_host'])) {
			$this->setCWHost($init_vars['cw_host']);
		}
	}

	public function useSSL($yn_ssl=TRUE) { $this->use_ssl = $yn_ssl; }
	public function validSSL($yn_ssl=TRUE) { $this->valid_ssl_cert = $yn_ssl; }

	public function setCWUrl($new_cw_url=NULL) {
		if(isset($new_cw_host)) {
			$this->cw_host = $new_cw_host;
		}
		$this->base_url = ($this->use_ssl?"https://":"http://").$this->cw_host."/".$this->cw_release."/".$this->base_url_ext;
	}
	public function getCWUrl() { return $this->base_url; }
	public function setCOID($new_co_id) { $this->co_id = $new_co_id; }
	public function getCOID() { return $this->co_id; }
	public function setUsername($username) { $this->username = $username; }
	public function getUsername() { return $this->username; }
	public function setPassword($password) { $this->password = $password; }
	public function getPassword() { return $this->password; }
	public function setAction($new_action="") { $this->actionName = $new_action; }
	public function getAction() { return $this->actionName; }
	public function setParameters($new_parms) { $this->parms = $new_parms; }
	public function getParameters() { return $this->parms; }
	public function setParameterValue($parm_key, $parm_val) { $this->parms[$parm_key] = $parm_val; }
	public function getParameterValue($parm_key) { return $this->parms[$parm_key]; }

	public function makeCall() {
		// Append Action stuff to URL
		$xml = $this->genActionString();
		$this->CI->curl->create($this->base_url);
		$this->CI->curl->ssl(TRUE);
		$this->CI->curl->OPTION(CURLOPT_SSL_VERIFYPEER,$this->valid_ssl_cert);
		$this->CI->curl->post(array('actionString'=>$xml));
		$rawXML = $this->CI->curl->execute();
		if($rawXML===FALSE) {return array('error' => array('code'=>$this->CI->curl->error_code,'string'=>$this->CI->curl->error_string));}
		// ConnectWise returns an XML file that claims to be UTF-16 but I get a PHP error
		// saying it's really UTF-8, so replacing the text does the trick
		$rawXML = str_replace("utf-16","utf-8",$rawXML);
		$ret = new SimpleXMLElement($rawXML);
		return json_decode(json_encode($ret), TRUE);
	}

	public function genActionString() {
		$xml =	'<?xml version="1.0" encoding="utf-16"?>'
					.'<'.$this->actionName.' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
					.'<CompanyName>'.$this->co_id.'</CompanyName>'
					.'<IntegrationLoginId>'.$this->username.'</IntegrationLoginId>'
					.'<IntegrationPassword>'.$this->password.'</IntegrationPassword>';
		$xml .= $this->genActionString_sub($this->parms);
		$xml .= '</'.$this->actionName.'>';
		return $xml;
	}

	public function genActionString_sub($parms) {
		$ret_string = "";
		if(is_array($parms)) {
			foreach($parms as $parm_name => $parm_val) {
				if($parm_name == "Conditions") { $parm_val = "<![CDATA[".$parm_val."]]>"; }
				$ret_string.="<".$parm_name.">".$this->genActionString_sub($parm_val)."</".$parm_name.">";
			}
		} else {
			$ret_string = $parms;
		}
		return $ret_string;
	}
}
/* End of Connectwise.php */
