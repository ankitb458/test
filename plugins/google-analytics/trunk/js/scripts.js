jQuery(document).ready(function() {
	jQuery('div.ga_event, form.ga_event, a.ga_event, div.ad_event, a.download_event, form.signup_event, div.signup_event, form.form_event, div.form_event').each(function() {
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
			if ( t.is('form') ) {
				if ( !category )
					category = google_analyticsL10n.form_event;
				action = google_analyticsL10n.submit_event;
			} else if ( t.hasClass('ad_event') ) {
				if ( !category )
					category = google_analyticsL10n.ad_event;
				action = google_analyticsL10n.click_event;
			} else if ( t.hasClass('download_event') ) {
				if ( !category )
					category = google_analyticsL10n.file_event;
				action = google_analyticsL10n.download_event;
				if ( !label )
					label = t.text();
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
			else if ( t.attr('id') )
				label = t.attr('id');
		}
		
		if ( t.is('form') ) {
			t.submit(function(e) {
				if ( e.isDefaultPrevented() )
					return false;
				
				if ( !label ) {
					window.pageTracker._trackEvent(category, action);
				} else {
					var count = jQuery(this).attr('ga_count');
					count = count ? parseInt(count) + 1 : 1;
					jQuery(this).attr('ga_count', count);
					window.pageTracker._trackEvent(category, action, label, count);
				}
				
				return true;
			});
		} else {
			t.live('click', function(e) {
				if ( !label ) {
					window.pageTracker._trackEvent(category, action);
				} else {
					var count = jQuery(this).attr('ga_count');
					count = count ? parseInt(count) + 1 : 1;
					jQuery(this).attr('ga_count', count);
					window.pageTracker._trackEvent(category, action, label, count);
				}
			});
		}
	});
});
