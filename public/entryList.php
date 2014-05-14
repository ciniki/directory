<?php
//
// Description
// -----------
// This function will return the list of directory entries for a business.  It is restricted
// to business owners and sysadmins.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get entries for.
//
// Returns
// -------
//
function ciniki_directory_entryList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'checkAccess');
    $ac = ciniki_directory_checkAccess($ciniki, $args['business_id'], 'ciniki.directory.entryList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

	$strsql = "SELECT "
		. "ciniki_directory_entries.id, "
		. "ciniki_directory_entries.name, "
		. "ciniki_directory_entries.url "
		. "FROM ciniki_directory_category_entries, ciniki_directory_entries "
		. "WHERE ciniki_directory_category_entries.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
		. "AND ciniki_directory_category_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_directory_category_entries.entry_id = ciniki_directory_entries.id "
		. "AND ciniki_directory_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY name "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
		array('container'=>'entries', 'fname'=>'id', 'name'=>'entry',
			'fields'=>array('id', 'name', 'url')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['entries']) ) {
		return array('stat'=>'ok', 'entries'=>array());
	}
	return array('stat'=>'ok', 'entries'=>$rc['entries']);
}
?>
