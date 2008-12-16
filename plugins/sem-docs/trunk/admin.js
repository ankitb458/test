jQuery(document).ready( function($) {
	$('a.plugin_doc_link').click(function() {
		var help_id = this.href.replace(/.+#/, '#') + '-wrap';
		
		if ( $(help_id).hasClass('hidden') ) {
			$(help_id).removeClass('hidden');
		} else {
			$(help_id).addClass('hidden');
		}
		
		return false;
	});
});