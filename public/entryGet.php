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
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'), 
        'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'), 
        'sponsors'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sponsors'), 
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'entryLoad');
	return ciniki_directory_entryLoad($ciniki, $args['business_id'], $args['entry_id'], $args);
}
?>
