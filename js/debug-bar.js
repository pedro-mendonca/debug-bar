var wpDebugBar;(function(d){var e,b,a,f,c;b={adminBarHeight:0,minHeight:0,marginBottom:0,inUpper:function(){return e.offset().top-f.scrollTop()>=b.adminBarHeight},inLower:function(){return e.outerHeight()>=b.minHeight&&f.height()>=b.minHeight},update:function(g){if(typeof g=="number"||g=="auto"){e.height(g)}if(!b.inUpper()||g=="upper"){e.height(f.height()-b.adminBarHeight)}if(!b.inLower()||g=="lower"){e.height(b.minHeight)}c.css("margin-bottom",e.height()+b.marginBottom)},restore:function(){c.css("margin-bottom",b.marginBottom)}};wpDebugBar=a={init:function(){e=d("#querylist");f=d(window);c=d(document.body);b.minHeight=d("#debug-bar-handle").outerHeight()+d("#debug-bar-menu").outerHeight();b.adminBarHeight=d("#wpadminbar").outerHeight();b.marginBottom=parseInt(c.css("margin-bottom"),10);a.dock();a.toggle();a.tabs();a.actions.init()},dock:function(){e.dockable({handle:"#debug-bar-handle",resize:function(h,g){return b.inUpper()&&b.inLower()},resized:function(h,g){b.update()}});f.resize(function(){if(e.is(":visible")&&!e.dockable("option","disabled")){b.update()}})},toggle:function(){d("#wp-admin-bar-debug-bar").click(function(h){var g=e.is(":hidden");h.preventDefault();e.toggle(g);d(this).toggleClass("active",g);if(g){b.update()}else{b.restore()}})},tabs:function(){var h=d(".debug-menu-link"),g=d(".debug-menu-target");h.click(function(j){var i=d(this);j.preventDefault();if(i.hasClass("current")){return}g.hide();h.removeClass("current");i.addClass("current");d("#"+this.href.substr(this.href.indexOf("#")+1)).show()})},actions:{height:0,overflow:"auto",buttons:{},init:function(){var g=d("#debug-bar-actions");a.actions.height=e.height();a.actions.overflow=c.css("overflow");a.actions.buttons.max=d(".plus",g).click(a.actions.maximize);a.actions.buttons.res=d(".minus",g).click(a.actions.restore)},maximize:function(){a.actions.height=e.height();c.css("overflow","hidden");b.update("auto");a.actions.buttons.max.hide();a.actions.buttons.res.show();e.dockable("disable")},restore:function(){c.css("overflow",a.actions.overflow);b.update(a.actions.height);a.actions.buttons.res.hide();a.actions.buttons.max.show();e.dockable("enable")}}};d(document).ready(wpDebugBar.init)})(jQuery);