<?php
//
// Description
// -----------
// This method will delete a directory entry from the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the entry is attached to.
// entry_id:            The ID of the entry to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_directory_entryDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'entry_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Entry'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'checkAccess');
    $ac = ciniki_directory_checkAccess($ciniki, $args['tnid'], 'ciniki.directory.entryDelete');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Get the entry uuid
    //
    $strsql = "SELECT uuid FROM ciniki_directory_entries "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' " 
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'entry');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['entry']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.17', 'msg'=>'The entry does not exist'));
    }
    $uuid = $rc['entry']['uuid'];

    //  
    // Turn off autocommit
    //  
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.directory');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the list of categories
    //
    $strsql = "SELECT ciniki_directory_category_entries.id, "
        . "ciniki_directory_category_entries.uuid "
        . "FROM ciniki_directory_category_entries "
        . "WHERE ciniki_directory_category_entries.entry_id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' "
        . "AND ciniki_directory_category_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.directory', array(
        array('container'=>'categories', 'fname'=>'id', 
            'fields'=>array('id', 'uuid')),
            ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        foreach($rc['categories'] as $cat) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.directory.category_entry', 
                $cat['id'], $cat['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Delete the object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.directory.entry', $args['entry_id'], $uuid, 0x04);
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
