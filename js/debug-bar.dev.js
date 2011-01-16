var wpDebugBar;

(function($) {

var debugBar, bounds, api, $win, $body;

bounds = {
	adminBarHeight: 0,
	minHeight: 0,
	marginBottom: 0,

	inUpper: function(){
		return debugBar.offset().top - $win.scrollTop() >= bounds.adminBarHeight;
	},
	inLower: function(){
		return debugBar.outerHeight() >= bounds.minHeight
			&& $win.height() >= bounds.minHeight;
	},
	update: function( to ){
		if ( typeof to == "number" || to == 'auto' )
			debugBar.height( to );
		if ( ! bounds.inUpper() || to == 'upper' )
			debugBar.height( $win.height() - bounds.adminBarHeight );
		if ( ! bounds.inLower() || to == 'lower' )
			debugBar.height( bounds.minHeight );
		$body.css( 'margin-bottom', debugBar.height() + bounds.marginBottom );
	},
	restore: function(){
		$body.css( 'margin-bottom', bounds.marginBottom );
	}
};

wpDebugBar = api = {
	init: function(){
		// Initialize variables.
		debugBar = $('#querylist');
		$win = $(window);
		$body = $(document.body);

		bounds.minHeight = $('#debug-bar-handle').outerHeight() + $('#debug-bar-menu').outerHeight();
		bounds.adminBarHeight = $('#wpadminbar').outerHeight();
		bounds.marginBottom = parseInt( $body.css('margin-bottom'), 10 );

		api.dock();
		api.toggle();
		api.tabs();
		api.actions.init();
	},

	dock: function(){
		debugBar.dockable({
			handle: '#debug-bar-handle',
			resize: function( e, ui ) {
				return bounds.inUpper() && bounds.inLower();
			},
			resized: function( e, ui ) {
				bounds.update();
			}
		});

		// If the window is resized, make sure the debug bar isn't too large.
		$win.resize( function(){
			if ( debugBar.is(':visible') && ! debugBar.dockable('option', 'disabled') )
				bounds.update();
		});
	},

	toggle: function(){
		$('#wp-admin-bar-debug-bar').click( function(e){
			var show = debugBar.is(':hidden');
			e.preventDefault();

			debugBar.toggle( show );
			$(this).toggleClass( 'active', show );

			if ( show )
				bounds.update();
			else
				bounds.restore();
		});
	},

	tabs: function(){
		var debugMenuLinks = $('.debug-menu-link'),
			debugMenuTargets = $('.debug-menu-target');

		debugMenuLinks.click( function(e){
			var t = $(this);

			e.preventDefault();

			if ( t.hasClass('current') )
				return;

			// Deselect other tabs and hide other panels.
			debugMenuTargets.hide();
			debugMenuLinks.removeClass('current');

			// Select the current tab and show the current panel.
			t.addClass('current');
			// The hashed component of the href is the id that we want to display.
			$('#' + this.href.substr( this.href.indexOf( '#' ) + 1 ) ).show();
		});
	},

	actions: {
		height: 0,
		overflow: 'auto',
		buttons: {},

		init: function() {
			var actions = $('#debug-bar-actions');

			api.actions.height = debugBar.height();
			api.actions.overflow = $body.css( 'overflow' );

			api.actions.buttons.max = $('.plus', actions).click( api.actions.maximize );
			api.actions.buttons.res = $('.minus', actions).click( api.actions.restore );
		},
		maximize: function() {
			api.actions.height = debugBar.height();
			$body.css( 'overflow', 'hidden' );
			bounds.update( 'auto' );
			api.actions.buttons.max.hide();
			api.actions.buttons.res.show();
			debugBar.dockable('disable');
		},
		restore: function() {
			$body.css( 'overflow', api.actions.overflow );
			bounds.update( api.actions.height );
			api.actions.buttons.res.hide();
			api.actions.buttons.max.show();
			debugBar.dockable('enable');
		}
	}
};

$(document).ready( wpDebugBar.init );

})(jQuery);