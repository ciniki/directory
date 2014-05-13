<?php
//
// Description
// -----------
// This function will return a list of entries for the website.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_directory_web_list($ciniki, $business_id) {

	$strsql = "SELECT ciniki_directory_entries.id, category AS cname, name, url, description "
		. "FROM ciniki_directory_entries "
		. "WHERE ciniki_directory_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY category, name ASC "
		. "";
	
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	return ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
		array('container'=>'categories', 'fname'=>'cname', 'name'=>'category',
			'fields'=>array('cname')),
		array('container'=>'links', 'fname'=>'name', 'name'=>'link',
			'fields'=>array('id', 'name', 'url', 'description')),
		));
}
?>
