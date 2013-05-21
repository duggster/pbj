<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once 'GoogleLogin.php';

$login = new GoogleLogin();
$authUrl = $login->getAuthUrl('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
//$authUrl = 'mocklogin.php';

$googleId = (isset($_COOKIE["googleId"]))? $_COOKIE["googleId"] : NULL;

?>
<html>
<head>
<title>PBJ</title>
<link rel="stylesheet" href="css/pbj.css">
</head>

<body>
  <div id="messageRegion"></div>
  <div id="pbjapp">
    <header>
      <div id="headerDiv">
        <div id="innerHeaderDiv"><div id="appHeader"></div></div>
      </div>
    </header>
    <div id="appMain"><div id="main"></div><div class="clearer"></div></div>
    <footer id="footer"></footer>
  </div>
  
  <script type="text/html" id="template-notificationItem">
    <span class="notificationText"><%= message %></span>
    <ul class="notificationSubList"></ul>
  </script>

  <script type="text/html" id="template-notificationList">
    <div id="messagesCloseAction" class="messagesAction">x</div>
    <div id="messagesMaximizeAction" class="messagesAction">+</div>
    <div id="messagesMinimizeAction" class="messagesAction">-</div>
    <ul id="notificationList"></ul>
  </script>
  
  <script type="text/html" id="template-appHeader">
    PB&J <a href="#">My Events</a> | <a href="#/create">New Event</a> <span id="currentUserHeader"><a href="#/user/<%= user.id %>"><%= user.name %></a> <a href="#/login" class="logoutAction">Logout</a></span>
  </script>
  
  <script type="text/html" id="template-loginHeader">
    &nbsp;<span id="currentUserHeader"><a href="<?php echo $authUrl ?>" class="loginAction">Login</a></span>
  </script>
  
  <script type="text/html" id="template-loginMain">
    You are not logged in.
  </script>
  
  <script type="text/html" id="template-eventItemView">
    <div class="view">
      <a href="#/event/<%= id %>"><%= title %></a> &nbsp;<span class="removeEventSpan"><a href="#" class="removeEventAction">[x]</a></span>
    </div>
  </script>

  <script type="text/html" id="template-eventListCompositeView">
    <ul id="event-list"></ul>
  </script>
  
  <script type="text/html" id="template-createEvent">
    Event Title: <input type="text" id="createEventTitleField"/><br/>
    <a href="" id="createEventSaveAction">Create</a> | <a href="" id="createEventCancelAction">Cancel</a>
  </script>
  
  <script type="text/html" id="template-userPage">
    <h2><%= name %></h2>
  </script>
  
  <script type="text/html" id="template-eventLayout">
    <header id="eventTitle"></header>
    <section id="moduleFooter" class="moduleListFooter"></section>
    <div id="sidebar">
      <section id="guestListSection" class="module"></section>
      <section id="discussionSection" class="module"></section>
    </div>
    <div id="eventMain" class="module"></div>
  </script>
  
  <script type="text/html" id="template-eventTitle">
    <div id="eventTitleDiv">
      <div id="eventTitleActions"><a href="#" id="copyNewEventAction">Copy To New Event</a></div>
      <h2 id="eventTitleText"><%= title %></h2><span id="userResponse"></span>
    </div>
  </script>
  
  <script type="text/html" id="template-guestResponse">
    Your Response: <a href="" class="respondInAction">In</a> <a href="" class="respondOutAction">Out</a>
  </script>
  
  <script type="text/html" id="template-organizerResponse">
    You are an organizer.
  </script>
  
  <script type="text/html" id="template-moduleList">
    <div id="moduleListContainer">
    <table>
      <tr id="moduleListRow">
      </tr>
    </table>
    </div>
  </script>
  
  <script type="text/html" id="template-moduleMinimizedDefault">
    <div class="module-min" data-id="<%= id %>"><%= title %></div>
  </script>
  
  <script type="text/html" id="template-guestListView">
      <div class="moduleTitleBar"><div class="moduleTitle">Guest list</div></div>
      <ul id="guestList"></ul>
  </script>
  
  <script type="text/html" id="template-guestItemView">
    <span class="guestStatus-<%= status%>"> <%= name %> <%= getLoggedInText() %></span>
  </script>

  <script type="text/html" id="template-eventMessageListView">
    <div>
      <input type="text" id="newMessageField" placeholder="Post a new message..."/><a href="" id="postNewMessage">Post</a>
    </div>
    <div id="eventMessagesContainer">
      <ul id="eventMessages"></ul>
    </div>
  </script>
  
  <script type="text/html" id="template-eventMessageView">
    <span class="eventmessage-user eventmessage-user<%=id%>" style="color: <%= assignedColor %>"><%= userName %></span> <span class="eventmessage-timestamp"><%= messageTimestampFormatted%></span><br/>
    <%= message %>
  </script>
  
  <link href="js/lib/jquery-ui-1.9.2.custom/css/ui-lightness/jquery-ui-1.9.2.custom.css" rel="stylesheet">
	<script type="text/javascript" src="js/lib/jquery-ui-1.9.2.custom/js/jquery-1.8.3.js"></script>
	<script type="text/javascript" src="js/lib/jquery-ui-1.9.2.custom/js/jquery-ui-1.9.2.custom.js"></script>
  
  <!--<script type="text/javascript" src="js/lib/json2-10.08.2012/json2.js"></script>-->
	<script type="text/javascript" src="js/lib/underscore-1.4.4/underscore.js"></script>
	<script type="text/javascript" src="js/lib/backbone-0.9.10/backbone.js"></script>
  <script type="text/javascript" src="js/lib/backbone.marionette-1.0.0.r6/backbone.marionette.js"></script>
  
  <script type="text/javascript" src="js/pbj.js"></script>
  <script type="text/javascript" src="js/pbj.models.js"></script>
  <script type="text/javascript" src="js/pbj.views.js"></script>
  <script type="text/javascript" src="js/pbj.controller.js"></script>
  <script type="text/javascript" src="js/pbj.layout.js"></script>
  
  <script type="text/javascript" src="js/lib/ckeditor_4.0.1.1_full/ckeditor/ckeditor.js"></script>
  <?php require 'js/module/rich-description.html' ?>
  <?php require 'js/module/guest-manager.html' ?>
  <?php require 'js/module/iframemodule.html' ?>
  <?php require 'js/module/module-manager.html' ?>
  
  <script>
      $(function(){
        // Start the PBJ app (defined in js/pbj.js)
        PBJ.start({
          authId: "<?php echo $googleId ?>"
        });
      });
    </script>
</body>
</html>