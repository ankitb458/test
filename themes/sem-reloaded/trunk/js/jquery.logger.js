jQuery.fn.log = function(name) {
	var d = ( new Date ).getTime(), c = d - jQuery.lastLog, e = d - jQuery.started;
	if ( name ) {
		var evt = {};
		evt.name = name;
		evt.completed = c;
		evt.elapsed = e;
		evt.size = this.length ? this.length : '';
		jQuery.logs.push(evt);
	}
	jQuery.lastLog = d;
	return this;
}

jQuery.log = function(name) {
	jQuery.fn.log(name);
}

jQuery.initLogs = function() {
	var d = ( new Date ).getTime(), ret = d - jQuery.started;
	jQuery.started = d;
	jQuery.lastLog = d;
	return ret;
}

jQuery.dumpLogs = function() {
	var ret = new Array;
	var i;
	ret.push('Total\tLength\tSize\tName');
	for ( i = 0; i < jQuery.logs.length; i++ ) {
		ret.push(jQuery.logs[i].elapsed + 'ms\t' + jQuery.logs[i].completed + 'ms\t' + jQuery.logs[i].size + '\t' + jQuery.logs[i].name);
	}
	jQuery('#jsdump').html(jQuery.trim(ret.join('\n')));
}

jQuery.logs = new Array;
jQuery.started = ( new Date ).getTime();
jQuery.initLogs();
jQuery.log('rendering - start');