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
 * 		Mike Koepke
 */

// Define the command.
var FCKGoogieSpellCheck = function()
{
	this.Name = 'SpellCheck' ;
	this.ContentId = FCK.InstanceName;
}

FCKGoogieSpellCheck.prototype.Execute = function()
{
	FCKDialog.OpenDialog( 'FCKDialog_SpellCheck', 'Spell Check', FCKConfig.PluginsPath + 'GoogleSpellCheck/googlespellcheck.html', 440, 480 ) ;
}
FCKGoogieSpellCheck.prototype.GetState = function()
{
	return FCK_TRISTATE_OFF ;

}

// Register the WordPress tag commands.
FCKCommands.RegisterCommand( 'GoogleSpellCheck', new FCKGoogieSpellCheck() ) ;

// Create the GoogieSpell button.
var oGoogleSpellItem = new FCKToolbarButton( 'GoogleSpellCheck', 'SpellCheck', null, FCK_TOOLBARITEM_ONLYICON, false, true  ) ;
oGoogleSpellItem.IconPath = FCKConfig.PluginsPath + 'GoogleSpellCheck/spellcheck/spellc.gif' ;
FCKToolbarItems.RegisterItem( 'GoogleSpellCheck', oGoogleSpellItem ) ;
