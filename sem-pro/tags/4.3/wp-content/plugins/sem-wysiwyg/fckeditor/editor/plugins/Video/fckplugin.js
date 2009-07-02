/* 
 *  FCKPlugin.js
 *  ------------
 *  This is a generic file which is needed for plugins that are developed
 *  for FCKEditor. With the below statements that toolbar is created and
 *  several options are being activated.
 *
 *  See the online documentation for more information:
 *  http://wiki.fckeditor.net/
 *
  * File Authors:
 *		Mike Koepke (mike.koepke@gmail.com)
 */

// Register the related commands.
FCKCommands.RegisterCommand(
	'Video',
	new FCKDialogCommand(
		'Video',
		FCKLang["VideoDlgTitle"],
		FCKPlugins.Items['Video'].Path + 'video_dialog.html',
		530,
		512
	)
);
 
// Create the "Video" toolbar button.
// FCKToolbarButton( commandName, label, tooltip, style, sourceView, contextSensitive )
var oVideoItem = new FCKToolbarButton( 'Video', FCKLang["VideoBtn"], FCKLang["VideoTooltip"], null, false, true ); 
oVideoItem.IconPath = FCKConfig.PluginsPath + 'Video/video.gif'; 

// 'Video' is the name that is used in the toolbar config.
FCKToolbarItems.RegisterItem( 'Video', oVideoItem );