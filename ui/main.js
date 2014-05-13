//
// This app will handle the listing, additions and deletions of directory.
//
function ciniki_directory_main() {
	//
	// Panels
	//
	this.toggleOptions = {'off':'Off', 'on':'On'};

	this.init = function() {
		//
		// directory panel
		//
		this.menu = new M.panel('Directory',
			'ciniki_directory_main', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.directory.main.menu');
		this.menu.sections = {
			'_':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':[''],
				'addTxt':'Add entry',
				'addFn':'M.ciniki_directory_main.showEdit(\'M.ciniki_directory_main.showMenu();\',0);',
				'noData':'No directory added',
				},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.cellValue = function(s, i, j, d) { return d.entry.name; }
		this.menu.rowFn = function(s, i, d) { return 'M.ciniki_directory_main.showEdit(\'M.ciniki_directory_main.showMenu();\',' + d.entry.id + ');' }
		this.menu.noData = function(s) { return this.sections[s].noData; }
		this.menu.addButton('add', 'Add', 'M.ciniki_directory_main.showEdit(\'M.ciniki_directory_main.showMenu();\',0);');
		this.menu.addClose('Back');

		//
		// The edit entry panel 
		//
		this.edit = new M.panel('Entry',
			'ciniki_directory_main', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.directory.main.edit');
		this.edit.data = null;
		this.edit.entry_id = 0;
        this.edit.sections = { 
            'general':{'label':'General', 'fields':{
                'name':{'label':'Name', 'hint':'Company or directory name', 'type':'text'},
                'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
                'url':{'label':'URL', 'hint':'Enter the http:// address for your entries website', 'type':'text'},
                }}, 
			'_description':{'label':'Additional Information', 'fields':{
				'description':{'label':'', 'hidelabel':'yes', 'hint':'Add additional information about your entry', 'type':'textarea'},
				}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_directory_main.saveEntry();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_directory_main.deleteEntry();'},
				}},
            };  
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.liveSearchCb = function(s, i, value) {
			if( i == 'category' ) {
				var rsp = M.api.getJSONBgCb('ciniki.directory.entrySearchField', {'business_id':M.curBusinessID, 'field':i, 'start_needle':value, 'limit':15},
					function(rsp) {
						M.ciniki_directory_main.edit.liveSearchShow(s, i, M.gE(M.ciniki_directory_main.edit.panelUID + '_' + i), rsp.results);
					});
			}
		};
		this.edit.liveSearchResultValue = function(s, f, i, j, d) {
			if( (f == 'category' ) && d.result != null ) { return d.result.name; }
			return '';
		};
		this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( (f == 'category' )
				&& d.result != null ) {
				return 'M.ciniki_directory_main.edit.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.result.name) + '\');';
			}
		};
		this.edit.updateField = function(s, lid, result) {
			M.gE(this.panelUID + '_' + lid).value = unescape(result);
			this.removeLiveSearch(s, lid);
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.directory.entryHistory', 'args':{'business_id':M.curBusinessID, 'entry_id':this.entry_id, 'field':i}};
		}
		this.edit.addButton('save', 'Save', 'M.ciniki_directory_main.saveEntry();');
		this.edit.addClose('Cancel');

	};

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
		var appContainer = M.createContainer(appPrefix, 'ciniki_directory_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 
		
		this.showMenu(cb);
	};

	this.showMenu = function(cb) {
		this.menu.data = [];
		//
		// Grab the list of sites
		//
		var rsp = M.api.getJSONCb('ciniki.directory.entryList', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_directory_main.menu;
			p.data = rsp.entries;

			p.sections = {};
			// 
			// Setup the menu to display the categories
			//
			p.data = {};
			if( rsp.sections.length > 0 ) {
				for(i in rsp.sections) {
					p.data[rsp.sections[i].section.sname] = rsp.sections[i].section.entries;
					p.sections[rsp.sections[i].section.sname] = {'label':rsp.sections[i].section.sname,
						'type':'simplegrid', 'num_cols':1,
						'headerValues':null,
						'cellClasses':[''],
						'addTxt':'Add entry',
						'addFn':'M.ciniki_directory_main.showEdit(\'M.ciniki_directory_main.showMenu();\',0,\'' + rsp.sections[i].section.sname + '\');',
						'noData':'No entries added',
						};
				}
			} else {
				p.data = {'_':{}};
				p.sections['_'] = {'label':'',
					'type':'simplegrid', 'num_cols':1,
					'headerValues':null,
					'cellClasses':[''],
					'addTxt':'Add entry',
					'addFn':'M.ciniki_directory_main.showEdit(\'M.ciniki_directory_main.showMenu();\',0);',
					'noData':'No entries added',
					};
			}

			p.refresh();
			p.show(cb);
		});
	};

	this.showEdit = function(cb, lid, category) {
		this.edit.reset();
		if( lid != null ) {
			this.edit.entry_id = lid;
		}
		if( this.edit.entry_id > 0 ) {
			var rsp = M.api.getJSONCb('ciniki.directory.entryGet', 
				{'business_id':M.curBusinessID, 'entry_id':this.edit.entry_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_directory_main.edit.data = rsp.entry;
					M.ciniki_directory_main.edit.refresh();
					M.ciniki_directory_main.edit.show(cb);
				});
		} else {
			this.edit.data = {};
			if( category != null && category != '' ) {
				this.edit.data.category = category;
			}
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.saveEntry = function() {
		if( this.edit.entry_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.directory.entryUpdate', 
					{'business_id':M.curBusinessID, 'entry_id':this.edit.entry_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_directory_main.edit.close();
					});
			} else {
				M.ciniki_directory_main.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.directory.entryAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_directory_main.edit.close();
				});
		}
	};

	this.deleteEntry = function() {
		if( confirm("Are you sure you want to remove '" + this.edit.data.name + "' as an entry ?") ) {
			var rsp = M.api.getJSONCb('ciniki.directory.entryDelete', 
				{'business_id':M.curBusinessID, 'entry_id':M.ciniki_directory_main.edit.entry_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_directory_main.edit.close();
				});
		}
	};
}
