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

	$strsql = "SELECT id, name, "
		. "IF(ciniki_directory_entries.category='', 'Uncategorized', ciniki_directory_entries.category) AS sname, "
		. "url, description FROM ciniki_directory_entries "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY sname "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
		array('container'=>'sections', 'fname'=>'sname', 'name'=>'section',
			'fields'=>array('sname')),
		array('container'=>'entries', 'fname'=>'id', 'name'=>'entry',
			'fields'=>array('id', 'name', 'url', 'description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sections']) ) {
		return array('stat'=>'ok', 'sections'=>array());
	}
	return array('stat'=>'ok', 'sections'=>$rc['sections']);
}
?>
