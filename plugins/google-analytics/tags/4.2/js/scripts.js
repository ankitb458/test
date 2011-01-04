jQuery(document).ready(function() {
	jQuery('div.ga_event, form.ga_event, form.signup_event, div.signup_event, form.form_event, div.form_event, div.ad_event, a.ga_event, a.download_event').each(function() {
		var t = jQuery(this);
		var category = t.children('input.event_category:first').val();
		var action = t.children('input.event_action:first').val();
		var label = t.children('input.event_label:first').val();
		
		if ( !category && t.is('.signup_event') )
			category = google_analyticsL10n.signup_event;
		
		if ( t.is('div.form_event, div.signup_event') ) {
			t = t.find('form:first');
			if ( !t.size() )
				return;
		}
		
		if ( !action ) {
			if ( t.hasClass('ad_event') ) {
				if ( !category )
					category = google_analyticsL10n.ad_event;
				action = google_analyticsL10n.click_event;
			} else if ( t.hasClass('download_event') ) {
				if ( !category )
					category = google_analyticsL10n.file_event;
				action = google_analyticsL10n.download_event;
				if ( !label )
					label = t.text();
			} else if ( t.is('form') ) {
				if ( !category )
					category = google_analyticsL10n.form_event;
				action = google_analyticsL10n.submit_event;
			} else {
				action = google_analyticsL10n.click_event;
			}
		}
		
		if ( !category )
			category = google_analyticsL10n.custom_event;
		
		if ( !label ) {
			if ( t.is('a') && t.attr('href') )
				label = t.attr('href');
			else if ( t.is('form') && t.attr('action') )
				label = t.attr('action');
			else if ( t.is('iframe') && t.attr('src') )
				label = t.attr('src');
			else if ( t.attr('id') )
				label = t.attr('id');
		}
		
		if ( t.is('form') ) {
			t.submit(function(e) {
				if ( e.isDefaultPrevented() )
					return false;
				
				if ( !label ) {
					try {
						window._gaq.push(['_trackEvent', category, action]);
					} catch ( err ) {}
				} else {
					var count = jQuery(this).attr('ga_count');
					count = count ? parseInt(count) + 1 : 1;
					jQuery(this).attr('ga_count', count);
					try {
						window._gaq.push(['_trackEvent', category, action, label, count]);
					} catch ( err ) {}
				}
				
				return true;
			});
		} else {
			t.click(function(e) {
				if ( !label ) {
					try {
						window._gaq.push(['_trackEvent', category, action]);
					} catch ( err ) {}
				} else {
					var count = jQuery(this).data('ga_count');
					count = count ? parseInt(count) + 1 : 1;
					jQuery(this).data('ga_count', count);
					try {
						window._gaq.push(['_trackEvent', category, action, label, count]);
					} catch ( err ) {}
				}
			});
		}
	});
	
	jQuery('a').click(function(e) {
		var t = jQuery(this);
		if ( !t.hasClass('ga_event') && !t.hasClass('download_event') ) {
			var href = jQuery(this).attr('href');
			if ( href && href.match(/^https?:/i) && !href.match(window.google_analytics_regexp) ) {
				try {
					window._gaq.push(['_trackPageview', '/outbound/?to=' + href]);
				} catch ( err ) {}
			}
		}
	});
});
