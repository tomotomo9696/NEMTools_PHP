<?php
#
#	NIS API documentation:
#	http://bob.nem.ninja/docs/
#	NCC API documentation:
#	https://github.com/NewEconomyMovement/NemCommunityClient/blob/master/docs/api.md

class NEM {

	// default configuration options
	private $_cfg = array(
		'nis' => array(
			'address' => '127.0.0.1', #NIS remote address
			'port'    => 7890,        #NIS remote port
			'context' => '/'          #NIS web context
		),
		'ncc' => array(
			'address' => '127.0.0.1', #NCC remote address
			'port'    => 8989,        #NCC remote port
			'context' => '/ncc/api/'  #NCC web context
		)
	);


	/**
	NEM object contructor
	@param : $cfg (optional) associative array containing NIS and/or NCC options
	*/
	function __construct($cfg = NULL) {

		// set the available configuration options
		$this->set_options($cfg);

	}


	/**
	sets the NEM nis or ncc connection options
	@param : $cfg associative array
	*/
	public function set_options($cfg) {

		//exit if no configuration found
		if (is_null($cfg)) return;

		foreach($cfg as $key => $val) {

			list($service, $option) = explode('_',$key);
			if (! isset($this->_cfg[$service])) continue;
			if (! isset($this->_cfg[$service][$option])) continue;

			$this->_cfg[$service][$option] = $val;
		}


	}


	/**
	returns
		the list of defined options (associative array)
		or single option value (string or integer) if $opt key is properly defined

	@param: $option (string) - option key
	*/
	public function get_options($option = NULL) {

		$cnf = array();

		foreach($this->_cfg as $service => $options) {

			foreach($options as $opt => $val) {

				$key = $service.'_'.$opt;
				if (! is_null($option) && $key == $option) return $val;
				$cnf[$key] = $val;
			}
		}

		return $cnf;
	}


	/**
	Sends the json request to NIS or NCC and returns the response output
	@param: $method (string) values "POST" or "GET"
	@param: $url (string) valid url link
	@param: $data (optional) - (string) in json format or php associative array
			containing key -> value pairs
	@param: $handler (optional) php user defined custom function to process the
			returned json response
	*/
	protected function _send($method,$url,$data = NULL,$handler = NULL) {

		$headers = array();
		$headers[] = 'Content-type: application/json';
		$params = '';

		if (! is_null($data)) {
			if ($method == 'POST') {

				if (! is_array($data)) {
					$data = json_decode($data,TRUE);
				}

				if (is_array($data)) {
					$data = json_encode($data);
				}
				else {
					//throw InvalidArgument
					throw new InvalidArgumentException(
						'The $data can not be converted into a valid JSON format!');
					return NULL;
				}

				$headers[] = 'Content-Length: '.strlen($data);
			}
			else {
				$params = http_build_query($data);
				$params = '?'.$params;
			}
		}

		//curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url.$params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,3);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, $method == 'POST');
			if (! is_null($data)) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		$content = curl_exec($ch);
		curl_close($ch);

		if(! is_null($handler))
			$content = $handler($content);

		return $content;
	}


	/**
	constructs and returns valid url (string)
	$type (string) -> values ('nis' or 'ncc')
	*/
	protected function _get_valid_call($type,$uri) {

		$service = (! isset($this->_cfg[$type])) ? NULL : $this->_cfg[$type];
		if (is_null($type)) return NULL;

		$uri = trim($uri);
		if (strlen($uri) == 0) return NULL;

		$addr = $service['address'];
		$port = $service['port'];
		$cntx = $service['context'];

		$url = "http://$addr:$port".$cntx;
		$url .= $uri[0] == '/' ? substr($uri,1) : $uri;

		return $url;
	}



	public function ncc_get($uri,$data = NULL,$handler = NULL) {

		$url = $this->_get_valid_call('ncc',$uri);
		return $this->_send('GET',$url,$data,$handler);
	}


	public function ncc_post($uri,$data = NULL,$handler = NULL) {

		$url = $this->_get_valid_call('ncc',$uri);
		return $this->_send('POST',$url,$data,$handler);
	}


	public function nis_get($uri,$data = NULL,$handler = NULL) {

		$url = $this->_get_valid_call('nis',$uri);
		return $this->_send('GET',$url,$data,$handler);
	}


	public function nis_post($uri,$data = NULL,$handler = NULL) {

		$url = $this->_get_valid_call('nis',$uri);
		return $this->_send('POST',$url,$data,$handler);
	}

}
?>
