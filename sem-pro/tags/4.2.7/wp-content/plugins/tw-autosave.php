<?php
# /*
# Plugin Name: Twilight AutoSave
# Plugin URI: http://twilightuniverse.com/2005/04/twilight-autosave/
# Description: Adds the ability to auto-save content typed into WordPress via cookies. The cookie is automatically deleted when post is saved, and expires after a week if not deleted.
# Author: Gregory Wild-Smith
# Author URI: http://twilightuniverse.com/
# Version: 1.0
# */

function twAutoSave(){
if ($_GET['action'] != "edit"){
	if (!isset($_COOKIE['twWPAutoSave']) || $_COOKIE['twWPAutoSave'] == "" ){
		$cookieset = 0;
	} else {
		$cookieset = 1;
	}
?>
<script type="text/javascript">

var elem;
var data;
var eID = 'content';
var cID = 'twWPAutoSave';
var cookieset = <?php echo $cookieset; ?>;

function createCookie(name,value,days){
	if (days)
	{
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name + "=" + escape(value) + expires;
}

function eraseCookie(name){
	createCookie(name,"",-1);
}

function readCookie(name){
if (cookieset == 1){
    var dc = document.cookie;
    var prefix = name + "=";
    var begin = dc.indexOf("; " + prefix);
    if (begin == -1)
    {
        begin = dc.indexOf(prefix);
        if (begin != 0) return null;
    }
    else
    {
        begin += 2;
    }
    var end = document.cookie.indexOf(";", begin);
    if (end == -1)
    {
        end = dc.length;
    }
    return unescape(dc.substring(begin + prefix.length, end));
    } else {
    return 'Cookie Not Set!';
    }
}

function KeyPressEvent(event){
	if (document.all) {
		event = window.event;
	}
	if (event.which){
		key = event.which;
	} else {
		key = event.keyCode;
	}
	createCookie(cID,elem.value, 7);
	cookieset = 1;
}

function setStyleById(i, p, v) {
	var n = document.getElementById(i);
	n.style[p] = v;
}

function WhenLoaded(){
var postdiv;
	if (postdiv = document.getElementById('poststuff')){
		if (cookieset == 1){
			var cookietemp = readCookie('twWPAutoSave');
			var cookiedata = cookietemp.substring(0, 100);
			data = postdiv.innerHTML
			if (cookietemp.length > 100){
				var endstring = "[...]";
			}
			postdiv.innerHTML = '<div id="twAutoSave" class="updated fade"><p>You seem to have left here without saving your post. If so, you may <a href="" onclick="restoreData(); return false;">click here to restore it</a> or <a href="" onclick="eraseCookie(\'twWPAutoSave\'); setStyleById(\'twAutoSave\',\'display\', \'none\'); return false;">delete it</a>.</p><p><small>Preview of auto-saved post: "' + cookiedata + endstring + '"</small></p></div>' + data;
		}
		edCanvas = document.getElementById('content');  //re-enable quicktags with correct element (original content element was overwritten).
		elem = document.getElementById('content');
		elem.onkeyup = KeyPressEvent;
	}
}

function restoreData(){
	elem.value = readCookie(cID);
}

</script>
<?php
	}
}

function twAutoSaveFoot(){ ?>
<script type="text/javascript">
WhenLoaded();
</script> 
<?php }

function twAutoSaveDelete(){ 
	setcookie('twWPAutoSave', "", -1);
}

add_action("admin_head", "twAutoSave");
add_action("admin_footer", "twAutoSaveFoot");
add_action("save_post", "twAutoSaveDelete");
add_action("publish_post", "twAutoSaveDelete");
?>