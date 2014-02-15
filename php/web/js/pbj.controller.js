PBJ.module("Controller", function(Controller, App, Backbone, Marionette, $, _){

  Controller.Router = Marionette.AppRouter.extend({
    appRoutes : {
      "": "home",
      "login": "login",
      "event/:eventid": "event",
      "guest/:guestid/set-status/:status": "setGuestStatus",
      "create": "createEvent",
      "user/:userid": "userPage",
      "error": "errorPage"
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
    PBJ.vent.on("user:changed", function(user) {
      PBJ.userSession.save({user: user}, {
        success: function(data) {
          PBJ.userSession = data;
          PBJ.headerRegion.currentView.render();
          console.log("User Session Updated:", PBJ.userSession);
        }
      });
    });
    PBJ.vent.on("controller:currentEventLoaded", function(event) {
      self.currentEvent = event;
      self.currentEvent.on("change:currentGuest", function(event) {
        self.currentGuest = event.currentGuest;
      });
      
      PBJ.userSession.save({eventid: event.get("id")},{
        success: function(data) {
          PBJ.userSession = data;
          console.log("User Session Updated:", PBJ.userSession);
        }
      });
      
      PBJ.vent.on("event:ajaxerror", function() {
        
      });
      
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
          },
          error: function() {
            PBJ.errorEnd("myevents", "Error loading events.");
            PBJ.controller.errorPage();
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
          },
          error: function() {
            PBJ.errorEnd("event"+selectedEvent.id, "Error loading event.");
            PBJ.controller.errorPage();
          }
        });
        
        PBJ.mainRegion.show(eventLayout);
      }
    },
    
    errorPage: function() {
      var self = this;
      if (PBJ.userSession) {
        PBJ.headerRegion.show(new PBJ.Views.HeaderView({model: PBJ.userSession}));
      }
      var view = new PBJ.Views.EventErrorView({
        model: null
      });
      PBJ.mainRegion.show(view);
    },
    
    setGuestStatus: function(guestid, status) {
      if (this.checkLogin()) {
        PBJ.infoBegin("guest"+guestid, "Retrieving guest info...");
        var guest = new PBJ.Models.Guest({id: guestid});
        guest.fetch({
          success: function() {
            PBJ.infoEnd("guest"+guestid, "Guest retrieved.");
            if (guest.get("userid") == PBJ.userSession.get("user").id) {
              guest.set("status", status);
              PBJ.infoBegin("gueststatus"+guestid, "Saving status '" + status + "'...");
              guest.save({},{
                success: function() {
                  PBJ.infoEnd("gueststatus"+guestid, "Status '" + status + "' saved.");
                  //load event only after save finishes to avoid race condition
                  var eventid = guest.get("eventid");
                  Backbone.history.navigate("event/" + eventid, true);
                },
                error: function() {
                  PBJ.errorEnd("gueststatus"+guestid, "Error saving guest status.");
                  PBJ.controller.errorPage();
                }
              });
            }
            else {
              console.error("You do not have permission to update this guest's status.");
            }
          },
          error: function() {
            PBJ.errorEnd("guest"+guestid, "Error retrieving guest info.");
            PBJ.controller.errorPage();
          }
        });
      }
    },
    
    createEvent: function() {
      if (this.checkLogin()) {
        PBJ.headerRegion.show(new PBJ.Views.HeaderView({model: PBJ.userSession}));
        PBJ.mainRegion.show(new PBJ.Views.CreateEventView());
      }
    },
    
    userPage: function(userid) {
      if (this.checkLogin()) {
        if (userid == PBJ.userSession.get("user").id) {
          PBJ.headerRegion.show(new PBJ.Views.HeaderView({model: PBJ.userSession}));
          
          var user = new PBJ.Models.User({id: userid});
          var view = new PBJ.Views.UserPageView({
            model: user
          });
          PBJ.mainRegion.show(view);
          PBJ.infoBegin("loaduser", "Loading user...");
          user.fetch({
            success: function() {
              PBJ.infoEnd("loaduser", "User loaded.");
              view.render();
            },
            error: function() {
              PBJ.errorEnd("loaduser", "Error loading user.");
              PBJ.controller.errorPage();
            }
          });
        }
        else {
          
        }
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
