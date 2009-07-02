/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2006 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * "Support Open Source software. What about a donation today?"
 * 
 * File Name: video.js
 * 	Scripts related to the Flash dialog window (see video_dialog.html).
 * 
 * File Authors:
 * 		Ziyad Saeed (myschizobuddy@gmail.com)
 */

var oEditor		= window.parent.InnerDialogLoaded() ;
var FCK			= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;

// Set the dialog tabs.
//window.parent.AddTab( 'flashVideos', 'Flash Videos' ) ;


// Function called when a dialog tag is selected.
/*function OnDialogTabChange( tabCode )
{
	ShowE('flashVideos'		, ( tabCode == 'flashVideos' ) ) ;
}*/

// Get the selected flash embed (if available).
var oFakeImage = FCK.Selection.GetSelectedElement() ;
var oEmbed ;

if ( oFakeImage )
{
	if ( oFakeImage.tagName == 'IMG' && oFakeImage.getAttribute('_fckflash') )
		oEmbed = FCK.GetRealElement( oFakeImage ) ;
	else
		oFakeImage = null ;
}

window.onload = function()
{
	// Translate the dialog box texts.
	oEditor.FCKLanguageManager.TranslatePage(document) ;

	window.parent.SetAutoSize( true ) ;

	// Activate the "OK" button.
	window.parent.SetOkButton( true ) ;
}

//#### The OK button was hit.
function Ok()
{
	if (GetE('videoCode').value.length == 0 )
	{
		GetE('videoCode').focus() ;
		alert( oEditor.FCKLang.VideoAlertUrl ) ;
		return false ;
	}

	if ( !oEmbed )
	{
		oEmbed		= FCK.EditorDocument.createElement( 'EMBED' ) ;
		oFakeImage  = null ;
	}
	
	UpdateEmbed( oEmbed ) ;
	
	if ( !oFakeImage )
	{
		oFakeImage	= oEditor.FCKDocumentProcessor_CreateFakeImage( 'FCK__Flash', oEmbed ) ;
		oFakeImage.setAttribute( '_fckflash', 'true', 0 ) ;
		oFakeImage	= FCK.InsertElementAndGetIt( oFakeImage ) ;
	}
	else
		oEditor.FCKUndo.SaveUndoStep() ;
	
	oEditor.FCKFlashProcessor.RefreshView( oFakeImage, oEmbed ) ;

	return true ;
}

function UpdateEmbed( e )
{
	SetAttribute( e, 'type', 'application/x-shockwave-flash' ) ;
	SetAttribute( e, 'id', 'VideoPlayback' ) ;
	SetAttribute(e,"quality",'high');
	SetAttribute(e,"pluginspage",'http://macromedia.com/go/getflashplayer');
	SetAttribute(e, "wmode",'transparent');
	
	if (GetE('selectYouTube').selected) {
		e.src = 'http://www.youtube.com/v/'.concat(GetE('videoCode').value) ;
		SetAttribute(e, "width" , '425' ) ;
		SetAttribute(e, "height", '350' ) ;
	}
	if (GetE('selectGoogle').selected) {
		e.src = 'http://video.google.com/googleplayer.swf?docId='.concat(GetE('videoCode').value,'&hl=en') ;
		SetAttribute(e, "width" , '400' ) ;
		SetAttribute(e, "height", '326' ) ;		
		SetAttribute(e, "flashvars",'');
		
	}
	if (GetE('selectSoapBox').selected) {
		e.src = 'http://soapbox.msn.com/flash/soapbox1_1.swf' ;
		SetAttribute(e, "width" , '412' ) ;
		SetAttribute(e, "height", '362' ) ;
		SetAttribute(e, "name",'msn_soapbox');
		SetAttribute(e, "flashvars",'c=v&v='.concat(GetE('videoCode').value));
	}
	if (GetE('selectRevver').selected) {
		e.src = 'http://flash.revver.com/player/1.0/player.swf' ;
		SetAttribute(e, "width" , '480') ;
		SetAttribute(e, "height", '392') ;
		SetAttribute(e, "flashvars",'mediaId='.concat(GetE('videoCode').value));
	}
	if (GetE('selectVideoJug').selected) {
		e.src = 'http://www.videojug.com/player/videoJugPlayer.swf?id='.concat(GetE('videoCode').value) ;
		SetAttribute(e, "width" , '400' ) ;
		SetAttribute(e, "height", '345' ) ;
	}
	if (GetE('selectMetaCafe').selected) {
		e.src = 'http://www.metacafe.com/fplayer/'.concat(GetE('videoCode').value) ;
		SetAttribute(e, "width" , '400' ) ;
		SetAttribute(e, "height", '345' ) ;
	}
	if (GetE('selectDailyMotion').selected) {
		e.src = 'http://www.dailymotion.com/swf/'.concat(GetE('videoCode').value) ;
		SetAttribute(e, "width" , '425' ) ;
		SetAttribute(e, "height", '335' ) ;
	}
	if (GetE('selectVimeo').selected) {
		e.src = 'http://www.vimeo.com/moogaloop.swf?clip_id='.concat(GetE('videoCode').value) ;
		SetAttribute(e, "width" , '400' ) ;
		SetAttribute(e, "height", '300' ) ;
	}
}
var ePreview ;

function SetPreviewElement( previewEl )
{
	ePreview = previewEl ;
	
	if ( GetE('videoCode').value.length > 0)
		 UpdatePreview();
}

function UpdatePreview()
{
	if ( !ePreview )
		return ;
		
	while ( ePreview.firstChild )
		ePreview.removeChild( ePreview.firstChild ) ;

	if ( GetE('videoCode').value.length == 0)
		ePreview.innerHTML = '&nbsp;' ;
	else
	{
		var oDoc	= ePreview.ownerDocument || ePreview.document ;
		var e		= oDoc.createElement( 'EMBED' ) ;
		e.type		= 'application/x-shockwave-flash' ;
		e.id		= 'VideoPlayback' ;
		e.wmode     = 'transparent';				
		if (GetE('selectYouTube').selected) {		
			e.src = 'http://www.youtube.com/v/'.concat(GetE('videoCode').value) ;
			e.width		= '425' ;
			e.height	= '350' ;
		}
		if (GetE('selectGoogle').selected) {		
			e.src = 'http://video.google.com/googleplayer.swf?docId='.concat(GetE('videoCode').value,'&hl=en') ;
			e.width		= '400' ;
			e.height	= '326' ;	
			e.flashwars = '';
		}
		if (GetE('selectSoapBox').selected) {
			e.src = 'http://soapbox.msn.com/flash/soapbox1_1.swf' ;
			e.width = '412' ;
			e.height = '362' ;
			SetAttribute(e, "name",'msn_soapbox');
			SetAttribute(e, "flashvars",'c=v&v='.concat(GetE('videoCode').value));
		}
		if (GetE('selectRevver').selected) {
			e.src = 'http://flash.revver.com/player/1.0/player.swf' ;
			e.width = '480' ;
			e.height = '392' ;
			SetAttribute(e, "flashvars",'mediaId='.concat(GetE('videoCode').value));
		}
		if (GetE('selectMetaCafe').selected) {
			e.src = 'http://www.metacafe.com/fplayer/'.concat(GetE('videoCode').value) ;
			e.width = '400' ;
			e.height = '345' ;
		}
		if (GetE('selectVideoJug').selected) {
			e.src = 'http://www.videojug.com/player/videoJugPlayer.swf?id='.concat(GetE('videoCode').value) ;
			e.width = '400' ;
			e.height = '345' ;
		}
		if (GetE('selectDailyMotion').selected) {
			e.src = 'http://www.dailymotion.com/swf/'.concat(GetE('videoCode').value) ;
			e.width = '425' ;
			e.height = '335' ;
		}
		if (GetE('selectVimeo').selected) {
			e.src = 'http://www.vimeo.com/moogaloop.swf?clip_id='.concat(GetE('videoCode').value) ;
			e.width = '400' ;
			e.height = '300' ;
		}
		ePreview.appendChild( e ) ;
		
	}
}