<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_directory_web_entryDetails($ciniki, $settings, $business_id, $permalink) {

	$strsql = "SELECT ciniki_directory_entries.id, "
		. "ciniki_directory_entries.name, "
		. "ciniki_directory_entries.permalink, "
		. "ciniki_directory_entries.image_id AS primary_image_id, "
		. "ciniki_directory_entries.url, "
		. "ciniki_directory_entries.synopsis, "
		. "ciniki_directory_entries.description, "
		. "ciniki_directory_entry_images.image_id AS image_id, "
		. "ciniki_directory_entry_images.name AS image_name, "
		. "ciniki_directory_entry_images.permalink AS image_permalink, "
		. "ciniki_directory_entry_images.description AS image_description, "
		. "ciniki_directory_entry_images.url AS image_url, "
		. "ciniki_directory_entry_images.last_updated AS image_last_updated "
		. "FROM ciniki_directory_entries "
		. "LEFT JOIN ciniki_directory_entry_images ON ("
			. "ciniki_directory_entries.id = ciniki_directory_entry_images.entry_id "
			. "AND (ciniki_directory_entry_images.webflags&0x01) = 0 "
			. ") "
		. "WHERE ciniki_directory_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_directory_entries.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artclub', array(
		array('container'=>'entries', 'fname'=>'id', 
			'fields'=>array('id', 'title'=>'name', 'permalink', 'image_id'=>'primary_image_id',
			'url', 'synopsis', 'content'=>'description')),
		array('container'=>'images', 'fname'=>'image_id', 
			'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
				'description'=>'image_description', 'url'=>'image_url',
				'last_updated'=>'image_last_updated')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['entries']) ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2067', 'msg'=>'Unable to find directory entry.'));
	}
	$entry = array_pop($rc['entries']);

	//
	// Check if any files are attached to the entry
	//
	$strsql = "SELECT id, name, extension, permalink, description "
		. "FROM ciniki_directory_entry_files "
		. "WHERE ciniki_directory_entry_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_directory_entry_files.entry_id = '" . ciniki_core_dbQuote($ciniki, $entry['id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.directory', array(
		array('container'=>'files', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'extension', 'permalink', 'description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['files']) ) {
		$entry['files'] = $rc['files'];
	}

	//
	// Get any sponsors for this event, and that references for sponsors is enabled
	//
	if( isset($ciniki['business']['modules']['ciniki.sponsors']) 
		&& ($ciniki['business']['modules']['ciniki.sponsors']['flags']&0x02) == 0x02
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'web', 'sponsorRefList');
		$rc = ciniki_sponsors_web_sponsorRefList($ciniki, $settings, $business_id, 
			'ciniki.directory.entry', $entry['id']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['sponsors']) ) {
			$entry['sponsors'] = $rc['sponsors'];
		}
	}

	return array('stat'=>'ok', 'entry'=>$entry);
}
?>
