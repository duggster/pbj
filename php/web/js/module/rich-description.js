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
  
  RichDescriptionViews.showInfo = function(el, show) {
    if (el) {
      if (show) {
        el.find('#showInfoSection').show();
      }
      else {
        el.find('#showInfoSection').hide();
      }
    }
  };

  RichDescriptionViews.EventDescriptionView = Backbone.Marionette.ItemView.extend({
    template: "#template-eventDescriptionView",
    
    events: {
      "click #editDescriptionAction" : "showEdit",
      "click #broadcastDescriptionAction" : "broadcastDescription"
    },
    
    templateHelpers: {
    },
    
    initialize: function() {
      this.model.on("change:currentGuest", function(event) {
        this.toggleActions();
      }, this);
    },
    
    onRender: function() {
      this.toggleActions();
      RichDescriptionViews.showInfo(this.$el, this.model.get("isShowInfo"));
    },
    
    toggleActions: function() {
      var editable = (this.model && this.model.get("currentGuest") && this.model.get("currentGuest").get("isOrganizer")) || false;
      this.$("#richDescriptionActionBar").toggle(editable);
    },
    
    showEdit: function() {
      this.controller.setEditState();
      return false;
    },
    
    broadcastDescription: function(e) {
      e.preventDefault();
      if (confirm("Are you sure you want to broadcast this event description out to all participating or invited guests?")) {
        var eventid = this.model.get("id");
        var url = PBJ.Models.apiBase + "/events/" + eventid + "/broadcast";
        PBJ.infoBegin("broadcast", "Sending event description...");
        Backbone.sync("create", null, {
          url: url,
          success: function() {
            PBJ.infoEnd("broadcast", "Event description sent.");
          }
        });
      }
    }
  });

  RichDescriptionViews.EventDescriptionEditView = Backbone.Marionette.ItemView.extend({
    template: "#template-eventDescriptionEditView",
    
    events: {
      "click #cancelEditEventDescription" : "showEventDescription",
      "click #saveEditEventDescription" : "saveEventDescription",
      "click #editShowInfoField" : "onShowInfo"
    },
    
    ui: {
      title: "#editEventTitleField",
      when: "#editWhenField",
      where: "#editWhereField",
      isShowInfo: "#editShowInfoField",
      editor: "#editDescription"
    },
    
    ckeditor: null,
    
    initialize: function() {
			CKEDITOR.disableAutoInline = true;
    },
    
    templateHelpers: {
      getShowInfoChecked: function() {
        return ((this.isShowInfo)?"checked":"");
      },
      getInfoHtml: function() {
        return RichDescriptionViews.getInfoHtmlHelper(this);
      }
    },
    
    onShow: function() {
      RichDescriptionViews.showInfo(this.$el, this.model.get("isShowInfo"));
      try {
        this.ckeditor = CKEDITOR.inline( this.ui.editor[0] );
        //this.ckeditor.config.toolbarLocation = "bottom";
        //console.log(this.ckeditor.config);
      } catch (e) {
        console.error(e);
      }
    },
    
    onBeforeClose: function() {
      this.ckeditor.destroy();
    },
    
    onShowInfo: function(e) {
      var isShowInfo = this.ui.isShowInfo.prop('checked');
      RichDescriptionViews.showInfo(this.$el, isShowInfo);
    },
    
    syncModel: function() {
      var html = this.ckeditor.getData();
      this.model.set("htmlDescription", html);
      
      var title = this.ui.title.val();
      var when = this.ui.when.val();
      var where = this.ui.where.val();
      var isShowInfo = this.ui.isShowInfo.prop('checked');
      this.model.set({title: title, when: when, where: where, isShowInfo: isShowInfo});
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
    }
  });
});

