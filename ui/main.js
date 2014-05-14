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
			'categories':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':[''],
				'noData':'No categories',
				},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.cellValue = function(s, i, j, d) { return d.category.name + ' <span class="count">' + d.category.num_entries + '</span>'; }
		this.menu.rowFn = function(s, i, d) { return 'M.ciniki_directory_main.showCategory(\'M.ciniki_directory_main.showMenu();\',' + d.category.id + ',\'' + escape(d.category.name) + '\');' }
		this.menu.noData = function(s) { return this.sections[s].noData; }
		this.menu.addButton('add', 'Add', 'M.ciniki_directory_main.showEdit(\'M.ciniki_directory_main.showMenu();\',0);');
		this.menu.addClose('Back');

		//
		// The list of entries for a category
		//
		this.category = new M.panel('Directory',
			'ciniki_directory_main', 'category',
			'mc', 'medium', 'sectioned', 'ciniki.directory.main.category');
		this.category.category_id = 0;
		this.category.sections = {
			'entries':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':[''],
				'addTxt':'Add entry',
				'addFn':'M.ciniki_directory_main.showEdit(\'M.ciniki_directory_main.showCategory();\',0,escape(M.ciniki_directory_main.category.category_name));',
				'noData':'No entries',
				},
			};
		this.category.sectionData = function(s) { return this.data[s]; }
		this.category.cellValue = function(s, i, j, d) { return d.entry.name; }
		this.category.rowFn = function(s, i, d) { return 'M.ciniki_directory_main.showEdit(\'M.ciniki_directory_main.showCategory();\',' + d.entry.id + ');' }
		this.category.noData = function(s) { return this.sections[s].noData; }
		this.category.addButton('add', 'Add', 'M.ciniki_directory_main.showEdit(\'M.ciniki_directory_main.showCategory();\',0);');
		this.category.addClose('Back');

		//
		// The edit entry panel 
		//
		this.edit = new M.panel('Entry',
			'ciniki_directory_main', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.directory.main.edit');
		this.edit.data = {};
		this.edit.entry_id = 0;
        this.edit.sections = { 
			'_image':{'label':'', 'aside':'yes', 'fields':{
				'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 
					'controls':'all', 'history':'no'},
				}},
            'general':{'label':'General', 'fields':{
                'name':{'label':'Name', 'hint':'Company or directory name', 'type':'text'},
//                'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
                'url':{'label':'URL', 'hint':'Enter the http:// address for your entries website', 'type':'text'},
                }}, 
			'_categories':{'label':'Categories', 'fields':{
				'categories':{'label':'', 'hidelabel':'yes', 'active':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
				}},
			'_description':{'label':'Additional Information', 'fields':{
				'description':{'label':'', 'hidelabel':'yes', 'hint':'Add additional information about your entry', 'type':'textarea'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_directory_main.saveEntry();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_directory_main.deleteEntry();'},
				}},
            };  
		this.edit.fieldValue = function(s, i, d) { 
			if( this.data[i] == null ) { return ''; } 
			return this.data[i]; 
			};
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
		this.edit.updateField = function(s, eid, result) {
			M.gE(this.panelUID + '_' + eid).value = unescape(result);
			this.removeLiveSearch(s, eid);
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
		M.api.getJSONCb('ciniki.directory.categoryList', {'business_id':M.curBusinessID,
			'count':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_directory_main.menu;
				p.data = {'categories':rsp.categories};
				p.refresh();
				p.show(cb);
			});
	};

	this.showCategory = function(cb, cid, cname) {
		this.category.data = [];
		if( cid != null ) { this.category.category_id = cid; }
		if( cname != null ) { this.category.category_name = cname; }
		//
		// Grab the list of sites
		//
		M.api.getJSONCb('ciniki.directory.entryList', {'business_id':M.curBusinessID,
			'category_id':this.category.category_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_directory_main.category;
				p.data = {'entries':rsp.entries};
				p.refresh();
				p.show(cb);
			});
	};

	this.showEdit = function(cb, eid, cname) {
		this.edit.reset();
		if( eid != null ) {
			this.edit.entry_id = eid;
		}
		if( this.edit.entry_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			M.api.getJSONCb('ciniki.directory.entryGet', 
				{'business_id':M.curBusinessID, 'entry_id':this.edit.entry_id, 
				'categories':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_directory_main.edit;
					p.data = rsp.entry;
					p.sections._categories.fields.categories.tags = [];
					if( rsp.categories != null ) {
						for(i in rsp.categories) {
							p.sections._categories.fields.categories.tags.push(rsp.categories[i].category.name);
						}
					}
					p.refresh();
					p.show(cb);
				});
		} else {
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			M.api.getJSONCb('ciniki.directory.categoryList', 
				{'business_id':M.curBusinessID, 'count':'no'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_directory_main.edit;
					p.data = {};
					p.sections._categories.fields.categories.tags = [];
					if( rsp.categories != null ) {
						for(i in rsp.categories) {
							p.sections._categories.fields.categories.tags.push(rsp.categories[i].category.name);
						}
					}
					if( cname != null ) {
						console.log(cname);
						p.data['categories'] = unescape(cname);
					}
					p.refresh();
					p.show(cb);
				});
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
