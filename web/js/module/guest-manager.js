PBJ.module("GuestManager", function(GuestManager, App, Backbone, Marionette, $, _){
  
  GuestManager.Controller = PBJ.EventWebModuleController.extend({
    
    initialize: function() {
      this.log("initialize");
      this.model = PBJ.controller.currentEvent;
      this.maxView = PBJ.GuestManagerViews.GuestManagerLayout;
    },
    
    showGuestManagerHome: function() {
      this.log("showGuestManagerHome");
      this.maxView = PBJ.GuestManagerViews.GuestManagerLayout;
      this.maximize();
    }
    
  });
  
});
PBJ.module("GuestManagerViews", function(GuestManagerViews, App, Backbone, Marionette, $, _){

  GuestManagerViews.GuestManagerLayout = Backbone.Marionette.Layout.extend({
    template: "#template-guestManagerLayoutView", 
    
    regions: {
      addGuestsFromEventsRegion: "#addGuestsFromEventsRegion",
      guestManagerMainRegion: "#guestManagerMainRegion"
    },
    
    events: {
      "click .otherEventsAction" : "showOtherEvents",
      "click .guestManagerHomeAction" : "showHomeClick"
    },
    
    initialize: function() {
      var self = this;
      PBJ.vent.on("GuestManager:selectOtherEvent", function(event) {
        self.showOtherEvent(event);
      });
    },
    
    onRender: function() {
      this.showHome();
    },
    
    showMainRegion: function(view) {
      this.guestManagerMainRegion.show(view);
      this.delegateEvents();
    },
    
    showHomeClick: function(e) {
      e.preventDefault();
      this.showHome();
    },
    
    showHome: function() {
      var guestListView = new GuestManagerViews.GuestManagerListView({
        collection: this.model.get("guests")
      });
      guestListView.controller = this.controller;
      this.showMainRegion(guestListView);
    },
    
    otherEventList: null,
    
    showOtherEvents: function(e) {
      e.preventDefault();
      var self = this;
      var currentEvent = this.model;
      if (!this.otherEventList) {
        this.otherEventList = new PBJ.Models.EventList();
        PBJ.infoBegin("otherEvents", "Loading events...");
        this.otherEventList.fetch({
          success: function(list) {
            //Find and remove the current event, we only want "other" events
            self.otherEventList.remove(list.find(function(event) {
              return event.get("id") == currentEvent.get("id");
            }));
            PBJ.infoEnd("otherEvents");
          }
        });
      }
      var otherEventsView = new GuestManagerViews.OtherEventsListView({
        collection: this.otherEventList
      });
      this.showMainRegion(otherEventsView);
    },
    
    showOtherEvent: function(event) {
      var self = this;
      
      var otherEventView = new GuestManagerViews.OtherEventGuestListView({
        collection: event.get("guests")
      });
      
      event.fetchGuests();
      event.get("guests").on("reset", function(otherGuests) {
        otherEventView.setExistingGuests(self.model.get("guests"));
      });
      
      this.showMainRegion(otherEventView);
    }
  });

  GuestManagerViews.GuestManagerItemView = Backbone.Marionette.ItemView.extend({
    tagName: 'li',
    template: "#template-guestManagerItemView",
    templateHelpers: {
      getStatus: function() {
        var str = "";
        switch(this.status) {
          case "in": 
            str = "Attending";
            break;
          case "invited":
            str = "Invited";
            break;
          case "out":
            str = "Not Attending";
            break;
          default:
            str = "Invite Unsent";
            break;
        }
        return str;
      },
      
      getRemoveGuestHtml: function() {
        var html = "";
        if (this.status == null) {
          html = "<a href='' class='removeGuest'>x</a>";
        }
        return html;
      },
      getInviteGuestHtml: function() {
        var html = "";
        if (this.status == null) {
          html = "<a href='' class='inviteGuest'>invite</a>";
        }
        return html;
      },
      getOrganizerHtml: function() {
        var html = "";
        if (this.isOrganizer === true) {
          html = "Organizer";
        }
        else {
          html = "";
        }
        return html;
      },
      getCommunicationHandleHtml: function() {
        var html = "&lt;" + this.communicationHandle + "&gt;";
        return html;
      }
    },
    
    onRender: function() {
      this.$el.addClass("ui-widget-content guestItem");
    },
    
    events: {
      "click .removeGuest" : "removeGuest"
    },
    
    removeGuest: function(e) {
      e.preventDefault();
      this.model.destroy();
    }
  });

  GuestManagerViews.GuestManagerListView = Backbone.Marionette.CompositeView.extend({
    template: "#template-guestManagerListView",
    itemViewContainer: "#guestManagerList",
    itemView: GuestManagerViews.GuestManagerItemView,
    
    initialize: function() {
      this.collection.bind("change", this.render);
    },
    
    ui: {
      newGuestField : "#newGuestField"
    },
    
    events: {
      "keydown #newGuestField": "onKeyDown",
      "click #newGuestAction": "addNewGuest",
      "click #newGuestField": "onFocus",
      "hover .guestItem": "onItemHover",
      "click .guestItem": "onItemClick",
      "click #selectedActionsAction" : "onSelectedActionsGo",
      "click #selectAllGuestsAction" : "onSelectAllGuests",
      "click #selectNoneGuestsAction" : "onSelectNoneGuests"
    },
    
    onRender: function() {
      /*this.$(this.itemViewContainer).selectable({
        selected: function( event, ui ) {
          console.log("Selected:", event, ui);
        }
      });*/
    },
    
    onFocus: function(e) {
      var doc = document;
      var element = this.ui.newGuestField[0];
      if (doc.body.createTextRange) {
        var range = document.body.createTextRange();
        range.moveToElementText(element);
        range.select();
      } else if (window.getSelection) {
        var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
      }
    },
    
    onItemHover: function(e) {
      var target = $(e.currentTarget);
      var el = target.parent('.guestItem').andSelf();
      if (e.type == "mouseover" || e.type == "mouseenter") {
        el.toggleClass('guestover', true);
      }
      else if (e.type == "mouseout" || e.type == "mouseleave") {
        el.toggleClass('guestover', false);
      }
    },
    
    onItemClick: function(e) {
      var target = $(e.currentTarget);
      var el = target.parent('.guestItem').andSelf();
      el.toggleClass('guestselect');
    },
    
    onKeyDown: function(e) {
      if (e.keyCode == 13) { //Enter key
        this.addNewGuest(e);
      }
    },
    
    addNewGuest: function(e) {
      e.preventDefault();
      var self = this;
      var handles = this.ui.newGuestField.text();
      var model = new PBJ.Models.GuestHandles();
      model.eventid = PBJ.controller.currentEvent.get("id");
      self.collection.eventid = model.eventid;
      model.set("handles", handles);
      PBJ.infoBegin("addNewGuests", "Saving guests...");
      Backbone.sync("create", model, {
        success: function() {
          self.ui.newGuestField.text("");
          self.collection.fetch();
          PBJ.infoEnd("addNewGuests", "Guests saved.");
        }
      });
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
      this.$('.guestselect .guestItemDataId').each(function(index, el) {
        var guestid = $(this).attr('data-id');
        var guest = self.collection.find(function(g) {
          return g.get("id") == guestid;
        });
        if (guest) {
          guests.push(guest);
        }
      });
        
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
      else if (op == "setorg") {
        this.updateGuests(guests, "isOrganizer", true);
      }
      else if (op == "unsetorg") {
        this.updateGuests(guests, "isOrganizer", false);
      }
      else if (op == "remove") {
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
      model.eventid = PBJ.controller.currentEvent.get("id");
      self.collection.eventid = model.eventid;
      model.set("guestids", guestids);
      Backbone.sync("create", model, {
        success: function() {
          self.collection.fetch();
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
      guestList.eventid = PBJ.controller.currentEvent.get("id");
      Backbone.sync("update", guestList, {
        success: function() {
          self.collection.sort();
        }
      });
    }
    
  });
  
  GuestManagerViews.OtherEventsItemView = Backbone.Marionette.ItemView.extend({
    tagName: "li",
    template: "#template-GuestManager-OtherEventsItemView",
    events: {
      "click .guestManager-selectEventAction" : "selectEvent"
    },
    selectEvent: function(e) {
      e.preventDefault();
      PBJ.vent.trigger("GuestManager:selectOtherEvent", this.model);
    }
  });
  
  GuestManagerViews.OtherEventsListView = Backbone.Marionette.CompositeView.extend({
    template: "#template-GuestManager-OtherEventsListView",
    itemView: PBJ.GuestManagerViews.OtherEventsItemView,
    itemViewContainer: "#otherEventsList"
  });
  
  GuestManagerViews.OtherEventGuestItemView = Backbone.Marionette.ItemView.extend({
    tagName: "option",
    template: "#template-GuestManager-OtherEventGuestItemView",
    
    attributes: function() {
      var attr = {};
      attr["value"] = this.model.get("communicationHandle");
      if (this.model.get("exists")) {
        attr["disabled"] = "disabled";
      }
      return attr;
    }
  });
  
  GuestManagerViews.OtherEventGuestListView = Backbone.Marionette.CompositeView.extend({
    template: "#template-GuestManager-OtherEventGuestListView",
    itemView: GuestManagerViews.OtherEventGuestItemView,
    itemViewContainer: "#otherEventGuestList",
    events: {
      "click .guestManagerAddOtherGuestsAction" : "addGuests"
    },
    ui: {
      otherEventGuestList: "#otherEventGuestList"
    },
    
    existingGuests: null,
    setExistingGuests: function(guests) {
      this.existingGuests = guests;
      this.collection.each(function(otherGuest) {
        if (otherGuest) {
          otherGuest.set("exists", false);
          var found = this.existingGuests.find(function(existingGuest) {
            if (existingGuest) {
              return otherGuest.get("userid") == existingGuest.get("userid");
            }
          });
          if (found) {
            otherGuest.set("exists", true);
          }
        }
      }, this);
      
      this.render();
    },

    addGuests: function(e) {
      e.preventDefault();
      var self = this;
      var handles = this.ui.otherEventGuestList.val();
      if (handles && handles.length > 0) {
        handles = handles.join(",");
        var model = new PBJ.Models.GuestHandles();
        model.eventid = PBJ.controller.currentEvent.get("id");
        model.set("handles", handles);
        Backbone.sync("create", model, {
          success: function() {
            PBJ.controller.currentEvent.once("change", function() {
              self.setExistingGuests(PBJ.controller.currentEvent.get("guests"));
            });
            PBJ.controller.currentEvent.fetchGuests();
          }
        });
      }
    }
  });

});

