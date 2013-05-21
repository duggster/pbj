PBJ.module("ModuleManagerModule", function(ModuleManagerModule, App, Backbone, Marionette, $, _){
  
  ModuleManagerModule.Controller = PBJ.EventWebModuleController.extend({
    
    initialize: function() {
      var self = this;
      this.log("initialize");
      this.collection = new PBJ.ModuleManagerModuleModels.ModuleList();
      this.isModuleListLoaded = false;
      this.maxView = PBJ.ModuleManagerModuleViews.ModuleListView;
      this.module.on("maximize", function() {
        if (!this.isModuleListLoaded) {
          PBJ.infoBegin("modulemanagerlist", "Loading modules...");
          this.collection.fetch({
            success: function() {
              self.isModuleListLoaded = true;
              PBJ.infoEnd("modulemanagerlist", "Modules loaded.");
            }
          });
        }
      }, this);
    }
    
  });
  
});

PBJ.module("ModuleManagerModuleModels", function(ModuleManagerModuleModels, App, Backbone, Marionette, $, _) {
  var m = ModuleManagerModuleModels;
  var base = PBJ.Models.apiBase;
  
  m.Module = Backbone.Model.extend({
    urlRoot: base + '/modules'
  });
  
  m.ModuleList = Backbone.Collection.extend({
    model: m.Module,
    url: base + '/modules',
    initialize: function() {
      this.bind("reset", function() {
        var eventModules = PBJ.controller.currentEvent.get("eventModules");
        this.forEach(function(module) {
          module.set("inUse", false);
          eventModules.forEach(function(eventModule) {
            if (module.get("id") == eventModule.get("id")) {
              module.set("inUse", true);
            }
          });
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
});

PBJ.module("ModuleManagerModuleViews", function(ModuleManagerModuleViews, App, Backbone, Marionette, $, _){
  var v = ModuleManagerModuleViews;
  
  v.ModuleItemView = Backbone.Marionette.ItemView.extend({
    template: "#template-moduleManager-ModuleItemView",
    tagName: 'li',
    templateHelpers: {
      getIsDefaultHtml: function() {
        return (this.isEventDefault ? "(default)" : "");
      }
    },
    events: {
      "click .addModuleAction" : "addModule",
      "click .removeModuleAction" : "removeModule"
    },
    onRender: function() {
      this.$('.addModuleAction').hide();
      this.$('.removeModuleAction').hide();
      if (!this.model.get("isEventDefault")) {
        if (this.model.get("inUse")) {
          this.$('.removeModuleAction').show();
        }
        else {
          this.$('.addModuleAction').show();
        }
      }
    },
    addModule: function(e) {
      e.preventDefault();
      var self = this;
      var eventModule = new PBJ.Models.EventWebModule(this.model.toJSON());
      eventModule.initEventModule(PBJ.controller.currentEvent);
      PBJ.infoBegin('moduleadd', 'Adding module...');
      eventModule.save({},{
        type: 'post',
        success: function() {
          PBJ.infoEnd('moduleadd', 'Module added.');
          self.refreshModules();
        }
      });
    },
    removeModule: function(e) {
      e.preventDefault();
      var self = this;
      var eventModule = new PBJ.Models.EventWebModule(this.model.toJSON());
      eventModule.initEventModule(PBJ.controller.currentEvent);
      PBJ.infoBegin('moduleremove', 'Removing module...');
      eventModule.destroy({
        success: function() {
          PBJ.infoEnd('moduleremove', 'Module removed.');
          self.refreshModules();
        }
      });
    },
    refreshModules: function() {
      PBJ.infoBegin("refreshmodules", "Refreshing Modules...");
      PBJ.controller.currentEvent.get("eventModules").fetch({
        success: function() {
          PBJ.infoEnd("refreshmodules", "Modules refreshed.");
        }
      });
    }
  });
  
  v.ModuleListView = Backbone.Marionette.CompositeView.extend({
    template: "#template-moduleManager-ModuleListView",
    itemView: v.ModuleItemView,
    itemViewContainer: "#moduleManagerList"
  });
});