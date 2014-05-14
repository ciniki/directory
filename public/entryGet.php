<?php
//
// Description
// ===========
// This function will return all the details for a directory entry.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_directory_entryGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'entry_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Entry'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'checkAccess');
    $rc = ciniki_directory_checkAccess($ciniki, $args['business_id'], 'ciniki.directory.entryGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the entry
	//
	$strsql = "SELECT ciniki_directory_entries.id, name, category, url, description, "
		. "date_added, last_updated "
		. "FROM ciniki_directory_entries "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_directory_entries.id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' "
		. "";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'entry');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['entry']) ) {
		return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'1721', 'msg'=>'Unable to find entry'));
	}
	$entry = $rc['entry'];

	//
	// Get the categories the entry is attached to
	//
	$strsql = "SELECT 'cat' AS type, name AS lists "
		. "FROM ciniki_directory_category_entries, ciniki_directory_categories "
		. "WHERE ciniki_directory_category_entries.entry_id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' "
		. "AND ciniki_directory_category_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_directory_category_entries.category_id = ciniki_directory_categories.id "
		. "AND ciniki_directory_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' ". "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
		array('container'=>'categories', 'fname'=>'type', 'name'=>'categories',
			'fields'=>array('lists'), 'dlists'=>array('lists'=>'::')),
			));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['categories'][0]['categories']['lists']) ) {
		$entry['categories'] = $rc['categories'][0]['categories']['lists'];
	} else {
		$entry['categories'] = '';
	}

	//
	// Check if the list of categories should be included in the result
	//
	if( isset($args['categories']) && $args['categories'] == 'yes' ) {
		$strsql = "SELECT id, name "
			. "FROM ciniki_directory_categories "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
			array('container'=>'categories', 'fname'=>'id', 'name'=>'category',
				'fields'=>array('id', 'name'))
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		return array('stat'=>'ok', 'categories'=>$rc['categories'], 'entry'=>$entry);
	}

	return array('stat'=>'ok', 'entry'=>$entry);
}
?>
