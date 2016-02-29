// Portions, or more, of this taken from work by Steven de Salas
// http://desalasworks.com/article/object-oriented-javascript-inheritance/

// Create a static 'extends' method on the Object class
// This allows us to extend existing classes
// for classical object-oriented inheritance
Object.extend = function(superClass, definition) {
    var subClass = function() {};
    // Our constructor becomes the 'subclass'
    if (definition.constructor !== Object)
        subClass = definition.constructor;
    subClass.prototype = new superClass();
    for (var prop in definition) {
        if (prop != 'constructor')
            subClass.prototype[prop] = definition[prop];
    }
    return subClass;
};

var delay = (function(){
    var timer = 0;
    return function(callback, ms){
        clearTimeout (timer);
        timer = setTimeout(callback, ms);
    };
})();

var BoltBBAdmin = Object.extend(Object, {

    selector: ".boltbb-bolt-container",
    messages:  {},
    paths:  {},

    constructor: function(){
        jQuery(this.selector).on("change", this, this.events.change);
        jQuery(this.selector).on("click", this, this.events.click);
        
        jQuery(document).ajaxStart(function() {

        }).ajaxSuccess(function() {
            clearInterval(active_interval);
        });
        
        // Call the BoltBB set up functions
        //this.checkInstalled();
        
    },
    
    find: function(selector) {
        return jQuery(this.selector).find(selector);
    },
    
    setMessage: function(key, value) {
        this.messages[key]=value;
    },

    
    setPath: function(key, value) {
        this.paths[key]=value;
    },
    
    doSync: function(e) {
        var controller = this;
        
        console.debug("Running forum DB table sync");
        
        $.get(baseurl + '/ajax?task=forumSync', function(data){})
            .done(function() {
                location.reload(true);
                })
            .fail(function() {
                alert("There was an error");
                });
    },
    
    doContenttypes: function(e) {
        var controller = this;
        
        console.debug("Setting up BoltBB contenttypes");
        
        $.get(baseurl + '/ajax?task=forumContenttypes', function(data){})
            .done(function() {
                location.reload(true);
                })
            .fail(function() {
                alert("There was an error");
                });
    },
    
    doOpen: function(e) {
        var controller = this;
        var data = new Array();
        
        $.each($("input[name='form[forums][]']:checked"), function () {
            data.push($(this).val());
        });
        
        console.debug("Opening forums: " + data);
        
        $.post(baseurl + '/ajax?task=forumOpen', {forums: data}, function(data){})
            .done(function() {
                location.reload(true);
                })
            .fail(function() {
                alert("There was an error");
                });
    },
    
    doClose: function(e) {
        var controller = this;
        var data = new Array();
        
        $.each($("input[name='form[forums][]']:checked"), function () {
            data.push($(this).val());
        });
        
        console.debug("Closing forums: " + data);
        
        $.post(baseurl + '/ajax?task=forumClose', {forums: data}, function(data){})
            .done(function() {
                location.reload(true);
                })
            .fail(function() {
                alert("There was an error");
                });
    },
    
    doRepairRelation: function(e) {
        var controller = this;
        
        console.debug("Repairing forum/reply relations");
        
        $.post(baseurl + '/ajax?task=repairRelation', '', function(data){})
            .done(function() {
                //location.reload(true);
                alert('Repair done');
                console.debug(data);
                })
            .fail(function() {
                alert("There was an error");
                });
    },
    
    doTestNotify: function(e) {
        var controller = this;
        
        console.debug("Sending test notification");

        $.post(baseurl + '/ajax?task=testNotify', '', function(data){})
            .done(function() {
                alert('Notification sent');
                console.debug(data);
                })
            .fail(function() {
                alert("There was an error");
                });
    },
        
    events: {
        change: function(e, t){
            var controller = e.data;
        },
        
        click: function(e, t){
            var controller = e.data;
            switch(jQuery(e.target).data('action')) {
                case "boltbb-forum-sync"         : controller.doSync(e.originalEvent); break;
                case "boltbb-forum-contenttypes" : controller.doContenttypes(e.originalEvent); break;
                case "boltbb-forum-open"         : controller.doOpen(e.originalEvent); break;
                case "boltbb-forum-close"        : controller.doClose(e.originalEvent); break;
                case "boltbb-repair-relation"    : controller.doRepairRelation(e.originalEvent); break;
                case "boltbb-test-notify"        : controller.doTestNotify(e.originalEvent); break;
            }
        }

    }

});