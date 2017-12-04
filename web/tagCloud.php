<?php
//
// Description
// -----------
// This function will return a list of posts organized by date
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get directory entries for.
// type:            The type of the tag.
//
//
// Returns
// -------
//
function ciniki_directory_web_tagCloud($ciniki, $settings, $tnid) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Build the query to get the tags
    //
    $strsql = "SELECT ciniki_directory_categories.name, "
        . "ciniki_directory_categories.permalink, "
        . "COUNT(ciniki_directory_category_entries.id) AS num_tags "
        . "FROM ciniki_directory_categories "
        . "LEFT JOIN ciniki_directory_category_entries ON ("
            . "ciniki_directory_categories.id = ciniki_directory_category_entries.category_id "
            . "AND ciniki_directory_category_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_directory_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY ciniki_directory_categories.name "
        . "HAVING num_tags > 0 "
        . "ORDER BY ciniki_directory_categories.name "
        . "";
    //
    // Get the list of posts, sorted by publish_date for use in the web CI List Categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.directory', array(
        array('container'=>'tags', 'fname'=>'permalink', 
            'fields'=>array('name', 'permalink', 'num_tags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags']) ) {
        $tags = $rc['tags'];
    } else {
        $tags = array();
    }

    return array('stat'=>'ok', 'tags'=>$tags);
}
?>
