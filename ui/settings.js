//
function ciniki_directory_settings() {
	//
	// Panels
	//
	this.main = null;
	this.add = null;

	this.cb = null;
	this.toggleOptions = {'off':'Off', 'on':'On'};

//	this.themes = {
//		'Black':'Blue Titles on Black',
//		'Default':'Black Titles on White',
//		};

	this.init = function() {
		//
		// The main panel, which lists the options for production
		//
		this.main = new M.panel('Settings',
			'ciniki_directory_settings', 'main',
			'mc', 'medium', 'sectioned', 'ciniki.directory.settings.main');
		this.main.sections = {
			'dropbox':{'label':'SMTP', 'fields':{
				'dropbox-directory':{'label':'Directory', 'type':'text'},
			}},
		};

		this.main.fieldValue = function(s, i, d) { 
			return this.data[i];
		};

		//  
		// Callback for the field history
		//  
		this.main.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.directory.settingsHistory', 'args':{'business_id':M.curBusinessID, 'setting':i}};
		};

		this.main.addButton('save', 'Save', 'M.ciniki_directory_settings.saveSettings();');
		this.main.addClose('Cancel');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_directory_settings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.showMain(cb);
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMain = function(cb) {
		var rsp = M.api.getJSON('ciniki.directory.settingsGet', {'business_id':M.curBusinessID});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.main.data = rsp.settings;
		this.main.refresh();
		this.main.show(cb);
	}

	this.saveSettings = function() {
		var c = this.main.serializeForm('no');
		if( c != '' ) {
			var rsp = M.api.postJSON('ciniki.directory.settingsUpdate', 
				{'business_id':M.curBusinessID}, c);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			} 
		}
		M.ciniki_directory_settings.main.close();
	}
}
