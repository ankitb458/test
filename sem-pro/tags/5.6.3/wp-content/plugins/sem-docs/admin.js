sem_docs = {
	getElt : function(id) {
		return document.getElementById(id);
	},
	
	show : function() {
		this.getElt('sem_docs__more').style.display = 'none';
		this.getElt('sem_docs__less').style.display = '';
		this.getElt('sem_docs__content').style.display = '';
		this.prepend('sem_docs__wrapper', 'wpbody');
	},
	
	hide : function() {
		this.getElt('sem_docs__more').style.display = '';
		this.getElt('sem_docs__less').style.display = 'none';
		this.getElt('sem_docs__content').style.display = 'none';
	},

	prepend : function(id, to) {
		this.getElt(to).innerHTML = this.getElt(id).innerHTML + this.getElt(to).innerHTML;
		this.getElt(id).innerHTML = '';
	}
};

sem_tips = {
	getElt : function(id) {
		return document.getElementById(id);
	},
	
	next : function() {
		this.load('<p>Loading next tip...<\/p>');
		this.exec('next');
	},
	
	prev : function() {
		this.load('<p>Loading previous tip...<\/p>');
		this.exec('prev');
	},
	
	stop : function() {
		this.load('<p>Updating Guru Tip Preferences...<\/p>');
		this.exec('stop');
	},
	
	exec : function(action) {
		var url = new String(document.location);
		
		url = url.replace(new RegExp("\\?.*$"), '');
		url = url.replace(new RegExp("[^/]*$"), '');
		
		var mySack = new sack(url);

		mySack.execute = 1;
		mySack.method = 'GET';
		mySack.setVar('sem_tips', action);
		mySack.onError = function() { sem_tips.load('<p>AJAX error<\/p>') };
		mySack.runAJAX();
	},

	load : function(tip) {
		if ( !tip )
		{
			tip = '<p>Error: No guru tip found!<\/p>';
		}
		
		this.getElt('sem_tips__content').innerHTML = tip;
	},
	
	close : function() {
		if ( !this.getElt('sem_tips__show').checked )
		{
			this.stop();
		}
		
		this.getElt('sem_tips__wrapper').style.display = 'none';
	}
};

sem_features = {
	getElt : function(id) {
		return document.getElementById(id);
	},
	
	show : function(id) {
		this.getElt(id + '__excerpt').style.display = 'none';
		this.getElt(id + '__more').style.display = 'none';
		this.getElt(id + '__content').style.display = '';
		this.getElt(id + '__less').style.display = '';
	},
	
	hide : function(id) {
		this.getElt(id + '__excerpt').style.display = '';
		this.getElt(id + '__more').style.display = '';
		this.getElt(id + '__content').style.display = 'none';
		this.getElt(id + '__less').style.display = 'none';
	},
	
	showhide : function(id) {
		if ( this.getElt(id + '__excerpt').style.display == '' )
		{
			this.show(id);
		}
		else
		{
			this.hide(id);
		}
	},
	
	bindFeatureSet : function(id, arr) {
		var features = new Array();
		var e;
		var i;
		
		id = 'sem_features__' + id;
		
		// register feature set for non-disabled features
		for ( i = 0; i < arr.length; i++ )
		{
			e = this.getElt(id + '__' + arr[i]);
		
			if ( !e.disabled )
			{
				features = features.concat(arr[i]);

				e.featureSet = id;
			}
			
			e.getElt = this.getElt;
			e.onchange = this.featureOnChange;
			e.onclick = this.featureOnChange;
			e.bind = new Array();
		}

		// register features on set
		e = this.getElt(id);
		e.features = features;
		e.getElt = this.getElt;
		e.setChecked = this.setChecked;
		e.setChecked();
		e.onchange = this.featureSetOnChange;
		e.onclick = this.featureSetOnChange;
	},
	
	bindFeatures : function(arr) {
		var e;
		var i;
		var j;
		
		var e2;
		var i2;
		var j2;
		
		var f;
		
		// for each feature set
		for ( i = 0; i < arr.length; i++ )
		{
			e = this.getElt('sem_features__' + arr[i]);
			
			// for each feature in set
			for ( j = 0; j < e.features.length; j++ )
			{
				// for each other feature set
				for ( i2 = 0; i2 < arr.length; i2++ )
				{
					if ( i2 == i ) continue;
					
					e2 = this.getElt('sem_features__' + arr[i2]);
					
					// for each feature in other set
					for ( j2 = 0; j2 < e2.features.length; j2++ )
					{
						if ( e2.features[j2] == e.features[j] )
						{
							// bind feature
							f = this.getElt(e2.id + '__' + e2.features[j2]);
							f.bind = f.bind.concat(e.id + '__' + e.features[j]);
						}
					}
				}
			}
		}
	},

	featureOnChange : function() {
		var e;
		var i;
		
		// check feature set if relevant
		e = this.getElt(this.featureSet);
		e.setChecked();
		
		// propagate check if relevant
		
		for ( i = 0; i < this.bind.length; i++ )
		{
			e = this.getElt(this.bind[i]);
			e.checked = this.checked;
			
			e = this.getElt(e.featureSet);
			e.setChecked();
		}
	},
	
	featureSetOnChange : function() {
		var e;
		var i;
		var j;
		
		var e2;
		
		// toggle features
		for ( i = 0; i < this.features.length; i++ )
		{
			e = this.getElt(this.id + '__' + this.features[i]);
			e.checked = this.checked;
			
			for ( j = 0; j < e.bind.length; j++ )
			{
				e2 = this.getElt(e.bind[j]);
				e2.checked = this.checked;

				e2 = this.getElt(e2.featureSet);
				e2.setChecked();
			}
		}
	},
	
	setChecked : function () {
		var e;
		var i;
		
		// uncheck if any feature is unchecked
		for ( i = 0; i < this.features.length; i++ )
		{
			e = this.getElt(this.id + '__' + this.features[i]);
			
			if ( !e.checked )
			{
				this.checked = false;
				return;
			}
		}
		
		// else check
		this.checked = true;
	}
};