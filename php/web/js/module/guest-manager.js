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
      var view = new GuestManagerViews.GuestManagerView({
        model: this.model
      });
      view.controller = this.controller;
      this.showMainRegion(view);
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
            PBJ.infoEnd("otherEvents", "Events loaded.");
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

  GuestManagerViews.GuestManagerView = Backbone.Marionette.ItemView.extend({
    template: "#template-guestManagerView",
    
    ui: {
      newGuestField : "#newGuestField"
    },
    
    events: {
      "keydown #newGuestField": "onKeyDown",
      "click #newGuestAction": "addNewGuest",
      "click #newGuestField": "onFocus"
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
      model.eventid = this.model.get("id");
      model.set("handles", handles);
      PBJ.infoBegin("addNewGuests", "Saving guests...");
      Backbone.sync("create", model, {
        success: function() {
          self.ui.newGuestField.text("");
          self.model.get("guests").fetch();
          PBJ.infoEnd("addNewGuests", "Guests saved.");
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

