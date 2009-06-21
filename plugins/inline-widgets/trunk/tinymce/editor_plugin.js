(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('inline_widgets');

	tinymce.create('tinymce.plugins.inline_widgets', {
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
			switch ( n ) {
			case 'inline_widgets':
				// Inline widgets
				if ( document.inlineWidgetItems ) {
					var myInlineWidgetsDropdown = cm.createListBox('InlineWidgetsDropdown', {
						title : 'inline_widgets.widgets',
						onselect : function(v) {
							if ( v ) {
								if ( v != 'inline_widget_help' ) {
									v = '[widget id="' + v + '"]' + v + '[/widget]';

									window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, v);
									window.tinyMCE.execCommand("mceCleanup");
								} else {
									tinyMCE.activeEditor.windowManager.alert('inline_widgets.whats_this_tip');
								}
							}
						
							tinyMCE.activeEditor.controlManager.get('InlineWidgetsDropdown').reset();
						}
					});
					
					var i;
					if ( document.inlineWidgetItems.length ) {
						for ( i = 0; i < document.inlineWidgetItems.length; i++ ) {
							myInlineWidgetsDropdown.add(document.inlineWidgetItems[i].label, document.inlineWidgetItems[i].value);
						}
					} else {
						myInlineWidgetsDropdown.add('inline_widgets.whats_this', 'inline_widget_help');
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
				version : "2.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('inline_widgets', tinymce.plugins.inline_widgets);
})();