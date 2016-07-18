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
function ciniki_directory_sync_objects($ciniki, &$sync, $business_id, $args) {
    
    $objects = array();
    $objects['entry'] = array(
        'name'=>'Entries',
        'table'=>'ciniki_directory_entries',
        'fields'=>array(
            'name'=>array(),
            'category'=>array(),
            'image_id'=>array('ref'=>'ciniki.images.image'),
            'url'=>array(),
            'description'=>array(),
            ),
        'history_table'=>'ciniki_directory_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
