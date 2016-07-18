<?php
//
// Description
// -----------
// This method will add a new directory entry to a business.  
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_directory_categoryAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'image_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Image'), 
        'short_description'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Description'), 
        'full_description'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Full Description'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'checkAccess');
    $ac = ciniki_directory_checkAccess($ciniki, $args['business_id'], 'ciniki.directory.categoryAdd');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

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
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1724', 'msg'=>'Category already exists'));
    }
    
    //
    // Add the object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.directory.category', $args, 0x07);
}
?>
