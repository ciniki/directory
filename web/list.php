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
// business_id:		The ID of the business to get directory entries for.
//
// Returns
// -------
//
function ciniki_directory_web_list($ciniki, $business_id, $cat_permalink) {

	$strsql = "SELECT ciniki_directory_entries.id, "
		. "ciniki_directory_categories.name AS cname, "
		. "ciniki_directory_entries.name, "
		. "ciniki_directory_entries.permalink, "
		. "ciniki_directory_entries.image_id, "
		. "ciniki_directory_entries.url, "
		. "ciniki_directory_entries.synopsis, "
		. "IF(ciniki_directory_entries.description <> '', 'yes', 'no') AS is_details, "
		. "ciniki_directory_entries.description "
		. "FROM ciniki_directory_categories, ciniki_directory_category_entries, ciniki_directory_entries "
		. "WHERE ciniki_directory_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_directory_categories.id = ciniki_directory_category_entries.category_id "
		. "AND ciniki_directory_category_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_directory_category_entries.entry_id = ciniki_directory_entries.id "
		. "";
	if( $cat_permalink != '' ) {
		$strsql .= "AND ciniki_directory_categories.permalink = '" . ciniki_core_dbQuote($ciniki, $cat_permalink) . "' ";
	}
	$strsql .= "AND ciniki_directory_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY ciniki_directory_categories.name, ciniki_directory_entries.name ASC "
		. "";
	
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	return ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.directory', array(
		array('container'=>'categories', 'fname'=>'cname',
			'fields'=>array('name'=>'cname')),
		array('container'=>'list', 'fname'=>'id', 
			'fields'=>array('id', 'title'=>'name', 'permalink', 'image_id', 'url', 
				'description'=>'synopsis', 'is_details')),
		));
}
?>
