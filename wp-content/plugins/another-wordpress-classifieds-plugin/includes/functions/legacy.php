<?php
/**
 * @package AWPCP
 */

/**
 * Count listings in the database.
 *
 * @since 1.8.9.4
 * @deprecated 4.0.0 awpcp_listings_collection()->count_enabled_listings()
 *                   awpcp_listings_collection()->count_disabled_listings()
 */
function countlistings( $is_active ) {
    if ( $is_active ) {
        _deprecated_function( __FUNCTION__, '4.0.0', 'awpcp_listings_collection()->count_enabled_listings()' );

        return awpcp_listings_collection()->count_enabled_listings();
    }

    _deprecated_function( __FUNCTION__, '4.0.0', 'awpcp_listings_collection()->count_disabled_listings()' );

    return awpcp_listings_collection()->count_disabled_listings();
}

// phpcs:disable

function get_awpcp_setting($column, $option) {
    global $wpdb;
    $tbl_ad_settings = $wpdb->prefix . "awpcp_adsettings";
    $myreturn=0;
    $tableexists=checkfortable($tbl_ad_settings);

    if($tableexists)
    {
        $query="SELECT ".$column." FROM  ".$tbl_ad_settings." WHERE config_option='$option'";
        $res = $wpdb->get_var($query);
        $myreturn = stripslashes_deep($res);
    }
    return $myreturn;
}

function get_awpcp_option_group_id($option) {
    return get_awpcp_setting('config_group_id', $option);
}

function get_awpcp_option_type($option) {
    return get_awpcp_setting('option_type', $option);
}

function get_awpcp_option_config_diz($option) {
    return get_awpcp_setting('config_diz', $option);
}


function checkifisadmin() {
    return awpcp_current_user_is_admin() ? 1 : 0;
}

function awpcpistableempty($table){
    global $wpdb;

    $query = 'SELECT COUNT(*) FROM ' . $table;
    $results = $wpdb->get_var( $query );

    if ( $results !== false && intval( $results ) === 0 ) {
        return true;
    } else {
        return false;
    }
}

function awpcpisqueryempty($table, $where){
    global $wpdb;

    $query = 'SELECT COUNT(*) FROM ' . $table . ' ' . $where;
    $count = $wpdb->get_var( $query );

    if ( $count !== false && intval( $count ) === 0 ) {
        return true;
    } else {
        return false;
    }
}

function adtermsset(){
    global $wpdb;
    $myreturn = !awpcpistableempty(AWPCP_TABLE_ADFEES);
    return $myreturn;
}

function categoriesexist(){
    return count( awpcp_categories_collection()->find_categories() ) > 0;
}

function countcategories(){
    return awpcp_categories_collection()->count_categories();
}

function countcategoriesparents() {
    $all_categories_count = countcategories();
    $childless_categories_count = countcategorieschildren();

    if ( $all_categories_count == $childless_categories_count ) {
        return 0;
    } else {
        return $all_categories_count - $childless_categories_count;
    }
}

function countcategorieschildren() {
    $childless_categories_count = awpcp_categories_collection()->count_categories(array(
        'childless' => true,
    ));

    if ( countcategories() == $childless_categories_count ) {
        return 0;
    } else {
        return $childless_categories_count;
    }
}


function get_adposteremail($adid) {
    try {
        $listing = awpcp_listings_collection()->get( $adid );
    } catch ( AWPCP_Exception $e ) {
        return null;
    }

    return awpcp_listing_renderer()->get_contact_email( $listing );
}

function get_adstartdate($adid) {
    try {
        $listing = awpcp_listings_collection()->get( $adid );
    } catch ( AWPCP_Exception $e ) {
        return null;
    }

    return awpcp_listing_renderer()->get_plain_start_date( $listing );
}

function get_numtimesadviewd($adid) {
    try {
        $listing = awpcp_listings_collection()->get( $adid );
    } catch ( AWPCP_Exception $e ) {
        return null;
    }

    return awpcp_listing_renderer()->get_views_count( $listing );
}

function get_adtitle($adid) {
    try {
        $listing = awpcp_listings_collection()->get( $adid );
    } catch ( AWPCP_Exception $e ) {
        return null;
    }

    return awpcp_listing_renderer()->get_listing_title( $listing );
}

function get_categorynameid( $cat_id = 0, $cat_parent_id = 0, $exclude = array() ) {
    $parent_categories = awpcp_categories_collection()->find_categories( array(
        'fields' => 'id=>name',
        'parent' => 0,
        'exclude' => $exclude,
        'hide_empty' => false,
    ) );

    $params = array(
        'current-value' => $cat_parent_id,
        'options' => $parent_categories
    );

    return awpcp_html_options( $params );
}

// END FUNCTION: create list of top level categories for admin category management

function get_adcatname($cat_ID) {
    try {
        $category = awpcp_categories_collection()->get( $cat_ID );
        $category_name = stripslashes_deep( $category->name );
    } catch( AWPCP_Exception $e ) {
        $category_name = null;
    }

    return $category_name;
}

function get_adparentcatname( $cat_ID ) {
    if ( $cat_ID == 0 ) {
        return __( 'Top Level Category', 'another-wordpress-classifieds-plugin' );
    }

    return get_adcatname( $cat_ID );
}

function get_cat_parent_ID($cat_ID){
    global $wpdb;

    $query = 'SELECT category_parent_id FROM ' . AWPCP_TABLE_CATEGORIES . ' WHERE category_id = %d';
    $query = $wpdb->prepare( $query, $cat_ID );

    return intval( $wpdb->get_var( $query ) );
}

function ads_exist_cat( $catid ) {
    $listings = awpcp_listings_collection()->find_listings(array(
        'tax_query' => array(
            array(
                'taxonomy' => AWPCP_CATEGORY_TAXONOMY,
                'terms' => (int) $catid,
                'include_children' => true,
            ),
        ),
    ));

    return count( $listings ) > 0;
}

function category_has_children($catid) {
    global $wpdb;
    $tbl_categories = $wpdb->prefix . "awpcp_categories";
    $myreturn=!awpcpisqueryempty($tbl_categories, " WHERE category_parent_id='$catid'");
    return $myreturn;
}

/**
 * @since 4.0.0     Updated to use Categories Collection.
 */
function category_is_child($catid) {
    try {
        $category = awpcp_categories_collection()->get( $catid );
    } catch ( AWPCP_Exception $e ) {
        return false;
    }

    return $category->parent !== 0;
}

/**
 * Originally developed by Dan Caragea.  
 * Permission is hereby granted to AWPCP to release this code 
 * under the license terms of GPL2
 * @author Dan Caragea
 * http://datemill.com
 */
function smart_table( $array, $table_cols, $opentable, $closetable ) {
    $usingtable = false;
    if (!empty($opentable) && !empty($closetable)) {
        $usingtable = true;
    }
    return smart_table2($array,$table_cols,$opentable,$closetable,$usingtable);
}


function smart_table2( $array, $table_cols, $opentable, $closetable, $usingtable ) {
    $myreturn="$opentable\n";
    $row=0;
    $total_vals=count($array);
    $i=1;
    $awpcpdisplayaditemclass='';

    foreach ($array as $v) {
            
        if ($i % 2 == 0) { $awpcpdisplayaditemclass = "displayaditemsodd"; } else { $awpcpdisplayaditemclass = "displayaditemseven"; }


        $v=str_replace("\$awpcpdisplayaditems",$awpcpdisplayaditemclass,$v);

        if ((($i-1)%$table_cols)==0)
        {
            if($usingtable)
            {
                $myreturn.="<tr>\n";
            }

            $row++;
        }
        if($usingtable)
        {
            $myreturn.="\t<td valign=\"top\">";
        }
        $myreturn.="$v";
        if($usingtable)
        {
            $myreturn.="</td>\n";
        }
        if ($i%$table_cols==0)
        {
            if($usingtable)
            {
                $myreturn.="</tr>\n";
            }
        }
        $i++;
    }
    $rest=($i-1)%$table_cols;
    if ($rest!=0) {
        $colspan=$table_cols-$rest;

        $myreturn.="\t<td".(($colspan==1) ? '' : " colspan=\"$colspan\"")."></td>\n</tr>\n";
    }
    //}
    $myreturn.="$closetable\n";
    return $myreturn;
}
