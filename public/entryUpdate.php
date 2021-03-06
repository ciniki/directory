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
function ciniki_directory_entryUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'entry_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Entry'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
        'sort_name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sort Name'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        'image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
        'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'URL'), 
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'), 
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'checkAccess');
    $rc = ciniki_directory_checkAccess($ciniki, $args['tnid'], 'ciniki.directory.entryUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    if( isset($args['sort_name']) ) {
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['sort_name']);
        //
        // Check to make sure the permalink doesn't exist
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_directory_entries "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'entry');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['entry']) || $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.35', 'msg'=>'You must choose a unique name for each entry in the directory'));
        }
    }

    //  
    // Turn off autocommit
    //  
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.directory');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    if( isset($args['categories']) ) {
        //
        // Get all the categories 
        //
        $strsql = "SELECT id, name "
            . "FROM ciniki_directory_categories "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.directory', array(
            array('container'=>'categories', 'fname'=>'name',
                'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $categories = $rc['categories'];

        //
        // Get the existing entry categories
        //
        $strsql = "SELECT ciniki_directory_category_entries.id, "
            . "ciniki_directory_category_entries.uuid, "
            . "ciniki_directory_categories.name "
            . "FROM ciniki_directory_category_entries, ciniki_directory_categories "
            . "WHERE ciniki_directory_category_entries.entry_id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' "
            . "AND ciniki_directory_category_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_directory_category_entries.category_id = ciniki_directory_categories.id "
            . "AND ciniki_directory_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ". "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.directory', array(
            array('container'=>'categories', 'fname'=>'name', 
                'fields'=>array('id', 'uuid', 'name')),
                ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) ) {
            $entry_categories = $rc['categories'];
        } else {
            $entry_categories = array();
        }

        //
        // Check for categories to be removed
        //
        foreach($entry_categories as $name => $cat) {
            if( !in_array($name, $args['categories']) ) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 
                    'ciniki.directory.category_entry', $cat['id'], $cat['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }

        //
        // Check for categories that need to be added
        //
        foreach($args['categories'] as $cat) {
            //
            // Check if entry is already in the category, and skip
            //
            if( isset($entry_categories[$cat]) ) {
                continue;
            }

            //
            // Check if category doesn't exist and add it
            //
            if( !isset($categories[$cat]) ) {   
                //
                // Create permalink
                //
                $cargs = array(
                    'name'=>$cat,
                    'image_id'=>0,
                    'short_description'=>'',
                    'full_description'=>'',
                    );
                $cargs['permalink'] = ciniki_core_makePermalink($ciniki, $cat);

                //
                // Check for duplication permalink
                //
                $strsql = "SELECT id, name "
                    . "FROM ciniki_directory_categories "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $cargs['permalink']) . "' "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'item');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['num_rows']) && $rc['num_rows'] > 0 ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.36', 'msg'=>'Category already exists'));
                }
                
                //
                // Add the object
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.directory.category', 
                    $cargs, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                    return $rc;
                }
                $categories[$cat] = array('id'=>$rc['id'], 'name'=>$cat);
            }

            //
            // Add the category entry
            //
            if( isset($categories[$cat]) ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.directory.category_entry', 
                    array('category_id'=>$categories[$cat]['id'], 'entry_id'=>$args['entry_id']), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                    return $rc;
                }
            }
        }

//      ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.directory', 'ciniki_directory_history',
//          $args['tnid'], 2, 'ciniki_directory_entries', 
//          $args['entry_id'], 'categories', implode('::', $args['categories']));
    }

    //
    // Update the object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.directory.entry', 
        $args['entry_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
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
