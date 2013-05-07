PBJ.module("Layout", function(Layout, App, Backbone, Marionette, $, _){
  
  Layout.EventDetailLayout = Backbone.Marionette.Layout.extend({
    template: "#template-eventLayout",
    
    regions: {
      eventTitle: "#eventTitle",
      guestList: "#guestList",
      discussion: "#discussion",
      eventMain: "#eventMain",
      moduleFooter: "#moduleFooter"
    },
    
    events: {
      "click .module-min" : "setClickedEventWebModule"
    },
    
    initialize: function() {
      this.model.bind("change", function() {
        //this.render();
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
    eventWebModuleList: null,
    
    loadEventWebModules: function() {
      var self = this;
      PBJ.infoBegin("modules", "Loading event modules...", "event"+this.model.get("id"));
      this.eventWebModuleList = new PBJ.Models.EventWebModuleList();
      this.eventWebModuleList.eventid = this.model.get("id");
      this.eventWebModuleList.bind("add", function(module) {
        self.initEventWebModule(module);
      });
      this.eventWebModuleList.bind("reset", function() {
        for (var i = 0; i < self.eventWebModuleList.length; i++) {
          self.initEventWebModule(self.eventWebModuleList.at(i));
        }
        var defaultModule = self.eventWebModuleList.at(0);
        if (defaultModule) {
          self.setCurrentEventWebModule(defaultModule.get("id"));
        }
      });
      this.eventWebModuleList.fetch({
        success: function() {
          PBJ.infoEnd("modules", "", "event"+self.model.get("id"));
        }
      });
    },
    
    initEventWebModule: function(module) {
      if (module.getController() && !module.getController().event) {
        var self = this;
        var controller = module.getController();
        controller.event = this.model;
        controller.initialize();
        module.bind("maximize", function() {
          self.showEventMain(module.getController().maxViewInst);
        });
        module.bind("minimize", function() {
          self.showEventMain(null);
        });
      }
    },
    
    showEventWebModuleList: function() {
      this.moduleFooter.show(new PBJ.Views.EventWebModuleListView({
        collection: this.eventWebModuleList
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
        this.currentEventWebModule = this.eventWebModuleList.get(moduleid);
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
      PBJ.infoBegin("discussion", "Loading discussion...", "event"+this.model.get("id"));
      this.messageList.fetch({
        data: $.param({ event: this.model.get("id")}),
        success: function() {
          PBJ.infoEnd("discussion", "", "event"+self.model.get("id"));
        }
      });
    },
    
    showDiscussion: function() {
      var eventMessageListView = new PBJ.Views.EventMessageListView({
        collection: this.messageList
      });
      this.messageList.bind("change:user", function() {
        eventMessageListView.render();
      });
      this.discussion.show(eventMessageListView);
    }
  });
  /**** Message List End ********/
  
});