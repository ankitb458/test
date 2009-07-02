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
 * 	Scripts related to the Video dialog window (see video_dialog.html).
 * 
 * File Authors:
 *		Mike Koepke (mike.koepke@gmail.com)
		Adapted from code by:
 * 		Ziyad Saeed (myschizobuddy@gmail.com)
 */

var oEditor		= window.parent.InnerDialogLoaded() ;
var FCK			= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;

// Get the selected flash embed (if available).
var oFakeImage = FCK.Selection.GetSelectedElement() ;
var oEmbed = null ;

if ( oFakeImage )
{
	if ( oFakeImage.tagName == 'IMG' && oFakeImage.getAttribute('_fckflash') )
	{
		oEmbed = FCK.GetRealElement( oFakeImage ) ;
	}
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
		var pnode = FCK.Selection.MoveToAncestorNode('P');
        if(pnode)
        {
			var tmpnode = FCK.EditorDocument.createElement( 'DIV' );
			pnode.parentNode.insertBefore(tmpnode, pnode.nextSibling);
			FCK.Selection.SelectNode(tmpnode);
		
		}	

		divNode		= FCK.EditorDocument.createElement( 'DIV' ) ;
		SetAttribute( divNode, 'class', 'media' ) ;	
		FCK.InsertElement(divNode);
		oEmbed		= FCK.EditorDocument.createElement( 'EMBED' ) ;
		divNode.appendChild(oEmbed);
		oFakeImage  = null ;
	}
	
	if ( !oFakeImage )
	{
		oFakeImage	= oEditor.FCKDocumentProcessor_CreateFakeImage( 'FCK__Flash', oEmbed ) ;
		oFakeImage.setAttribute( '_fckflash', 'true', 0 ) ;
		oFakeImage	= FCK.InsertElementAndGetIt( oFakeImage ) ;
	}
	else
		oEditor.FCKUndo.SaveUndoStep() ;

	UpdateEmbedObject( oEmbed ) ;
	
	oEditor.FCKFlashProcessor.RefreshView( oFakeImage, oEmbed ) ;

	return true ;
}

var ePreview ;

// called by video_preview.html file
function SetPreviewElement( previewEl )
{
	ePreview = previewEl ;
	
	if ( GetE('videoCode').value.length > 0)
		UpdatePreview();
}

var videoSite;

function SetVideoSource(source)
{
	// update the sample video ID for the selected site
	GetE('videoCode').value = source.options[source.selectedIndex].value;
	videoSite = source.options[source.selectedIndex].id;

	// set focus to video ID field
	GetE('videoCode').focus();
	GetE('videoCode').select();	
	
	UpdatePreview();
}

function UpdatePreview( )
{
	if ( !ePreview )
		return ;
		
	while ( ePreview.firstChild )
		ePreview.removeChild( ePreview.firstChild ) ;

	if ( GetE('videoCode').value.length == 0)
	{
		ePreview.innerHTML = '&nbsp;' ;
		alert( FCKLang.VideoAlertUrl ) ;
		GetE('videoCode').focus();		
	}
	else
	{
		var oDoc	= ePreview.ownerDocument || ePreview.document ;
		var e		= oDoc.createElement( 'EMBED' ) ;
		
		PopulateVideoSiteData( e );

		ePreview.appendChild( e ) ;
	}
}

function UpdateEmbedObject( e )
{
	PopulateVideoSiteData( e );

	SetAttribute(e,"quality",'high');
	SetAttribute(e,"pluginspage",'http://macromedia.com/go/getflashplayer');
}

function PopulateVideoSiteData( e )
{

	var videoID = GetE('videoCode').value;
	
	SetAttribute( e, 'type', 'application/x-shockwave-flash' ) ;
	SetAttribute( e, 'id', 'VideoPlayback' ) ;
	SetAttribute(e, "wmode",'transparent');
	
	switch (videoSite) {
		case 'selectYouTube': 
			e.src = 'http://www.youtube.com/v/' + videoID;
			SetAttribute(e, "width" , '425' ) ;
			SetAttribute(e, "height", '350' ) ;
			break
		case 'selectGoogle':
			e.src = 'http://video.google.com/googleplayer.swf?docId=' + videoID;
			SetAttribute(e, "width" , '400' ) ;
			SetAttribute(e, "height", '326' ) ;		
			break
		case 'selectYahoo':
			e.src = 'http://us.i1.yimg.com/cosmos.bcst.yahoo.com/player/media/swf/FLVVideoSolo.swf';
			SetAttribute(e, "width" , '425' ) ;
			SetAttribute(e, "height", '350' ) ;
			SetAttribute(e, "flashvars",'id=' + videoID);			
			break
		case 'selectMySpace':
			e.src = 'http://lads.myspace.com/videos/vplayer.swf';
			SetAttribute(e, "width" , '480' ) ;
			SetAttribute(e, "height", '386' ) ;
			SetAttribute(e, "flashvars",'m=' + videoID + 'type=video');			
			break			
		case 'selectAOLUnCut':
			e.src = 'http://uncutvideo.aol.com/v0.750/en-US/uc_videoplayer.swf' ;
			SetAttribute(e, "width" , '415' ) ;
			SetAttribute(e, "height", '347' ) ;
			SetAttribute(e, "wmode",'opaque');			
			SetAttribute(e, "flashvars",'&aID=1' + videoID + '&site=http://uncutvideo.aol.com/');			
			break
		case 'selectatomFilms':
			e.src = 'http://www.atomfilms.com:80/a/autoplayer/shareEmbed.swf?keyword=' + videoID;
			SetAttribute(e, "width" , '426' ) ;
			SetAttribute(e, "height", '350' ) ;
			break
		case 'selectbliptv':
			e.src = 'http://blip.tv/scripts/flash/blipplayer.swf?autoStart=false&file=http://blip.tv/file/get/' + videoID +  '/';
			SetAttribute(e, "width" , '425' ) ;
			SetAttribute(e, "height", '350' ) ;
			SetAttribute(e, "name", 'movie' ) ;			
			break
		case 'selectBreakCom':
			e.src = 'http://embed.break.com/' + videoID;
			SetAttribute(e, "width" , '425' ) ;
			SetAttribute(e, "height", '350' ) ;
			break			
		case 'selectDailyMotion':
			e.src = 'http://www.dailymotion.com/swf/' + videoID;
			SetAttribute(e, "width" , '425' ) ;
			SetAttribute(e, "height", '335' ) ;
			break
		case 'selectFreeiQ':
			e.src = 'http://freeiq.com/ipnew.swf';
			SetAttribute(e, "width" , '480' ) ;
			SetAttribute(e, "height", '338' ) ;
			SetAttribute(e, "flashvars",'playlistURL=http://freeiq.com/vidxml.dhtml?lx=' + videoID );			
			break			
		case 'selectGoFish':
			e.src = 'http://www.gofish.com/player/fwplayer.swf';
			SetAttribute(e, "width" , '343' ) ;
			SetAttribute(e, "height", '289' ) ;
			SetAttribute(e, "flashvars",'gfid=' + videoID );	
			break	
		case 'selectGrouper':
			e.src = 'http://grouper.com/mtg/mtgPlayer.swf?v=1.7';
			SetAttribute(e, "width" , '400' ) ;
			SetAttribute(e, "height", '325' ) ;
			SetAttribute(e, "flashvars",'id=' + videoID );	
			break			
		case 'selectHeavy':
			e.src = 'http://www.heavy.com/ve/flvplayer';
			SetAttribute(e, "width" , '480' ) ;
			SetAttribute(e, "height", '480' ) ;
			SetAttribute(e, "flashvars",'videoID=' + videoID );			
			break			
		case 'selectiFilm':
			e.src = 'http://www.ifilm.com/efp';
			SetAttribute(e, "width" , '448' ) ;
			SetAttribute(e, "height", '365' ) ;
			SetAttribute(e, "flashvars",'flvbaseclip=' + videoID );			
			break
		case 'selectLiveVideo':
			e.src = 'http://www.livevideo.com/flvplayer/embed/' + videoID;
			SetAttribute(e, "width" , '445' ) ;
			SetAttribute(e, "height", '369' ) ;
			break			
		case 'selectmetacafe':
			e.src = 'http://www.metacafe.com/fplayer/' + videoID;
			SetAttribute(e, "width" , '400' ) ;
			SetAttribute(e, "height", '345' ) ;
			break
		case 'selectMicrosoftSoapbox': 
			e.src = 'http://soapbox.msn.com/flash/soapbox1_1.swf';
			SetAttribute(e, "width" , '380' ) ;
			SetAttribute(e, "height", '333' ) ;
			SetAttribute(e, "name",'msn_soapbox');
			SetAttribute(e, "flashvars",'c=v&v=' + videoID );
			break
		case 'selectRevver':
			e.src = 'http://flash.revver.com/player/1.0/player.swf';
			SetAttribute(e, "width" , '480') ;
			SetAttribute(e, "height", '392') ;
			SetAttribute(e, "flashvars",'mediaId=' + videoID);
			break
		case 'selectveoh':
			e.src = 'http://www.veoh.com/videodetails2.swf?permalinkId=' + videoID;
			SetAttribute(e, "width" , '480' ) ;
			SetAttribute(e, "height", '400' ) ;
			break		
		case 'selectViddler':
			e.src = 'http://www.viddler.com/player/' + videoID;
			SetAttribute(e, "width" , '437' ) ;
			SetAttribute(e, "height", '370' ) ;
			SetAttribute(e, "allowScriptAccess", 'always' ) ;
			SetAttribute(e, "name", 'viddler' ) ;			
			break			
		case 'selectVideoJug':
			e.src = 'http://www.videojug.com/player/videoJugPlayer.swf?id=' + videoID;
			SetAttribute(e, "width" , '400' ) ;
			SetAttribute(e, "height", '345' ) ;
			break
		case 'selectVimeo':
			e.src = 'http://www.vimeo.com/moogaloop.swf?clip_id=' + videoID;
			SetAttribute(e, "width" , '400' ) ;
			SetAttribute(e, "height", '300' ) ;
			break
		case 'selectvSocial':
			e.src = 'http://www.vsocial.com/ups/' + videoID;
			SetAttribute(e, "width" , '400' ) ;
			SetAttribute(e, "height", '410' ) ;
			break			
		default:
	}
}