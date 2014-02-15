PBJ.module("IframeModule", function(IframeModule, App, Backbone, Marionette, $, _){
  
  IframeModule.Controller = PBJ.EventWebModuleController.extend({
    
    rpc: null,
    
    initialize: function() {
      var self = this;
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
    ui: {
      iframecontainer: "#iframecontainer"
    },
    onRender: function() {
      console.log(this.model);
      var props = this.model.get("props");
      var url = "";
      if (props && props.length > 0) {
        var prop = _.findWhere(props, {propName: "url"});
        url = prop.propValue;
      }
      var self = this;
      var el = this.ui.iframecontainer[0];
      this.controller.rpc = new easyXDM.Rpc(
        {
          remote: url,
          container: el,
          props: {
            style: {
              width: "100%",
              height: "100%"
            },
            frameborder: 0
          }
        },
        {
          local: {
            notify: function(userNotification, success, error) {
              PBJ.notify(userNotification);
            },
            setFrameHeight: function(height, success, error) {
              //TIP: the iframe's body should not have any padding/border/margin or there will always be scrollbars
              el.style.height = height;
            }
          }
        }
      );
    }
  });
});