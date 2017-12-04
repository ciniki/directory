<?php
//
// Description
// -----------
// This method will delete a directory category from the tenant.  Any entries that were
// in this category will be removed from the category but left in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the category is attached to.
// category_id:         The ID of the category to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_directory_categoryDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'category_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Category'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'checkAccess');
    $ac = ciniki_directory_checkAccess($ciniki, $args['tnid'], 'ciniki.directory.categoryDelete');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Get the category uuid
    //
    $strsql = "SELECT uuid FROM ciniki_directory_categories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' " 
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'category');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['category']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.12', 'msg'=>'The category does not exist'));
    }
    $uuid = $rc['category']['uuid'];

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.directory');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the list of entries to remove from this category
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_directory_category_entries "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'entry');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $entries = $rc['rows'];
    foreach($entries as $entry) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.directory.category_entry', 
            $entry['id'], $entry['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Delete the object
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.directory.category', $args['category_id'], $uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.directory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'directory');

    return array('stat'=>'ok');
}
?>
