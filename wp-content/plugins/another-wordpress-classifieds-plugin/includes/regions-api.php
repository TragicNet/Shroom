<?php

function awpcp_basic_regions_api() {
    static $instance = null;

    if ( is_null( $instance ) ) {
        $instance = new AWPCP_BasicRegionsAPI( $GLOBALS['wpdb'] );
    }

    return $instance;
}

class AWPCP_BasicRegionsAPI {

    private $db;

    public function __construct( $db ) {
        $this->db = $db;
    }

    // public function find_by_id( $region_id ) {
    //     $sql = 'SELECT * FROM ' . AWPCP_TABLE_AD_REGIONS . ' WHERE region_id = %d';
    //     $row = $this->db->get_row( $this->db->prepare( $sql, $region_id ) );

    //     return false !== $row ? $row : null;
    // }

    // public function find_by_name( $region_name ) {
    //     $sql = 'SELECT * FROM ' . AWPCP_TABLE_AD_REGIONS . ' WHERE region_name = %s';
    //     $row = $this->db->get_row( $this->db->prepare( $sql, $region_name ) );

    //     return false !== $row ? $row : null;
    // }

    /**
     * TODO: trigger an exception on SQL errors
     */
    public function find_by_type($type) {

        // column are named after the type of the reigon
        $sql = 'SELECT DISTINCT `%s` FROM ' . AWPCP_TABLE_AD_REGIONS;

        $rows = $this->db->get_col( sprintf( $sql, $type ) );

        return false !== $rows ? $rows : array();
    }

    // public function find_by_parent_type($parent_type, $type) {
    //     $sql = 'SELECT ' . $type . ' FROM ' . AWPCP_TABLE_AD_REGIONS . ' WHERE ad_id IN ( ';
    //     $sql.= '    SELECT ad_id FROM ' . AWPCP_TABLE_AD_REGIONS . ' WHERE region_type = %s';
    //     $sql.= ')';

    //     $rows = $this->db->get_results( $this->db->prepare( $sql, $type, $parent_type ) );

    //     return false !== $rows ? $rows : array();
    // }

    // public function find_by_parent($parent_id, $type) {
    //     if ( is_numeric( $parent_id ) ) {
    //         return $this->find_by_parent_id( $parent_id, $type );
    //     } else {
    //         return $this->find_by_parent_name( $parent_id, $type );
    //     }
    // }

    // public function find_by_parent_id($parent_id, $type) {
    //     $sql = 'SELECT ' . $type . ' FROM ' . AWPCP_TABLE_AD_REGIONS . ' WHERE ad_id IN ( ';
    //     $sql.= '    SELECT ad_id FROM ' . AWPCP_TABLE_AD_REGIONS . ' WHERE region_id = %d';
    //     $sql.= ')';

    //     return $this->db->get_results( $this->db->prepare( $sql, $type, $parent_id ) );
    // }

    public function find_by_parent_name($parent_name, $parent_type, $type) {
        $sql = 'SELECT DISTINCT `%s` FROM ' . AWPCP_TABLE_AD_REGIONS . ' AS r1 INNER JOIN ( ';
        $sql.= '    SELECT id FROM ' . AWPCP_TABLE_AD_REGIONS . ' WHERE `%s` = %%s';
        $sql.= ') AS r2 ON ( r1.id = r2.id )';

        $sql = sprintf( $sql, $type, $parent_type );

        return $this->db->get_col( $this->db->prepare( $sql, $parent_name ) );
    }

    public function save($region) {
        if ( ! isset( $region['ad_id'] ) || empty( $region['ad_id'] ) ) {
            return false;
        }

        $region = stripslashes_deep( $region );

        if ( intval( awpcp_array_data( 'id', null, $region ) ) > 0 ) {
            $result = $this->db->update( AWPCP_TABLE_AD_REGIONS, $region, array( 'id' => $region['id'] ) );
        } else {
            $result = $this->db->insert( AWPCP_TABLE_AD_REGIONS, $region );
        }

        return $result !== false;
    }

    public function delete_by_ad_id($ad_id) {
        $result = $this->db->query( $this->db->prepare( "DELETE FROM " . AWPCP_TABLE_AD_REGIONS . " WHERE ad_id = %s", $ad_id ) );
        return $result !== false;
    }

    public function find_by_ad_id($ad_id) {
        $sql = 'SELECT * FROM ' . AWPCP_TABLE_AD_REGIONS . ' WHERE ad_id = %d ';
        $sql.= 'ORDER BY id ASC';

        return $this->db->get_results( $this->db->prepare( $sql, $ad_id ) );
    }

    public function update_ad_regions( $ad, $regions, $max_regions = 1 ) {
        // remove existing regions before adding the new ones
        $this->delete_by_ad_id( $ad->ID );

        $count = 0;
        foreach ($regions as $region) {
            if ( empty( implode( $region ) ) ) {
                continue;
            }
            if ($count < $max_regions) {
                $data = array_map( 'trim', $region );
                $this->save( array_merge( array( 'ad_id' => $ad->ID ), $data ) );
            }
            ++$count;
        }
    }
}
