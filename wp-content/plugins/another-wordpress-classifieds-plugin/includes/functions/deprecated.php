<?php

/**
 * Check that the given file meets the file size, dimensions and file type
 * constraints and moves the file to the AWPCP Uploads directory.
 *
 * @param $error    if an error occurs the error message will be returned by reference
 *                  using this variable.
 * @param $action   'upload' if the file was uploaded using an HTML File field.
 *                  'copy' if the file was uploaded using a different method. Images
 *                  extracted from a ZIP file during Ad import.
 * @return          false if an error occurs or an array with the upload file information
 *                  on success.
 * @since 3.0.2
 * @deprecated  3.4
 */
function awpcp_upload_file( $file, $constraints, &$error=false, $action='upload' ) {
    $filename = sanitize_file_name( strtolower( $file['name'] ) );
    $tmpname = $file['tmp_name'];

    $mime_type = $file[ 'type' ];

    if ( ! in_array( $mime_type, $constraints[ 'mime_types' ] ) ) {
        $error = _x( 'The type of the uploaded file %s is not allowed.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
        return false;
    }

    $paths = awpcp_get_uploads_directories();

    if ( ! file_exists( $tmpname ) ) {
        $error = _x( 'The specified file does not exists: %s.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
        return false;
    }

    if ( $action == 'upload' && ! is_uploaded_file( $tmpname ) ) {
        $error = _x( 'Unknown error encountered while uploading the image.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
        return false;
    }

    $file_size = filesize( $tmpname );

    if ( empty( $file_size ) || $file_size <= 0 ) {
        $error = _x( 'There was an error trying to find out the file size of the image %s.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
        return false;
    }

    if ( in_array( $mime_type, awpcp_get_image_mime_types() ) ) {
        if ( $file_size > $constraints['max_image_size'] ) {
            $error = _x( 'The file %s was larger than the maximum allowed file size of %s bytes. The file was not uploaded.', 'upload files', 'another-wordpress-classifieds-plugin' );
            $error = sprintf( $error, '<strong>' . $filename . '</strong>', $constraints['max_image_size'] );
            return false;
        }

        if ( $file_size < $constraints['min_image_size'] ) {
            $error = _x( 'The size of %1$s was too small. The file was not uploaded. File size must be greater than %2$d bytes.', 'upload files', 'another-wordpress-classifieds-plugin' );
            $error = sprintf( $error, '<strong>' . $filename . '</strong>', $constraints['min_image_size'] );
            return false;
        }

        $img_info = getimagesize( $tmpname );

        if ( ! isset( $img_info[ 0 ] ) && ! isset( $img_info[ 1 ] ) ) {
            $error = _x( 'The file %s does not appear to be a valid image file.', 'upload files', 'another-wordpress-classifieds-plugin' );
            $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
            return false;
        }

        if ( $img_info[ 0 ] < $constraints['min_image_width'] ) {
            $error = _x( 'The image %s did not meet the minimum width of %s pixels. The file was not uploaded.', 'upload files', 'another-wordpress-classifieds-plugin');
            $error = sprintf( $error, '<strong>' . $filename . '</strong>', $constraints['min_image_width'] );
            return false;
        }

        if ( $img_info[ 1 ] < $constraints['min_image_height'] ) {
            $error = _x( 'The image %s did not meet the minimum height of %s pixels. The file was not uploaded.', 'upload files', 'another-wordpress-classifieds-plugin');
            $error = sprintf( $error, '<strong>' . $filename . '</strong>', $constraints['min_image_height'] );
            return false;
        }
    } else {
        if ( $file_size > $constraints['max_attachment_size'] ) {
            $error = _x( 'The file %s was larger than the maximum allowed file size of %s bytes. The file was not uploaded.', 'upload files', 'another-wordpress-classifieds-plugin' );
            $error = sprintf( $error, '<strong>' . $filename . '</strong>', $constraints['max_attachment_size'] );
            return false;
        }
    }

    $newname = awpcp_unique_filename( $tmpname, $filename, array( $paths['files_dir'], $paths['thumbnails_dir'] ) );
    $newpath = trailingslashit( $paths['files_dir'] ) . $newname;

    if ( $action == 'upload' && ! @move_uploaded_file( $tmpname, $newpath ) ) {
        $error = _x( 'The file %s could not be moved to the destination directory.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
        return false;
    } else if ( $action == 'copy' && ! @copy( $tmpname, $newpath ) ) {
        $error = _x( 'The file %s could not be copied to the destination directory.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $message, '<strong>' . $filename . '</strong>' );
        return false;
    }

    if ( in_array( $mime_type, awpcp_get_image_mime_types() ) ) {
        if ( ! awpcp_create_image_versions( $newname, $paths['files_dir'] ) ) {
            $error = _x( 'Could not create resized versions of image %s.', 'upload files', 'another-wordpress-classifieds-plugin' );
            $error = sprintf( $error, '<strong>' . $filename . '</strong>' );

            @unlink( $newpath );

            return false;
        }
    }

    @chmod( $newpath, 0644 );

    return array(
        'original' => $filename,
        'filename' => awpcp_utf8_basename( $newpath ),
        'path' => str_replace( $paths['files_dir'], '', $newpath ),
        'mime_type' => $mime_type,
    );
}

/**
 * @since 3.0.2
 * @deprecated 3.4
 */
function awpcp_get_allowed_mime_types() {
    return awpcp_array_data( 'mime_types', array(), awpcp_get_upload_file_constraints() );
}

/**
 * File type, size and dimension constraints for uploaded files.
 *
 * @since 3.0.2
 * @deprecated 3.4
 */
function awpcp_get_upload_file_constraints( ) {
    return apply_filters( 'awpcp-upload-file-constraints', array(
        'mime_types' => awpcp_get_image_mime_types(),

        'max_image_size' => get_awpcp_option( 'maximagesize' ),
        'min_image_size' => get_awpcp_option( 'minimagesize' ),
        'min_image_height' => get_awpcp_option( 'imgminheight' ),
        'min_image_width' => get_awpcp_option( 'imgminwidth' ),
    ) );
}

/**
 * Returns information about the number of files uploaded to an Ad, and
 * the number of files that can still be added to that same Ad.
 *
 * @since 3.0.2
 * @deprecated 3.4
 */
function awpcp_get_ad_uploaded_files_stats( $ad ) {
    $payment_term = awpcp_payments_api()->get_ad_payment_term( $ad );

    $images_allowed = get_awpcp_option( 'imagesallowedfree', 0 );
    $images_allowed = awpcp_get_property( $payment_term, 'images', $images_allowed );
    $images_uploaded = $ad->count_image_files();
    $images_left = max( $images_allowed - $images_uploaded, 0 );

    return apply_filters( 'awpcp-ad-uploaded-files-stats', array(
        'images_allowed' => $images_allowed,
        'images_uploaded' => $images_uploaded,
        'images_left' => $images_left,
    ), $ad );
}

/**
 * Verifies the upload directories exists and have proper permissions, then
 * returns the path to the directories to store raw files and image thumbnails.
 *
 * @since 3.0.2
 * @deprecated 3.4
 */
function awpcp_get_uploads_directories() {
    static $uploads_directories = null;

    if ( is_null( $uploads_directories ) ) {
        // TODO: Remove directory permissions setting when this code is finally removed.
        $permissions = awpcp_directory_permissions();

        $upload_dir_name = get_awpcp_option( 'uploadfoldername', 'uploads' );
        $upload_dir = WP_CONTENT_DIR . '/' . $upload_dir_name . '/';

        // Required to set permission on main upload directory
        require_once(AWPCP_DIR . '/includes/class-fileop.php');

        $fileop = new fileop();
        $owner = fileowner( WP_CONTENT_DIR );

        if ( ! is_dir( $upload_dir ) && is_writable( WP_CONTENT_DIR ) ) {
            umask( 0 );
            wp_mkdir_p( $upload_dir );
            chown( $upload_dir, $owner );
        }

        // TODO: It is a waste of resources to check this on every request.
        if ( ! is_writable( $upload_dir ) ) {
            $fileop->set_permission( $upload_dir, $permissions );
        }

        $files_dir = $upload_dir . 'awpcp/';
        $thumbs_dir = $upload_dir . 'awpcp/thumbs/';

        if ( ! is_dir( $files_dir ) && is_writable( $upload_dir ) ) {
            umask( 0 );
            wp_mkdir_p( $files_dir );
            @chown( $files_dir, $owner );
        }

        if ( ! is_dir( $thumbs_dir ) && is_writable( $upload_dir ) ) {
            umask( 0 );
            wp_mkdir_p( $thumbs_dir );
            @chown( $thumbs_dir, $owner );
        }

        // TODO: It is a waste of resources to check this on every request.
        if ( ! is_writable( $files_dir ) ) {
            $fileop->set_permission( $files_dir, $permissions );
        }

        // TODO: It is a waste of resources to check this on every request.
        if ( ! is_writable( $thumbs_dir ) ) {
            $fileop->set_permission( $thumbs_dir, $permissions );
        }

        $uploads_directories = array(
            'files_dir' => $files_dir,
            'thumbnails_dir' => $thumbs_dir,
        );
    }

    return $uploads_directories;
}

/**
 * Resize images if they're too wide or too tall based on admin's Image Settings.
 * Requires both max width and max height to be set otherwise no resizing
 * takes place. If the image exceeds either max width or max height then the
 * image is resized proportionally.
 *
 * @deprecated 3.4
 */
function awpcp_resizer($filename, $dir) {
    $maxwidth = get_awpcp_option('imgmaxwidth');
    $maxheight = get_awpcp_option('imgmaxheight');

    if ('' == trim($maxheight) || '' == trim ($maxwidth)) {
        return false;
    }

    $parts = awpcp_utf8_pathinfo( $filename );

    if( 'jpg' == $parts['extension'] || 'jpeg' == $parts['extension'] ) {
        $src = imagecreatefromjpeg( $dir . $filename );
    } else if ( 'png' == $parts['extension'] ) {
        $src = imagecreatefrompng( $dir . $filename );
    } else {
        $src = imagecreatefromgif( $dir . $filename );
    }

    list($width, $height) = getimagesize($dir . $filename);

    if ($width < $maxwidth && $height < $maxheight) {
        return true;
    }

    $newwidth = '';
    $newheight = '';

    $aspect_ratio = (float) $height / $width;

    $newheight = $maxheight;
    $newwidth = round($newheight / $aspect_ratio);

    if ($newwidth > $maxwidth) {
        $newwidth = $maxwidth;
        $newheight = round( $newwidth * $aspect_ratio );
    }

    $tmp = imagecreatetruecolor( $newwidth, $newheight );

    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

    $newname = $dir . $filename;

    switch ($parts['extension']) {
        case 'gif':
            @imagegif($tmp, $newname);
            break;
        case 'png':
            @imagepng($tmp, $newname, 0);
            break;
        case 'jpg':
        case 'jpeg':
            @imagejpeg($tmp, $newname, 100);
            break;
    }

    imagedestroy($src);
    imagedestroy($tmp);

    return true;
}

/**
 * @deprecated 3.4
 */
function get_categorynameidall($cat_id = 0) {
    global $wpdb;

    $optionitem='';

    // Start with the main categories
    $query = "SELECT category_id,category_name FROM " . AWPCP_TABLE_CATEGORIES . " ";
    $query.= "WHERE category_parent_id=0 AND category_name <> '' ";
    $query.= "ORDER BY category_order, category_name ASC";

    $query_results = $wpdb->get_results( $query, ARRAY_N );

    foreach ( $query_results as $rsrow ) {
        $cat_ID = $rsrow[0];
        $cat_name = stripslashes(stripslashes($rsrow[1]));

        $opstyle = "class=\"dropdownparentcategory\"";

        if($cat_ID == $cat_id) {
            $maincatoptionitem = "<option $opstyle selected='selected' value='$cat_ID'>$cat_name</option>";
        } else {
            $maincatoptionitem = "<option $opstyle value='$cat_ID'>$cat_name</option>";
        }

        $optionitem.="$maincatoptionitem";

        // While still looping through main categories get any sub categories of the main category

        $maincatid = $cat_ID;

        $query = "SELECT category_id,category_name FROM " . AWPCP_TABLE_CATEGORIES . " ";
        $query.= "WHERE category_parent_id=%d ";
        $query.= "ORDER BY category_order, category_name ASC";

        $query = $wpdb->prepare( $query, $maincatid );

        $sub_query_results = $wpdb->get_results( $query, ARRAY_N );

        foreach ( $sub_query_results as $rsrow2) {
            $subcat_ID = $rsrow2[0];
            $subcat_name = stripslashes(stripslashes($rsrow2[1]));

            if($subcat_ID == $cat_id) {
                $subcatoptionitem = "<option selected='selected' value='$subcat_ID'>- $subcat_name</option>";
            } else {
                $subcatoptionitem = "<option  value='$subcat_ID'>- $subcat_name</option>";
            }

            $optionitem.="$subcatoptionitem";
        }
    }

    return $optionitem;
}

/**
 * @deprecated since 2.0.7
 */
function checkfortable($table) {
    return awpcp_table_exists($table);
}

/**
 * Return the number of pages with the given post_name.
 *
 * @deprecated 4.0.0    This is no longer used.
 */
function checkforduplicate($cpagename_awpcp) {
    global $wpdb;

    $awpcppagename = sanitize_title( $cpagename_awpcp );

    $query = "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s";
    $query = $wpdb->prepare( $query, $awpcppagename, 'post' );

    $post_ids = $wpdb->get_col( $query );

    if ( $post_ids !== false ) {
        return count( $post_ids );
    } else {
        return '';
    }
}

/**
 * @deprecated 4.0.0    Use a instance of CAPTCHA instead.
 */
function awpcp_create_captcha($type='default') {
    return awpcp()->container['CAPTCHAProviderFactory']->get_captcha_provider( $type );
}

/**
 * Returns an array of Region fields. Only those enabled in the settings will
 * be returned.
 *
 * @since 3.0.2
 * @deprecated 4.0.0    This function is now implemented as a private method on
 *                      Multiple Region Selector class.
 */
function awpcp_region_fields( $context='details', $enabled_fields = null ) {
    if ( function_exists( '_doing_it_wrong' ) ) {
        _doing_it_wrong( 'awpcp_region_fields', 'This function is now implemented as a private method on Multiple Region Selector class and will be removed in future versions.', 'another-wordpress-classifieds-plugin', '4.0.0' );
    }

    if ( is_null( $enabled_fields ) ) {
        $enabled_fields = awpcp_get_enabled_region_fields( $context );
    }

    $fields = apply_filters( 'awpcp-region-fields', false, $context, $enabled_fields );

    if ( false === $fields ) {
        $fields = awpcp_default_region_fields( $context, $enabled_fields );
    }

    return $fields;
}

/**
 * @deprecated 4.0.0 This function will be removed in 4.1.0.
 */
function vector2options($show_vector,$selected_map_val,$exclusion_vector=array()) {
   $myreturn='';

   foreach ( $show_vector as $k => $v ) {
       if (!in_array($k,$exclusion_vector)) {
           $myreturn.="<option value=\"".$k."\"";
           if ($k==$selected_map_val) {
               $myreturn.=" selected='selected'";
           }
           $myreturn.=">".$v."</option>\n";
       }
   }
   return $myreturn;
}

/**
 * @deprecated 4.0.0 This function will be removed in 4.1.0.
 */
function unix2dos($mystring) {
    $mystring=preg_replace("/\r/m",'',$mystring);
    $mystring=preg_replace("/\n/m","\r\n",$mystring);
    return $mystring;
}

/**
 * @deprecated 4.0.0 This function will be removed in 4.1.0.
 */
function create_awpcp_random_seed() {
    list($usec, $sec) = explode(' ', microtime());
    return (int)$sec+(int)($usec*100000);
}
