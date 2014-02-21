<?php
require_once '../env/env.php';
require_once 'GoogleLogin.php';

$login = new GoogleLogin();
$authUrl = $login->getAuthUrl('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);

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
  <div id="dialogRegion"></div>
  
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
  <div class="pbjinfobackground">
  <div class="pbjinfo">
(You are not logged in).
<h1>PB&J</h1>
<p>
PB&J is designed to make planning social gatherings easier. 
</p>
<p>
Simply include <a href="mailto:pbj@pbj.mailgun.org">pbj@pbj.mailgun.org</a> in an email and an event is created and everyone on the thread is added as a guest. It's that easy - no need to go through steps to create an account, create a design, or set up contacts in yet another site. From there on out, a unique email address for your event is created for you and your guests to include on emails going forward. 
</p>
<p>
Use PB&J to help plan casual outings like going out to a movie, a bar/restaurant, or organizing a game night. You need something to let everyone know what the plan is and to keep track of who's going and who's not. 
</p>
<p>
With PB&J you can:
<ul>
<li>Invite guests simply by including them in an email - no need to create or import yet another list of contacts</li>
<li>PBJ will automatically include guests on an email thread in case you accidentally left someone off</li>
<li>PBJ will automatically drop guests who are not interested in an event they are not attending</li>
<li>Use email normally, no need to log in to PBJ if you don't want</li>
<li>Have ultimate flexibility over how to display your event information with rich text editing</li>
<li>Use a predefined design to make your event information look pretty</li>
<li>Use modules designed by other users to help with specific types of events</li>
<li>Manage guests by linking family members or couples</li>
<li>Search and filter the guest list to easily find out if your ex is going</li>
<li>Copy a previous event, including the guest list, to a new event</li>
<li>Easily add guests from previous events you've attended</li>
</ul>
</p>
<p>
<strong>Featured Event Module: Boardgames!</strong><br/>
"What do you want to play?" "I dunno, what do you want to play?" "Well what games do you have?"<br/>
Plan your next game night with the Boardgames! PBJ event module. Each guest can enter games that they own (game data and pictures are pulled from boardgamegeek.com), and you can sign up for what games you want to play at the party.
</p>
</div>
</div>
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
    <p>
    Display Name: <span contenteditable="true" class="editField" id="editNameField"><%= name %></span>
    </p>
    <a href="#" id="saveUserProfileAction">Save</a>
    <p></p>
  </script>
  
  <script type="text/html" id="template-eventError">
    An error has occurred and the page cannot be loaded.
  </script>
  
  <script type="text/html" id="template-eventLayout">
    <header id="eventTitle"></header>
    <section id="moduleFooter" class="moduleListFooter"></section>
    <div id="eventMain" class="module"></div>
    <div id="sidebar">
      <section id="guestListSection" class="module"></section>
      <section id="discussionSection" class="module"></section>
    </div>
  </script>
  
  <script type="text/html" id="template-eventTitle">
    <div id="eventTitleDiv">
      <div id="eventTitleActions"><a href="#" id="copyNewEventAction">Copy To New Event</a></div>
      <h2 id="eventTitleText"><%= title %></h2> &lt;<a href="mailto:<%= emailAddress %>" title="This event's email address"><%= emailAddress %></a>&gt;
      <br/><span id="guestResponseOrganizer">You are an organizer. </span>Your Response: <a href="" class="respondInAction">In</a> <a href="" class="respondOutAction">Out</a> <span id="notifyPrefSpan"><input type="checkbox" id="notifyPrefOption"/><label for="notifyPrefOption">Keep me in the loop</label></span>
    </div>
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
  
  <script type="text/html" id="template-guestListSectionView">
    <div id="guestListTitle">
      <div id="guestListActions">
        <input type="text" id="guestListFilterBox" placeholder="Find guests..."/>
      </div>
      <div class="moduleTitleBar moduleTitle">Guest list 
        <span class="guestCount guestCountActive" data-id="in" title="<%= getGuestInCountDetail() %>"><span class="guestStatus-in">In</span> <%= getGuestInCount() %>
          <!--<img src='/pbj/web/img/adult_gray_6x16.png'/>
          <img src='/pbj/web/img/kid_gray_6x16.png'/>
          <img src='/pbj/web/img/baby_gray_6x16.png'/>-->
        </span>
        <span class="guestCount guestCountActive" data-id="invited" title="<%= getGuestPendingCountDetail() %>"><span class="guestStatus-invited">Pending</span> <%= getGuestPendingCount() %></span>
        <span class="guestCount guestCountActive" data-id="out" title="<%= getGuestOutCountDetail() %>"><span class="guestStatus-out">Out</span> <%= getGuestOutCount() %></span>
      </div>
    </div>
    <div id="guestManagerListActions">
      <select id="selectedActions">
        <option value="">--Action--</option>
        <option value="sendinvite">Send invite</option>
        <option value="markin">Mark In</option>
        <option value="markout">Mark Out</option>
        <option value="markpending">Mark Pending</option>
        <option value="link">Link</option>
        <option value="unlink">Un-Link</option>
        <option value="setorg">Set as Organizer</option>
        <option value="unsetorg">Set as Participant</option>
        <option value="remove">Remove</option>
      </select>
      Select: <a href="#" id="selectAllGuestsAction">All</a> | <a href="#" id="selectNoneGuestsAction">None</a> 
    </div>
    <div id="guestListContainer"></div>
  </script>
  
  <script type="text/html" id="template-guestListView">
    <ul id="guestList"></ul>
  </script>
  
  <script type="text/html" id="template-guestItemView">
    <span class='kidStatusIcon'><%= getKidStatus() %></span><div class="guestItemName guestStatus-<%= status%>"> <%= name %> <%= getAdditionalText() %></div> <input type="text" class="guestItemComments" placeholder="Comments" value="<%= comments %>"/>
  </script>
  
  <script type="text/html" id="template-guestResponseDialogLinkedGuestItemView">
    <select class="linkedGuestResponseSelect">
      <option value=""></option>
      <option value="in">In</option>
      <option value="pending">Pending</option>
      <option value="out">Out</option>
    </select><span class="guestStatus-<%= status%>"><%= name %></span>
  </script>
  
  <script type="text/html" id="template-guestResponseDialogLinkedGuestListView">
    You are linked to the following guests. Would you also like to change their statuses?
    <ul id="linkedGuestList">
    </ul>
  </script>
  
  <script type="text/html" id="template-guestResponseDialogView">
    <div class="dialogTitle">
      Change status to '<%= status %>'
    </div>
    <div class="dialogContent">
      <div id="linkedGuestsRegion"></div>
      Comments:
      <div id="guestResponseMessageField" class="guestResponseMessageFieldClass" contentEditable="true"></div>
    </div>
    <div class="dialogButtons">
      <input type="button" value="OK" class="okButton"/>
      <input type="button" value="Cancel" class="cancelButton"/>
    </div>
  </script>

  <script type="text/html" id="template-eventMessageListView">
    <div>
      <a href="" id="postNewMessage">Post Message</a>
      <div id="newMessageField" contentEditable="true"></div>
    </div>
    <div id="eventMessagesContainer">
      <ul id="eventMessages"></ul>
    </div>
  </script>
  
  <script type="text/html" id="template-eventMessageView">
    <span class="eventmessage-user eventmessage-user<%=id%>" style="color: <%= assignedColor %>"><%= userName %></span> <span class="eventmessage-timestamp"><%= messageTimestampFormatted%></span><br/>
    <%= message %>
  </script>
  
  <script type="text/html" id="template-modalDialogView">
    <div id="modalBackground">
      <div id="theDialog"></div>
    </div>
  </script>
  
  <link href="js/lib/jquery-ui-1.9.2.custom/css/ui-lightness/jquery-ui-1.9.2.custom.css" rel="stylesheet">
	<script type="text/javascript" src="js/lib/jquery-ui-1.9.2.custom/js/jquery-1.8.3.js"></script>
	<script type="text/javascript" src="js/lib/jquery-ui-1.9.2.custom/js/jquery-ui-1.9.2.custom.js"></script>
  
  <!--<script type="text/javascript" src="js/lib/json2-10.08.2012/json2.js"></script>-->
	<script type="text/javascript" src="js/lib/underscore-1.4.4/underscore.js"></script>
	<script type="text/javascript" src="js/lib/backbone-0.9.10/backbone.js"></script>
  <script type="text/javascript" src="js/lib/backbone.marionette-1.0.0.r6/backbone.marionette.js"></script>
  <script type="text/javascript" src="js/lib/easyXDM-2.4.18.25/easyXDM<?php echo (($ISDEBUG)?".debug":".min"); ?>.js"></script>
  
  <script type="text/javascript" src="js/pbj.js"></script>
  <script type="text/javascript" src="js/pbj.models.js"></script>
  <script type="text/javascript" src="js/pbj.views.js"></script>
  <script type="text/javascript" src="js/pbj.controller.js"></script>
  <script type="text/javascript" src="js/pbj.layout.js"></script>
  
  <script type="text/javascript" src="js/lib/ckeditor_4.2_full/ckeditor/ckeditor.js"></script>
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