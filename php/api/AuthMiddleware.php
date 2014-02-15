<?php
require_once '../vendor/autoload.php';

class AuthMiddleware extends \Slim\Middleware
{
    public function call()
    {
        // Get reference to application
        $slim = $this->app;
        
        $req = $slim->request();
        $sessionStarting = (substr($req->getResourceUri(),0,8) == "/session");
        $sessionStarted = false;
        
        global $sessionManager;
        if (!$sessionStarting) {
          if ($sessionManager->getUserSession() != NULL) {
            $sessionStarted = true;
          }
        }
        
        $sessionRequired = (substr($req->getResourceUri(),0,8) != "/mailgun");
        
        if (!$sessionStarting && !$sessionStarted && $sessionRequired) {
          $slim->response()->status(401);
          echo 'Your session is not valid.';
        }
        else {
          // Run inner middleware and application
          $this->next->call();
        }
    }
}

?>