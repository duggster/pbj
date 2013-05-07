<?php
require_once '../vendor/autoload.php';
require_once '../model/models.php';

class SessionManager {
  
  private $userSession;
  
  public function getUserSession() {
    if ($this->userSession == NULL) {
      if (isset($_COOKIE[session_name()])) { //Don't start session unless one already exists
        session_start();
        session_write_close();
        if (isset($_SESSION["userSession"])) {
          $this->userSession = $_SESSION["userSession"];
        }
      }
    }
    return $this->userSession;
  }
  
  public function getUserId() {
    $id = NULL;
    $sess = $this->getUserSession();
    if ($sess != NULL && $sess->user != NULL) {
      $id = $sess->user->id;
    }
    return $id;
  }
  
  public function setUserSession($userSession) {
    if ($userSession != NULL && $userSession->user != NULL) {
      session_start();
      $userSession->id = session_id();
      $_SESSION["userSession"] = $userSession;
      session_write_close();
    }
  }
  
  public function destroySession() {
    session_start();
    // Unset all of the session variables.
    $_SESSION = array();
    $this->userSession = NULL;

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    if (session_id() != "") {
      // Finally, destroy the session.
      session_destroy();
    }
    session_write_close();
  }
}

?>