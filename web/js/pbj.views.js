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
    
    if (this.model.get("type") == "error") {
      this.$('.notificationText').addClass("notificationError");
    }
    else if (this.model.get("type") == "info") {
      this.$('.notificationText').addClass("notificationInfo");
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
        },
        error: function() {
          PBJ.errorEnd("removeevent", "Error deleting event '" + title + "'; event not deleted.");
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
      },
      error: function() {
        PBJ.errorEnd("createevent", "Error creating event.");
      }
    });
    return false;
  }
});

Views.UserPageView = Backbone.Marionette.ItemView.extend({
  template: "#template-userPage",
  events: {
    "click #saveUserProfileAction" : "saveUser"
  },
  saveUser: function(e) {
    e.preventDefault();
    var self = this;
    var name = this.$('#editNameField').text();
    PBJ.infoBegin("saveUser", "Updating user profile...");
    this.model.set("name", name);
    this.model.save({},{
      success: function() {
        PBJ.infoEnd("saveUser", "User profile saved.");
        PBJ.vent.trigger("user:changed", self.model);
      },
      error: function() {
        PBJ.errorEnd("saveUser", "Error saving user profile.");
      }
    });    
  }
});

Views.EventErrorView = Backbone.Marionette.Layout.extend({
  template: "#template-eventError"
});

Views.EventTitleView = Backbone.Marionette.Layout.extend({
  template: "#template-eventTitle",
  regions: {
    response: "#userResponse"
  },
  events: {
    "click .respondInAction" : "respondIn",
    "click .respondOutAction" : "respondOut",
    "click #copyNewEventAction" : "copyToNewEvent",
    "change #notifyPrefOption": "onChangeNotifyPref"
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
      this.$("#guestResponseOrganizer").hide();
      if (guest.get("isOrganizer")) {
        this.$("#guestResponseOrganizer").show();
      }
      this.showResponse();
      this.$('#notifyPrefSpan').hide();
      if (guest.get("status") == "out") {
        this.$('#notifyPrefSpan').show();
        if (guest.get("notifyPref") == "in") {
          this.$('#notifyPrefOption').attr("checked", true);
        }
        else {
          this.$('#notifyPrefOption').attr("checked", false);
        }
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
    var self = this;
    var guest = this.model.get("currentGuest");
    var guests = this.model.get("guests");
    var linkedGuests = guests.filter(function(g) {
      return ((g.get("guestLinkId") == guest.get("guestLinkId"))
        && (guest.get("guestLinkId") != null)
        && (g.get("id") != guest.get("id")));
    });
    linkedGuests = new Backbone.Collection(linkedGuests);
    
    var dialogModel = new Backbone.Model();
    dialogModel.set("status", status);
    dialogModel.set("guest", guest);
    dialogModel.set("linkedGuests", linkedGuests);
    
    var view = new Views.GuestResponseDialogView({model: dialogModel});
    PBJ.showModalDialog(view, {
      onOk: function() {
        guest.set("status", status);
        var comments = dialogModel.get("comments");
        var guestsToUpdate = dialogModel.get("linkedGuests").models;
        for (var i = 0; i < guestsToUpdate.length; i++) {
          var g = guestsToUpdate[i];
          g.set("status", g.get("statusChange"));
        }
        guestsToUpdate.push(guest);
        self.updateGuests(guestsToUpdate);
        self.postMessage(comments);
        self.render();
      }
    });
  },
  updateGuests: function(guests) {
    var self = this;
    var guestList = new PBJ.Models.GuestList(guests);
    guestList.eventid = this.model.get("id");
    var distinct = new Date().getTime();
    PBJ.infoBegin("updateguests"+distinct, "Updating status...");
    Backbone.sync("update", guestList, {
      success: function() {
        self.model.get("guests").sort();
        PBJ.vent.trigger("guestupdate", guestList);
        PBJ.infoEnd("updateguests"+distinct, "Status updated.");
      },
      error: function() {
        PBJ.errorEnd("updateguests"+distinct, "Error updating status.");
      }
    });
  },
  postMessage: function(message) {
    if (message && message.length > 0) {
      PBJ.infoBegin("postmessage", "Posting message...");
      var messageList = this.model.get("eventMessages");
      messageList.create({
        message: message,
        userid: PBJ.userSession.get("user").id,
        eventid: PBJ.controller.currentEvent.get("id")
      }, 
      {
        wait:true,
        success: function(m) {
          PBJ.infoEnd("postmessage", "Message posted.");
        },
        error: function() {
          PBJ.errorEnd("postmessage", "Error posting message.");
        }
      });
    }
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
  onChangeNotifyPref: function(e) {
    var guest = this.model.get("currentGuest");
    if (guest) {
      var checked = ((this.$("#notifyPrefOption").attr("checked") == "checked") ? "in" : "out");
      PBJ.infoBegin("guestNotifyPref", "Saving preference...");
      guest.save({notifyPref: checked},{
        success: function(savedEvent) {
          PBJ.infoEnd("guestNotifyPref", "Preference saved.");
        },
        error: function() {
          PBJ.errorEnd("guestNotifyPref", "Error saving preference.");
        }
      });
    }
  },
  copyToNewEvent: function(e) {
    e.preventDefault();
    var event = new PBJ.Models.Event();
    event.urlRoot = PBJ.Models.apiBase + '/events/' + PBJ.controller.currentEvent.get("id") + '/copy';
    PBJ.infoBegin("copynew", "Copying event...");
    event.save({},{
      success: function(savedEvent) {
        PBJ.infoEnd("copynew", "Event copied.");
        Backbone.history.navigate("event/" + savedEvent.get("id"), true);
      },
      error: function() {
        PBJ.errorEnd("copynew", "Error copying event.");
      }
    });
  }
});

Views.GuestResponseLinkedGuestItemView = Backbone.Marionette.ItemView.extend({
  tagName: 'div',
  template: "#template-guestResponseDialogLinkedGuestItemView",
  events: {
    "change .linkedGuestResponseSelect" : "onStatusChange"
  },
  onRender: function() {
    this.$('.linkedGuestResponseSelect').val(this.model.get("status"));
  },
  onStatusChange: function(e) {
    var status = this.$('.linkedGuestResponseSelect').val();
    this.model.set("statusChange", status);
  }
});

Views.GuestResponseLinkedGuestListView = Backbone.Marionette.CompositeView.extend({
  template: "#template-guestResponseDialogLinkedGuestListView",
  itemViewContainer: "#linkedGuestList",
  itemView: Views.GuestResponseLinkedGuestItemView
});

Views.GuestResponseDialogView = Backbone.Marionette.Layout.extend({
  tagName: 'div',
  template: "#template-guestResponseDialogView",
  ui: {
    comments: "#guestResponseMessageField"
  },
  regions: {
    linkedGuestsRegion: "#linkedGuestsRegion"
  },
  ckeditor: null,
  initialize: function() {
    CKEDITOR.disableAutoInline = false;
  },
  linksView: null,
  initialize: function() {
  },
  onRender: function() {
    if (this.model.get("linkedGuests").length > 0) {
      this.linksView = new PBJ.Views.GuestResponseLinkedGuestListView({collection: this.model.get("linkedGuests")});
      this.linkedGuestsRegion.show(this.linksView);
    }
    
    var el = this.ui.comments[0];
    if (el) {
      try {
        this.ckeditor = CKEDITOR.replace( el , {
          enterMode: CKEDITOR.ENTER_BR,
          width: '98%',
          height: 100,
          contentsCss: 'css/pbj.css',
          bodyClass: 'ckeditorBody',
          toolbar: [
            { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', '-', 'RemoveFormat' ] },
            { name: 'links', items: [ 'Link', 'Unlink'] },
            { name: 'insert', items: [ 'Image', 'Smiley', 'SpecialChar' ] },
            { name: 'styles', items: [ 'Format', 'Font', 'FontSize' ] },
            { name: 'colors', items: [ 'TextColor', 'BGColor' ] }
          ]
        });
      } catch (e) {
        console.error(e);
      }
    }
  },
  onBeforeClose: function() {
    if (this.ckeditor) {
      this.ckeditor.destroy();
    }
  },
  onOk: function() {
    this.model.set("comments", this.ckeditor.getData());
  }
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
  events: {
    "click .kidStatusIcon" : "onKidStatusClick",
    "blur .guestItemComments" : "onBlurGuestComments"
  },
  templateHelpers: {
    getAdditionalText: function() {
      var text = "";
      if (this.isCurrentUser || this.isOrganizer) {
        var org = (this.isOrganizer) ? "Organizer" : "";
        var both = (this.isOrganizer && this.isCurrentUser) ? ", " : "";
        var you = (this.isCurrentUser) ? "You" : "";
        text = " (" + org + both + you + ")";
      }
      //text += "" + this.guestLinkId;
      return text;
    },
    getKidStatus: function() {
      var icon = " ";
      if (this.kidStatus == "adult") {
        icon = "<img src='/pbj/web/img/adult_gray_6x16.png' title='Adult; Click to change to Kid.'/>";
      }
      else if (this.kidStatus == "kid") {
        icon = "<img src='/pbj/web/img/kid_gray_6x16.png' title='Kid; Click to change to Baby.'/>";
      }
      else if (this.kidStatus == "baby") {
        icon = "<img src='/pbj/web/img/baby_gray_6x16.png' title='Baby; Click to change to Adult.'/>";
      }
      var output = icon;
      return output;
    }
  },
  onRender: function() {
    this.$('.guestItemComments').hide();
    if (this.getIsOrganizer()) {
      this.$('.guestItemName').addClass("guestItem");
      this.$el.attr("data-id", this.model.get("id"));
      this.$('.guestItemComments').show();
    }
    var canEditKidStatus = (this.getIsOrganizer() || this.getIsLinked());
    this.$('.kidStatusIcon').toggleClass('kidStatusIconEdit', canEditKidStatus);
  },
  onKidStatusClick: function(e) {
    e.preventDefault();
    if (this.getIsOrganizer() || this.getIsLinked()) {
      var self = this;
      var current = this.model.get("kidStatus");
      var next = current;
      if (current == "adult") {
        next = "kid";
      }
      if (current == "kid") {
        next = "baby";
      }
      if (current == "baby") {
        next = "adult";
      }
      PBJ.infoBegin("updatekidstatus"+this.model.get("id"), "Updating guest...");
      this.model.save({kidStatus: next}, {
        success: function() {
          PBJ.infoEnd("updatekidstatus"+self.model.get("id"), "Guest updated.");
          self.render();
        },
        error: function() {
          PBJ.errorEnd("updatekidstatus"+self.model.get("id"), "Error updating guest.");
        }
      });
    }
  },
  onBlurGuestComments: function(e) {
    var self = this;
    var target = $(e.currentTarget);
    var val = target.val();
    var guest = this.model;
    if (guest.get("comments") != val) {
      PBJ.infoBegin("updateguestcomments"+this.model.get("id"), "Updating guest...");
      guest.save({comments:val}, {
        success: function() {
          PBJ.infoEnd("updateguestcomments"+self.model.get("id"), "Guest updated.");
          self.render();
        },
        error: function() {
          PBJ.errorEnd("updateguestcomments"+self.model.get("id"), "Error updating guest.");
        }
      });
    }
  },
  getIsOrganizer: function() {
    if (PBJ.controller.currentEvent && PBJ.controller.currentEvent.get("currentGuest")) {
      return PBJ.controller.currentEvent.get("currentGuest").get("isOrganizer");
    }
    return false;
  },
  getIsLinked: function() {
    if (PBJ.controller.currentEvent && PBJ.controller.currentEvent.get("currentGuest")) {
      var linkid = PBJ.controller.currentEvent.get("currentGuest").get("guestLinkId");
      return (linkid && linkid == this.model.get("guestLinkId"));
    }
    return false;
  }
});

Views.GuestListView = Backbone.Marionette.CompositeView.extend({
  template: "#template-guestListView",
  itemView: Views.GuestItemView,
  itemViewContainer: "#guestList"
});

Views.GuestListSectionView = Backbone.Marionette.Layout.extend({
  template: "#template-guestListSectionView",
  regions: {
    guestListContainer: "#guestListContainer"
  },
  events: {
    "keyup #guestListFilterBox": "onFilterType",
    "change #guestListStatusFilter": "onStatusChange",
    "hover .guestItem": "onItemHover",
    "click .guestItem": "onItemClick",
    "click #selectAllGuestsAction" : "onSelectAllGuests",
    "click #selectNoneGuestsAction" : "onSelectNoneGuests",
    "change #selectedActions" : "onSelectedActionsGo",
    "click .guestCount" : "onGuestCountClick"
  },
  templateHelpers: {
    getGuestInCount: function() {
      var count = "";
      if (this && this.eventMetadata && this.eventMetadata.get && this.eventMetadata.get("guestsIn")) {
        var m = this.eventMetadata;
        count = "" + m.get("guestsIn").total;
      }
      return count;
    },
    getGuestInCountDetail: function() {
      var count = "";
      if (this && this.eventMetadata && this.eventMetadata.get && this.eventMetadata.get("guestsIn")) {
        var m = this.eventMetadata;
        /*count = "" + m.get("guestsIn").adults + "<img src='/pbj/web/img/adult_gray_6x16.png'/><br/>" 
                  + m.get("guestsIn").kids + "<img src='/pbj/web/img/kid_gray_6x16.png'/><br/>" 
                  + m.get("guestsIn").babies + "<img src='/pbj/web/img/baby_gray_6x16.png'/>";
        */
        count = "" + m.get("guestsIn").adults + " Adults\n" 
                  + m.get("guestsIn").kids + " Kids\n" 
                  + m.get("guestsIn").babies + " Babies";
      }
      return count;
    },
    getGuestOutCount: function() {
      var count = "";
      if (this && this.eventMetadata && this.eventMetadata.get && this.eventMetadata.get("guestsOut")) {
        var m = this.eventMetadata;
        count = "" + m.get("guestsOut").total;
      }
      return count;
    },
    getGuestOutCountDetail: function() {
      var count = "";
      if (this && this.eventMetadata && this.eventMetadata.get && this.eventMetadata.get("guestsOut")) {
        var m = this.eventMetadata;
        /*count = "" + m.get("guestsIn").adults + "<img src='/pbj/web/img/adult_gray_6x16.png'/><br/>" 
                  + m.get("guestsIn").kids + "<img src='/pbj/web/img/kid_gray_6x16.png'/><br/>" 
                  + m.get("guestsIn").babies + "<img src='/pbj/web/img/baby_gray_6x16.png'/>";
        */
        count = "" + m.get("guestsOut").adults + " Adults\n" 
                  + m.get("guestsOut").kids + " Kids\n" 
                  + m.get("guestsOut").babies + " Babies";
      }
      return count;
    },
    getGuestPendingCount: function() {
      var count = "";
      if (this && this.eventMetadata && this.eventMetadata.get && this.eventMetadata.get("guestsPending")) {
        var m = this.eventMetadata;
        count = "" + m.get("guestsPending").total;
      }
      return count;
    },
    getGuestPendingCountDetail: function() {
      var count = "";
      if (this && this.eventMetadata && this.eventMetadata.get && this.eventMetadata.get("guestsPending")) {
        var m = this.eventMetadata;
        /*count = "" + m.get("guestsIn").adults + "<img src='/pbj/web/img/adult_gray_6x16.png'/><br/>" 
                  + m.get("guestsIn").kids + "<img src='/pbj/web/img/kid_gray_6x16.png'/><br/>" 
                  + m.get("guestsIn").babies + "<img src='/pbj/web/img/baby_gray_6x16.png'/>";
        */
        count = "" + m.get("guestsPending").adults + " Adults\n" 
                  + m.get("guestsPending").kids + " Kids\n" 
                  + m.get("guestsPending").babies + " Babies";
      }
      return count;
    }
  },
  filteredCollection: null,
  listView: null,
  initialize: function() {
    //These show and close events are necessary because this layout is destroyed
    //and recreated occassionally and the change event listener needs to be cleaned up.
    this.guestListContainer.on("show", function() {
      this.model.get("guests").on("reset add", this.createFilteredList, this);
      this.model.get("guests").on("change:status", this.createFilteredList, this);
    }, this);
    this.guestListContainer.on("close", function() {
      this.model.get("guests").off("reset add", this.createFilteredList, this);
      this.model.get("guests").off("change:status", this.createFilteredList, this);
    }, this);
    this.model.on("change:eventMetadata", function() {
      //TODO: put the metadata in a different region to support partial re-rendering.
      this.render();
    }, this);
  },
  onRender: function() {
    this.showGuestManagerListActions();
    this.model.on("change:currentGuest", function() {
      this.showGuestManagerListActions();
    }, this);
    
    this.createFilteredList();
  },
  showGuestManagerListActions: function() {
    if (!this.getIsOrganizer()) {
      this.$('#guestManagerListActions').hide();
    } else {
      this.$('#guestManagerListActions').show();
    }
  },
  onFilterType: function(e) {
    this.createFilteredList();    
  },
  onStatusChange: function(e) {
    this.createFilteredList();
  },
  createFilteredList: function() {
    var self = this;
    var filterCriteria = this.$('#guestListFilterBox').val().toLowerCase();
    var statusCriteria = this.getSelectedStatusCriteria();
    var guests = this.model.get("guests");
    //Can this be cached if criteria is the same?
    //short circuit if no filtering?
    var filteredArray = guests.filter(function(guest) {
      var keep = false;
      var name = guest.get("name").toLowerCase();
      keep = (name.indexOf(filterCriteria) > -1);
      keep &= _.contains(statusCriteria, guest.get("status"));
      return keep;
    });
    this.filteredCollection = new PBJ.Models.GuestList(filteredArray);
    this.listView = new PBJ.Views.GuestListView({
      collection: this.filteredCollection
    });
    this.guestListContainer.show(this.listView);
  },
  onGuestCountClick: function(e) {
    e.preventDefault();
    var target = $(e.currentTarget);
    var el = target.parent('.guestCount').andSelf();
    el.toggleClass('guestCountActive');
    this.createFilteredList();
  },
  getSelectedStatusCriteria: function() {
    var statuses = [];
    statuses.push(null); //always show non-invited guests (for now).
    this.$('.guestCountActive').each(function(index, el) {
      var status = $(this).attr('data-id');
      statuses.push(status);
    });
    return statuses;
  },
  getIsOrganizer: function() {
    if (this.model && this.model.get("currentGuest")) {
      return this.model.get("currentGuest").get("isOrganizer");
    }
    return false;
  },
  onItemHover: function(e) {
    if (this.getIsOrganizer()) {
      var target = $(e.currentTarget);
      var el = target.parent('.guestItem').andSelf();
      if (e.type == "mouseover" || e.type == "mouseenter") {
        el.toggleClass('guestover', true);
      }
      else if (e.type == "mouseout" || e.type == "mouseleave") {
        el.toggleClass('guestover', false);
      }
    }
  },
  onItemClick: function(e) {
    if (this.getIsOrganizer()) {
      var target = $(e.currentTarget);
      var el = target.parent('.guestItem').andSelf();
      el.toggleClass('guestselect');
    }
  },
  onSelectAllGuests: function(e) {
    e.preventDefault();
    this.$('.guestItem').addClass('guestselect');
  },
  onSelectNoneGuests: function(e) {
    e.preventDefault();
    this.$('.guestItem').removeClass('guestselect');
  },
  onSelectedActionsGo: function(e) {
    e.preventDefault();
    var self = this;
    var op = this.$('#selectedActions').val();
    var guests = [];
    this.$('.guestselect').each(function(index, el) {
      var guestid = $(this).parent('li').attr('data-id');
      var guest = self.filteredCollection.find(function(g) {
        return g.get("id") == guestid;
      });
      if (guest) {
        guests.push(guest);
      }
    });
    
    this.$('#selectedActions').val("");
    if (guests.length == 0) {
      return;
    }
      
    if (op == "sendinvite") {
      this.sendInvites(guests);
    }
    else if (op == "markin") {
      this.updateGuests(guests, "status", "in");
    }
    else if (op == "markout") {
      this.updateGuests(guests, "status", "out");
    }
    else if (op == "markpending") {
      this.updateGuests(guests, "status", "invited");
    }
    else if (op == "link") {
      this.linkGuests(guests, true);
    }
    else if (op == "unlink") {
      this.linkGuests(guests, false);
    }
    else if (op == "setorg") {
      this.updateGuests(guests, "isOrganizer", true);
    }
    else if (op == "unsetorg") {
      this.updateGuests(guests, "isOrganizer", false);
    }
    else if (op == "remove") {
      this.removeGuests(guests);
    }
  },
  
  sendInvites: function(guests) {
    var self = this;
    var guestids = [];
    for (var i = 0; i < guests.length; i++) {
      guestids.push(guests[i].get("id"));
    }
    guestids = guestids.join(",");
    var model = new PBJ.Models.GuestInviteIds();
    model.eventid = this.model.get("id");
    model.set("guestids", guestids);
    PBJ.infoBegin("sendinvites", "Sending invites...");
    Backbone.sync("create", model, {
      success: function() {
        self.model.get("guests").fetch({
          success: function() {
            PBJ.infoEnd("sendinvites", "Invites sent.");
          },
          error: function() {
            PBJ.errorEnd("sendinvites", "Error sending invites.");
          }
        });
      }
    });
  },
  
  updateGuestsStatus: function(guests, status) {
    this.updateGuests(guests, "status", status);
  },
  
  updateGuests: function(guests, fieldName, fieldValue) {
    var self = this;
    for (var i = 0; i < guests.length; i++) {
      var guest = guests[i];
      guest.set(fieldName, fieldValue);
    }
    var guestList = new PBJ.Models.GuestList(guests);
    guestList.eventid = this.model.get("id");
    var distinct = new Date().getTime();
    PBJ.infoBegin("updateguests"+distinct, "Updating guests...");
    Backbone.sync("update", guestList, {
      success: function() {
        self.model.get("guests").sort();
        PBJ.vent.trigger("guestupdate", guestList);
        PBJ.infoEnd("updateguests"+distinct, "Guests updated.");
      },
      error: function() {
        PBJ.errorEnd("updateguests"+distinct, "Error updating guests.");
      }
    });
  },
  
  removeGuests: function(guests) {
    var self = this;
    var guestids = [];
    var guestnames = [];
    for (var i = 0; i < guests.length; i++) {
      guestids.push(guests[i].get("id"));
      guestnames.push(guests[i].get("name"));
    }
    guestids = guestids.join(",");
    guestnames = guestnames.join(", ");
    var eventid = this.model.get("id");
    var url = PBJ.Models.apiBase + "/events/" + eventid + "/guests/" + guestids;
    PBJ.infoBegin("removeguests", "Removing guests " + guestnames + "...");
    Backbone.sync("delete", new PBJ.Models.Guest(), {
      url: url,
      success: function() {
        self.model.get("guests").remove(guests);
        self.render();
        PBJ.infoEnd("removeguests", "Guests " + guestnames + " removed.");
      },
      error: function() {
        PBJ.errorEnd("removeguests", "Error removing guests.");
      }
    });
  },
  linkGuests: function(guests, link) {
    var self = this;
    var guestids = [];
    for (var i = 0; i < guests.length; i++) {
      guestids.push(guests[i].get("id"));
    }
    guestids = guestids.join(",");
    var eventid = this.model.get("id");
    var service = ((link) ? "link" : "unlink");
    var linkactiontext = ((link) ? "Linking" : "Unlinking");
    var linkdonetext = ((link) ? "Linked" : "Unlinked");
    var url = PBJ.Models.apiBase + "/events/" + eventid + "/guests/" + service;
    var model = new Backbone.Model();
    model.set("guestids", guestids);
    PBJ.infoBegin("linkguests", linkactiontext + " guests...");
    Backbone.sync("create", model, {
      url: url,
      success: function(sent, response) {
        if (response && response.length > 0) {
          self.filteredCollection.update(response, {remove: false});
        }
        PBJ.infoEnd("linkguests", "Guests " + linkdonetext);
      },
      error: function() {
        PBJ.errorEnd("linkguests", "Error linking guests.");
      }
    });
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
  ckeditor: null,
  ui: {
    newMessage: "#newMessageField"
  },
  events: {
    "click #postNewMessage" : "postNewMessage"
  },
  initialize: function() {
    this.collection.bind("change", this.render);
    CKEDITOR.disableAutoInline = true;
  },
  onRender: function() {
    var el = this.ui.newMessage[0];
    if (el) {
      try {
        this.ckeditor = CKEDITOR.inline( el , {
          enterMode: CKEDITOR.ENTER_BR,
          toolbar: [
            { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', '-', 'RemoveFormat' ] },
            { name: 'links', items: [ 'Link', 'Unlink'] },
            { name: 'insert', items: [ 'Image', 'Smiley', 'SpecialChar' ] },
            { name: 'styles', items: [ 'Format', 'Font', 'FontSize' ] },
            { name: 'colors', items: [ 'TextColor', 'BGColor' ] }
          ]
        });
      } catch (e) {
        console.error(e);
      }
    }
  },
  onBeforeClose: function() {
    if (this.ckeditor) {
      this.ckeditor.destroy();
    }
  },
  postNewMessage: function(e) {
    e.preventDefault();
    var self = this;
    var message = this.ckeditor.getData();
    if (message == "") {
      return;
    }
    PBJ.infoBegin("postmessage", "Posting message...");
    this.collection.create({
      message: message,
      userid: PBJ.userSession.get("user").id,
      eventid: PBJ.controller.currentEvent.get("id")
    }, 
    {
      wait:true,
      success: function(m) {
        self.ckeditor.setData("");
        PBJ.infoEnd("postmessage", "Message posted.");
      },
      error: function() {
        PBJ.errorEnd("postmessage", "Error posting message.");
      }
    });
  }
});

Views.ModalDialogView = Backbone.Marionette.Layout.extend({
  template: "#template-modalDialogView",
  regions: {
    theDialog: "#theDialog"
  },
  dialogView: null,
  events: {
    "click #modalBackground": "onBackgroundClick",
    "click .okButton": "onOK",
    "click .cancelButton": "onCancel"
  },
  ui: {
    dialog: "#theDialog",
    background: "#modalBackground"
  },
  modalOpen: false,
  initialize: function() {
  },
  onRender: function() {
    if (this.dialogView) {
      this.theDialog.show(this.dialogView);
      this.modalOpen = true;
      this.applyOptions();
    }
    else {
      PBJ.hideModalDialog();
    }
  },
  setOptions: function(opts) {
    this.opts = opts;
  },
  applyOptions: function() {
    var opts = this.opts;
    if (opts) {
    }
    var self = this;
  },
  onBackgroundClick: function(e) {
    e.preventDefault();
    var target = $(e.target);
    var parents = target.parents('#theDialog');
    if ((target.parents('#theDialog').length == 0) 
        && (target.attr('id') != 'theDialog')) {
      PBJ.hideModalDialog();
    }
  },
  closeDialog: function() {
    if (!this.modalOpen) {
      return;
    }
    this.theDialog.close();
    this.dialogView = null;
    this.opts = null;
    this.modalOpen = false;
  },
  onOK: function() {
    if (this.dialogView.onOk) {
      if (this.dialogView.onOk() === false) {
        return;
      }
    }
    if (this.opts.onOk) {
      if (this.opts.onOk(this.dialogView) === false) {
        return;
      }
    }
    PBJ.hideModalDialog();
  },
  onCancel: function() {
    if (this.dialogView.onCancel) {
      this.dialogView.onCancel();
    }
    if (this.opts.onCancel) {
      this.opts.onCancel(this.dialogView);
    }
    PBJ.hideModalDialog();
  }
});

});