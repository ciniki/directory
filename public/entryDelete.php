<?php
//
// Description
// -----------
// This method will delete a directory entry from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the entry is attached to.
// entry_id:			The ID of the entry to be removed.
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
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'entry_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Entry'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'checkAccess');
	$ac = ciniki_directory_checkAccess($ciniki, $args['business_id'], 'ciniki.directory.entryDelete');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Get the entry uuid
	//
	$strsql = "SELECT uuid FROM ciniki_directory_entries "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' " 
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'entry');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['entry']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1722', 'msg'=>'The entry does not exist'));
	}
	$uuid = $rc['entry']['uuid'];

	//
	// Delete the object
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	return ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.directory.entry', $args['entry_id'], $uuid, 0x07);
}
?>
