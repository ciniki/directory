<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/dropbox/lib/Dropbox/autoload.php');
use \Dropbox as dbx;

function ciniki_directory_dropboxDownload(&$ciniki, $business_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'insertFromDropbox');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dropboxParseRTFToText');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dropboxOpenTXT');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'entryLoad');

    //
    // Check to make sure the dropbox flag is enabled for this business
    //
    if( !isset($ciniki['business']['modules']['ciniki.directory']['flags'])
        || ($ciniki['business']['modules']['ciniki.directory']['flags']&0x01) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.5', 'msg'=>'Dropbox integration not enabled'));
    }

    //
    // Get the categories available for a business
    //
    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_directory_categories "
        . "WHERE ciniki_directory_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'cat');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $ciniki_entry['categories'] = array();
    if( isset($rc['rows']) ) {
        foreach($rc['rows'] as $row) {
            $categories[$row['permalink']] = $row['id'];
        }
    }

    //
    // Get the settings for directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_directory_settings', 
        'business_id', $business_id, 'ciniki.directory', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']['dropbox-directory']) || $rc['settings']['dropbox-directory'] == '') {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.6', 'msg'=>'Dropbox directory not setup.'));
    }
    $directory = $rc['settings']['dropbox-directory'];
    if( $directory[0] != '/' ) {
        $directory = '/' . $directory;
    }
    rtrim($directory, '/');
    $dropbox_cursor = null;
    if( isset($rc['settings']['dropbox-cursor']) && $rc['settings']['dropbox-cursor'] != '') {
        $dropbox_cursor = $rc['settings']['dropbox-cursor'];
    }


    //
    // Get the settings for dropbox
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_business_details', 
        'business_id', $business_id, 'ciniki.businesses', 'settings', 'apis');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']['apis-dropbox-access-token']) 
        || $rc['settings']['apis-dropbox-access-token'] == ''
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.7', 'msg'=>'Dropbox not configured.'));
    }
    $access_token = $rc['settings']['apis-dropbox-access-token'];

    $client = new dbx\Client($access_token, 'Ciniki');

    //
    // Get the latest changes from Dropbox
    //
    $rc = $client->getDelta($dropbox_cursor, $directory);
    if( !isset($rc['entries']) ) {
        // Nothing to update, return
        return array('stat'=>'ok');
    }
    $updates = array();
    $new_dropbox_cursor = $rc['cursor'];
    $entries = $rc['entries'];
    foreach($entries as $entry) {
        if( preg_match("#^($directory)/([^/]+)/([^/]+)/(info.rtf|info.txt|(primary_image|synopsis|description|images|audio|links|videos)/(.*))$#", $entry[0], $matches) ) {
            $sort_name = $matches[3];
            if( !isset($updates[$sort_name]) ) {
                // Create an entry in updates, with the category permalink
                $updates[$sort_name] = array('category'=>$matches[2]);
            }
            if( isset($matches[5]) ) {
                switch($matches[5]) {
                    case 'primary_image': 
                    case 'synopsis': 
                    case 'description': 
                        $updates[$sort_name][$matches[5]] = array(
                            'path'=>$entry[1]['path'], 
                            'modified'=>$entry[1]['modified'], 
                            'mime_type'=>$entry[1]['mime_type'],
                            ); 
                        break;
                    case 'images': 
                    case 'audio': 
                    case 'video': 
                    case 'links': 
                        if( !isset($updates[$sort_name][$matches[5]]) ) {
                            $updates[$sort_name][$matches[5]] = array();
                        }
                        $updates[$sort_name][$matches[5]][] = array(
                            'path'=>$entry[1]['path'], 
                            'modified'=>$entry[1]['modified'], 
                            'mime_type'=>$entry[1]['mime_type'],
                            ); 
                        break;
                }
            } elseif( isset($matches[4]) && $matches[4] == 'info.txt' ) {
                $updates[$sort_name]['info'] = array(
                    'path'=>$entry[1]['path'], 
                    'modified'=>$entry[1]['modified'], 
                    'mime_type'=>$entry[1]['mime_type'],
                    ); 
            }
        }
    }

    //
    // Update Ciniki
    //
    foreach($updates as $sort_name => $entry) {
        //  
        // Turn off autocommit
        //  
        $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.directory');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
        
        //
        // Lookup the entry in the directory
        //
        $strsql = "SELECT id "
            . "FROM ciniki_directory_entries "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $sort_name) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'entry');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
            return $rc;
        }
        //
        // Add entry
        //
        if( !isset($rc['entry']) && $rc['num_rows'] == 0 ) {
            //
            // Check permalink doesn't already exist
            //
            $permalink = ciniki_core_makePermalink($ciniki, $sort_name);
            $strsql = "SELECT id, name "
                . "FROM ciniki_directory_categories "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $cargs['permalink']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'item');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                return $rc;
            }
            if( isset($rc['num_rows']) && $rc['num_rows'] > 0 ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.8', 'msg'=>'Directory entry already exists for ' . $sort_name));
            }
            
            // 
            // Add the entry
            //
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.directory.entry', array(
                'name'=>$sort_name,
                'sort_name'=>$sort_name,
                'permalink'=>$permalink, 
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                return $rc;
            }
            $entry_id = $rc['id'];
            $ciniki_entry = array(
                'id'=>$entry_id,
                'name'=>$sort_name,
                'sort_name'=>$sort_name,
                'permalink'=>$permalink,
                'synopsis'=>'',
                'description'=>'',
                'url'=>'',
                'image_id'=>0,
                'images'=>array(),
                'audio'=>array(),
                'video'=>array(),
                'links'=>array(),
                'categories'=>array(),
                );
        } 
    
        //
        // Load the full entry
        //
        else {
            $entry_id = $rc['entry']['id'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'directory', 'private', 'entryLoad');
            $rc = ciniki_directory_entryLoad($ciniki, $business_id, $entry_id, array('images'=>'yes', 'audio'=>'yes', 'video'=>'yes', 'links'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                return $rc;
            }
            $ciniki_entry = $rc['entry'];

            //
            // Get the categories for the entry
            //
            $strsql = "SELECT ciniki_directory_categories.id, "
                . "ciniki_directory_categories.name, "
                . "ciniki_directory_categories.permalink "
                . "FROM ciniki_directory_category_entries, ciniki_directory_categories "
                . "WHERE ciniki_directory_category_entries.entry_id = '" . ciniki_core_dbQuote($ciniki, $entry_id) . "' "
                . "AND ciniki_directory_category_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_directory_category_entries.category_id = ciniki_directory_categories.id "
                . "AND ciniki_directory_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.directory', 'cat');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                return $rc;
            }
            $ciniki_entry['categories'] = array();
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $row) {
                    $ciniki_entry['categories'][$row['permalink']] = $row['id'];
                }
            }
        }

        //
        // Decide what needs to be updated
        //
        $update_args = array();

        //
        // Go through the updated items
        //
        foreach($entry as $field => $details) {
            if( $field == 'info' || $field == 'info' ) {
                if( $details['mime_type'] == 'text/plain' ) {
                    $rc = ciniki_core_dropboxOpenTXT($ciniki, $business_id, $client, $details['path']);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                        return $rc;
                    }
                    $content = $rc['content'];
                } elseif( $details['mime_type'] == 'application/rtf' ) {
                    $rc = ciniki_core_dropboxParseRTFToText($ciniki, $business_id, $client, $details['path']);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                        return $rc;
                    }
                    $content = $rc['content'];
                }
                $lines = explode("\n", $content);
                foreach($lines as $line) {
                    $pieces = explode(":", $line);
                    if( $pieces[0] == 'name' ) {
                        $name = rtrim(ltrim($pieces[1]));
                        if( $name != $ciniki_entry['name'] ) {
                            $update_args['name'] = $name;
                        }
                    }
                }
            }
            elseif( $field == 'primary_image' && $details['mime_type'] == 'image/jpeg' ) {
                $rc = ciniki_images_insertFromDropbox($ciniki, $business_id, $ciniki['session']['user']['id'], $client, $details['path'], 1, '', '', 'no');
                if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                    return $rc;
                }
                if( $rc['id'] != $ciniki_entry['image_id'] ) {
                    $update_args['image_id'] = $rc['id'];
                }
            }
            elseif( ($field == 'synopsis' || $field == 'description') && $details['mime_type'] == 'application/rtf' ) {
                $rc = ciniki_core_dropboxParseRTFToText($ciniki, $business_id, $client, $details['path']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                    return $rc;
                }
                if( $rc['content'] != $ciniki_entry[$field] ) {
                    $update_args[$field] = $rc['content'];
                }
            }
            elseif( ($field == 'synopsis' || $field == 'description') && $details['mime_type'] == 'text/plain' ) {
                $rc = ciniki_core_dropboxOpenTXT($ciniki, $business_id, $client, $details['path']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                    return $rc;
                }
                if( $rc['content'] != $ciniki_entry[$field] ) {
                    $update_args[$field] = $rc['content'];
                }
            }
            elseif( $field == 'images' ) {
                //
                // Load the extra images
                //
                foreach($details as $img) {
                    if( $img['mime_type'] == 'image/jpeg' ) {
                        $rc = ciniki_images_insertFromDropbox($ciniki, $business_id, $ciniki['session']['user']['id'], $client, $img['path'], 1, '', '', 'no');
                        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                            return $rc;
                        }
                        $found = 'no';
                        if( isset($ciniki_entry['images']) ) {
                            foreach($ciniki_entry['images'] as $cimg) {
                                if( $cimg['image']['image_id'] == $rc['id'] ) {
                                    $found = 'yes';
                                    break;
                                }
                            }
                        }
                        if( $found == 'no' ) {
                            $image_id = $rc['id'];
                            // Get UUID
                            $rc = ciniki_core_dbUUID($ciniki, 'ciniki.directory');
                            if( $rc['stat'] != 'ok' ) {
                                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.directory.9', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
                            }
                            $uuid = $rc['uuid'];
                            // Add object
                            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.directory.entry_image', array(
                                'uuid'=>$uuid,
                                'entry_id'=>$entry_id,
                                'name'=>'',
                                'permalink'=>$uuid,
                                'webflags'=>0,
                                'image_id'=>$image_id,
                                'description'=>'',
                                'url'=>'',
                                ), 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                                return $rc;
                            }
                        }
                    }
                }
            }
        }

        //
        // Check categories
        //
        if( !isset($categories[$entry['category']]) ) {
            // Add category
            $permalink = ciniki_core_makePermalink($ciniki, $entry['category']);
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.directory.category', array(
                'name'=>$entry['category'],
                'permalink'=>$permalink), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                return $rc;
            }
            $categories[$permalink] = $rc['id'];
        }
        if( !isset($ciniki_entry['categories'][$entry['category']]) ) {
            // Add the category entry
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.directory.category_entry', array(
                'category_id'=>$categories[$entry['category']],
                'entry_id'=>$entry_id), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                return $rc;
            }
        }

        //
        // Update the entry
        //
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.directory.entry', $entry_id, $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
                return $rc;
            }
        }

        //  
        // Commit the changes
        //  
        $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.directory');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
    }

    //
    // Update the dropbox cursor
    //
    $strsql = "INSERT INTO ciniki_directory_settings (business_id, detail_key, detail_value, date_added, last_updated) "
        . "VALUES ('" . ciniki_core_dbQuote($ciniki, $business_id) . "'"
        . ", '" . ciniki_core_dbQuote($ciniki, 'dropbox-cursor') . "'"
        . ", '" . ciniki_core_dbQuote($ciniki, $new_dropbox_cursor) . "'"
        . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
        . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $new_dropbox_cursor) . "' "
        . ", last_updated = UTC_TIMESTAMP() "
        . "";
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.directory');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.directory');
        return $rc;
    }
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.directory', 'ciniki_directory_history', $business_id, 
        2, 'ciniki_directory_settings', 'dropbox-cursor', 'detail_value', $new_dropbox_cursor);
    $ciniki['syncqueue'][] = array('push'=>'ciniki.directory.setting', 
        'args'=>array('id'=>'dropbox-cursor'));

    return array('stat'=>'ok');
}
?>
