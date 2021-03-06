var wpWidgets;
(function($) {

wpWidgets = {
	sortables : null,
	
	init : function() {
		var rem;
		//wpWidgets.sortables = $('div.widgets-sortables');
		
		if ( $('body').hasClass('widgets_access') ) {
			return;
		}
		//$.log('check widgets_access');
		
		var availableWidgets = $('#widget-list').children('.widget');
		//$.log('availble widgets - find');
		
		var droppableSidebar = $('#available-widgets');
		//$.log('droppable sidebar - find');
		
		var sortableSidebars = $('div.widgets-sortables');
		//$.log('sortable sidebars - find');
		
		$('#widgets-right').children('.widgets-holder-wrap').children('.sidebar-name')
		//.log('find widgets-right sidebar name')
		.click(function(){
			var c = $(this).siblings('.widgets-sortables'), p = $(this).parent();
			if ( !p.hasClass('closed') ) {
				c.sortable('disable');
				p.addClass('closed');
			} else {
				p.removeClass('closed');
				c.sortable('enable').sortable('refresh');
			}
		});
		//$.log('widgets-right sidebar name');
		
		$('#widgets-left').children('.widgets-holder-wrap').children('.sidebar-name')
		//.log('find widgets-left sidebar name')
		.click(function() {
			$(this).siblings('.widget-holder').parent().toggleClass('closed');
		});
		//$.log('widgets-left sidebar name');
		
		sortableSidebars.not('#wp_inactive_widgets')
		.each(function(){
			var h = 50, H = $(this).children('.widget').length;
			h = h + parseInt(H * 48, 10);
			$(this).css( 'minHeight', h + 'px' );
		});
		//$.log('sidebars - resize');
		
		sortableSidebars.children('.widget')
		//.log('widgets - find active widgets')
		.each(function() {
			wpWidgets.addEvents(this);
		})
		//.log('widgets - attach events')
		.each(function() {
			wpWidgets.appendTitle(this);
		});
		//$.log('widgets - set title');
		
		availableWidgets.draggable({
			connectToSortable: 'div.widgets-sortables',
			handle: '> .widget-top > .widget-title',
			distance: 2,
			helper: 'clone',
			zIndex: 5,
			containment: 'document',
			start: function(e,ui) {
				wpWidgets.fixWebkit(1);
				ui.helper.find('div.widget-description').hide();
			},
			stop: function(e,ui) {
				if ( rem )
					$(rem).hide();
				rem = '';
				wpWidgets.fixWebkit();
			}
		});
		//$.log('available widgets - make draggable');
				
		sortableSidebars.sortable({
			placeholder: 'widget-placeholder',
			items: '> .widget',
			handle: '> .widget-top > .widget-title',
			cursor: 'move',
			distance: 2,
			containment: 'document',
			start: function(e,ui) {
				wpWidgets.fixWebkit(1);
				ui.item.children('.widget-inside').hide();
				ui.item.css({'marginLeft':'','width':''});
			},
			stop: function(e,ui) {
				if ( ui.item.hasClass('ui-draggable') )
					ui.item.draggable('destroy');

				if ( ui.item.hasClass('deleting') ) {
					wpWidgets.save( ui.item, 1, 0, 1 ); // delete widget
					ui.item.remove();
					return;
				}

				var add = ui.item.find('input.add_new').val(),
					n = ui.item.find('input.multi_number').val(),
					id = ui.item.attr('id'),
					sb = $(this).attr('id');

				ui.item.css({'marginLeft':'','width':''});
				wpWidgets.fixWebkit();
				if ( add ) {
					if ( 'multi' == add ) {
						ui.item.html( ui.item.html().replace(/<[^<>]+>/g, function(m){ return m.replace(/__i__|%i%/g, n); }) );
						ui.item.attr( 'id', id.replace(/__i__|%i%/g, n) );
						n++;
						$('div#' + id).find('input.multi_number').val(n);
					} else if ( 'single' == add ) {
						ui.item.attr( 'id', 'new-' + id );
						rem = 'div#' + id;
					}
					wpWidgets.addEvents(ui.item);
					wpWidgets.save( ui.item, 0, 0, 1 );
					ui.item.find('input.add_new').val('');
					ui.item.find('a.widget-action').click();
					return;
				}
				wpWidgets.saveOrder(sb);
			},
			receive: function(e,ui) {
				if ( !$(this).is(':visible') )
					$(this).sortable('cancel');
			}
		})
		//.log('sortable sidebars - enable sortables')
		.sortable('option', 'connectWith', 'div.widgets-sortables')
		//.log('sortable sidebars - set connectWith')
		.parent().filter('.closed').children('.widgets-sortables')
		//.log('sortable sidebars - find hidden')
		.sortable('disable');
		//$.log('sortable sidebars - disable sortables if hidden');
		
		droppableSidebar.droppable({
			tolerance: 'pointer',
			accept: function(o){
				return $(o).parent().attr('id') != 'widget-list';
			},
			drop: function(e,ui) {
				ui.draggable.addClass('deleting');
				$('#removing-widget').hide().children('span').html('');
			},
			over: function(e,ui) {
				ui.draggable.addClass('deleting');
				$('div.widget-placeholder').hide();

				if ( ui.draggable.hasClass('ui-sortable-helper') )
					$('#removing-widget').show().children('span')
					.html( ui.draggable.find('div.widget-title').children('h4').html() );
			},
			out: function(e,ui) {
				ui.draggable.removeClass('deleting');
				$('div.widget-placeholder').show();
				$('#removing-widget').hide().children('span').html('');
			}
		});
		//$.log('droppable sidebar - enable droppable');
		
		$('p.widget-error').closest('div.widget').find('a.widget-action').click();
		//$.log('widgets - open if errors');
	},

	saveOrder : function(sb) {
		if ( sb ) {
			$('#' + sb).closest('div.widgets-holder-wrap').find('img.ajax-feedback')
			.css('visibility', 'visible');
		}

		var a = {
			action: 'widgets-order',
			savewidgets: $('#_wpnonce_widgets').val(),
			sidebars: []
		};

		$('div.widgets-sortables').each( function() {
			a['sidebars[' + $(this).attr('id') + ']'] = $(this).sortable('toArray').join(',');
		});

		$.post( ajaxurl, a, function() {
			$('img.ajax-feedback').css('visibility', 'hidden');
		});
		
		this.resize();
	},

	save : function(widget, del, animate, order) {
		var sb = widget.closest('div.widgets-sortables').attr('id'),
			data = widget.find('form').serialize(),
			a;
		widget = $(widget);
		widget.find('.ajax-feedback').css('visibility', 'visible');

		a = {
			action: 'save-widget',
			savewidgets: $('#_wpnonce_widgets').val(),
			sidebar: sb
		};

		if ( del )
			a['delete_widget'] = 1;

		data += '&' + $.param(a);

		$.post( ajaxurl, data, function(r){
			var id;

			if ( del ) {
				if ( !$('input.widget_number', widget).val() ) {
					id = $('input.widget-id', widget).val();
					$('#available-widgets').find('input.widget-id')
					.each(function(){
						if ( $(this).val() == id )
							$(this).closest('div.widget').show();
					});
				}

				if ( animate ) {
					order = 0;
					widget.slideUp('fast', function(){
						$(this).remove();
						wpWidgets.saveOrder();
					});
				} else {
					widget.remove();
					wpWidgets.resize();
				}
			} else {
				$('.ajax-feedback').css('visibility', 'hidden');
				if ( r && r.length > 2 ) {
					$('div.widget-content', widget).html(r);
					wpWidgets.appendTitle(widget);
					wpWidgets.fixLabels(widget);
				}
			}
			if ( order )
				wpWidgets.saveOrder();
		});
	},

	appendTitle : function(widget) {
		$('input[id*=title]:first', widget).each(function() {
			var title = $(this).val().replace(/<[^<>]+>/g, '')
			.replace(/</g, '&lt;').replace(/>/g, '&gt;');
			
			if ( title )
				$(widget).children('.widget-top')
				.children('.widget-title').children().children('.in-widget-title')
				.html(': ' + title);
		});
	},

	resize : function() {
		$('div.widgets-sortables').not('#wp_inactive_widgets')
		//.log('find resizables')
		.each(function(){
			var h = 50, H = $(this).children('.widget').length;
			h = h + parseInt(H * 48, 10);
			$(this).css( 'minHeight', h + 'px' );
		});
	},

    fixWebkit : function(n) {
        n = n ? 'none' : '';
        $('body').css({
			WebkitUserSelect: n,
			KhtmlUserSelect: n
		});
    },
    
    fixLabels : function(widget) {
		widget.children('.widget-inside').find('label').each(function(){
			var f = $(this).attr('for');

			if ( f && f == $('input', this).attr('id') )
				$(this).removeAttr('for');
		});
	},
    
    close : function(widget) {
		widget.children('.widget-inside').slideUp('fast', function(){
			widget.css({'width':'','marginLeft':''});
		});
	},

    addEvents : function(sc) {
		sc = sc || document;
		
		$('a.widget-action', sc).click(function(){
			var css = {},
				widget = $(this).closest('div.widget'),
				inside = widget.children('.widget-inside'),
				w = parseInt( widget.find('input.widget-width').val(), 10 );
			
			if ( inside.is(':hidden') ) {
				if ( w > 250 && inside.closest('div.widgets-sortables').length ) {
					css['width'] = w + 30 + 'px';
					if ( inside.closest('div.widget-liquid-right').length )
						css['marginLeft'] = 235 - w + 'px';
					widget.css(css);
				}
				wpWidgets.fixLabels(widget);
				inside.slideDown('fast');
			} else {
				inside.slideUp('fast', function() {
					widget.css({'width':'','marginLeft':''});
				});
			}
			return false;
		});
		// todo: optimize this further by using a button tag instead
		$('input.widget-control-save', sc).click(function(){
			wpWidgets.save( $(this).closest('div.widget'), 0, 1, 0 );
			return false;
		});
		$('a.widget-control-remove', sc).click(function(){
			wpWidgets.save( $(this).closest('div.widget'), 1, 1, 0 );
			return false;
		});
		$('a.widget-control-close', sc).click(function(){
			wpWidgets.close( $(this).closest('div.widget') );
			return false;
		});
	}
};
$(document).ready(function($){
	//$.log('widgets.js - start');
	wpWidgets.init();
	//$.log('widgets.js - done');
});

})(jQuery);