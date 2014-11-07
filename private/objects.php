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
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'url'=>array(),
			'synopsis'=>array(),
			'description'=>array(),
			),
		'history_table'=>'ciniki_directory_history',
		);
	$objects['category'] = array(
		'name'=>'Category',
		'table'=>'ciniki_directory_categories',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'short_description'=>array(),
			'full_description'=>array(),
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
