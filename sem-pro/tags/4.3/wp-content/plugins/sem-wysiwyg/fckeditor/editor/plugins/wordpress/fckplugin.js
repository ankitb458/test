/*
* Modified: 2006-May-16
*
* File Authors:
*		Luigi Maniscalco (l.maniscalco@visioni.info)
*		LiuCougar
*		Denis de Bernardy <http://www.semiologic.com>
*/

// Define the command.

var FCKWordPress = function( name )
{
    this.Name = name ;
    this.EditMode = FCK.EditMode;
}

FCKWordPress.prototype.Execute = function()
{
    var iname = this.Name.toLowerCase();
    switch ( iname )
    {
        case 'more' :
        case 'nextpage' :
        case 'contactform' :
        case 'newsletter' :
            var pnode = FCKSelection.MoveToAncestorNode('P');
            if(pnode)
            {

                var tmpnode = FCK.EditorDocument.createElement( 'DIV' );
                tmpnode.innerHTML = CreateFakeElement(iname);
                var node = tmpnode.firstChild;
                pnode.parentNode.insertBefore(node, pnode.nextSibling);
            }
            else
            {
                FCK.InsertHtml(CreateFakeElement(iname));
            }
            break;
        case 'podcast' :
            var audiofile = '';
            audiofile = prompt('Enter an audio file (.mp3)', ( FCKConfig.BaseHref + 'audio/' ));
            audiofile = audiofile.replace(/^\s*|\s*$/g, '');

	       	if ( audiofile )
            {

	       		var pnode = FCKSelection.MoveToAncestorNode('P');
	            if(pnode)
	            {
	                var tmpnode = FCK.EditorDocument.createElement( 'DIV' );
	                tmpnode.innerHTML = CreateFakePodcastElement(audiofile);
	                var node = tmpnode.firstChild;
	                pnode.parentNode.insertBefore(node, pnode.nextSibling);
	            }
	            else
	        		FCK.InsertHtml(CreateFakePodcastElement(audiofile));
        	}
        	break;

        case 'videocast' :
       		var videofile = '';
            videofile = prompt('Enter a flash movie (.flv)', ( FCKConfig.BaseHref + 'movies/' ));
            videofile = videofile.replace('/^\s*|\s*$/g', '');

            videowidth = prompt('Enter the flash movie\'s width', 320);
            videoheight = prompt('Enter the flash movie\'s width', 240);

            if ( videofile )
            {
            	videofile = videofile + '#' + videowidth + '#' + videoheight;

	       		var pnode = FCKSelection.MoveToAncestorNode('P');
	            if(pnode)
	            {
	                var tmpnode = FCK.EditorDocument.createElement( 'DIV' );
	                tmpnode.innerHTML = CreateFakeVideocastElement(videofile);
	                var node = tmpnode.firstChild;
	                pnode.parentNode.insertBefore(node, pnode.nextSibling);
	            }
	            else
	        		FCK.InsertHtml(CreateFakeVideocastElement(videofile));
        	}
        	break;
        default :
    }
}

function CreateFakeElement(fakename)
{
	return '<img class="wordpress_'+fakename+'" _fckwordpress="'+fakename+'" src="'+FCKConfig.EditorPath+'editor/plugins/wordpress/'+fakename+'_bug.gif" _fckfake _moz_resizing="true" />';
}

function CreateFakePodcastElement(audiofile)
{
	return '<img class="wordpress_podcast" _fckwordpress="podcast#'+audiofile+'" src="'+FCKConfig.EditorPath+'editor/plugins/wordpress/podcast_bug.gif" _fckfake _moz_resizing="true" />';
}

function CreateFakeVideocastElement(videofile)
{
	return '<img class="wordpress_videocast" _fckwordpress="videocast#'+videofile+'" src="'+FCKConfig.EditorPath+'editor/plugins/wordpress/videocast_bug.gif" _fckfake _moz_resizing="true" />';
}

function CreateFakeAdUnitElement(adunit)
{
	if (adunit)
	{
		return '<img class="wordpress_adunit" _fckwordpress="adunit#'+adunit+'" src="'+FCKConfig.EditorPath+'editor/plugins/wordpress/adunit_bug.gif" _fckfake _moz_resizing="true" />';
	}
	else
	{
		return '<img class="wordpress_adunit" _fckwordpress="adunit" src="'+FCKConfig.EditorPath+'editor/plugins/wordpress/adunit_bug.gif" _fckfake _moz_resizing="true" />';
	}
}

// We must process the DIV tags to replace then with the real tag
FCKXHtml.TagProcessors['img'] = function( node, htmlNode )
{
		// Tag processors don't chain so we need to duplicate it's functionality here
		var sSavedUrl = htmlNode.getAttribute( '_fcksavedurl' ) ;
		if ( sSavedUrl != null )
			FCKXHtml._AppendAttribute( node, 'src', sSavedUrl ) ;

		// The "ALT" attribute is required in XHTML.. 
		if ( ! node.attributes.getNamedItem( 'alt' ) )
			FCKXHtml._AppendAttribute( node, 'alt', '' ) ;
			
		// now for the WordPress stuff...
        var _fckwordpress = htmlNode.getAttribute('_fckwordpress');
        if(_fckwordpress == undefined)
            return node;
        _fckwordpress = _fckwordpress.toLowerCase();
        var wrapinp = false;
        if ( _fckwordpress.match(/#.*/) )
        {
			var attribute = new String(_fckwordpress.match(/#.*/));
        	_fckwordpress = _fckwordpress.replace(attribute, '');
        	//alert(_fckwordpress);
        	attribute = attribute.replace(/^#/g, '');
		    attribute = attribute.replace(/^\s*|\s*$/g, '');
		    //alert(attribute);
		}
		else
		{
			attribute = false;
		}

        switch ( _fckwordpress )
        {
            case 'more':
//                 wrapinp = true;
                //fall through
            case 'nextpage':
            case 'contactform':
            case 'newsletter':
                var comment = FCKXHtml.XML.createComment( _fckwordpress );

                var para = FCKTools.GetElementAscensor(htmlNode.parentNode, 'p,div');
                if(wrapinp && !para)
                {
                    node = FCKXHtml.XML.createElement( 'p' );
                    node.appendChild(comment);
                } else
                    node = comment;

                break;
            case 'podcast':
            case 'videocast':
            case 'adunit':
            	var tag = _fckwordpress;

            	if ( attribute )
            	{
	            	tag = tag + '#' + attribute;
            	}
				var comment = FCKXHtml.XML.createComment( tag );

                var para = FCKTools.GetElementAscensor(htmlNode.parentNode, 'p,div');
                if(wrapinp && !para)
                {
                    node = FCKXHtml.XML.createElement( 'p' );
                    node.appendChild(comment);
                } else
                    node = comment;

                break;
            default:
        }
        return node ;
}

FCKWordPress.prototype.GetState = function()
{
    return FCK_TRISTATE_OFF ;
}


// if ( FCKBrowserInfo.IsIE )
FCKWordPress.Redraw = function()
{
	if ( FCK.EditorDocument )
	{
 	   var xhtml = FCKConfig.ProtectedSource.Revert(FCK.EditorDocument.body.innerHTML, false);
//     alert(xhtml);
//    var wpPlaholders = xhtml.match( /<\!\-\-(more|nextpage|contactform)\-\->/g ) ;
//    if ( !wpPlaholders )
//        return ;

//         FCK.EditorDocument.body.innerText =
// 		var oRange = FCK.EditorDocument.body.createTextRange() ;
	    xhtml = xhtml.replace(/(?:<p>\s*)?<\!\-\-\s*(more|nextpage|contactform|newsletter)\s*\-\->(?:<\/p>)?/g, CreateFakeElement('$1'));
	    xhtml = xhtml.replace(/<\!\-\-\s*podcast\s*#([^>]*)\-\->/ig, CreateFakePodcastElement('$1'));
	    xhtml = xhtml.replace(/<\!\-\-\s*videocast\s*#([^>]*)\-\->/ig, CreateFakeVideocastElement('$1'));
    	xhtml = xhtml.replace(/<\!\-\-\s*(?:ad(?:_)?(?:unit|block|space|sense))\s*\-\->/ig, CreateFakeAdUnitElement(''));
	    xhtml = xhtml.replace(/<\!\-\-\s*(?:ad(?:_)?(?:unit|block|space|sense))\s*#+([^>]*)?\-\->/ig, CreateFakeAdUnitElement('$1'));
	    FCK.EditorDocument.body.innerHTML = FCKConfig.ProtectedSource.Protect(xhtml);
	}
}

<!------------------------------------>
/*
    Make sure to include the wp tags within the WYSIWYG tags
    Make sure those tags aren't visible outside of FCKeditor...best to use relative tags
*/
var CodeToSource = function()
{
    /*
    var currEditMode = FCK.EditMode;
    var HTMLtext = FCK.GetXHTML( FCKConfig.FormatSource );
    FCK.SetHTML(HTMLtext, false);
    */
    return;
}
var CodeToWYSIWYG = function()
{
    /*
    var currEditMode = FCK.EditMode;
    var HTMLtext = FCK.GetXHTML( FCKConfig.FormatSource );
    FCK.SetHTML(HTMLtext, false);
    */
    return;
}
<!------------------------------------>


/*****************************************************************
// ad unit combobox
******************************************************************/

/*----------------- FCKAdUnitComboCommand --------------------------*/

var FCKAdUnitComboCommand = function()
{
    this.Name="InsertAdUnit";
}

FCKAdUnitComboCommand.prototype.Execute = function(itemid, item)
{
    if (itemid)
    {
    	var adunit = itemid;
    }
    else
    {
    	var adunit = '';
    }
    FCK.InsertHtml(CreateFakeAdUnitElement(adunit))
}

FCKAdUnitComboCommand.prototype.GetState = function()
{
    return FCK_TRISTATE_OFF ;
}

/*------------------ Registering the Command ------------------------------*/
FCKCommands.RegisterCommand( 'InsertAdUnit', new FCKAdUnitComboCommand() ) ;



/*------------ The ToolBarItem --------------------------*/

//creation function
var FCKToolbarAdUnitCombo = function( tooltip, style )
{
	this.CommandName= 'InsertAdUnit' ;
	this.Command    = FCKCommands.GetCommand('InsertAdUnit');
	this.Label		= this.GetLabel() ;
	this.Tooltip	= tooltip ? tooltip : this.Label ;
	this.Style		= style ? style : FCK_TOOLBARITEM_ICONTEXT ;
}

// Inherit from FCKToolbarSpecialCombo.
FCKToolbarAdUnitCombo.prototype = new FCKToolbarSpecialCombo ;

FCKToolbarAdUnitInsertAd = new Object();

FCKToolbarAdUnitCombo.prototype.GetLabel = function()
{
	return 'Ad Unit';
}

FCKToolbarAdUnitCombo.prototype.CreateItems = function( targetSpecialCombo )
{
	targetSpecialCombo.FieldWidth = 70 ;

	var ad_units = window.parent.document.all_ad_blocks;

	this._Combo.AddItem( '', 'Default Inline', 'Default Inline' ) ;

	for ( var i = 0 ; i < ad_units.length ; i++ )
	{
		this._Combo.AddItem( ad_units[i], ad_units[i], ad_units[i] ) ;
	}
}


/*------------------------------------------------------------------------------------------------------------------*/
/*------------------------------------------------------------------------------------------------------------------*/

// Register the WordPress tag commands.
FCKCommands.RegisterCommand( 'WordPressMore', new FCKWordPress( 'More' ) ) ;
FCKCommands.RegisterCommand( 'WordPressNextPage', new FCKWordPress( 'NextPage' ) ) ;
FCKCommands.RegisterCommand( 'WordPressContactForm', new FCKWordPress( 'ContactForm' ) ) ;
FCKCommands.RegisterCommand( 'WordPressNewsletter', new FCKWordPress( 'Newsletter' ) ) ;
FCKCommands.RegisterCommand( 'WordPressPodcast', new FCKWordPress( 'Podcast' ) ) ;
FCKCommands.RegisterCommand( 'WordPressVideocast', new FCKWordPress( 'Videocast' ) ) ;

FCK.Events.AttachEvent( 'OnAfterSetHTML', FCKWordPress.Redraw ) ;

FCKWordPress.Redraw();




// Create the WordPress tag buttons.
var oWordPressItem = new FCKToolbarButton( 'WordPressMore', 'More...', null, FCK_TOOLBARITEM_ICONTEXT, true, true ) ;
oWordPressItem.IconPath = FCKPlugins.Items['wordpress'].Path + 'more.gif' ;
FCKToolbarItems.RegisterItem( 'WordPressMore', oWordPressItem ) ;

var oWordPressItem = new FCKToolbarButton( 'WordPressNextPage', 'Next Page', null, FCK_TOOLBARITEM_ICONTEXT, true, true ) ;
oWordPressItem.IconPath = FCKPlugins.Items['wordpress'].Path + 'nextpage.gif' ;
FCKToolbarItems.RegisterItem( 'WordPressNextPage', oWordPressItem ) ;

var oWordPressItem = new FCKToolbarButton( 'WordPressContactForm', 'Contact', null, FCK_TOOLBARITEM_ONLYICON, true, true ) ;
oWordPressItem.IconPath = FCKPlugins.Items['wordpress'].Path + 'contactform.gif' ;
FCKToolbarItems.RegisterItem( 'WordPressContactForm', oWordPressItem ) ;

var oWordPressItem = new FCKToolbarButton( 'WordPressNewsletter', 'Newsletter', null, FCK_TOOLBARITEM_ONLYICON, true, true ) ;
oWordPressItem.IconPath = FCKPlugins.Items['wordpress'].Path + 'newsletter.gif' ;
FCKToolbarItems.RegisterItem( 'WordPressNewsletter', oWordPressItem ) ;

var oWordPressItem = new FCKToolbarButton( 'WordPressPodcast', 'Podcast', null, FCK_TOOLBARITEM_ONLYICON, true, true ) ;
oWordPressItem.IconPath = FCKPlugins.Items['wordpress'].Path + 'podcast.gif' ;
FCKToolbarItems.RegisterItem( 'WordPressPodcast', oWordPressItem ) ;

var oWordPressItem = new FCKToolbarButton( 'WordPressVideocast', 'Videocast', null, FCK_TOOLBARITEM_ONLYICON, true, true ) ;
oWordPressItem.IconPath = FCKPlugins.Items['wordpress'].Path + 'videocast.gif' ;
FCKToolbarItems.RegisterItem( 'WordPressVideocast', oWordPressItem ) ;

var oWordPressItem = new FCKToolbarAdUnitCombo( 'Ad&nbsp;Unit', FCK_TOOLBARITEM_ONLYTEXT );
oWordPressItem.IconPath = FCKPlugins.Items['wordpress'].Path + 'adunit.gif' ;
FCKToolbarItems.RegisterItem( 'WordPressAdUnit', oWordPressItem ) ;