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
function ciniki_directory_categoryList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'count'=>array('required'=>'no', 'default'=>'yes', 'blank'=>'yes', 'name'=>'Number of Entries'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'checkAccess');
    $ac = ciniki_directory_checkAccess($ciniki, $args['tnid'], 'ciniki.directory.categoryList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   


    if( $args['count'] == 'yes' ) {
        $strsql = "SELECT ciniki_directory_categories.id, "
            . "ciniki_directory_categories.name, "
            . "COUNT(ciniki_directory_category_entries.id) AS num_entries "
            . "FROM ciniki_directory_categories "
            . "LEFT JOIN ciniki_directory_category_entries ON ( "
                 . "ciniki_directory_categories.id = ciniki_directory_category_entries.category_id "
                 . "AND ciniki_directory_category_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                 . ") "
            . "WHERE ciniki_directory_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY ciniki_directory_categories.id "
            . "ORDER BY ciniki_directory_categories.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
            array('container'=>'categories', 'fname'=>'id', 'name'=>'category',
                'fields'=>array('id', 'name', 'num_entries')),
            ));
    } else {
        $strsql = "SELECT ciniki_directory_categories.id, "
            . "ciniki_directory_categories.name "
            . "FROM ciniki_directory_categories "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_directory_categories.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
            array('container'=>'categories', 'fname'=>'id', 'name'=>'category',
                'fields'=>array('id', 'name')),
            ));
    }
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        $categories = array();
    } else {
        $categories = $rc['categories'];
    }


    if( $args['count'] == 'yes' ) {
        // Check for uncategorized entries
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
            . "ORDER BY name "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            array_push($categories, array('category'=>array('id'=>'0', 'name'=>'Uncategorized', 'num_entries'=>$rc['num_rows'])));
        }

    }

    return array('stat'=>'ok', 'categories'=>$categories);
}
?>
