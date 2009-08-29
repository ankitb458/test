/*
Based in part on Flickr Gallery 0.7 by Ramon Darrow - http://www.worrad.com/
Based in part on DAlbum by Alexei Shamov, DeltaX Inc. - http://www.dalbum.org/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//-----------------------------------------------------------------------------

var falbum_prefetch_image;
var falbum_prefetch_image_src;
var falbum_remote_url;

var falbum_photo_id;
var falbum_desc;
var falbum_nodesc;
var falbum_title;
var falbum_post_value;

var falbum_next_page;
var falbum_next_id;
var falbum_prev_page;
var falbum_prev_id;
var falbum_album;
var falbum_tags;


function falbum_checkIt(string){
	falbum_place = falbum_detect.indexOf(string) + 1;
	falbum_thestring = string;
	return falbum_place;
}

//

function falbum_prefetch(imgsrc) {
	if (imgsrc.length>0 && document.getElementById)	{
		falbum_prefetch_image=new Image();
		// Find flickr-photo object and start prefetching once its loaded
		if (document.getElementById("flickr-photo")) {
			falbum_prefetch_image_src=imgsrc;
			
			if (document.getElementById("flickr-photo").complete) {
				falbum_prefetch_image.src=falbum_prefetch_image_src;
			} else {
				document.getElementById("flickr-photo").onload=new function(e) { falbum_prefetch_image.src=falbum_prefetch_image_src; }
			}
		}
	}
}

/* Annotations */
var aI = {

	init: function() {
		if (!document.getElementById ||
		!document.createElement ||
		!document.getElementsByTagName)
		return;
		var anni = document.getElementsByTagName('img');
		for (var i=0;i<anni.length;i++) {
			if ((anni[i].className.search(/\bannotated\b/) != -1) &&
			(anni[i].getAttribute('usemap') != null)) {
				aI.prepImage(anni[i]);
			}
		}
	},

	prepImage: function(img) {
		var mapName = img.getAttribute('usemap');
		var mapObj = document.getElementById('imgmap');
		var areas  = [];
		if (mapObj != null) {
			areas = mapObj.getElementsByTagName('area');
		}
		img.areas = [];
		for (var j=areas.length-1;j>=0;j--) {
			if (areas[j].getAttribute('shape').toLowerCase() == 'rect') {
				var coo = areas[j].getAttribute('coords').split(',');
				if (coo.length != 4) break;
				var a = document.createElement('a');
				a.associatedCoords = coo;
				a.style.width = (parseInt(coo[2]) - parseInt(coo[0])) + 'px';
				a.style.height = (parseInt(coo[3]) - parseInt(coo[1])) + 'px';
				var thisAreaPosition = aI.__getAreaPosition(img,coo);
				a.style.left = thisAreaPosition[0] + 'px';
				a.style.top = thisAreaPosition[1] + 'px';
				a.className = 'annotation';
				var href = areas[j].getAttribute('href');
				if (href) {
					a.href = href;
				} else {
					// set an explicit href, otherwise it doesn't count as a link
					// for IE
					a.href = "#"+j;
				}
				var s = document.createElement('span');
				s.appendChild(document.createTextNode(''));
				a.appendChild(s);

				img.areas[img.areas.length] = a;
				document.getElementsByTagName('body')[0].appendChild(a);

				aI.addEvent(a,"mouseover",
				function() {
					clearTimeout(aI.hiderTimeout);
				}
				);

				//eval("var fn"+j+" = function() {overlib( aI.getTitle("+j+"), STICKY, MOUSEOFF, BELOW, WRAP, CELLPAD, 5, FGCOLOR, '#FFFFCC', BGCOLOR, '#FFFF44', BORDER, 2, TEXTCOLOR, '#000000', TEXTSIZE, 2, TIMEOUT, 2000, DELAY, 50);}");
				eval("var fn"+j+" = function() {overlib( aI.getTitle("+j+"), STICKY, MOUSEOFF, HAUTO, VAUTO, WRAP, CSSCLASS, TEXTFONTCLASS,'annotation-fontClass',FGCLASS,'annotation-fgClass', BGCLASS,'annotation-bgClass',CAPTIONFONTCLASS,'annotation-capfontClass', TIMEOUT, 2000, DELAY, 50);}");

				aI.addEvent(a,"mouseover", eval("fn"+j));
				aI.addEvent(a,"mouseout",function() {
					nd();
				});
			}
		}

		aI.addEvent(img,"mouseover",aI.showAreas);
		aI.addEvent(img,"mouseout",aI.hideAreas);
	},

	__getAreaPosition: function(img,coo) {
		var aleft = (img.offsetLeft + parseInt(coo[0]));
		var atop = (img.offsetTop + parseInt(coo[1]));
		var oo = img;
		while (oo.offsetParent) {
			oo = oo.offsetParent;
			aleft += oo.offsetLeft;
			atop += oo.offsetTop;
		}
		return [aleft,atop];
	},

	__setAreas: function(t,disp) {
		if (!t || !t.areas) return;
		for (var i=0;i<t.areas.length;i++) {
			t.areas[i].style.display = disp;
		}
	},

	showAreas: function(e) {
		var t = null;
		if (e && e.target) t = e.target;
		if (window.event && window.event.srcElement) t = window.event.srcElement;
		// Recalculate area positions
		for (var k=0;k<t.areas.length;k++) {
			var thisAreaPosition = aI.__getAreaPosition(t,t.areas[k].associatedCoords);
			t.areas[k].style.left = thisAreaPosition[0] + 'px';
			t.areas[k].style.top = thisAreaPosition[1] + 'px';

		}
		aI.__setAreas(t,'block');
	},

	hideAreas: function(e) {
		var t = null;
		if (e && e.target) t = e.target;
		if (window.event && window.event.srcElement) t = window.event.srcElement;
		clearTimeout(aI.hiderTimeout);
		aI.hiderTimeout = setTimeout(
		function() { aI.__setAreas(t,'none') }, 300);
	},

	addEvent: function(elm, evType, fn, useCapture) {
		// cross-browser event handling for IE5+, NS6 and Mozilla
		// By Scott Andrew
		if (elm.addEventListener){
			elm.addEventListener(evType, fn, useCapture);
			return true;
		} else if (elm.attachEvent){
			var r = elm.attachEvent("on"+evType, fn);
			return r;
		} else {
			elm['on'+evType] = fn;
		}
	},

	getTitle: function(j) {
		var mapObj = document.getElementById('imgmap');
		var areas  = [];
		if (mapObj != null) {
			areas = mapObj.getElementsByTagName('area');
		}
		var t = areas[j].getAttribute('title');
		re = /(\n|\r|\r\n)/gi;
		t=t.replace(re, "");
		
		t=t.replace(/&amp;/gi,'&');
		t=t.replace(/&lt;/gi,'<');
		t=t.replace(/&gt;/gi,'>');		
		t=t.replace(/&nbsp;/gi,' ');
		t=t.replace(/&quot;/gi,'"');
		
		return t;
	}
}

aI.addEvent(window,"load",aI.init);

//

function showExif(photo_id, secret, remote_url){	
	var url = remote_url + '?action=exif&photo_id=' + photo_id + '&secret=' + secret;	
	jQuery('#exif').html('Retrieving Data ...').load(url);	
}

// Title / Description Edits

function falbum_makeEditable(id){
	
	var e = jQuery('#'+id);  
	  	 
	e.click(function(){falbum_edit(e)});
	 
    e.mouseover(function(){falbum_showAsEditable(e)});
	 
    e.mouseout(function(){falbum_showAsEditable(e, true)});
}

function falbum_showAsEditable(e, clear){  
     var id = e.get(0).id;     
     if (!clear){
          e.addClass('falbum-editable');
          if (id == 'falbum-photo-desc') {
          		if (falbum_nodesc != '') {
          			e.html(falbum_nodesc);
          		}
          }
     }else{
          e.removeClass('falbum-editable');
          if (id == 'falbum-photo-desc') {
          		if (falbum_nodesc != '') {
          			e.html('&nbsp;');
          		}
          }
     }
 }
 
function falbum_edit(e){
	e.hide();
	var id = e.get(0).id;
	
	if (id == 'falbum-photo-title') {
		var textarea ='<div id="' + id + '_editor"><input type="text" size="50" id="' + id + '_edit" name="' + id + '" value="' + e.html() + '" />';
     } else if (id == 'falbum-photo-desc'){
     	 var t = e.html();
     	 
     	 if (t == falbum_nodesc) {
     	 	t = '';
     	 }
     	 
     	 var re = /<br.*?>/gi;
     	 
     	 if (jQuery.browser == "msie") {
     	 	t=t.replace(re ,'\n');
     	 } else {
     	 	t=t.replace(re ,'');
     	 }
		     
     	 var textarea ='<div id="' + id + '_editor"><textarea id="' + id + '_edit" name="' + id + '" rows="4" cols="60">'
          + t + '</textarea>';
     }

     var button = '<br /><input id="' + id + '_save" type="button" value="SAVE" /> OR <input id="' + id + '_cancel" type="button" value="CANCEL" /></div>';

     e.after(textarea+button);
               
     jQuery('#'+id+'_save').click(function(){falbum_saveChanges(e)});
     jQuery('#'+id+'_cancel').click(function(){falbum_cleanUp(e)});	
}
 
function falbum_cleanUp(e, keepEditable){
    jQuery('#'+e.get(0).id+'_editor').remove();     
    e.show();
    if (!keepEditable) falbum_showAsEditable(e, true);
}
 
function falbum_saveChanges(e){
    var id = e.get(0).id;
     var new_content = jQuery('#'+id+'_edit').val();
      
     if (id == 'falbum-photo-desc'){
     	 if (new_content != falbum_nodesc) {
     	 	falbum_nodesc = '';
     	 }
     }
     
     e.html("Saving...");
     falbum_cleanUp(e, true);

     var success = function(t){falbum_editComplete(t, e);}
	 var failure = function(t){falbum_editFailed(t, e);}
     var pars = {
     	action: 'edit',
     	id: id ,
     	content: new_content,
     	o_desc: falbum_desc,
     	o_title: falbum_title,
     	photo_id: falbum_photo_id };    
 	 
	 jQuery.ajax({
	    url: falbum_remote_url,
	    type: "POST",
	    data: jQuery.param(pars),
	    success: success,
	    error: failure
	  });
 }

 function falbum_editComplete(t, e){
  
 	var id = e.get(0).id;
 		
 	if (id == 'falbum-photo-desc'){
		falbum_desc = t.responseText;
	} else {
     	falbum_title = t.responseText;
    } 
  
	e.html( t.responseText );
    falbum_showAsEditable(e, true);
}
 
 function falbum_editFailed(t, obj){
	e.html('Sorry, the update failed.');
    falbum_cleanUp(e);
 }

 
 // Post Helper
 
function falbum_enable_post_helper(){ 	 
    jQuery('#falbum-post-helper-switch').click(function(){falbum_show_post_helper_block()});
     
    var x=document.getElementsByName("size");
	for (i=0; i<x.length; i++){
		if(x[i].value == 's') {
			 x[i].checked = true;			 
		}	
	}
}
 
function falbum_show_post_helper_block(){     	
    jQuery('#falbum-post-helper-switch').hide();
   	jQuery('#falbum-post-helper-block').show(); 
  	
	jQuery('#falbum-post-helper-block-rb').click(function(){falbum_post_helper_update_value()});
	jQuery('#falbum-post-helper-block-close').click(function(){falbum_show_post_helper_block_close()});
}
 
function falbum_show_post_helper_block_close(){ 
   	jQuery('#falbum-post-helper-switch').show();
   	jQuery('#falbum-post-helper-block').hide();	
}
  
function falbum_post_helper_update_value() {
	
	var e = jQuery('#falbum-post-helper-value');
	var t = e.html();
 	
 	var x=document.getElementsByName("size");
 	var l=x.length;
 	var size = 't';
	for (i=0; i<l; i++){
		if(x[i].checked) {
			 size = x[i].value;
			 break;			 
		}	
	}
	
	var x=document.getElementsByName("position");
 	var l=x.length; 
 	var f = 'l';
	for (i=0; i<l; i++){
		if(x[i].checked) {
			 f = x[i].value;
			 break;			 
		}	
	} 	
	
	var x=document.getElementsByName("linkto");
 	var l=x.length; 
 	var linkto = 'p';
	for (i=0; i<l; i++){
		if(x[i].checked) {
			 linkto = x[i].value;
			 break;			 
		}	
	} 	
	 	
   	var re = new RegExp('(.*(j|justification)=)(l|left|r|right|c|center)(.*])', 'g');   	
   	t = t.replace(re, '$1'+f+'$4');
   	
   	var re = new RegExp('(.*(s|size)=)(sq|t|s|m|l|o)(.*])', 'g');   	
   	t = t.replace(re, '$1'+size+'$4');
   	
   	var re = new RegExp('(.*(l|linkto)=)(index|photo|i|p)(.*])', 'g');   	
   	t = t.replace(re, '$1'+linkto+'$4');
    
    e.html(t);
}
 
function falbum_show_photo(dir) {
	
	var id;
	var page;
	
	if (dir == 'next') {		
		id = falbum_next_id;
		page = falbum_next_page;
	} else {
		id = falbum_prev_id;
		page = falbum_prev_page;
	}
	
	
	var success = function(t){	 	
		var e = jQuery("#falbum");		
		e.set('id','falbum_delete_me');
		e.empty();		
		e.before(t.responseText);
		e.remove();
	}
	
	var failure = function(t){
	 	alert('error');
	}
	
	var pars = {
     	action: 'show_photo',
     	photo: id ,
     	page: page,
     	album: falbum_album,
     	tags: falbum_tags };   		
 	 
	 jQuery.ajax({
	    url: falbum_remote_url,
	    type: "POST",
	    data: jQuery.param(pars),
	    success: success,
	    error: failure
	  });
}

