PBJ.module("IframeModule", function(IframeModule, App, Backbone, Marionette, $, _){
  
  IframeModule.Controller = PBJ.EventWebModuleController.extend({
    
    initialize: function() {
      this.log("initialize");
      this.model = this.module;
      this.maxView = PBJ.IframeModuleViews.IframeModuleView;
    }
  });
  
});

PBJ.module("IframeModuleModels", function(IframeModuleModels, App, Backbone, Marionette, $, _) {

});

PBJ.module("IframeModuleViews", function(IframeModuleViews, App, Backbone, Marionette, $, _){
  IframeModuleViews.IframeModuleView = Backbone.Marionette.ItemView.extend({
    template: "#template-iframeModuleView",
    events: {
      "click #reloadAction" : "reload"
    },
    reload: function(e) {
      e.preventDefault();
      this.$("#iframe1").attr('src', function ( i, val ) { return val; });
    }
  });
});