(function($) {

var apiBase = '../api/slim.php';

var EventModel = Backbone.Model.extend({
  initialize : function() {
  },
  urlRoot: apiBase + '/events'
});

var EventList = Backbone.Collection.extend({
  model: EventModel,
  url: apiBase + '/events'
});

var UserModel = Backbone.Model.extend({
  initialize : function() {
  },
  urlRoot: apiBase + '/users'
});

var EventListItemView = Backbone.View.extend({
  tagName : 'li',
  
  listView: null,
  
  events: {
    "click .eventLink" : "eventClick"
  },
  
  initialize: function() {
  },
  
  render : function() {
    $(this.el).html('<a href="#event/'+ this.model.get('id')+'" class="eventLink">' + this.model.get('title') + "</a>");
    return this;
  },
  
  eventClick: function(target) {
    this.listView.selectedModel = this.model;
    app.navigate("event/" + this.model.get("id"), true);
    return false;
  }
});

var EventListView = Backbone.View.extend({
  
  tagName: 'ul',
  
  selectedModel: null,
  
  initialize: function() {
    var self = this;
    this.render();
    this.collection.bind('add', function(eventModel) {
      this.appendEvent(eventModel);
    }, this);
  },
  
  render: function() {
    _.each(this.collection.models, function (eventModel) {
        this.appendEvent(eventModel);
    }, this);
    return this;
  },
  
  appendEvent: function(eventModel) {
    $('#subtitle').html('Events');
    var v = new EventListItemView({ model : eventModel, listView: this });
    $(this.el).append(v.render().el);
  }
});

var EventView = Backbone.View.extend({
  
  tagName: "div",
  
  initialize: function() {
  },
  
  render: function() {
    $('#subtitle').html(this.model.get('title'));
    $(this.el).html('event');
    return this;
  }

});

var AppRouter = Backbone.Router.extend({
  routes: {
    "": "home",
    "event/:eventid": "eventPage"
  },
  
  home: function() {
    var self = this;
    this.listView = new EventListView({collection: new EventList()});
    $('#pageContent').html(this.listView.render().el);
    this.listView.collection.fetch({
      data: $.param({userid: 2}),
      success: function() {
        self.listView.render();
      }
    });
  },
  
  eventPage: function(eventid) {
    var self = this;
    this.eventView = new EventView();
    if (this.listView && this.listView.selectedModel) {
      this.eventView.model = this.listView.selectedModel;
      $('#pageContent').html(this.eventView.render().el);
    } else {
      m = new EventModel();
      m.set("id", eventid);
      m.fetch({
        success: function(data) {
          self.eventView.model = data;
          $('#pageContent').html(self.eventView.render().el);
        }
      });
    }
  }

});

var app = new AppRouter();
Backbone.history.start();

})(jQuery);