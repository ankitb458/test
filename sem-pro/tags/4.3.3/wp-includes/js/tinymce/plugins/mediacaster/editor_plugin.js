


tinyMCE.importPluginLanguagePack('mediacaster', '');


function TinyMCE_mediacaster_initInstance(inst)
{
	tinyMCE.importCSS(inst.getDoc(), tinyMCE.baseURL + "/plugins/mediacaster/mediacaster.css");
}


function TinyMCE_mediacaster_getInfo()
{
	return {
		longname  : 'Mediacaster plugin',
		author    : 'Denis de Bernardy',
		authorurl : 'http://www.semiologic.com',
		infourl   : 'http://www.semiologic.com',
		version   : "0.2"
	};
}


function TinyMCE_mediacaster_getControlHTML(control_name)
{
	switch (control_name) {
		case "mediacaster":
			var html = '<select id="{$editor_id}_media_select" name="{$editor_id}_media_select" onchange="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mce_media_select\',false,this.options[this.selectedIndex].value);this.selectedIndex=0;" class="mceSelectList" style="font-size:8pt;">';
			html += '<option value="">Media</option>';

			// Build format select
			if( document.all_media )
			{
				for( var i=0; i < document.all_media.length; i++ ){
					media_name = document.all_media[i];
					media_name = media_name.replace(/.*\//, '');
					html += '<option value="media#' + document.all_media[i] + '">' + media_name + '</option>';
				}

			}

			html += '</select>';
			//media_select

			return html;
	}

	return '';
}


function TinyMCE_mediacaster_parseAttributes(attribute_string)
{
	var attributeName = "";
	var attributeValue = "";
	var withInName;
	var withInValue;
	var attributes = new Array();
	var whiteSpaceRegExp = new RegExp('^[ \n\r\t]+', 'g');
	var titleText = tinyMCE.getLang('lang_wordpress_more');
	var titleTextPage = tinyMCE.getLang('lang_wordpress_page');

	if (attribute_string == null || attribute_string.length < 2)
		return null;

	withInName = withInValue = false;

	for (var i=0; i<attribute_string.length; i++) {
		var chr = attribute_string.charAt(i);

		if ((chr == '"' || chr == "'") && !withInValue)
			withInValue = true;
		else if ((chr == '"' || chr == "'") && withInValue) {
			withInValue = false;

			var pos = attributeName.lastIndexOf(' ');
			if (pos != -1)
				attributeName = attributeName.substring(pos+1);

			attributes[attributeName.toLowerCase()] = attributeValue.substring(1).toLowerCase();

			attributeName = "";
			attributeValue = "";
		} else if (!whiteSpaceRegExp.test(chr) && !withInName && !withInValue)
			withInName = true;

		if (chr == '=' && withInName)
			withInName = false;

		if (withInName)
			attributeName += chr;

		if (withInValue)
			attributeValue += chr;
	}

	return attributes;
}


function TinyMCE_mediacaster_execCommand(editor_id, element, command, user_interface, value)
{
	var inst = tinyMCE.getInstanceById(editor_id);
	var focusElm = inst.getFocusElement();
	var doc = inst.getDoc();

	function getAttrib(elm, name) {
		return elm.getAttribute(name) ? elm.getAttribute(name) : "";
	}

	// Handle commands
	switch (command) {
			case "mce_media_select":
				//var rc = alert("Value = " + value);
				//element.selectedIndex = 0; // reset menu
				//return true;

			case "mce_plugin_mediacaster":
				var flag = "";
				var template = new Array();
				var altMore = tinyMCE.getLang('lang_mediacaster_alt');

				// Is selection a image
				if (focusElm != null && focusElm.nodeName.toLowerCase() == "img") {
					flag = getAttrib(focusElm, 'class');

					if (flag != 'mce_plugin_mediacaster') // Not a wordpress
						return true;

					action = "update";
				}

				html = TinyMCE_mediacaster_make_imgtag(value);
				tinyMCE.execCommand("mceInsertContent",true,html);
				tinyMCE.selectedInstance.repaint();
				return true;

	}

	// Pass to next handler in chain
	return false;
}


function TinyMCE_mediacaster_make_imgtag(media_file){
	var media_ext = media_file;
	media_ext = media_ext.replace(/.*\./, '');

	switch ( media_ext )
	{
	case 'mp3':
	case 'm4a':
		var html = ''
				+ '<img src="' + (tinyMCE.baseURL + "/plugins/mediacaster/images/audio.gif") + '" '
				+ ' width="320" height="20" '
				+ 'alt="' + media_file + '" title="'+ media_file +'" class="mce_plugin_mediacaster" name="mce_plugin_mediacaster" />';
		break;
	case 'flv':
	case 'swf':
	case 'mp4':
	case 'm4v':
	case 'mov':
		var html = ''
				+ '<img src="' + (tinyMCE.baseURL + "/plugins/mediacaster/images/video.gif") + '" '
				+ ' width="320" height="260" '
				+ 'alt="' + media_file + '" title="'+ media_file +'" class="mce_plugin_mediacaster" name="mce_plugin_mediacaster" />';
		break;
		break;
	}
	return html;
}


function TinyMCE_mediacaster_cleanup(type, content) {
	switch (type) {

		case "insert_to_editor":
			var startPos = 0;
			var altMore = tinyMCE.getLang('lang_mediacaster_alt');
			var media_files = new Array();
			if( document.all_media != null ){
				for(var i=0; i<document.all_media.length; i++){
					media_files.push('media#' + document.all_media[i]);
				}
			}

			// Parse all <!--media--> tags and replace them with images
			for(var i=0; i<media_files.length; i++){
				startPos = 0;
				//alert('<!--'+media_files[i]+'-->');
				while ((startPos = content.indexOf('<!--'+media_files[i]+'-->', startPos)) != -1) {
					// Insert image
					var contentAfter = content.substring(startPos + media_files[i].length+7);
					content = content.substring(0, startPos);
					content += TinyMCE_mediacaster_make_imgtag(media_files[i]);
					content += contentAfter;

					startPos++;
				}
				// go to next media placeholder
			}

			// If any units weren't replaced, do it with this statement
			content = content.replace(new RegExp('<!--media#([^-]+)-->', 'g'), '<strong style="color:red">[Undefined Media ($1)]</strong>');

			break;

		case "get_from_editor":
			// Parse all img tags and replace them with <!--media-->
			var startPos = -1;
			while ((startPos = content.indexOf('<img', startPos+1)) != -1) {
				var endPos = content.indexOf('/>', startPos);
				var attribs = TinyMCE_mediacaster_parseAttributes(content.substring(startPos + 4, endPos));

				if (attribs['class'] == "mce_plugin_mediacaster") {
					endPos += 2;

					var embedHTML = '<!--media-->';
					if( attribs['title'] != null && attribs['title'] != '' )
						embedHTML = '<!--' + attribs['title'] + '-->';

					// Insert embed/object chunk
					chunkBefore = content.substring(0, startPos);
					chunkAfter = content.substring(endPos);
					content = chunkBefore + embedHTML + chunkAfter;
				}
			}
			break;
	}

	// Pass through to next handler in chain
	return content;
}


function TinyMCE_mediacaster_handleNodeChange(editor_id, node, undo_index, undo_levels, visual_aid, any_selection) {
	return false;

	function getAttrib(elm, name) {
		return elm.getAttribute(name) ? elm.getAttribute(name) : "";
	}

	tinyMCE.switchClassSticky(editor_id + '_wordpress_more', 'mceButtonNormal');

	if (node == null)
		return;

	do {
		if (node.nodeName.toLowerCase() == "img" && getAttrib(node, 'class').indexOf('mce_plugin_wordpress_more') == 0)
			tinyMCE.switchClassSticky(editor_id + '_wordpress_more', 'mceButtonSelected');
		if (node.nodeName.toLowerCase() == "img" && getAttrib(node, 'class').indexOf('mce_plugin_wordpress_page') == 0)
			tinyMCE.switchClassSticky(editor_id + '_wordpress_page', 'mceButtonSelected');
	} while ((node = node.parentNode));

	return true;
}


