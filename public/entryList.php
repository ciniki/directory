<?php
//
// Description
// -----------
// This function will return the list of directory entries for a tenant.  It is restricted
// to tenant owners and sysadmins.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get entries for.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'checkAccess');
    $ac = ciniki_directory_checkAccess($ciniki, $args['tnid'], 'ciniki.directory.entryList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

    if( $args['category_id'] == '0' ) {
        $strsql = "SELECT "
            . "ciniki_directory_entries.id, "
            . "ciniki_directory_entries.name, "
            . "ciniki_directory_entries.url, "
            . "COUNT(ciniki_directory_category_entries.id) AS num_categories "
            . "FROM ciniki_directory_entries "
            . "LEFT JOIN ciniki_directory_category_entries ON ("
                . "ciniki_directory_entries.id = ciniki_directory_category_entries.entry_id "
                . "AND ciniki_directory_category_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_directory_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY ciniki_directory_entries.id "
            . "HAVING num_categories = 0 "
            . "ORDER BY sort_name, name "
            . "";
    } else {
        $strsql = "SELECT "
            . "ciniki_directory_entries.id, "
            . "ciniki_directory_entries.name, "
            . "ciniki_directory_entries.url "
            . "FROM ciniki_directory_category_entries, ciniki_directory_entries "
            . "WHERE ciniki_directory_category_entries.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "AND ciniki_directory_category_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_directory_category_entries.entry_id = ciniki_directory_entries.id "
            . "AND ciniki_directory_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY sort_name, name "
            . "";
    }

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
