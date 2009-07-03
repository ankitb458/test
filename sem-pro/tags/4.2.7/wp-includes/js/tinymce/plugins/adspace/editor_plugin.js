


tinyMCE.importPluginLanguagePack('adspace', '');


function TinyMCE_adspace_initInstance(inst)
{
	tinyMCE.importCSS(inst.getDoc(), tinyMCE.baseURL + "/plugins/adspace/adspace.css");
}


function TinyMCE_adspace_getInfo()
{
	return {
		longname  : 'Ad Space plugin',
		author    : 'Denis de Bernardy',
		authorurl : 'http://www.semiologic.com',
		infourl   : 'http://www.semiologic.com',
		version   : "0.1"
	};
}


function TinyMCE_adspace_getControlHTML(control_name)
{
	switch (control_name) {
		case "adspace":
			var html = '<select id="{$editor_id}_ad_block_select" name="{$editor_id}_ad_block_select" onchange="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mce_ad_block_select\',false,this.options[this.selectedIndex].value);this.selectedIndex=0;" class="mceSelectList" style="font-size:8pt;">';
			html += '<option value="">Ad&nbsp;Block</option>';

			// Build format select
			html += '<option value="ad_block">' + "Default embeddable" + '</option>';
			if( all_ad_blocks )
			{
				for( var i=0; i < all_ad_blocks.length; i++ ){
					html += '<option value="ad_block#' + all_ad_blocks[i] + '">' + all_ad_blocks[i]  + '</option>';
				}

			}

			html += '</select>';
			//ad_select

			return html;
	}

	return '';
}


function TinyMCE_adspace_parseAttributes(attribute_string)
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


function TinyMCE_adspace_execCommand(editor_id, element, command, user_interface, value)
{
	var inst = tinyMCE.getInstanceById(editor_id);
	var focusElm = inst.getFocusElement();
	var doc = inst.getDoc();

	function getAttrib(elm, name) {
		return elm.getAttribute(name) ? elm.getAttribute(name) : "";
	}

	// Handle commands
	switch (command) {
			case "mce_ad_block_select":
				//var rc = alert("Value = " + value);
				//element.selectedIndex = 0; // reset menu
				//return true;

			case "mce_plugin_adspace":
				var flag = "";
				var template = new Array();
				var altMore = tinyMCE.getLang('lang_adspace_alt');

				// Is selection a image
				if (focusElm != null && focusElm.nodeName.toLowerCase() == "img") {
					flag = getAttrib(focusElm, 'class');

					if (flag != 'mce_plugin_adspace') // Not a wordpress
						return true;

					action = "update";
				}

				html = TinyMCE_adspace_make_imgtag(value);
				tinyMCE.execCommand("mceInsertContent",true,html);
				tinyMCE.selectedInstance.repaint();
				return true;

	}

	// Pass to next handler in chain
	return false;
}


function TinyMCE_adspace_make_imgtag(ad_name){
	var html = ''
			+ '<img src="' + (tinyMCE.getParam("theme_href") + "/images/spacer.gif") + '" '
			+ ' width="140" height="16" '
			+ 'alt="' + ad_name + '" title="'+ ad_name +'" class="mce_plugin_adspace" name="mce_plugin_adspace" />';
	return html;
}


function TinyMCE_adspace_cleanup(type, content) {
	switch (type) {

		case "insert_to_editor":
			var startPos = 0;
			var altMore = tinyMCE.getLang('lang_adspace_alt');
			var ad_names = new Array('ad_block');
			if( all_ad_blocks != null ){
				for(var i=0; i<all_ad_blocks.length; i++){
					ad_names.push('ad_block#' + all_ad_blocks[i]);
				}
			}

			// Parse all <!--ad_block--> tags and replace them with images
			for(var i=0; i<ad_names.length; i++){
				startPos = 0;
				//alert('<!--'+ad_names[i]+'-->');
				while ((startPos = content.indexOf('<!--'+ad_names[i]+'-->', startPos)) != -1) {
					// Insert image
					var contentAfter = content.substring(startPos + ad_names[i].length+7);
					content = content.substring(0, startPos);
					content += TinyMCE_adspace_make_imgtag(ad_names[i]);
					content += contentAfter;

					startPos++;
				}
				// go to next ad placeholder
			}

			// If any blocks weren't replaced, do it with this statement
			content = content.replace(new RegExp('<!--ad_block#([^-]+)-->', 'g'), '<strong style="color:red">[Undefined Ad Block ($1)]</strong>');

			break;

		case "get_from_editor":
			// Parse all img tags and replace them with <!--ad_block-->
			var startPos = -1;
			while ((startPos = content.indexOf('<img', startPos+1)) != -1) {
				var endPos = content.indexOf('/>', startPos);
				var attribs = TinyMCE_adspace_parseAttributes(content.substring(startPos + 4, endPos));

				if (attribs['class'] == "mce_plugin_adspace") {
					endPos += 2;

					var embedHTML = '<!--ad_block-->';
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


function TinyMCE_adspace_handleNodeChange(editor_id, node, undo_index, undo_levels, visual_aid, any_selection) {
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


