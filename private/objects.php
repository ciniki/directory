<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_directory_objects($ciniki) {
	$objects = array();
	$objects['entry'] = array(
		'name'=>'Entry',
		'table'=>'ciniki_directory_entries',
		'fields'=>array(
			'name'=>array(),
			'sort_name'=>array(),
			'permalink'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image', 'default'=>'0'),
			'url'=>array('default'=>''),
			'synopsis'=>array('default'=>''),
			'description'=>array('default'=>''),
			),
		'history_table'=>'ciniki_directory_history',
		);
	$objects['entry_image'] = array(
		'name'=>'Entry Image',
		'sync'=>'yes',
		'table'=>'ciniki_directory_entry_images',
		'fields'=>array(
			'entry_id'=>array('ref'=>'ciniki.directory.entry'),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			'url'=>array(),
			),
		'history_table'=>'ciniki_directory_history',
		);
	$objects['entry_file'] = array(
		'name'=>'Entry File',
		'sync'=>'yes',
		'table'=>'ciniki_directory_entry_files',
		'fields'=>array(
			'entry_id'=>array('ref'=>'ciniki.directory.file'),
			'extension'=>array(),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'description'=>array(),
			'org_filename'=>array(),
			'publish_date'=>array(),
			'binary_content'=>array('history'=>'no'),
			),
		'history_table'=>'ciniki_directory_history',
		);
	$objects['category'] = array(
		'name'=>'Category',
		'table'=>'ciniki_directory_categories',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image', 'default'=>'0'),
			'synopsis'=>array('default'=>''),
			'description'=>array('default'=>''),
			),
		'history_table'=>'ciniki_directory_history',
		);
	$objects['category_entry'] = array(
		'name'=>'Category Entry',
		'table'=>'ciniki_directory_category_entries',
		'fields'=>array(
			'category_id'=>array('ref'=>'ciniki.directory.category'),
			'entry_id'=>array('ref'=>'ciniki.directory.entry'),
			),
		'history_table'=>'ciniki_directory_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
