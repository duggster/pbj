var PBJ = new Backbone.Marionette.Application();

PBJ.addRegions({
  headerRegion  : '#appHeader',
  mainRegion    : '#main',
  messageRegion : '#messageRegion'
});

PBJ.on("initialize:after", function(options){
  PBJ.setupNotifications();
  
  if (options.authId && options.authId != "") {
    PBJ.login(options.authId);
  }
  
  $(document).ajaxError( function(e, xhr, options){
    if (xhr.status == 401) {
      console.log("ajax error", xhr);
      PBJ.logout();
    }
  });
  
  Backbone.history.start();
});

PBJ.login = function(googleId) {
  var userSession = new PBJ.Models.UserSession();
  userSession.set("googleId", googleId);

  userSession.save({},{
    success: function(data) {
      PBJ.userSession = data;
      console.log("User logged in:", PBJ.userSession);
      PBJ.vent.trigger("user:loginsuccess", PBJ.userSession);
      Backbone.history.navigate("", true);
    }
  });
};

PBJ.logout = function() {
  if (PBJ.userSession) {
    PBJ.userSession.destroy({
      success: function() {
        PBJ.vent.trigger("user:logout", PBJ.userSession);
        Backbone.history.navigate("login", true);
      }
    });
  }
};

PBJ.notifyTimerHandle = null;
PBJ.setupNotifications = function() {
  PBJ.notificationList = new PBJ.Models.UserNotificationList();
  var notificationListView = new PBJ.Views.NotificationListView({
    collection: PBJ.notificationList
  });
  PBJ.notificationList.on("all", function(e, changed) {
    notificationListView.showMessages();
    window.clearTimeout(PBJ.notifyTimerHandle);
    if (changed && changed.get("stage") == "end") {
      PBJ.notifyTimerHandle = window.setTimeout(function() {
        notificationListView.minimizeMessages();
      }, 1500);
    }
    notificationListView.render();
  });
  PBJ.messageRegion.show(notificationListView);
  notificationListView.hideMessages();
};

PBJ.infoBegin = function(name, message, parent) {
  opts = {
    name: name,
    parent: parent,
    message: message,
    stage: "begin"
  };
  PBJ.info(opts);
};

PBJ.infoEnd = function(name, message, parent) {
  opts = {
    name: name,
    parent: parent,
    message: message,
    stage: "end"
  };
  PBJ.info(opts);
};

PBJ.info = function(opts) {
  opts.type = "info";
  PBJ.notify(opts);
};

PBJ.notify = function(opts) {
  /*PBJ.notify({
          name: "home",
          type: "info",
          scope: "route",
          stage: "begin",
          message: "Loading Events..."
        });
  */
  
  if (opts) {
    opts.timestamp = Date.now();
    var parent = PBJ.notificationList.find(function(item) {
      return (opts.parent == item.get("name"));
    });
    var list = PBJ.notificationList;
    if (parent) {
      if (!parent.get("childNotifications")) {
        parent.set("childNotifications", new PBJ.Models.UserNotificationList());
      }
      list = parent.get("childNotifications");
    }
    var n = list.find(function(item) {
      return (item.get("stage") != "end" && opts.name == item.get("name"));
    });
    if (!n) {
      n = new PBJ.Models.UserNotification(opts);
      list.add(n, {at:0});
    } else {
      if (!opts.message || opts.message == "") {
        list.remove(n);
      }
      else {
        n.set(opts);
      }
    }
    //console.log("NOTIFY:", opts.name+"-"+opts.stage, n);
  }
};

//.extend() functionality stolen from http://blog.usefunnel.com/2011/03/js-inheritance-with-backbone/
(function () {
    "use strict";

    var Toolbox = window.Toolbox = {};

    // `ctor` and `inherits` are from Backbone (with some modifications):
    // http://documentcloud.github.com/backbone/

    // Shared empty constructor function to aid in prototype-chain creation.
    var ctor = function () {};

    // Helper function to correctly set up the prototype chain, for subclasses.
    // Similar to `goog.inherits`, but uses a hash of prototype properties and
    // class properties to be extended.
    var inherits = function (parent, protoProps, staticProps) {
        var child;

        // The constructor function for the new subclass is either defined by you
        // (the "constructor" property in your `extend` definition), or defaulted
        // by us to simply call `super()`.
        if (protoProps && protoProps.hasOwnProperty('constructor')) {
            child = protoProps.constructor;
        } else {
            child = function () { return parent.apply(this, arguments); };
        }

        // Inherit class (static) properties from parent.
        _.extend(child, parent);

        // Set the prototype chain to inherit from `parent`, without calling
        // `parent`'s constructor function.
        ctor.prototype = parent.prototype;
        child.prototype = new ctor();

        // Add prototype properties (instance properties) to the subclass,
        // if supplied.
        if (protoProps) _.extend(child.prototype, protoProps);

        // Add static properties to the constructor function, if supplied.
        if (staticProps) _.extend(child, staticProps);

        // Correctly set child's `prototype.constructor`.
        child.prototype.constructor = child;

        // Set a convenience property in case the parent's prototype is needed later.
        child.__super__ = parent.prototype;

        return child;
    };

    // Self-propagating extend function.
    // Create a new class that inherits from the class found in the `this` context object.
    // This function is meant to be called in the context of a constructor function.
    function extendThis(protoProps, staticProps) {
        var child = inherits(this, protoProps, staticProps);
        child.extend = extendThis;
        return child;
    }

    // A primitive base class for creating subclasses.
    // All subclasses will have the `extend` function.
    // Example:
    //     var MyClass = Toolbox.Base.extend({
    //         someProp: 'My property value',
    //         someMethod: function () { ... }
    //     });
    //     var instance = new MyClass();
    Toolbox.Base = function () {}
    Toolbox.Base.extend = extendThis;
})();

PBJ.EventWebModuleController = Toolbox.Base.extend({
  maxView: null,
  maxViewInst: null,
  
  collection: null,
  model: null,
  
  module: null,
  
  debug: false,
  
  log: function(msg) {
    if (this.debug) {
      if (this.module) {
        console.log("Module [" + this.module.get("id") + "] " + this.module.get("title") + ":", msg, this);
      }
      else {
        console.log("Module unknown:", msg);
      }
    }
  },
  
  initialize: function() {
    this.log("initialize");
  },
  
  minimize: function() {
    this.module.trigger("minimize");
  },
  
  maximize: function() {
    this.log("maximize");
    if (!this.maxView) {
      this.log("No maximized view set.");
      this.minimize();
      return;
    }
    
    var viewInst = null;
    if (this.collection) {
      viewInst = new this.maxView({
        collection: this.collection
      });
    }
    else {
      viewInst = new this.maxView({
        model: this.model
      });
    }
    viewInst.controller = this;
    this.maxViewInst = viewInst;
    this.module.trigger("maximize");
  }
});
