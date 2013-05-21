PBJ.module("RichDescription", function(RichDescription, App, Backbone, Marionette, $, _){
  
  RichDescription.Controller = PBJ.EventWebModuleController.extend({
    
    initialize: function() {
      this.log("initialize");
      this.model = this.event;
      this.maxView = PBJ.RichDescriptionViews.EventDescriptionView;
    },
    
    setViewState: function() {
      this.log("setViewState");
      this.maxView = PBJ.RichDescriptionViews.EventDescriptionView;
      this.maximize();
    },
    
    setEditState: function() {
      this.log("setEditState");
      this.maxView = PBJ.RichDescriptionViews.EventDescriptionEditView;
      this.maximize();
    }
  });
  
});
PBJ.module("RichDescriptionViews", function(RichDescriptionViews, App, Backbone, Marionette, $, _){

  RichDescriptionViews.EventDescriptionView_Minimized = Backbone.Marionette.ItemView.extend({
    template: "#template-eventDescriptionViewMin"
  });

  RichDescriptionViews.EventDescriptionView = Backbone.Marionette.ItemView.extend({
    template: "#template-eventDescriptionView",
    
    events: {
      "click #editDescriptionAction" : "showEdit"
    },
    
    initialize: function() {
      this.model.on("change:currentGuest", function(event) {
        this.toggleActions();
      }, this);
    },
    
    onRender: function() {
      this.toggleActions();
    },
    
    toggleActions: function() {
      var editable = (this.model && this.model.get("currentGuest") && this.model.get("currentGuest").get("isOrganizer")) || false;
      this.$("#richDescriptionActionBar").toggle(editable);
    },  
    
    showEdit: function() {
      this.controller.setEditState();
      return false;
    }
  });

  RichDescriptionViews.EventDescriptionEditView = Backbone.Marionette.ItemView.extend({
    template: "#template-eventDescriptionEditView",
    
    events: {
      "click #cancelEditEventDescription" : "showEventDescription",
      "click #saveEditEventDescription" : "saveEventDescription"
    },
    
    ui: {
      title: "#editEventTitleField",
      editor: "#editDescription"
    },
    
    ckeditor: null,
    
    initialize: function() {
			CKEDITOR.disableAutoInline = true;
    },
    
    onShow: function() {
      try {
        this.ckeditor = CKEDITOR.inline( this.ui.editor[0] );
        //this.ckeditor.setData(this.model.get("htmlDescription"));
      } catch (e) {
        console.error(e);
      }
    },
    
    onBeforeClose: function() {
      this.ckeditor.destroy();
    },
    
    syncModel: function() {
      var html = this.ckeditor.getData();
      this.model.set("htmlDescription", html);
      
      var title = this.ui.title.val();
      this.model.set("title", title);
    },
    
    showEventDescription: function() {
      this.controller.setViewState();
      return false;
    },
    
    saveEventDescription: function() {
      var self = this;
      this.syncModel();
      PBJ.infoBegin("saveEventDesc", "Saving event description...");
      this.model.save({},{
        success: function() {
          self.showEventDescription();
          PBJ.infoEnd("saveEventDesc", "Event description saved.");
        }
      });
      
      return false;
    },
  });
});

