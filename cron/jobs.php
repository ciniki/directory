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
function ciniki_directory_cron_jobs(&$ciniki) {
    ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for directory jobs', 'severity'=>'5'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'dropboxDownload');

    //
    // Get the list of tenants that have directory enables and dropbox flag 
    //
    $strsql = "SELECT tnid "
        . "FROM ciniki_tenant_modules "
        . "WHERE package = 'ciniki' "
        . "AND module = 'directory' "
        . "AND (flags&0x01) = 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.1', 'msg'=>'Unable to get list of tenants with campaigns', 'err'=>$rc['err']));
    }
    if( !isset($rc['rows']) ) {
        return array('stat'=>'ok');
    }
    $tenants = $rc['rows'];
    
    foreach($tenants as $tenant) {
        //
        // Load tenant modules
        //
        $rc = ciniki_tenants_checkModuleAccess($ciniki, $tenant['tnid'], 'ciniki', 'directory');
        if( $rc['stat'] != 'ok' ) { 
            ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'ciniki.directory.39', 'msg'=>'ciniki.directory not configured', 
                'severity'=>30, 'err'=>$rc['err']));
            continue;
        }

        ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'0', 'msg'=>'Updating directory from dropbox', 'severity'=>'10'));

        //
        // Update the tenant directory from dropbox
        //
        $rc = ciniki_directory_dropboxDownload($ciniki, $tenant['tnid']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'ciniki.directory.40', 'msg'=>'Unable to update directory', 
                'severity'=>50, 'err'=>$rc['err']));
            continue;
        }
    }

    return array('stat'=>'ok');
}
?>
