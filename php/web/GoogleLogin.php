<?php 
require_once '../env/env.php';
require_once '../google-api-php-client/src/Google_Client.php';
require_once '../google-api-php-client/src/contrib/Google_PlusService.php';

class GoogleLogin {

  private $plus;
  private $client;
  private $googleId;
  
  private $appName;
  private $clientId;
  private $clientSecret;
  private $redirectUri;
  
  private $offline;
  
  public function __construct () {
    global $GOOGLE_APP_NAME, $GOOGLE_CLIENT_ID, $GOOGLE_CLIENT_SECRET, $GOOGLE_REDIRECT_URI, $GOOGLE_OFFLINE;
    $this->appName = $GOOGLE_APP_NAME;
    $this->clientId = $GOOGLE_CLIENT_ID;
    $this->clientSecret = $GOOGLE_CLIENT_SECRET;
    $this->redirectUri = $GOOGLE_REDIRECT_URI;
    $this->offline = $GOOGLE_OFFLINE;
  }
  
  public function getClient() {
    if ($this->client == NULL) {
      $this->client = new Google_Client();
      $this->client->setApplicationName($this->appName);
      $this->client->setClientId($this->clientId);
      $this->client->setClientSecret($this->clientSecret);
      $this->client->setRedirectUri($this->redirectUri); //registered URL
      $this->plus = new Google_PlusService($this->client);
    }
    return $this->client;
  }
  
  public function getAuthUrl($state) {
    $authurl = "";
    $this->getClient()->setState(urlencode("$state"));
    $authurl = $this->getClient()->createAuthUrl();
    if ($this->offline) {
      $authurl = "mocklogin.php";
    }
    return $authurl;
  }
  
  public function authenticateWithCode() {
    if (isset($_GET['code'])) {
      $client = $this->getClient();
      $client->authenticate();
    }
  }
  
  public function getGoogleId() {
    if ($this->googleId == NULL) {
      $this->getClient();
      $me = $this->plus->people->get('me');
      $this->googleId = $me["id"];
    }
    return $this->googleId;
  }
  
  public function redirectToApp() {
    $state = urldecode($_GET['state']);
    header('Location: ' . $state);
  }
}

?>