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
    }
  });
  
  Models.EventWebModuleList = Backbone.Collection.extend({
    model: Models.EventWebModule,
    url: function() {
      return Models.apiBase + '/events/' + this.eventid + '/modules';
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
  
  Models.Event = Backbone.Model.extend({
    urlRoot: Models.apiBase + '/events',
    defaults: {
      title: "None",
      htmlDescription: "",
      currentGuest: null,
      fetchGuests: false //set to true to automatically fetch all guests from server
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
      });
      console.log("initialize Event:", this);
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
        PBJ.infoBegin("eventguests", "Loading guests...", "event"+this.id);
        guests.fetch({
          url: guestsRef.ref, //follow HATEOS principles by using the URL given by the resource
          success: function() {
            PBJ.infoEnd("eventguests", "", "event"+self.id);
          }
        });
      }
    },
    toJSON: function() {
      var json = Backbone.Model.prototype.toJSON.call(this);
      json = _.omit(json, 'guests', 'fetchGuests');
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
      //user: new Models.User(),
      message: ""
    },
    initialize: function() {
      
    },
    fetchUser: function() {
      var u = new PBJ.Models.User();
      var ref = this.get("userRef").ref;
      u.bind("change", function(arg) {
        this.trigger("change:user", u); //Backbone doesn't fire events for nested sub-objects, so we have to do that ourselves
      }, this);
      this.set("user", u);
      u.fetch({
        url: ref //follow HATEOS principles by using the url provided by the resource
      });
    }
  });
  
  Models.EventMessageList = Backbone.Collection.extend({
    model: Models.EventMessage,
    url: Models.apiBase + '/eventMessages',
    
    initialize: function() {
      var self = this;
      this.bind("reset", function() {
        self.each(function(m) {
          //m.fetchUser();
        });
      });
      this.bind("add", function(added) {
        //added.fetchUser();
      });
    }
  });

});