<?php
//
// Description
// ===========
//
// Arguments
// =========
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_directory_cron_dropboxUpdate(&$ciniki) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'dropboxDownload');

	//
	// Get the list of businesses that have directory enables and dropbox flag 
	//
	$strsql = "SELECT business_id "
		. "FROM ciniki_business_modules "
		. "WHERE package = 'ciniki' "
		. "AND module = 'directory' "
		. "AND (flags&0x01) = 1 "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['rows']) ) {
		return array('stat'=>'ok');
	}
	$businesses = $rc['rows'];
	
	foreach($businesses as $business) {
		//
		// Load business modules
		//
		$rc = ciniki_businesses_checkModuleAccess($ciniki, $business['business_id'], 'ciniki', 'directory');
		if( $rc['stat'] != 'ok' ) {	
			error_log('CRON: directory not configured');
		}
		//
		// Update the business directory from dropbox
		//
		error_log('CRON: Updating dropbox for business: ' . $business['business_id']);
		$rc = ciniki_directory_dropboxDownload($ciniki, $business['business_id']);
		if( $rc['stat'] != 'ok' ) {
			error_log('CRON: ' . serialize($rc['err']));
		}
	}

	return array('stat'=>'ok');
}
?>
