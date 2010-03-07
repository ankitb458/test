jQuery(document).ready(function($) {
	$(':input.product-group').data('group', 'product-group').data('captions', sem_backendL10n.product);
	$(':input.campaign-group').data('group', 'campaign-group').data('captions', sem_backendL10n.campaign);
	$(':input.coupon-group').data('group', 'coupon-group').data('captions', sem_backendL10n.campaign);
	
	// todo: target form:onsubmit instead and disable the buttons
	// todo: clear the timeout in the event a double click manages to pass through
	$('#publish, #save, #hidden-save').click(function() {
		$('#ajax-loading').css('visibility', '');
		setTimeout(function() {
			$('#ajax-loading').css('visibility', 'hidden');
		}, 3000);
	});
	
	$('#search-button, #filters-submit').click(function() {
		$('#bulk_action, #bulk_action2, #bulk_manage, #bulk_manage2').attr('disabled', 'disabled');
		$('#_wpnonce, input[name=_wp_http_referer]').attr('disabled', 'disabled');
		$('#search-input, #aff_id, #product_id').filter('[value=]').attr('disabled', 'disabled');
	});
	
	$('input.coupon_code').keyup(function() {
		$('input.coupon_code').not(this).val($(this).val());
	});
	
	$('input.sbsuggest').focus(function() {
		var suggest = $(this),
			hidden_suggest = $('#hidden_' + suggest.attr('id')),
			id = $('#' + suggest.attr('id').replace(/^suggest_/, '')),
			hidden_id = $('#hidden_' + suggest.attr('id').replace(/^suggest_/, ''));
		if ( suggest.val() == suggest.attr('title') ) {
			suggest.val('');
		} else {
			setTimeout(function() {
				suggest.select();
			}, 5);
		}
		hidden_id.val(id.val());
		hidden_suggest.val(suggest.val());
	}).blur(function() {
		var suggest = $(this),
			hidden_suggest = $('#hidden_' + suggest.attr('id')),
			id = $('#' + suggest.attr('id').replace(/^suggest_/, '')),
			hidden_id = $('#hidden_' + suggest.attr('id').replace(/^suggest_/, ''));
		if ( !suggest.val() ) {
			id.val('');
			hidden_id.val('');
			suggest.val(suggest.attr('title'));
			hidden_suggest.val('');
			suggest.removeData('item');
		} else {
			id.val(hidden_id.val());
			suggest.val(hidden_suggest.val());
		}
	}).keyup(function(e) {
		var suggest = $(this);
		if ( suggest.val() !== '' )
			return;
		var hidden_suggest = $('#hidden_' + suggest.attr('id')),
			id = $('#' + suggest.attr('id').replace(/^suggest_/, '')),
			hidden_id = $('#hidden_' + suggest.attr('id').replace(/^suggest_/, ''));
		id.val('');
		hidden_id.val('');
		hidden_suggest.val('');
		suggest.removeData('item');
	}).each(function() {
		var suggest = $(this),
			hidden_suggest = $('#hidden_' + suggest.attr('id')),
			id = $('#' + suggest.attr('id').replace(/^suggest_/, '')),
			hidden_id = $('#hidden_' + suggest.attr('id').replace(/^suggest_/, '')),
			args = {
				minchars: 1,
				resultsClass:'s_results',
				selectClass:'s_over',
				matchClass:'s_match',
				createItems: function(txt) {
					var items,
						ret = new Array();
					try {
						if ( typeof JSON !== 'undefined' && typeof JSON.parse === 'function' )
							items = JSON.parse(txt);
						else
							items = eval('(' + txt + ')');
					} catch ( e ) {
						return new Array('An unknown error occurred...');
					}

					return items;
				},
				formatItem:function(item, q) {
					if ( typeof item.preview !== 'undefined' )
						return '<li>' + this.addMatchClass(item.preview, q) + '</li>';
					else
						return '<li>' + this.addMatchClass(item.name, q) + '</li>';
				},
				selectItemText:function(item) {
					if ( typeof item.display !== 'undefined' )
						return item.display;
					else
						return item.name;
				},
				onSelect: function(item){
					var callback = suggest.data('suggest_callback');
					if ( typeof callback !== 'undefined' )
						return callback(item);
					id.val(item.id);
					hidden_id.val(item.id);
					hidden_suggest.val(suggest.val());
					suggest.data('item', item);
					return item;
				}
			},
			actions = ['user', 'product', 'campaign', 'coupon', 'order', 'transaction'];
		
		for ( var i in actions ) {
			if ( suggest.hasClass('suggest_' + actions[i]) ) {
				suggest.sbsuggest(window.ajaxurl + '?action=suggest_' + actions[i], args);
				break;
			}
		}
	});
	
	$('#suggest_user_id.suggest_campaign_owner').blur(function() {
		var suggest = $(this),
			hidden_suggest = $('#hidden_' + suggest.attr('id')),
			id = $('#' + suggest.attr('id').replace(/^suggest_/, '')),
			hidden_id = $('#hidden_' + suggest.attr('id').replace(/^suggest_/, ''));
		
		if ( $('#init_price').size() && !suggest.val() ) {
			$('#init_comm, #rec_comm').filter('.campaign-group').attr('disabled', 'disabled');
			setPrice('#user_id');
		}
	}).each(function() {
		var suggest = $(this),
			hidden_suggest = $('#hidden_' + suggest.attr('id')),
			id = $('#' + suggest.attr('id').replace(/^suggest_/, '')),
			hidden_id = $('#hidden_' + suggest.attr('id').replace(/^suggest_/, ''));
		
		suggest.data('suggest_callback', function(item) {
			id.val(item.id);
			hidden_id.val(item.id);
			hidden_suggest.val(suggest.val());
			suggest.data('item', item);
			if ( $('#init_price').size() ) {
				$('#init_comm, #rec_comm').filter('.campaign-group').attr('disabled', '');
				setPrice('#user_id');
			}
			return item;
		});
	});
	
	$('#suggest_product_id.campaign_product').blur(function() {
		if ( !$('#suggest_product_id').val() ) {
			$('div.coupon-group.misc-pub-section:not(#status-picker)')
				.removeClass('misc-pub-section-last')
				.siblings(':visible')
				.not('div.coupon-group.misc-pub-section:not(#status-picker)')
				.filter(':last')
				.addClass('misc-pub-section-last');
			
			$('div.coupon-group:not(#productdiv, #status-picker)')
				.fadeOut(function() {
				$('#product_id, #hidden_product_id, #hidden_suggest_product_id').val('');
				$('#hidden_init_price, #hidden_rec_price').val('');
				$('#hidden_init_comm, #hidden_rec_comm').val('');
				$('#init_discount, #rec_discount').val('');
				$('span.rec_interval').text(sem_backendL10n.month);
				$('div.coupon-group.misc-pub-section-last:not(#status-picker)')
					.removeClass('misc-pub-section-last');
				setPrice('#product_id');
			});
		}
	}).each(function() {
		var suggest = $(this),
			hidden_suggest = $('#hidden_' + suggest.attr('id')),
			id = $('#' + suggest.attr('id').replace(/^suggest_/, '')),
			hidden_id = $('#hidden_' + suggest.attr('id').replace(/^suggest_/, ''));
		
		suggest.data('suggest_callback', function(item) {
			id.val(item.id);
			hidden_id.val(item.id);
			hidden_suggest.val(suggest.val());
			suggest.data('item', item);
			
			$('#hidden_init_price').val(item.init_price ? item.init_price : '');
			$('#hidden_rec_price').val(item.rec_price ? item.rec_price : '');
			$('#hidden_init_comm').val(item.init_comm ? item.init_comm : '');
			$('#hidden_rec_comm').val(item.rec_comm ? item.rec_comm : '');
			$('#init_discount, #rec_discount').val('');
			setPrice('#product_id', item);
			
			switch ( item.rec_interval ) {
			case 'month':
				$('span.rec_interval').text(sem_backendL10n.month);
				break;
			
			case 'quarter':
				$('span.rec_interval').text(sem_backendL10n.quarter);
				break;
			
			case 'year':
				$('span.rec_interval').text(sem_backendL10n.year);
				break;
			}
			
			if ( $('div.coupon-group.misc-pub-section')
				.filter(':first')
				.removeClass('misc-pub-section-last')
				.prevAll('.misc-pub-section-last:visible')
				.removeClass('misc-pub-section-last') ) {
				$('div.coupon-group.misc-pub-section')
				.filter(':last')
				.addClass('misc-pub-section-last');
			}
			
			$('div.coupon-group').fadeIn();
			
			return item;
		});
	});
	
	$('#init_price, #rec_price, #init_comm, #rec_comm, #init_discount_price, #rec_discount_price')
		.filter('.product-group')
		.change(function() {
		// process
		$('#init_price, #rec_price, #init_comm, #rec_comm').each(function() {
			var t = $(this);
			$('#hidden_' + t.attr('id')).val(t.val());
		});
		setPrice(this);
		// process again, in case there was invalid data
		$('#init_price, #rec_price, #init_comm, #rec_comm').each(function() {
			var t = $(this);
			$('#hidden_' + t.attr('id')).val(t.val());
		});
	});
	
	$('#rec_interval').change(function() {
		$("span.rec_interval").text($('#rec_interval :selected').text());
	});
	
	$('#init_comm, #rec_comm, #init_discount_price, #rec_discount_price')
		.filter('.campaign-group').change(function() {
		setPrice(this);
	});
	
	$('a.edit-status').click(function() {
		var t = $(this),
			id = t.attr('href');
		if ( $(id + '-select').is(':hidden') ) {
			$(id + '-select').slideDown(function() {
				$(id).focus();
			});
			t.hide();
		}
		return false;
	});
	
	$('a.save-status').click(function() {
		var id = $(this).attr('href');
		$(id + '-select').slideUp(function() {
			$(id + '-select').siblings('a.edit-status').show();
			$('#hidden_' + id.replace(/^#/, '')).val($(id).val());
			setStatusDate(id);
		});
		return false;
	});

	$('a.cancel-status').click(function() {
		var id = $(this).attr('href');
		$(id + '-select').slideUp(function() {
			$(id + '-select').siblings('a.edit-status').show();
			$(id ).val($('#hidden_' + id.replace(/^#/, '')).val());
		});
		return false;
	});
	
	$('input.availability').blur(function() {
		var t = $(this),
			id = '#' + t.attr('id'),
			new_availability = t.val(),
			captions = t.data('captions');
		
		if ( !captions )
			captions = sem_backendL10n.defaults;
		
		if ( new_availability !== '' ) {
			new_availability = parseInt(new_availability);
			if ( !new_availability && new_availability !== 0 ) {
				new_availability = '';
				t.val('');
			}
		}
		
		switch ( new_availability ) {
		case '':
			$(id + '-select').fadeOut(function() {
				$(id + '-unlimited').fadeIn();
			})
			break;
		
		case 1:
			$(id + '-units').text(captions.unit);
			$(id + '-unlimited').fadeOut(function() {
				$(id + '-select').fadeIn();
			})
			break
		
		default:
			$(id + '-units').text(captions.units);
			$(id + '-unlimited').fadeOut(function() {
				$(id + '-select').fadeIn();
			})
			break;
		}
	});
	
	$('a.edit-availability').click(function() {
		var id = $(this).attr('href');
		if ( $(id + '-select').is(':hidden') ) {
			$(id + '-unlimited').fadeOut(function() {
				$(id + '-select').fadeIn(function() {
					$(id).focus();
				});
			})
		}
		return false;
	});
	
	$('a.edit-timestamp, a.edit-expires').click(function() {
		var id = $(this).attr('href');
		if ( $(id + 'div').is(':hidden') ) {
			$(id + 'div').slideDown(function() {
				$(id).focus();
			});
			$(this).hide();
		}
		return false;
	});
	
	$('a.save-timestamp, a.save-expires').click(function () {
		var date_id = $(this).attr('href'),
			time_id = date_id.replace('date', 'time'),
			type = $(this).hasClass('save-timestamp') ? 'timestamp' : 'expires';
		$(date_id + 'div').slideUp(function() {
			$(date_id + 'div').siblings('a.edit-' + type).show();
			if ( !$(date_id).val() && $(time_id).val() )
				$(time_id).val('');
			$('#hidden_' + date_id.replace(/^#/, '')).val($(date_id).val());
			$('#hidden_' + time_id.replace(/^#/, '')).val($(time_id).val());
			setStatusDate(date_id);
		});
		return false;
	});
	
	$('a.cancel-timestamp, a.cancel-expires').click(function() {
		var date_id = $(this).attr('href'),
			time_id = date_id.replace('date', 'time'),
			type = $(this).hasClass('cancel-timestamp') ? 'timestamp' : 'expires';
		$(date_id + 'div').slideUp(function() {
			$(date_id + 'div').siblings('a.edit-' + type).show();
			$(date_id).val($('#hidden_' + date_id.replace(/^#/, '')).val());
			$(time_id).val($('#hidden_' + time_id.replace(/^#/, '')).val());
		});
		return false;
	});
	
	$('input.date').datepicker();
	
	$('input.date').change(function() {
		var v = $(this).val();
		try {
			var t = $.datepicker.parseDate('mm/dd/yy', v);
		} catch ( e ) {
			$(this).val('');
			return;
		}
	});
	
	$('input.time').change(function() {
		var v = $(this).val();
		if ( !v )
			return;
		
		v = $.trim(v);
		v = v.toLowerCase();
		if ( !v.match(/\d/) ) {
			$(this).val('');
			return;
		}
		
		var patterns = [{
			re: /^\s*(\d{1,2})(?::?(\d{1,2}))?(?::?(\d{1,2}))?\s*(am?|pm?)?\s*$/,
			handle: function(bits) {
				var d = new Date;
				bits[1] = bits[1] ? bits[1].replace(/^0+/, '') : 0;
				bits[2] = bits[2] ? bits[2].replace(/^0+/, '') : 0;
				bits[3] = bits[3] ? bits[3].replace(/^0+/, '') : 0;
				var h = bits[1] ? parseInt(bits[1]) : 0;
				var m = bits[2] ? parseInt(bits[2]) : 0;
				var s = bits[3] ? parseInt(bits[3]) : 0;
				
				if ( h >= 1 && h <= 12 && bits[4] ) {
					if ( h == 12 )
						h = 0;
					if ( bits[4].match(/^p/i) )
						h += 12;
				}
				
				d.setHours(h);
	            d.setMinutes(m);
	            d.setSeconds(s);
	            
				return d;
			}
		}];
		
		for ( var i = 0; i < patterns.length; i++ ) {
			var bits = patterns[i].re.exec(v);
			if ( bits ) {
				var d = patterns[i].handle(bits);
				var h = d.getHours();
				var m = d.getMinutes();
				
				var p = 'am';
				if ( h >= 12 )
					p = 'pm';
				
				if ( h > 12 ) {
					h -= 12;
				} else if ( !h ) {
					h += 12;
				}
				h = h.toString();
				if ( h.length == 1 )
					h = '0' + h;
				m = m.toString();
				if ( m.length == 1 )
					m = '0' + m;
				
				v = h + ':' + m + ' ' + p;
				
				if ( v != $(this).val() )
					$(this).val(v);
				return;
			}
		}
		
		$(this).val('');
		return;
	});
	
	window.setStatusDate = function(sender) {
		var sender = $(sender),
			main = sender.hasClass('main-group'),
			group = sender.data('group'),
			captions = sender.data('captions'),
			cur_status = $('#cur_status').val(),
			cur_date = $('#cur_date').val(),
			cur_datetime = false,
			new_status = false,
			status = false,
			timestamp_date = false,
			timestamp_time = false,
			expires_date = false,
			expires_time = false,
			future = false,
			timestamp = false,
			timestamp_datetime = false,
			new_timestamp_date = false,
			new_timestamp_time = false,
			expired = false,
			expires = false,
			expires_datetime = false,
			new_expires_date = false,
			new_expires_time = false;
		
		if ( !captions )
			captions = sem_backendL10n.defaults;
			
		$(':input.' + group + ':hidden').each(function() {
			var t = $(this);
			if ( t.hasClass('status') )
				status = t.attr('id');
			else if ( t.hasClass('timestamp_date') )
				timestamp_date = t.attr('id');
			else if ( t.hasClass('timestamp_time') )
				timestamp_time = t.attr('id');
			else if ( t.hasClass('expires_date') )
				expires_date = t.attr('id');
			else if ( t.hasClass('expires_time') )
				expires_time = t.attr('id');
		});
		
		if ( status ) {
			new_status = $('#hidden_' + status).val();
			$('#' + status + '-display').text($('#' + status + ' :selected').text());
		}
		
		cur_datetime = new Date(cur_date);
		
		if ( timestamp_date ) {
			new_timestamp_date = $('#hidden_' + timestamp_date).val();
			new_timestamp_time = $('#hidden_' + timestamp_time).val();
			if ( new_timestamp_date )
				timestamp_datetime = new Date($.trim(new_timestamp_date + ' ' + new_timestamp_time));
		}
		
		if ( expires_date ) {
			new_expires_date = $('#hidden_' + expires_date).val();
			new_expires_time = $('#hidden_' + expires_time).val();
			if ( new_expires_date )
				expires_datetime = new Date($.trim(new_expires_date + ' ' + new_expires_time));
		}
		
		if ( timestamp_datetime && expires_datetime && expires_datetime < timestamp_datetime ) {
			$('#' + expires_date + ', #hidden_' + expires_date).val($('#hidden_' + timestamp_date).val());
			$('#' + expires_time + ', #hidden_' + expires_time).val($('#hidden_' + timestamp_time).val());
			return setStatusDate(sender);
		}
		
		if ( main && status ) {
			switch ( new_status ) {
			case 'draft':
				$('#save-post').val(captions.save_draft);
				break;
			case 'pending':
				$('#save-post').val(captions.save_pending);
				break;
			}
		}
		
		if ( timestamp_date ) {
			if ( !new_timestamp_date ) {
				timestamp = sem_backendL10n.immediately;
				timestamp = '<b>' + timestamp + '</b>';
			} else {
				future = ( timestamp_datetime > cur_datetime );
				timestamp = $.datepicker.formatDate('M d, yy', new Date(new_timestamp_date));
				if ( new_timestamp_time )
					timestamp += ' @ ' + new_timestamp_time;
				timestamp = '<b>' + timestamp + '</b>';
			}
			
			if ( future ) {
				timestamp = sem_backendL10n.publishOnFuture + ' ' + timestamp;
			} else if ( new_timestamp_date ) {
				switch ( new_status ) {
				case 'draft':
				case 'pending':
				case 'future':
				case 'inactive':
					timestamp = captions.publishOn + ' ' + timestamp;
					break;
				default:
					switch ( cur_status ) {
					case 'draft':
					case 'pending':
					case 'future':
					case 'inactive':
						timestamp = captions.publishOn + ' ' + timestamp;
						break;
					default:
						timestamp = captions.publishOnPast + ' ' + timestamp;
						break;
					}
					break;
				}
			} else {
				timestamp = captions.publishNow + ' ' + timestamp;
			}
			
			$('#' + timestamp_date + '-display').html(timestamp);
		}
		
		if ( expires_date ) {
			if ( !new_expires_date ) {
				expires = sem_backendL10n.never;
				expires = '<b>' + expires + '</b>';
			} else {
				switch ( new_status ) {
				case 'draft':
				case 'pending':
				case 'future':
				case 'inactive':
					break;
				default:
					expired = ( expires_datetime <= cur_datetime );
				}
				expires = $.datepicker.formatDate('M d, yy', new Date(new_expires_date));
				if ( new_expires_time )
					expires += ' @ ' + new_expires_time;
				expires = '<b>' + expires + '</b>';
			}

			if ( expired ) {
				switch ( cur_status ) {
				case 'draft':
				case 'pending':
				case 'future':
				case 'inactive':
					expires = sem_backendL10n.expireOn + ' ' + expires;
					break;
				default:
					expires = sem_backendL10n.expireOnPast + ' ' + expires;
					break;
				}
			} else if ( new_expires_date ) {
				switch ( new_status ) {
				case 'draft':
				case 'pending':
				case 'future':
				case 'inactive':
					expires = sem_backendL10n.expireOn + ' ' + expires;
					break;
				default:
					switch ( cur_status ) {
					case 'draft':
					case 'pending':
					case 'future':
					case 'inactive':
						expires = sem_backendL10n.expireOn + ' ' + expires;
						break;
					default:
						expires = sem_backendL10n.expireOnFuture + ' ' + expires;
						break;
					}
					break;
				}
			} else {
				expires = sem_backendL10n.expiresNever + ' ' + expires;
			}
			
			$('#' + expires_date + '-display').html(expires);
		}
		
		if ( !main || $('#publish').val() == sem_backendL10n.save_pending )
			return;
		
		switch ( new_status ) {
		case 'draft':
		case 'pending':
			if ( future )
				$('#publish').val(sem_backendL10n.schedule);
			else
				$('#publish').val(captions.publish);
			break;
		case 'future':
			if ( future )
				$('#publish').val(sem_backendL10n.update);
			else
				$('#publish').val(captions.publish);
			break;
		case 'active':
			if ( future )
				$('#publish').val(sem_backendL10n.schedule);
			else if ( cur_status != 'active' )
				$('#publish').val(captions.publish);
			else
				$('#publish').val(sem_backendL10n.update);
			break;
		default:
			$('#publish').val(sem_backendL10n.update);
			break;
		}
	}
	
	window.setPrice = function(sender, item) {
		var init_price = parseFloat($('#hidden_init_price').val()),
			init_comm = parseFloat($('#hidden_init_comm').val()),
			init_discount = parseFloat($('#init_discount').val()),
			rec_price = parseFloat($('#hidden_rec_price').val()),
			rec_comm = parseFloat($('#hidden_rec_comm').val()),
			rec_discount = parseFloat($('#rec_discount').val()),
			campaign_owner = false,
			new_value = parseFloat($(sender).val());
		
		if ( typeof item != 'undefined' ) {
			init_price = item.init_price;
			init_comm = item.init_comm;
			init_discount = item.init_discount;
			rec_price = item.rec_price;
			rec_comm = item.rec_comm;
			rec_discount = item.rec_discount;
		}
		
		init_price = init_price ? Math.round(100 * init_price) / 100 : 0;
		init_comm = init_comm ? Math.round(100 * init_comm) / 100 : 0;
		init_discount = init_discount ? Math.round(100 * init_discount) / 100 : 0;
		rec_price = rec_price ? Math.round(100 * rec_price) / 100 : 0;
		rec_comm = rec_comm ? Math.round(100 * rec_comm) / 100 : 0;
		rec_discount = rec_discount ? Math.round(100 * rec_discount) / 100 : 0;
		new_value = new_value ? Math.round(100 * new_value) / 100 : 0;
		
		if ( $('input.campaign_owner').size() )
			campaign_owner = parseInt($('input.campaign_owner').val());
		
		if ( campaign_owner ) {
			switch ( $(sender).attr('id') ) {
			case 'init_comm':
				init_discount = init_comm - new_value;
				break;
			case 'rec_comm':
				rec_discount = rec_comm - new_value;
				break;
			case 'init_discount_price':
				init_discount = init_price - new_value;
				break;
			case 'rec_discount_price':
				rec_discount = rec_price - new_value;
				break;
			case 'product_id':
				init_discount = 0;
				rec_discount = 0;
				break;
			}
			
			init_price = init_price ? Math.max(init_price, 0) : 0;
			rec_price = rec_price ? Math.max(rec_price, 0) : 0;
			init_comm = init_comm ? Math.min(Math.max(init_comm, 0), init_price) : 0;
			rec_comm = rec_comm ? Math.min(Math.max(rec_comm, 0), rec_price) : 0;
			init_discount = init_discount ? Math.min(Math.max(init_discount, 0), init_comm) : 0;
			rec_discount = rec_discount ? Math.min(Math.max(rec_discount, 0), rec_comm) : 0;
			init_comm = init_comm - init_discount;
			rec_comm = rec_comm - rec_discount;
		} else {
			switch ( $(sender).attr('id') ) {
			case 'init_comm':
				ini_comm = new_value;
				break;
			case 'rec_comm':
				rec_comm = new_value;
				break;
			case 'init_discount_price':
				init_discount = init_price - new_value;
				break;
			case 'rec_discount_price':
				rec_discount = rec_price - new_value;
				break;
			case 'product_id':
				init_discount = 0;
				rec_discount = 0;
				break;
			}
			
			init_price = init_price ? Math.max(init_price, 0) : 0;
			rec_price = rec_price ? Math.max(rec_price, 0) : 0;
			init_comm = init_comm ? Math.min(Math.max(init_comm, 0), init_price) : 0;
			rec_comm = rec_comm ? Math.min(Math.max(rec_comm, 0), rec_price) : 0;
			init_discount = init_discount ? Math.min(Math.max(init_discount, 0), init_price - init_comm) : 0;
			rec_discount = rec_discount ? Math.min(Math.max(rec_discount, 0), rec_price - rec_comm) : 0;
		}
		
		$('#init_price').val(init_price ? init_price : '');
		$('#rec_price').val(rec_price ? rec_price : '');
		$('#init_comm').val(init_price ? init_comm : '');
		$('#rec_comm').val(rec_price ? rec_comm : '');
		$('#init_discount_price').val(init_price ? init_price - init_discount : '');
		$('#rec_discount_price').val(rec_price ? rec_price - rec_discount : '');
		$('#init_discount').val(init_price ? init_discount : '');
		$('#rec_discount').val(rec_price ? rec_discount : '');
	}
});