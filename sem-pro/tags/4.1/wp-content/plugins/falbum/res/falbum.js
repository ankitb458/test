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

var falbum_detect = navigator.userAgent.toLowerCase();
var falbum_browser,falbum_version, falbum_thestring;

if (falbum_checkIt('konqueror')) {
	falbum_browser = "Konqueror";
} else if (falbum_checkIt('safari')) falbum_browser = "Safari"
else if (falbum_checkIt('omniweb')) falbum_browser = "OmniWeb"
else if (falbum_checkIt('opera')) falbum_browser = "Opera"
else if (falbum_checkIt('webtv')) falbum_browser = "WebTV";
else if (falbum_checkIt('icab')) falbum_browser = "iCab"
else if (falbum_checkIt('msie')) falbum_browser = "IE"
else if (!falbum_checkIt('compatible')) {
	falbum_browser = "Netscape Navigator"
	falbum_version = falbum_detect.charAt(8);
} else falbum_browser = "An unknown browser";

if (!falbum_version) falbum_version = falbum_detect.charAt(falbum_place + falbum_thestring.length);

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

///

var element = null;
var req = null;

function showExif(photo_id, secret, remote_url){
	element = document.getElementById("exif");
	element.innerHTML='Retrieving Data ...';

	var url = remote_url + '?action=exif&photo_id=' + photo_id + '&secret=' + secret;

	// branch for native XMLHttpRequest object
	if (window.XMLHttpRequest) {
		req = new XMLHttpRequest();
		req.onreadystatechange = processReqChange;
		req.open("GET", url, true);
		req.send(null);
		// branch for IE/Windows ActiveX version
	} else if (window.ActiveXObject) {
		req = new ActiveXObject("Microsoft.XMLHTTP");
		if (req) {
			req.onreadystatechange = processReqChange;
			req.open("GET", url, true);
			req.send();
		}
	}
}

function processReqChange() {
	// only if req shows "complete"
	if (req.readyState == 4) {
		// only if "OK"
		if (req.status == 200) {
			element.innerHTML=req.responseText;
		} else {
			alert("There was a problem retrieving the XML data:\n" + req.statusText);
		}
	}
}


// Title / Description Edits

function falbum_makeEditable(id){
     Event.observe(id, 'click', function(){falbum_edit($(id))}, false);
     Event.observe(id, 'mouseover', function(){falbum_showAsEditable($(id))}, false);
     Event.observe(id, 'mouseout', function(){falbum_showAsEditable($(id), true)}, false);
}

function falbum_showAsEditable(obj, clear){
     if (!clear){
          Element.addClassName(obj, 'falbum-editable');
          if (obj.id == 'falbum-photo-desc') {
          		if (falbum_nodesc != '') {
          			obj.innerHTML = falbum_nodesc;
          		}
          }
     }else{
          Element.removeClassName(obj, 'falbum-editable');
          if (obj.id == 'falbum-photo-desc') {
          		if (falbum_nodesc != '') {
          			obj.innerHTML = '&nbsp;';
          		}
          }
     }
 }
 
function falbum_edit(obj){
     Element.hide(obj);

	if (obj.id == 'falbum-photo-title') {
		var textarea ='<div id="' + obj.id + '_editor"><input type="text" size="50" id="' + obj.id + '_edit" name="' + obj.id + '" value="' + obj.innerHTML + '" />';
     } else if (obj.id == 'falbum-photo-desc'){
     	 var t = obj.innerHTML;
     	 
     	 if (t == falbum_nodesc) {
     	 	t = '';
     	 }
     	 
     	 re = /<br.*?>/gi;
     	 
     	 if (falbum_browser == "IE") {
     	 	t=t.replace(re ,'\n');
     	 } else {
     	 	t=t.replace(re ,'');
     	 }
		     
     	 var textarea ='<div id="' + obj.id + '_editor"><textarea id="' + obj.id + '_edit" name="' + obj.id + '" rows="4" cols="60">'
          + t + '</textarea>';
     }

     var button = '<br /><input id="' + obj.id + '_save" type="button" value="SAVE" /> OR <input id="' + obj.id + '_cancel" type="button" value="CANCEL" /></div>';

     new Insertion.After(obj, textarea+button);

     Event.observe(obj.id+'_save', 'click', function(){falbum_saveChanges(obj)}, false);
     Event.observe(obj.id+'_cancel', 'click', function(){falbum_cleanUp(obj)}, false);

 }
 
function falbum_cleanUp(obj, keepEditable){
     Element.remove(obj.id+'_editor');
     Element.show(obj);
     if (!keepEditable) falbum_showAsEditable(obj, true);
 }
 
function falbum_saveChanges(obj){
     var new_content = escape($F(obj.id+'_edit'));
     
     if (obj.id == 'falbum-photo-desc'){
     	 if (new_content != falbum_nodesc) {
     	 	falbum_nodesc = '';
     	 }
     }
     
     obj.innerHTML = "Saving...";
     falbum_cleanUp(obj, true);

     var success = function(t){falbum_editComplete(t, obj);}
     var failure = function(t){falbum_editFailed(t, obj);}
     
     var pars = 'action=edit&id=' + obj.id + '&content=' + escape(new_content) + '&o_desc=' + escape(falbum_desc) + '&o_title=' + escape(falbum_title) + '&photo_id=' + falbum_photo_id;
      
     var myAjax = new Ajax.Request(falbum_remote_url, {method:'post',
          postBody:pars, onSuccess:success, onFailure:failure});
 }

 function falbum_editComplete(t, obj){
 		
 	if (obj.id == 'falbum-photo-desc'){
		falbum_desc = t.responseText;
	} else {
     	falbum_title = t.responseText;
    } 
      
     obj.innerHTML = t.responseText;
     falbum_showAsEditable(obj, true);
 }

 function falbum_editFailed(t, obj){
     obj.innerHTML = 'Sorry, the update failed.';
     falbum_cleanUp(obj);
 }
 
 
 // Post Helper
 
function falbum_enable_post_helper(){
 	var id = $('falbum-post-helper-switch');
    Event.observe(id, 'click', function(){falbum_show_post_helper_block()}, false);
     
    var x=document.getElementsByName("size");
	for (i=0; i<x.length; i++){
		if(x[i].value == 's') {
			 x[i].checked = true;			 
		}	
	}
}
 
function falbum_show_post_helper_block(){ 
   	Element.hide( $('falbum-post-helper-switch') );      
   	Element.show( $('falbum-post-helper-block') );    
   	
   	Event.observe('falbum-post-helper-block-rb', 'click', function(){falbum_post_helper_update_value()}, false);    
	Event.observe('falbum-post-helper-block-close', 'click', function(){falbum_show_post_helper_block_close()}, false);
}
 
function falbum_show_post_helper_block_close(){ 
   	Element.show( $('falbum-post-helper-switch') );      
   	Element.hide( $('falbum-post-helper-block') ); 
}
  
function falbum_post_helper_update_value() {
 	var v = $('falbum-post-helper-value'); 	
 	var t = v.innerHTML;
 	
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
 	var float = 'l';
	for (i=0; i<l; i++){
		if(x[i].checked) {
			 var float = x[i].value;
			 break;			 
		}	
	} 	
	
	var x=document.getElementsByName("linkto");
 	var l=x.length; 
 	var linkto = 'p';
	for (i=0; i<l; i++){
		if(x[i].checked) {
			 var linkto = x[i].value;
			 break;			 
		}	
	} 	
	 	
   	var re = new RegExp('(.*(j|justification)=)(l|left|r|right|c|center)(.*])', 'g');   	
   	t = t.replace(re, '$1'+float+'$4');
   	
   	var re = new RegExp('(.*(s|size)=)(sq|t|s|m|l|o)(.*])', 'g');   	
   	t = t.replace(re, '$1'+size+'$4');
   	
   	var re = new RegExp('(.*(l|linkto)=)(index|photo|i|p)(.*])', 'g');   	
   	t = t.replace(re, '$1'+linkto+'$4');
    
    v.innerHTML = t;
}
 

