<?php
//
// Description
// ===========
// This function will return all the details for a directory entry.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_directory_entryLoad($ciniki, $tnid, $entry_id, $args) {

    //
    // Get the entry
    //
    $strsql = "SELECT ciniki_directory_entries.id, "
        . "ciniki_directory_entries.name, "
        . "ciniki_directory_entries.sort_name, "
        . "ciniki_directory_entries.image_id AS primary_image_id, "
        . "ciniki_directory_entries.url, "
        . "ciniki_directory_entries.synopsis, "
        . "ciniki_directory_entries.description, "
        . "ciniki_directory_entries.date_added, "
        . "ciniki_directory_entries.last_updated, "
        . "ciniki_directory_entry_images.id AS img_id, "
        . "ciniki_directory_entry_images.name AS image_name, "
        . "ciniki_directory_entry_images.webflags AS image_webflags, "
        . "ciniki_directory_entry_images.image_id, "
        . "ciniki_directory_entry_images.description AS image_description, "
        . "ciniki_directory_entry_images.url AS image_url "
        . "FROM ciniki_directory_entries "
        . "LEFT JOIN ciniki_directory_entry_images ON ("
            . "ciniki_directory_entries.id = ciniki_directory_entry_images.entry_id "
            . "AND ciniki_directory_entry_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_directory_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_directory_entries.id = '" . ciniki_core_dbQuote($ciniki, $entry_id) . "' "
        . "";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
        array('container'=>'entries', 'fname'=>'id', 'name'=>'entry',
            'fields'=>array('id', 'name', 'sort_name', 'image_id'=>'primary_image_id', 
                'url', 'synopsis', 'description')),
        array('container'=>'images', 'fname'=>'img_id', 'name'=>'image',
            'fields'=>array('id'=>'img_id', 'name'=>'image_name', 'webflags'=>'image_webflags',
                'image_id', 'description'=>'image_description', 'url'=>'image_url')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['entries']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.directory.10', 'msg'=>'Unable to find entry'));
    }
    $entry = array_pop($rc['entries']);
    $entry = $entry['entry'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
    if( isset($entry['images']) ) {
        foreach($entry['images'] as $img_id => $img) {
            if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
                $rc = ciniki_images_loadCacheThumbnail($ciniki, $tnid, $img['image']['image_id'], 75);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $entry['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
            }
        }
    }

    //
    // Get the categories the entry is attached to
    //
    $strsql = "SELECT 'cat' AS type, name AS lists "
        . "FROM ciniki_directory_category_entries, ciniki_directory_categories "
        . "WHERE ciniki_directory_category_entries.entry_id = '" . ciniki_core_dbQuote($ciniki, $entry_id) . "' "
        . "AND ciniki_directory_category_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_directory_category_entries.category_id = ciniki_directory_categories.id "
        . "AND ciniki_directory_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ". "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
        array('container'=>'categories', 'fname'=>'type', 'name'=>'categories',
            'fields'=>array('lists'), 'dlists'=>array('lists'=>'::')),
            ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories'][0]['categories']['lists']) ) {
        $entry['categories'] = $rc['categories'][0]['categories']['lists'];
    } else {
        $entry['categories'] = '';
    }

    //
    // Get any files if requested
    //
    if( isset($args['files']) && $args['files'] == 'yes' ) {
        $strsql = "SELECT id, name, extension, permalink "
            . "FROM ciniki_directory_entry_files "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_directory_entry_files.entry_id = '" . ciniki_core_dbQuote($ciniki, $entry_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
            array('container'=>'files', 'fname'=>'id', 'name'=>'file',
                'fields'=>array('id', 'name', 'extension', 'permalink')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            $entry['files'] = $rc['files'];
        }
    }

    //
    // Check if sponsors was requested
    //
    if( isset($args['files']) && $args['files'] == 'yes' 
        && isset($ciniki['tenant']['modules']['ciniki.sponsors'])
        && ($ciniki['tenant']['modules']['ciniki.sponsors']['flags']&0x02) == 0x02
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'hooks', 'sponsorList');
        $rc = ciniki_sponsors_hooks_sponsorList($ciniki, $args['tnid'], 
            array('object'=>'ciniki.directory.entry', 'object_id'=>$entry_id));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['sponsors']) ) {
            $entry['sponsors'] = $rc['sponsors'];
        }
    }

    //
    // Check if the list of categories should be included in the result
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_directory_categories "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
            array('container'=>'categories', 'fname'=>'id', 'name'=>'category',
                'fields'=>array('id', 'name'))
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        return array('stat'=>'ok', 'categories'=>$rc['categories'], 'entry'=>$entry);
    }

    return array('stat'=>'ok', 'entry'=>$entry);
}
?>
