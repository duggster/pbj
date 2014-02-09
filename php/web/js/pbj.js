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

PBJ.notifyTimer = {handle: null, inprogress: {}};
PBJ.setupNotifications = function() {
  PBJ.notificationList = new PBJ.Models.UserNotificationList();
  var notificationListView = new PBJ.Views.NotificationListView({
    collection: PBJ.notificationList
  });
  var displayNotifications = function(model) {
    var name = (model && model.get && model.get("name")) ? model.get("name") : "";
    var stage = (model && model.get && model.get("stage")) ? model.get("stage") : "";
    if (stage == "begin") {
      if (name != "" && !PBJ.notifyTimer.inprogress[name]) {
        PBJ.notifyTimer.inprogress[name] = model;
        window.clearTimeout(PBJ.notifyTimer.handle);
      }
    }
    if (stage == "end") {
      if (name != "" && PBJ.notifyTimer.inprogress[name]) {
        delete PBJ.notifyTimer.inprogress[name];
      }
      var size = 0, key;
      for (key in PBJ.notifyTimer.inprogress) {
          if (PBJ.notifyTimer.inprogress.hasOwnProperty(key)) size++;
      }
      if (size == 0) {
        PBJ.notifyTimer.handle = window.setTimeout(function() {
          notificationListView.minimizeMessages();
        }, 1500);
      }
    }
    notificationListView.render();
    notificationListView.showMessages();
  };
  PBJ.notificationList.on({
    "add remove change change:childNotifications" : function(m) { displayNotifications(m); }
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
      if (parent) {
        parent.trigger("change:childNotifications", n, opts, parent.get("childNotifications"));
      }
    } else {
      if (!opts.message || opts.message == "") {
      //  list.remove(n);
      }
      else {
        n.set(opts);
        if (parent) {
          parent.trigger("change:childNotifications", n, opts, parent.get("childNotifications"));
        }
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

/**
 * http://snipplr.com/view/14590/hsv-to-rgb/
 * HSV to RGB color conversion
 *
 * H runs from 0 to 360 degrees
 * S and V run from 0 to 100
 * 
 * Ported from the excellent java algorithm by Eugene Vishnevsky at:
 * http://www.cs.rit.edu/~ncs/color/t_convert.html
 */
function hsvToHex(h, s, v) {
	var r, g, b;
	var i;
	var f, p, q, t;
	
	// Make sure our arguments stay in-range
	h = Math.max(0, Math.min(360, h));
	s = Math.max(0, Math.min(100, s));
	v = Math.max(0, Math.min(100, v));
	
	// We accept saturation and value arguments from 0 to 100 because that's
	// how Photoshop represents those values. Internally, however, the
	// saturation and value are calculated from a range of 0 to 1. We make
	// That conversion here.
	s /= 100;
	v /= 100;
	
	if(s == 0) {
		// Achromatic (grey)
		r = g = b = v;
		return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
	}
	
	h /= 60; // sector 0 to 5
	i = Math.floor(h);
	f = h - i; // factorial part of h
	p = v * (1 - s);
	q = v * (1 - s * f);
	t = v * (1 - s * (1 - f));

	switch(i) {
		case 0:
			r = v;
			g = t;
			b = p;
			break;
			
		case 1:
			r = q;
			g = v;
			b = p;
			break;
			
		case 2:
			r = p;
			g = v;
			b = t;
			break;
			
		case 3:
			r = p;
			g = q;
			b = v;
			break;
			
		case 4:
			r = t;
			g = p;
			b = v;
			break;
			
		default: // case 5:
			r = v;
			g = p;
			b = q;
	}
	
	r = Math.round(r * 255);
  g = Math.round(g * 255);
  b = Math.round(b * 255);
  return rgbToHex(r,g,b);
}

function componentToHex(c) {
    var hex = c.toString(16);
    return hex.length == 1 ? "0" + hex : hex;
}

function rgbToHex(r, g, b) {
    return "#" + componentToHex(r) + componentToHex(g) + componentToHex(b);
}
