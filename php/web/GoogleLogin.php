<?php 

require_once '../google-api-php-client/src/Google_Client.php';
require_once '../google-api-php-client/src/contrib/Google_PlusService.php';

class GoogleLogin {

  private $plus;
  private $client;
  private $googleId;
  
  public function getClient() {
    if ($this->client == NULL) {
      $this->client = new Google_Client();
      $this->client->setApplicationName("PB&J");
      $this->client->setClientId('232676017603-e1vfb9ci8qgisqms9c6nda2m2mq842en.apps.googleusercontent.com');
      $this->client->setClientSecret('AedJlDsAnTByF0nr5GFaRfjQ');
      $this->client->setRedirectUri('http://pbj-dougandjeanne.rhcloud.com/web/oauth2callback.php'); //registered URL
      $this->plus = new Google_PlusService($this->client);
    }
    return $this->client;
  }
  
  public function getAuthUrl($state) {
    $this->getClient()->setState(urlencode("$state"));
    return $this->getClient()->createAuthUrl();
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