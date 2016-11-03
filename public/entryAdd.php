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
function ciniki_directory_entryAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'sort_name'=>array('required'=>'no', 'blank'=>'no', 'default'=>'', 'name'=>'Sort Name'), 
        'permalink'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Permalink'), 
        'image_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Image'), 
        'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'URL'), 
        'synopsis'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Synopsis'), 
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'checkAccess');
    $ac = ciniki_directory_checkAccess($ciniki, $args['business_id'], 'ciniki.directory.entryAdd');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');

    if( !isset($args['sort_name']) || $args['sort_name'] == '' ) {
        $args['sort_name'] = $args['name'];
    }
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['sort_name']);
    }

    //
    // Check to make sure the permalink doesn't exist
    //
    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_directory_entries "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'entry');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['entry']) || $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.15', 'msg'=>'You must choose a unique name for each entry in the directory'));
    }

    //
    // Get the categories 
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_directory_categories "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.directory', array(
        array('container'=>'categories', 'fname'=>'name',
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $categories = $rc['categories'];
    } else {
        $categories = array();
    }

    //  
    // Turn off autocommit
    //  
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.directory');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add the object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.directory.entry', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
        return $rc;
    }
    $entry_id = $rc['id'];

    //
    // Find categories that need to be added
    //
    if( isset($args['categories']) ) {
        foreach($args['categories'] as $cat) {
            if( $cat == '' ) {
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
                    . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $cargs['permalink']) . "' "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'item');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['num_rows']) && $rc['num_rows'] > 0 ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.16', 'msg'=>'Category already exists'));
                }
                
                //
                // Add the object
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.directory.category', 
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
                $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.directory.category_entry', 
                    array('category_id'=>$categories[$cat]['id'], 'entry_id'=>$entry_id), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                    return $rc;
                }
                
            }
        }

//      ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.directory', 'ciniki_directory_history',
//          $args['business_id'], 1, 'ciniki_directory_entries', $entry_id, 'categories', $args['categories']);
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.directory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'directory');

    return array('stat'=>'ok', 'id'=>$entry_id);
}
?>
