/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2004 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * File Name: fckplugin.js
 * 	This is the Chars Counter plugin definition file.
 * 
 * Version:  2.0 FC
 * Modified: 2005-05-25
 * 
 * File Authors:
 * 		Luigi Maniscalco (l.maniscalco@visioni.info)
 */
 
// Define the command.

var ie4 = (document.all) ? 1:0;
var gecko=(navigator.userAgent.indexOf('Gecko') > -1) ? 1:0;
var sf=(navigator.userAgent.indexOf('Safari') > -1) ? 1:0;
if (sf) { ie4 = 0; gecko = 1; }


var FCKJacuba = function( )
{
	// do nothing
}

FCKJacuba.prototype.Execute = function()
{
	// Creates the jacuba window which does all the dirty work.
	var popUpUrl = FCK.Config['BasePath']+'plugins/jacuba/jacuba.html';	
	var height = 380; 
	var width = 500;

	if (ie4) { height = height + 20; }
	if (!(gecko || ie4)) {
		alert("jacuba only supports one of the following browsers:\nMozilla 1+, Internet Explorer 4+");
	} else {
		win1=window.open(popUpUrl,"spellcheckwin",'resizable=no,width='+width+',height='+height);
	}
}

FCKJacuba.prototype.GetState = function()
{
	return FCK_TRISTATE_OFF ;
}



// Register the WordPress tag commands.
FCKCommands.RegisterCommand( 'Jacuba', new FCKJacuba( ) ) ;

// Create the Jacuba button.
var oJacubaItem = new FCKToolbarButton( 'Jacuba', 'Spell Check', null, FCK_TOOLBARITEM_ONLYICON, true, true ) ;
oJacubaItem.IconPath = FCKPlugins.Items['jacuba'].Path + 'images/spellcheck.gif' ;
FCKToolbarItems.RegisterItem( 'Jacuba', oJacubaItem ) ;