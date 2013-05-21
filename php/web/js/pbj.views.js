PBJ.module("Views", function(Views, App, Backbone, Marionette, $, _){

Views.HeaderView = Backbone.Marionette.ItemView.extend({
  template: "#template-appHeader",
  
  events: {
    "click .logoutAction": "doLogout"
  },
  
  doLogout: function(e) {
    e.preventDefault();
    PBJ.logout();
  }
});

Views.LoginHeaderView = Backbone.Marionette.ItemView.extend({
  template: "#template-loginHeader"
});

Views.LoginMainView = Backbone.Marionette.ItemView.extend({
  template: "#template-loginMain"
});

Views.NotificationItemView = Backbone.Marionette.CompositeView.extend({
  tagName: "li",
  template: "#template-notificationItem",
  itemView: Views.NotificationItemView,
  itemViewContainer: ".notificationSubList",
  initialize: function() {
    if (this.model) {
      this.collection = this.model.get("childNotifications");
    }
  },
  onRender: function() {
    if (this.model.get("stage") != "end") {
      this.$('.notificationText').addClass("notificationInProgress");
    }
    else {
      this.$('.notificationText').removeClass("notificationInProgress");
    }
  }/*
  appendHtml: function(cv, iv){
      cv.$("ul:first").append(iv.el);
  },
  onRender: function() {
      if(_.isUndefined(this.collection)){
          this.$("ul:first").remove();
      }
  }*/
});

Views.NotificationListView = Backbone.Marionette.CompositeView.extend({
  template: "#template-notificationList",
  itemView: Views.NotificationItemView,
  itemViewContainer: "#notificationList",
  events: {
    "click #messagesCloseAction" : "hideMessages",
    "click #messagesMinimizeAction" : "minimizeMessagesAction",
    "click #messagesMaximizeAction" : "maximizeMessagesAction"
  },
  maximizeMessagesAction: function(e) {
    e.preventDefault();
    this.showMessages();
  },
  showMessages: function() {
    this.$el.parent().removeClass("messagesMinimized");
    this.$('#messagesMinimizeAction').show();
    this.$('#messagesMaximizeAction').hide();
    this.$el.parent().addClass("messagesCentered");
    this.$el.parent().show();
  },
  minimizeMessagesAction: function(e) {
    e.preventDefault();
    this.minimizeMessages();
  },
  minimizeMessages: function() {
    this.$el.parent().removeClass("messagesCentered");
    this.$el.parent().addClass("messagesMinimized");
    this.$('#messagesMaximizeAction').show();
    this.$('#messagesMinimizeAction').hide();
    this.$el.parent().show();
  },
  hideMessages: function() {
    this.$el.parent().hide();
  }  
});

Views.ItemView = Backbone.Marionette.ItemView.extend({
  tagName : 'li',
  template : "#template-eventItemView",
  events: {
    "click .removeEventAction" : "removeEvent"
  },
  onRender: function() {
    if (!this.model || !this.model.get("isOrganizer")) {
      this.$('.removeEventSpan').hide();
    }
  },
  removeEvent: function(e) {
    e.preventDefault();
    var title = this.model.get("title");
    if (confirm("This will permanently delete the event '" + title + "'.")) {
      PBJ.infoBegin("removeevent", "Deleting event '" + title + "'...");
      this.model.destroy({
        success: function() {
          PBJ.infoEnd("removeevent", "Event '" + title + "' deleted.");
        }
      });
    }
  }
});

Views.ListView = Backbone.Marionette.CompositeView.extend({
  template : "#template-eventListCompositeView",
  itemView : Views.ItemView,
  itemViewContainer : '#event-list'
});

Views.CreateEventView = Backbone.Marionette.ItemView.extend({
  template: "#template-createEvent",
  ui: {
    title: "#createEventTitleField"
  },
  events: {
    "click #createEventSaveAction" : "saveNewEvent"
  },
  saveNewEvent: function() {
    var event = new PBJ.Models.Event();
    event.set("title", this.ui.title.val());
    PBJ.infoBegin("createevent", "Creating event '"+event.get("title")+"'...");
    event.save({},{
      success: function(savedEvent) {
        PBJ.infoEnd("createevent", "Event '"+event.get("title")+"' created.");
        Backbone.history.navigate("event/" + savedEvent.get("id"), true);
      }
    });
    return false;
  }
});

Views.UserPageView = Backbone.Marionette.ItemView.extend({
  template: "#template-userPage"
});

Views.EventTitleView = Backbone.Marionette.Layout.extend({
  template: "#template-eventTitle",
  regions: {
    response: "#userResponse"
  },
  events: {
    "click .respondInAction" : "respondIn",
    "click .respondOutAction" : "respondOut",
    "click #copyNewEventAction" : "copyToNewEvent"
  },
  initialize: function() {
    this.model.on("change:currentGuest", function(e) {
      if (this.model.get("currentGuest")) {
        //only add the listener at most once, so if it exists, remove the existing one
        this.model.get("currentGuest").off("change:status", this.showResponse, this);
        this.model.get("currentGuest").on("change:status", this.showResponse, this);
      }
      this.showResponse();
    }, this);
  },
  onRender: function() {
    var guest = this.model.get("currentGuest");
    if (guest) {
      if (guest.get("isOrganizer")) {
        this.response.show(new Views.OrganizerResponseView({model: this.model}));
      }
      else {
        this.response.show(new Views.GuestResponseView({model: this.model}));
        this.showResponse();
      }
    }
  },
  respondIn: function(e) {
    this.respond(e, "in");
  },
  respondOut: function(e) {
    this.respond(e, "out");
  },
  respond: function(e, status) {
    e.preventDefault();
    var guest = this.model.get("currentGuest");
    PBJ.infoBegin("respond", "Updating status...");
    guest.save({status: status}, {
      success: function() {
        PBJ.infoEnd("respond", "Status updated.");
      }
    });
  },
  showResponse: function() {
    var guest = this.model.get("currentGuest");
    if (guest) {
      var response = guest.get("status");
      var inaction = this.$(".respondInAction");
      var outaction = this.$(".respondOutAction");
      if (response == "in") {
        inaction.addClass("responseSelected");
        outaction.removeClass("responseSelected");
      }
      else {
        outaction.addClass("responseSelected");
        inaction.removeClass("responseSelected");
      }
    }
  },
  copyToNewEvent: function(e) {
    e.preventDefault();
    var event = new PBJ.Models.Event();
    event.urlRoot = PBJ.Models.apiBase + '/events/' + PBJ.controller.currentEvent.get("id") + '/copy';
    PBJ.infoBegin("copynew", "Copying event...");
    event.save({},{
      success: function(savedEvent) {
        console.log("SAVED:", savedEvent);
        PBJ.infoEnd("copynew", "Event copied.");
        Backbone.history.navigate("event/" + savedEvent.get("id"), true);
      }
    });
  }
});

Views.GuestResponseView = Backbone.Marionette.ItemView.extend({
  template: "#template-guestResponse",
  tagName: "span"
});

Views.OrganizerResponseView = Backbone.Marionette.ItemView.extend({
  template: "#template-organizerResponse",
  tagName: "span"
});

Views.ModuleMinimizedDefaultView = Backbone.Marionette.ItemView.extend({
  tagName: 'td',
  template: "#template-moduleMinimizedDefault"
});

Views.EventWebModuleListView = Backbone.Marionette.CompositeView.extend({
  template: "#template-moduleList",
  itemViewContainer: "#moduleListRow",
  itemView: Views.ModuleMinimizedDefaultView
});

Views.GuestItemView = Backbone.Marionette.ItemView.extend({
  tagName: 'li',
  template: "#template-guestItemView",
  templateHelpers: {
    getLoggedInText: function() {
      var text = "";
      if (this.isCurrentUser) {
        text = " (You)";
      }
      return text;
    }
  }
});

Views.GuestListView = Backbone.Marionette.CompositeView.extend({
  template: "#template-guestListView",
  itemView: Views.GuestItemView,
  itemViewContainer: "#guestList",
  initialize: function() {
    this.collection.bind("change", this.render);
  }
});

Views.EventMessageView = Backbone.Marionette.ItemView.extend({
  tagName: 'li',
  template: "#template-eventMessageView"
});

Views.EventMessageListView = Backbone.Marionette.CompositeView.extend({
  template: "#template-eventMessageListView",
  itemView: Views.EventMessageView,
  itemViewContainer: "#eventMessages",
  ui: {
    newMessage: "#newMessageField"
  },
  events: {
    "click #postNewMessage" : "postNewMessage",
    "keydown #newMessageField": "onKeyDown",
  },
  initialize: function() {
    this.collection.bind("change", this.render);
  },
  onKeyDown: function(e) {
    if (e.keyCode == 13) { //Enter key
      this.postNewMessage(e);
    }
  },
  postNewMessage: function(e) {
    e.preventDefault();
    var self = this;
    var message = this.ui.newMessage.val();
    PBJ.infoBegin("postmessage", "Posting message...");
    this.collection.create({
      message: message,
      userid: PBJ.userSession.get("user").id,
      eventid: PBJ.controller.currentEvent.get("id")
    }, 
    {
      wait:true,
      success: function(m) {
        self.ui.newMessage.val("");
        //self.collection.sort();
        PBJ.infoEnd("postmessage", "Message posted.");
      }
    });
  }
});

});