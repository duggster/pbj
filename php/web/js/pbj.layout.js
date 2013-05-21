PBJ.module("Layout", function(Layout, App, Backbone, Marionette, $, _){
  
  Layout.EventDetailLayout = Backbone.Marionette.Layout.extend({
    template: "#template-eventLayout",
    
    regions: {
      eventTitle: "#eventTitle",
      guestList: "#guestListSection",
      discussion: "#discussionSection",
      eventMain: "#eventMain",
      moduleFooter: "#moduleFooter"
    },
    
    events: {
      "click .module-min" : "setClickedEventWebModule"
    },
    
    initialize: function() {
      //Different colors for different guests
      this.guestColors = {};
      _.extend(this.guestColors, Backbone.Events);
      this.setGuestColors();
      this.model.get("guests").on("reset add", function() {
        this.setGuestColors();
      }, this);
      
      this.loadEventWebModules();
      this.loadMessageList();
    },
    
    onRender: function() {
      this.showEventTitle();
      this.showGuestList();
      this.showDiscussion();
      this.showEventWebModuleList();
    },
    
    setGuestColors: function() {
      var guests = this.model.get("guests");
      var hue = 0;
      if (guests) {
        guests.forEach(function(guest) {
          this.guestColors[guest.get("userid")] = hsvToHex(hue, 100, 100);
          hue += 360 / guests.length;
        }, this);
        this.guestColors.trigger("reset");
      }
    },
    
    showEventTitle: function() {
      var view = new PBJ.Views.EventTitleView({
        model: this.model
      });
      
      view.model.bind("change", function() {
        view.render();
      }, this);
      
      this.eventTitle.show(view);
    },
    
    showEventMain: function(view) {
      this.eventMain.show(view);
    },
    
    /****** Modules Start ***************/
    currentEventWebModule: null,
    
    loadEventWebModules: function() {
      var self = this;
      this.model.get("eventModules").on("reset", function(coll) {
        if (coll) {
          coll.forEach(function(module) {
            module.bind("maximize", function() {
              self.showEventMain(module.getController().maxViewInst);
            });
            module.bind("minimize", function() {
              self.showEventMain(null);
            });
          });
          var defaultModule = coll.at(0);
          if (defaultModule) {
            this.setCurrentEventWebModule(defaultModule.get("id"));
          }
        }
      }, this);
      this.model.fetchEventModules();
    },
    
    showEventWebModuleList: function() {
      this.moduleFooter.show(new PBJ.Views.EventWebModuleListView({
        collection: this.model.get("eventModules")
      }));
      this.setCurrentEventWebModule((this.currentEventWebModule)?this.currentEventWebModule.get("id"):null);
    },
    
    setClickedEventWebModule: function(e) {
      var target = $(e.target);
      var moduleid = target.parent('.module-min').andSelf().attr('data-id');
      this.setCurrentEventWebModule(moduleid);
    },
    
    setCurrentEventWebModule: function(moduleid) {
      if (moduleid != null) {
        this.currentEventWebModule = this.model.get("eventModules").get(moduleid);
        this.currentEventWebModule.getController().initialize();
        this.currentEventWebModule.getController().maximize();
      }
    },
    /******* Modules End **********/
    
    showGuestList: function() {
      var guestListView = new PBJ.Views.GuestListView({
        collection: this.model.get("guests")
      });
      
      this.guestList.show(guestListView);
    },
    
    /**** Message List Start ********/
    loadMessageList: function() {
      var self = this;
      this.messageList = new PBJ.Models.EventMessageList();
      
      this.messageList.on("reset add", function() {
        this.assignGuestColors();
      }, this);
      
      this.guestColors.on("reset", function() {
        this.assignGuestColors();
      }, this);
      this.assignGuestColors();
      
      PBJ.infoBegin("discussion", "Loading discussion...", "event"+this.model.get("id"));
      this.messageList.fetch({
        data: $.param({ event: this.model.get("id")}),
        success: function() {
          PBJ.infoEnd("discussion", "Discussion loaded.", "event"+self.model.get("id"));
        }
      });
    },
    
    assignGuestColors: function() {
      this.messageList.forEach(function(message) {
        if (this.guestColors && this.guestColors[message.get("userid")]) {
          message.set("assignedColor", this.guestColors[message.get("userid")]);
        }
      }, this);
      this.messageList.trigger("assignedColors");
    },
    
    showDiscussion: function() {
      var eventMessageListView = new PBJ.Views.EventMessageListView({
        collection: this.messageList
      });
      this.messageList.bind("change:user", function() {
        eventMessageListView.render();
      });
      this.messageList.on("assignedColors", function() {
        eventMessageListView.render();
      });
      this.discussion.show(eventMessageListView);
    }
  });
  /**** Message List End ********/
  
});