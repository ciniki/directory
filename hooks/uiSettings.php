<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get directory for.
//
// Returns
// -------
//
function ciniki_directory_hooks_uiSettings($ciniki, $tnid, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['tenant']['modules']['ciniki.directory'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>1600,
            'label'=>'Directory', 
            'edit'=>array('app'=>'ciniki.directory.main'),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.directory', 0x01) 
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>1600, 'label'=>'Directory', 'edit'=>array('app'=>'ciniki.directory.settings'));
    }

    return $rsp;
}
?>
