<?php
//
// Description
// ===========
// This function will return the file details and content so it can be sent to the client.
//
// Returns
// -------
//
function ciniki_directory_web_fileDownload($ciniki, $business_id, $entry_permalink, $file_permalink) {

	//
	// Get the file details
	//
	$strsql = "SELECT ciniki_directory_entry_files.id, "
		. "ciniki_directory_entry_files.name, "
		. "ciniki_directory_entry_files.permalink, "
		. "ciniki_directory_entry_files.extension, "
		. "ciniki_directory_entry_files.binary_content "
		. "FROM ciniki_directory_entries, ciniki_directory_entry_files "
		. "WHERE ciniki_directory_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_directory_entries.permalink = '" . ciniki_core_dbQuote($ciniki, $entry_permalink) . "' "
		. "AND ciniki_directory_entries.id = ciniki_directory_entry_files.entry_id "
		. "AND ciniki_directory_entry_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND CONCAT_WS('.', ciniki_directory_entry_files.permalink, ciniki_directory_entry_files.extension) = '" . ciniki_core_dbQuote($ciniki, $file_permalink) . "' "
		. "AND (ciniki_directory_entry_files.webflags&0x01) = 0 "		// Make sure file is to be visible
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'file');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['file']) ) {
		return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'1245', 'msg'=>'Unable to find requested file'));
	}
	$rc['file']['filename'] = $rc['file']['name'] . '.' . $rc['file']['extension'];

	return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
