PBJ.module("Models", function(Models, App, Backbone, Marionette, $, _){

  Models.apiBase = '../api/slim.php';
  
  Models.UserSession = Backbone.Model.extend({
    urlRoot: Models.apiBase + '/session'
  });
  
  Models.UserNotification = Backbone.Model.extend({
    defaults: {
      message: ""
    }
  });
  
  Models.UserNotificationList = Backbone.Collection.extend({
    model: Models.UserNotification
  });
  
  Models.EventWebModule = Backbone.Model.extend({
    urlRoot: function() {
      return Models.apiBase + '/events/' + this.getController().event.get("id") + '/modules';
    },
    defaults: {
      controllerName: "",
      controller: null
    },
    initialize: function() {
    },
    getController: function() {
      if (!this.get("controller")) {
        var qualifiedName = "PBJ." + this.get("controllerName");
        var arr = qualifiedName.split(".");
        var obj = window;
        for (var i = 0; i < arr.length; i++) {
          obj = obj[arr[i]];
          if (!obj) {
            console.error("Could not find class for Module controller: " + qualifiedName);
            break;
          }
        }
        var inst = new obj();
        inst.module = this;
        this.set("controller", inst);
      }
      return this.get("controller");
    },
    initEventModule: function(event) {
      var self = this;
      var controller = this.getController();
      if (controller && !controller.event) {
        controller.event = event;
        //controller.initialize();
      }
    },
    toJSON: function() {
      var json = Backbone.Model.prototype.toJSON.call(this);
      json = _.omit(json, 'controller');
      return json;
    }
  });
  
  Models.EventWebModuleList = Backbone.Collection.extend({
    model: Models.EventWebModule,
    url: function() {
      return Models.apiBase + '/events/' + this.eventid + '/modules';
    },
    initialize: function() {
      var self = this;
      this.bind("add", function(module) {
        module.initEventModule(this.event);
      }, this);
      this.bind("reset", function() {
        this.forEach(function(module) {
          module.initEventModule(self.event);
        });
      });
    },
    comparator: function(module) {
      var order = parseInt(module.get("id"));
      if (!module.get("isEventDefault")) {
        order += 100;
      }
      return order;
    }
  });

  Models.User = Backbone.Model.extend({
    urlRoot: Models.apiBase + '/users',
    defaults: {
      name: ""
    }
  });
  
  Models.Guest = Backbone.Model.extend({
    urlRoot: Models.apiBase + '/guests',
    defaults: {
      isCurrentUser: false
    },
    initialize: function() {
    }
  });
  
  Models.GuestList = Backbone.Collection.extend({
    initialize: function() {
      this.statusOrder = {"null":4,"in":3,"invited":2,"out":1};
      this.setSortBy("status");
    },
    model: Models.Guest,
    url: function() {
      return Models.apiBase + '/events/' + this.eventid + '/guests';
    },
    setSortBy: function(s) {
      this.sortBy = s;
      if (!this.sortBy || this.sortBy == "" || this.sortBy == "status") {
        this.comparator = this.compareStatus;
      }
      else if (this.sortBy == "name") {
        this.comparator = this.compareName;
      }
      this.comparator = this.compareLink; //TODO: clean this up
    },
    compareLink: function(a, b) {
      alink = a.get("guestLinkId") || 0;
      blink = b.get("guestLinkId") || 0;
      var result = 0;
      var comp = blink - alink;
      if (alink == blink) {
        result = this.compareStatus(a, b);
      }
      else if (comp < 0) {
        result = -1;
      }
      else if (comp > 0) {
        result = 1;
      }
      return result;
    },
    compareStatus: function(a, b) {
      astatus = a.get("status") || "null";
      bstatus = b.get("status") || "null";
      var comp = this.statusOrder[astatus] - this.statusOrder[bstatus];
      var result = 0;
      result = ((comp == 0) ? 0 : ((comp > 0) ? -1 : 1));
      if (result == 0) {
        result = this.compareName(a, b);
      }
      return result;
    },
    compareName: function(a, b) {
      a = a.get("name") || "";
      b = b.get("name") || "";
      a = a.toLowerCase();
      b = b.toLowerCase();
      var result = 0;
      if (a < b) {
        result = -1;
      }
      if (a > b) {
        result = 1;
      }
      return result;
    }
  });
  
  Models.GuestHandles = Backbone.Model.extend({
    urlRoot: function() {
      return Models.apiBase + '/events/' + this.eventid + '/guests/handles';
    }
  });
  
  Models.GuestInviteIds = Backbone.Model.extend({
    urlRoot: function() {
      return Models.apiBase + '/events/' + this.eventid + '/guests/sendinvites';
    }
  });
  
  Models.EventMetadata = Backbone.Model.extend({
    urlRoot: function() {
      return Models.apiBase + '/events/' + this.eventid + '/eventMetadata';
    }
  });
  
  Models.Event = Backbone.Model.extend({
    urlRoot: Models.apiBase + '/events',
    defaults: {
      title: "None",
      htmlDescription: "",
      emailAddress: "",
      currentGuest: null,
      fetchGuests: false, //set to true to automatically fetch all guests from server,
      eventModules: null,
      eventMessages: null,
      eventMetadata: new Models.EventMetadata(),
      when: "",
      where: ""
    },
    initialize: function() {
      var self = this;
      this.set("guests", new Models.GuestList());
      //if the fetchGuests flag is true, immediately after the Models.Event object is fetched (via "change" event), then fetch all the guests too
      if (this.get("fetchGuests")) {
        this.bind("change", this._fetchGuests, this);
      }
      var guests = this.get("guests");
      guests.bind("reset", function() {
        var userid = PBJ.userSession.get("user").id;
        self.set("currentGuest", guests.find(function(guest) {
          return guest.get("userid") == userid;
        }));
        self.get("currentGuest").set("isCurrentUser", true);
        guests.eventid = self.get("id");
      });
      
      this.set("eventModules", new Models.EventWebModuleList());
      this.get("eventModules").event = this;
      
      this.set("eventMessages", new Models.EventMessageList());
      
      PBJ.vent.on("guestupdate", function() {
        this.fetchEventMessages();
        this.fetchEventMetadata();
      }, this);
      
      console.log("initialize Event:", this);
    },
    //Override fetch so that the eventMetadata subobject can be converted before anyone else tries to access it
    fetch: function(options) {
      if (!options) {
        options = {};
      }
      var success = options.success;
      options.success = function(model, resp, options) {
        //Transform the metadata subobject into a Backbone model so it can be fetched asynchronously later
        model.set("eventMetadata", new Models.EventMetadata(model.get("eventMetadata")));
        if (success) success(model, resp, options);
      };
      return Backbone.Model.prototype.fetch.call(this, options);
    },
    save: function(attrs,options) {
      if (!options) {
        options = {};
      }
      var success = options.success;
      options.success = function(model, resp, options) {
        //Transform the metadata subobject into a Backbone model so it can be fetched asynchronously later
        model.set("eventMetadata", new Models.EventMetadata(model.get("eventMetadata")));
        if (success) success(model, resp, options);
      };
      return Backbone.Model.prototype.save.call(this, attrs, options);
    },
    _fetchGuests: function() {
      if (this.hasChanged("guestsRef")) {
        this.fetchGuests();
      }
    },
    fetchGuests: function() {
      var self = this;
      var guests = this.get("guests");
      var guestsRef = this.get("guestsRef");
      if (guestsRef) {
        PBJ.infoBegin("eventguests", "Loading guests...");
        guests.fetch({
          url: guestsRef.ref, //follow HATEOS principles by using the URL given by the resource
          success: function() {
            PBJ.infoEnd("eventguests", "Guests loaded.");
          },
          error: function() {
            PBJ.errorEnd("eventguests", "Error loading guests");
          }
        });
      }
    },
    fetchEventModules: function() {
      var self = this;
      var eventid = this.get("id");
      if (!eventid) {
        return;
      }
      PBJ.infoBegin("modules", "Loading event modules...");
      this.get("eventModules").eventid = eventid;
      this.get("eventModules").fetch({
        success: function() {
          PBJ.infoEnd("modules", "Event modules loaded.");
        },
        error: function() {
          PBJ.errorEnd("modules", "Error loading modules.");
        }
      });
    },
    fetchEventMessages: function() {
      var self = this;
      PBJ.infoBegin("discussion", "Loading discussion...");
      this.get("eventMessages").fetch({
        data: $.param({ event: this.get("id")}),
        success: function() {
          PBJ.infoEnd("discussion", "Discussion loaded.");
        },
        error: function() {
          PBJ.errorEnd("discussion", "Error loading discussion.");
        }
      });
    },
    fetchEventMetadata: function() {
      var self = this;
      PBJ.infoBegin("eventMetadata", "Loading event details...");
      this.get("eventMetadata").eventid = this.get("id");
      this.get("eventMetadata").fetch({
        success: function(model) {
          //unset and set so that the "change" event is triggered
          self.unset("eventMetadata", {silent: true});
          self.set("eventMetadata", model); 
          PBJ.infoEnd("eventMetadata", "Event details loaded.");
        },
        error: function() {
          PBJ.errorEnd("eventMetadata", "Error loading event details.");
        }
      });
    },
    toJSON: function() {
      var json = Backbone.Model.prototype.toJSON.call(this);
      json = _.omit(json, 'guests', 'fetchGuests', 'eventModules', 'eventMessages');
      return json;
    }
  });
  
  Models.EventList = Backbone.Collection.extend({
    model: Models.Event,
    url: Models.apiBase + '/events'
  });
  
  Models.EventMessage = Backbone.Model.extend({
    urlRoot: Models.apiBase + '/eventMessages',
    defaults: {
      message: "",
      assignedColor: "#CCC"
    },
    initialize: function() {
    }
  });
  
  Models.EventMessageList = Backbone.Collection.extend({
    model: Models.EventMessage,
    url: Models.apiBase + '/eventMessages',
    initialize: function() {
    },
    comparator: function(a,b) {
      var astamp = a.get("messageTimestamp");
      var bstamp = b.get("messageTimestamp");
      a = new Date(a.get("messageTimestamp").date).getTime();
      b = new Date(b.get("messageTimestamp").date).getTime();
      return ((a > b) ? -1 : 1);
    }
  });
});