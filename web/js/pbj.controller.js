PBJ.module("Controller", function(Controller, App, Backbone, Marionette, $, _){

  Controller.Router = Marionette.AppRouter.extend({
    appRoutes : {
      "": "home",
      "login": "login",
      "event/:eventid": "event",
      "create": "createEvent"
    }
  });
  
  Controller.Controller = function(){
    var self = this;
    
    PBJ.vent.on("user:loginsuccess", function() {
      self.loggedIn = true;
    });
    PBJ.vent.on("user:logout", function() {
      self.loggedIn = false;
    });
    PBJ.vent.on("controller:currentEventLoaded", function(event) {
      self.currentEvent = event;
      self.currentEvent.on("change:currentGuest", function(event) {
        self.currentGuest = event.currentGuest;
      });
      PBJ.vent.trigger("notify", "Event '" + event.get("title") + "' loaded.");
    });
    
  };

  _.extend(Controller.Controller.prototype, {
    
    checkLogin: function() {
      if (!this.loggedIn) {
        Backbone.history.navigate("login", true);
        return false;
      }
      return true;
    },
    
    login: function() {
      if (!this.checkLogin()) {
        PBJ.headerRegion.show(new PBJ.Views.LoginHeaderView({model: null}));
        PBJ.mainRegion.show(new PBJ.Views.LoginMainView({model: null}));
      } else {
        Backbone.history.navigate("", true);
      }
    },
    
    home: function() {
      if (this.checkLogin()) {
        PBJ.infoBegin("myevents", "Loading Events...");
        this.eventList = new PBJ.Models.EventList();
        PBJ.headerRegion.show(new PBJ.Views.HeaderView({model: PBJ.userSession}));
        PBJ.mainRegion.show(new PBJ.Views.ListView({
          collection : this.eventList
        }));
        this.eventList.fetch({
          success: function() {
            PBJ.infoEnd("myevents", "Events loaded.");
          }
        });
      }
    },
    
    event: function(eventid) {
      if (this.checkLogin()) {
        PBJ.infoBegin("event"+eventid, "Loading event...");
        PBJ.headerRegion.show(new PBJ.Views.HeaderView({model: PBJ.userSession}));
        //TODO pull event from this.eventList if exists
        var selectedEvent = new PBJ.Models.Event({
          id: eventid,
          fetchGuests:true
        });
        
        var eventLayout = new PBJ.Layout.EventDetailLayout({
          model: selectedEvent
        });
        var self = this;
        
        
        selectedEvent.fetch({
          success: function() {
            PBJ.vent.trigger("controller:currentEventLoaded", selectedEvent);
            eventLayout.render();
            PBJ.infoEnd("event"+selectedEvent.id, "'"+selectedEvent.get("title")+"' loaded.");
          }
        });
        
        PBJ.mainRegion.show(eventLayout);
      }
    },
    
    createEvent: function() {
      if (this.checkLogin()) {
        PBJ.headerRegion.show(new PBJ.Views.HeaderView({model: PBJ.userSession}));
        PBJ.mainRegion.show(new PBJ.Views.CreateEventView());
      }
    }
  
  });
  
  Controller.addInitializer(function(){
    PBJ.controller = new Controller.Controller();
    PBJ.router = new Controller.Router({
      controller: PBJ.controller
    });
  });

});
