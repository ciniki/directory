<?php
//
// Description
// ===========
// This function will update an entry in the database.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_directory_categoryUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'), 
		'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
		'image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
		'short_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
		'full_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Full Description'), 
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
    $rc = ciniki_directory_checkAccess($ciniki, $args['business_id'], 'ciniki.directory.categoryUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	if( isset($args['name']) ) {
		//
		// Create permalink
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);

		//
		// Check for duplication permalink
		//
		$strsql = "SELECT id, name "
			. "FROM ciniki_directory_categories "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'item');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num_rows']) && $rc['num_rows'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1714', 'msg'=>'Category already exists'));
		}
	}
    
	//
	// Update the object
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.directory.category', $args['category_id'], $args, 0x07);
}
?>
