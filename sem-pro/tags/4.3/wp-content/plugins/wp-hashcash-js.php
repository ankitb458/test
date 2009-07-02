<?php
# fix php 5.2

if ( !function_exists('ob_end_flush_all') ) :
function ob_end_flush_all()
{
	while ( @ob_end_flush() );
}

register_shutdown_function('ob_end_flush_all');
endif;


	ob_start("ob_gzhandler");
	require_once(dirname(dirname(dirname(__FILE__))) . '/wp-config.php');
	require_once(realpath(dirname(__FILE__) . '/') . '/wp-hashcash.lib');

	$field_id = hashcash_random_string(rand(6,18));
	$fn_enable_name = hashcash_random_string(rand(6,18));
?>
var wphc_loaded = false;
addLoadEvent(<?php echo $fn_enable_name; ?>);

function overloadOnsubmitHandler(){
	var e = document.getElementById('<?php echo HASHCASH_FORM_ID; ?>');
  e.onsubmit = checkLoadedStatus;
  if (e.captureEvents) e.captureEvents(Event.SUBMIT);
}

function checkLoadedStatus(e){
	if (!e) var e = window.event;

	if(!wphc_loaded){
		alert('Wordpress Hashcash has not finished downloading information from the server.  Please wait and try again in a few momemnts.');
		return false;
	} else {
		return true;
	}
}

function createHiddenField(){
	var inp = document.createElement('input');
	inp.setAttribute('type', 'hidden');
	inp.setAttribute('id', '<?php echo $field_id; ?>');
	inp.setAttribute('name', 'hashcash_value');
	inp.setAttribute('value', '-1');

	var e = document.getElementById('<?php echo HASHCASH_FORM_ID; ?>');
	e.appendChild(inp);
}

function addVerbage(){
	var e = document.getElementById('<?php echo HASHCASH_FORM_ID; ?>');
	var p = document.createElement('p');
	p.innerHTML = '<?php echo str_replace("'", "\'", hashcash_verbage()); ?>';
	e.appendChild(p);
}

function <?php echo $fn_enable_name;?>(){
	overloadOnsubmitHandler();
	createHiddenField();
	//addVerbage();
	loadHashCashKey('<?php echo trailingslashit(get_option('siteurl')); ?>wp-content/plugins/wp-hashcash-getkey.php', '<?php echo $field_id; ?>');
}

function loadHashCashKey(fragment_url, e_id) {
	var xmlhttp=createXMLHttp();
	var element = document.getElementById(e_id);

	xmlhttp.open("GET", fragment_url);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			element.value = eval(xmlhttp.responseText);
			wphc_loaded = true;
		}
	}

	xmlhttp.send(null);
}

function createXMLHttp() {
	if (typeof XMLHttpRequest != "undefined")
		return new XMLHttpRequest();

	var xhrVersion = [ "MSXML2.XMLHttp.5.0", "MSXML2.XMLHttp.4.0","MSXML2.XMLHttp.3.0", "MSXML2.XMLHttp","Microsoft.XMLHttp" ];

	for (var i = 0; i < xhrVersion.length; i++) {
  	try {
			var xhrObj = new ActiveXObject(xhrVersion[i]);
      return xhrObj;
    } catch (e) { }
  }

  return null;
}

function addLoadEvent(func) {
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      func();
			oldonload();
    }
  }
}