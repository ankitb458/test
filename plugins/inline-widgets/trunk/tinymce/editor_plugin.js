(function() {
	// Load plugin specific language pack
	//tinymce.PluginManager.requireLangPack('inlinewidgets');

	tinymce.create('tinymce.plugins.inline_widgets', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			var inlineWidgetHTML = '<img src="' + url + '/images/trans.gif" alt="$1" class="mceInlineWidget mceItemNoResize" title="$1" />';

			// Load plugin specific CSS into editor
			ed.onInit.add(function() {
				ed.dom.loadCSS(url + '/css/content.css');
			});

			// Display inline widget instead if img in element path
			ed.onPostRender.add(function() {
				if (ed.theme.onResolveName) {
					ed.theme.onResolveName.add(function(th, o) {
						if (o.node.nodeName == 'IMG' && ed.dom.hasClass(o.node, 'mceInlineWidget')) {
							var widgetId = ed.dom.getAttrib(o.node, 'alt');
							var i;
							for ( i = 0; i < document.inlineWidgetItems.length; i++ )
							{
								if ( document.inlineWidgetItems[i].value == widgetId )
								{
									o.name = document.inlineWidgetItems[i].label;
									break;
								}
							}
						}
					});
				}
			});

			// Replace inline widgets with images
			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = o.content.replace(/\[widget:(.*?)\]/ig, inlineWidgetHTML);
			});

			// Replace images with inline widgets
			ed.onPostProcess.add(function(ed, o) {
				if (o.get)
					o.content = o.content.replace(/<img[^>]+>/g, function(im) {
						if (im.indexOf('class="mceInlineWidget') !== -1) {
                            var m = im.match(/alt="(.*?)"/i);
							var file = m[1];

                            im = '[widget:' + file + ']' + "\n\n";
                        }
						
                        return im;
					});
			});
		},
		
		
		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			switch ( n )
			{
			case 'inline_widgets':
				// Inline widgets
				if ( document.inlineWidgetItems )
				{
					var myInlineWidgetsDropdown = cm.createListBox('InlineWidgetsDropdown', {
						title : 'Widget',
						onselect : function(v) {
							if ( v )
							{
								if ( v != 'inline_widget_help' )
								{
									v = '[widget:' + v + ']';

									window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, v);
									window.tinyMCE.execCommand("mceCleanup");
								}
								else
								{
									alert('This drop down lets you insert any widget you\'ve configured in your Inline Widgets "sidebar", under Appearance / Widgets. For instance, you could use this to configure and then insert an inline ad unit, a newsletter subscription form, and so on.');
								}
							}
						
							tinyMCE.activeEditor.controlManager.get('InlineWidgetsDropdown').reset();
						}
					});
				
					var i;
					if ( document.inlineWidgetItems.length )
					{
						for ( i = 0; i < document.inlineWidgetItems.length; i++ )
						{
							myInlineWidgetsDropdown.add(document.inlineWidgetItems[i].label, document.inlineWidgetItems[i].value);
						}
					}
					else
					{
						myInlineWidgetsDropdown.add('What\'s this?', 'inline_widget_help');
					}

					// Return the new listbox instance
					return myInlineWidgetsDropdown;
				}
			}
			
			return null;
		},
		

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : "Inline Widgets",
				author : 'Denis de Bernardy',
				authorurl : 'http://www.semiologic.com/',
				infourl : 'http://www.semiologic.com/software/inline-widgets/',
				version : "1.5"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('inline_widgets', tinymce.plugins.inline_widgets);
})();