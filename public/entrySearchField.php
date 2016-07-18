<?php
//
// Description
// -----------
// This method will return a list of entries from a field.  Typically this is used for live search.
//
// Arguments
// ---------
// user_id:         The user making the request
// search_str:      The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_directory_entrySearchField($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('category'), 'name'=>'Field'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_directory_checkAccess($ciniki, $args['business_id'], 'ciniki.directory.entrySearchField', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the number of faqs in each status for the business, 
    // if no rows found, then return empty array
    //
    $strsql = "SELECT " . $args['field'] . " AS name "
        . "FROM ciniki_directory_entries "
        . "WHERE ciniki_directory_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND (" . $args['field']  . " LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "AND " . $args['field'] . " <> '' "
            . ") "
        . "";
    $strsql .= "ORDER BY " . $args['field'] . " COLLATE latin1_general_cs "
        . "";
    if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
        array('container'=>'results', 'fname'=>'name', 'name'=>'result', 'fields'=>array('name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['results']) || !is_array($rc['results']) ) {
        return array('stat'=>'ok', 'results'=>array());
    }
    return array('stat'=>'ok', 'results'=>$rc['results']);
}
?>
