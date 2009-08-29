function loadHashCashKey(fragment_url, e_id) {
	var xmlhttp=false;

	if(window.XMLHttpRequest) {
    		try {
			xmlhttp = new XMLHttpRequest();
       	} catch(e) {
			xmlhttp = false;
	}

	// branch for IE/Windows ActiveX version
	} else if(window.ActiveXObject) {
		try {
			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		} catch(e) {
			try {
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	        	} catch(e) {
          			xmlhttp = false;
        		}
		}
    	}

	var element = document.getElementById(e_id);

	xmlhttp.open("GET", fragment_url);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			element.value = eval(xmlhttp.responseText);
			document.getElementById('submit').disabled = false;
		}
	}

	xmlhttp.send(null);

}
