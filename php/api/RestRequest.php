<?php

/**
 This class based on http://www.gen-x-design.com/archives/making-restful-requests-in-php/
*/
class RestRequest
{
	protected $url;
	protected $verb;
	protected $requestBody;
	protected $requestLength;
	protected $username;
	protected $password;
	protected $headers;
	protected $responseBody;
	protected $responseInfo;
  
  protected $debug = false;
	
	public function __construct ($url = null, $verb = 'GET', $headers = null, $requestBody = null)
	{
		$this->url				= $url;
		$this->verb				= $verb;
		$this->requestBody		= $requestBody;
		$this->requestLength	= 0;
		$this->username			= null;
		$this->password			= null;
		$this->responseBody		= null;
		$this->responseInfo		= null;
    $this->headers        = $headers;
	}
	
	public function flush ()
	{
		$this->requestBody		= null;
		$this->requestLength	= 0;
		$this->verb				= 'GET';
		$this->responseBody		= null;
		$this->responseInfo		= null;
	}
	
	public function execute ()
	{
    if ($this->debug) {
      echo "Debugging turned ON<br/>";
    }
		$ch = curl_init();
		$this->setAuth($ch);
		
		try
		{
			switch (strtoupper($this->verb))
			{
				case 'GET':
					$this->executeGet($ch);
					break;
				case 'POST':
					$this->executePost($ch);
					break;
				case 'PUT':
					$this->executePut($ch);
					break;
				case 'DELETE':
					$this->executeDelete($ch);
					break;
				default:
					throw new InvalidArgumentException('Current verb (' . $this->verb . ') is an invalid REST verb.');
			}
		}
		catch (InvalidArgumentException $e)
		{
			curl_close($ch);
			throw $e;
		}
		catch (Exception $e)
		{
			curl_close($ch);
			throw $e;
		}
		
	}
	
	public function buildPostBody ($data = null)
	{
		$data = ($data !== null) ? $data : $this->requestBody;
		
		if (!is_array($data))
		{
			throw new InvalidArgumentException('Invalid data input for postBody.  Array expected');
		}
		
		$data = http_build_query($data, '', '&');
		$this->requestBody = $data;
	}
	
	protected function executeGet ($ch)
	{		
		$this->doExecute($ch);	
	}
	
	protected function executePost ($ch)
	{
		if (!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}
    $this->requestLength = strlen($this->requestBody);
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
		curl_setopt($ch, CURLOPT_POST, 1);
    
		$this->doExecute($ch);
	}
	
	protected function executePut ($ch)
	{
		if (!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}
		
		$this->requestLength = strlen($this->requestBody);
		
		$fh = fopen('php://memory', 'rw');
		fwrite($fh, $this->requestBody);
		rewind($fh);
		
		curl_setopt($ch, CURLOPT_INFILE, $fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, $this->requestLength);
		curl_setopt($ch, CURLOPT_PUT, true);
		
		$this->doExecute($ch);
		
		fclose($fh);
	}
	
	protected function executeDelete ($ch)
	{
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		
		$this->doExecute($ch);
	}
	
	protected function doExecute (&$curlHandle)
	{
		$this->buildHeaders($curlHandle);
    $this->setCurlOpts($curlHandle);
		$this->responseBody = curl_exec($curlHandle);
		$this->responseInfo	= curl_getinfo($curlHandle);
    if ($this->debug) {
      echo "<br/>cURL error number:" .curl_errno($curlHandle); // print error info
      echo "<br/>cURL error:" . curl_error($curlHandle) . "<br/>"; 
    }
		
		curl_close($curlHandle);
	}
  
  protected function buildHeaders(&$curlHandle) {
    if ($this->headers != null && !is_array($this->headers))
		{
			throw new InvalidArgumentException('Invalid headers.  Array expected');
		}
    
    if (is_integer($this->requestLength) && (strtoupper($this->verb) == 'POST')) {
      if ($this->headers == null) {
        $this->headers = array();
      }
      $this->headers[] = 'Content-Length: ' . $this->requestLength;
    }
    
    if ($this->headers != null) {
      //Convert associative array to properly formatted values in a normal array
      $headerarr = array();
      foreach ($this->headers as $key => $value) {
        $headerarr[] = "$key: $value";
      }
      
      curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headerarr);
    }
  }
	
	protected function setCurlOpts (&$curlHandle)
	{
    //Set the timeout (in seconds)
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10);
    //Set the URL
		curl_setopt($curlHandle, CURLOPT_URL, $this->url);
    //Wait for the response from the server
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
    //Trust all certs: http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
    curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
    //Verbose option useful for debugging
    curl_setopt($curlHandle, CURLOPT_VERBOSE, $this->debug);
	}
	
	protected function setAuth (&$curlHandle)
	{
		if ($this->username !== null && $this->password !== null)
		{
			curl_setopt($curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curlHandle, CURLOPT_USERPWD, $this->username . ':' . $this->password);
		}
	}
	
	public function getHeaders ()
	{
		return $this->headers;
	} 
	
	public function setHeaders ($headers)
	{
		$this->headers = $headers;
	} 
	
	public function getPassword ()
	{
		return $this->password;
	} 
	
	public function setPassword ($password)
	{
		$this->password = $password;
	} 
	
	public function getResponseBody ()
	{
		return $this->responseBody;
	} 
	
	public function getResponseInfo ()
	{
		return $this->responseInfo;
	} 
	
	public function getUrl ()
	{
		return $this->url;
	} 
	
	public function setUrl ($url)
	{
		$this->url = $url;
	} 
	
	public function getUsername ()
	{
		return $this->username;
	} 
	
	public function setUsername ($username)
	{
		$this->username = $username;
	} 
	
	public function getVerb ()
	{
		return $this->verb;
	} 
	
	public function setVerb ($verb)
	{
		$this->verb = $verb;
	} 
}

?>