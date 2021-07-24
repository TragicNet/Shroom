<?php
/**
 * CSV import class
 *
 * @package Includes/Admin/CSV Exporter
 */

/**
 * CSV export.
 *
 * @since 4.1.0
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AWPCP_CSVExporter {
    // phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fwrite
    // phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fclose
    // phpcs:disableWordPress.WP.AlternativeFunctions.file_system_read_fopen
    const BATCH_SIZE = 20;

    private $settings = array(
        'csv-file-separator'    => ',',
        'time-separator'        => ';',
        'date-separator'        => ';',
        'images-separator'      => ';',
        'category-separator'    => ';',
        'export-images'         => false,
        'include-users'         => false,
        'listing_status'        => 'all',
        'generate-sequence-ids' => false,
    );

    private $settings_api;

    private $workingdir = '';

    private $columns  = array();
    private $listings = array();
    private $exported = 0;
    private $listing;
    private $listing_data;

    public function __construct( $settings, $settings_api, $workingdir = null, $listings = array() ) {
        $this->settings     = array_merge( $this->settings, $settings );
        $this->settings_api = $settings_api;

        $this->setup_columns();
        $this->setup_working_dir( $workingdir );
        $this->get_listings( $listings );
    }

    public function setup_columns() {
        global $awpcp;
        $importer_columns = $awpcp->container['CSVImporterColumns'];
        $this->columns    = $importer_columns->get_supported_columns();
        if ( ! $this->settings['generate-sequence-ids'] ) {
            unset( $this->columns['metadata']['sequence_id'] );
        }

        if ( ! $this->settings['export-images'] ) {
            unset( $this->columns['custom']['images'] );
        }

        if ( ! $this->settings['include-users'] ) {
            unset( $this->columns['post_fields']['username'] );
        }
    }

    /**
     * @throws AWPCP_Exception If unable to create exports directory.
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function setup_working_dir( $workingdir ) {
        $this->workingdir = $workingdir;

        if ( ! $workingdir ) {
            $direrror = '';

            $upload_dir = wp_upload_dir();

            if ( ! $upload_dir['error'] ) {
                $csvexportsdir = rtrim( $upload_dir['basedir'], DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'awpcp-csv-exports';
                if ( is_dir( $csvexportsdir ) || mkdir( $csvexportsdir ) ) {
                    $this->workingdir = rtrim( $csvexportsdir . DIRECTORY_SEPARATOR . uniqid(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

                    if ( ! mkdir( $this->workingdir, 0777 ) ) {
                        $direrror = _x( 'Could not create a temporary directory for handling this CSV export.', 'admin csv-export', 'another-wordpress-classifieds-plugin' );
                    }
                } else {
                    $direrror = _x( 'Could not create awpcp-csv-exports directory.', 'admin csv-export', 'another-wordpress-classifieds-plugin' );
                }
            }

            if ( $direrror ) {
                /* translators: %s the error. */
                throw new Exception( sprintf( _x( 'Error while creating a temporary directory for CSV export: %s', 'admin csv-export', 'another-wordpress-classifieds-plugin' ), $direrror ) );
            }
        }
    }

    public function get_listings( $listings ) {
        if ( $listings ) {
            $this->listings = $listings;

            return false;
        }
        switch ( $this->settings['listing_status'] ) {
            case 'publish+disabled':
                $post_status = array( 'publish', 'disabled', 'pending' );
                break;
            case 'publish':
                $post_status = 'publish';
                break;
            case 'all':
            default:
                $post_status = array( 'publish', 'draft', 'pending', 'private', 'future', 'trash', 'disabled' );
                break;
        }

        $this->listings = get_posts(
            array(
                'post_status'    => $post_status,
                'posts_per_page' => - 1,
                'post_type'      => AWPCP_LISTING_POST_TYPE,
                'fields'         => 'ids',
            )
        );

    }

    public static function &from_state( $state ) {
        $export           = new self( $state['settings'], awpcp_settings_api(), trailingslashit( $state['workingdir'] ), (array) $state['listings'] );
        $export->exported = abs( intval( $state['exported'] ) );

        return $export;
    }

    public function get_state() {
        return array(
            'settings'   => $this->settings,
            'columns'    => array_keys( $this->columns ),
            'workingdir' => $this->workingdir,
            'listings'   => $this->listings,
            'exported'   => $this->exported,
            'filesize'   => file_exists( $this->get_file_path() ) ? filesize( $this->get_file_path() ) : 0,
            'done'       => $this->is_done(),
        );
    }

    public function cleanup() {
        $upload_dir = wp_upload_dir();

        awpcp_rmdir( $this->workingdir );

        if ( ! $upload_dir['error'] ) {
            $csvexportsdir = rtrim( $upload_dir['basedir'], DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'awpcp-csv-exports';
            $contents      = awpcp_scandir( $csvexportsdir );

            if ( ! $contents ) {
                awpcp_rmdir( $csvexportsdir );
            }
        }
    }

    public function advance() {
        if ( $this->is_done() ) {
            return;
        }

        $csvfile = $this->get_csvfile( $this->workingdir . 'export.csv' );

        // Write header as first line.
        if ( $this->exported === 0 ) {
            fwrite( $csvfile, $this->prepare_header( $this->header() ) );
        }

        $nextlistings = array_slice( $this->listings, $this->exported, self::BATCH_SIZE );

        foreach ( $nextlistings as $listing_id ) {
            $this->listing = get_post( $listing_id );
            $data          = $this->extract_data();
            if ( $data ) {
                $content = implode( $this->settings['csv-file-separator'], $data );
                fwrite( $csvfile, $this->prepare_content( $content ) );
            }

            $this->exported ++;
        }

        fclose( $csvfile );

        if ( $this->is_done() ) {
            if ( file_exists( $this->workingdir . 'images.zip' ) ) {
                $zip = $this->get_pclzip_instance( $this->workingdir . 'export.zip' );

                $files   = array();
                $files[] = $this->workingdir . 'export.csv';
                $files[] = $this->workingdir . 'images.zip';

                $zip->create( implode( ',', $files ), PCLZIP_OPT_REMOVE_ALL_PATH );

                unlink( $this->workingdir . 'export.csv' );
                unlink( $this->workingdir . 'images.zip' );
            }
        }
    }

    protected function get_csvfile( $path ) {
        return fopen( $path, 'a' );
    }

    protected function get_pclzip_instance( $path ) {
        if ( ! class_exists( 'PclZip' ) ) {
            define( 'PCLZIP_TEMPORARY_DIR', $this->workingdir );
            require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
        }

        return new PclZip( $path );
    }

    public function is_done() {
        return $this->exported === count( $this->listings );
    }

    private function prepare_header( $header ) {
        $bom = "\xEF\xBB\xBF"; /* UTF-8 BOM */

        return $bom . $header . "\n";
    }

    private function prepare_content( $content ) {
        // remove line break to avoid empty line on last write.
        if ( $this->exported !== count( $this->listings ) - 1 ) {
            $content = $content . "\n";
        }

        return $content;
    }

    public function get_file_path() {
        if ( file_exists( $this->workingdir . 'export.zip' ) ) {
            return $this->workingdir . 'export.zip';
        }

        return $this->workingdir . 'export.csv';
    }

    public function get_file_url() {
        $uploaddir = wp_upload_dir();
        $urldir    = trailingslashit( untrailingslashit( $uploaddir['baseurl'] ) . '/' . ltrim( str_replace( DIRECTORY_SEPARATOR, '/', str_replace( $uploaddir['basedir'], '', $this->workingdir ) ), '/' ) );

        if ( file_exists( $this->workingdir . 'export.zip' ) ) {
            return $urldir . 'export.zip';
        }

        return $urldir . 'export.csv';

    }

    /**
     * @return bool|string
     */
    private function header() {
        $out     = '';
        $columns = $this->columns;
        foreach ( $columns as $colname => &$col ) {
            $out .= $colname;
            $out .= $this->settings['csv-file-separator'];
        }

        $out = substr( $out, 0, - 1 );

        return $out;
    }

    private function prepare_images() {
        $images        = array();
        $image_objects = get_attached_media( 'image', $this->listing->ID );
        if ( count( $image_objects ) > 0 ) {
            $upload_dir = wp_upload_dir();

            foreach ( $image_objects as $image ) {
                $img_meta = wp_get_attachment_metadata( $image->ID );

                if ( empty( $img_meta['file'] ) ) {
                    continue;
                }

                $img_path = realpath( $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $img_meta['file'] );

                if ( ! is_readable( $img_path ) ) {
                    continue;
                }

                $this->images_archive = ( ! isset( $this->images_archive ) ) ? $this->get_pclzip_instance( $this->workingdir . 'images.zip' ) : $this->images_archive;
                $success              = $this->images_archive->add( $img_path, PCLZIP_OPT_REMOVE_ALL_PATH );
                if ( $success ) {
                    $images[] = basename( $img_path );
                }
            }
        }

        return implode( $this->settings['images-separator'], $images );
    }

    private function prepare_categories() {
        $categories = get_the_terms( $this->listing->ID, AWPCP_CATEGORY_TAXONOMY );
        $term_array = array();
        foreach ( $categories as $category ) {
            $term_array[] = $category->name;
        }

        return implode( $this->settings['category-separator'], $term_array );
    }

    /**
     * @param array $column field information.
     * Processing for date and extra fields values.
     *
     * @return string
     */
    private function prepare_meta_value( $column ) {
        $value = get_post_meta( $this->listing->ID, $column['name'], true );

        if ( $column['name'] === '_awpcp_start_date' || $column['name'] === '_awpcp_end_date' ) {
            $value = date_create( $value );
            $value = date_format( $value, 'm/d/y H:i:s' );
        }

        if ( $column['name'] === '_awpcp_sequence_id' ) {
            if ( ! $value ) {
                $sequence_id = "awpcp-{$this->listing->ID}";
                update_post_meta( $this->listing->ID, $column['name'], $sequence_id );
                $value = $sequence_id;
            }
        }

        if ( $column['description'] === 'Select Multiple' || $column['description'] === 'Checkbox' ) {
            $value = is_array( $value ) ? implode( ';', $value ) : '';
        }

        return $value;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return array|bool
     */
    private function extract_data() {
        global $awpcp;
        $this->listing_data = $awpcp->container['FormFieldsData']->get_stored_data( $this->listing );
        if ( ! $this->listing ) {
            return false;
        }

        $data = array();

        foreach ( $this->columns as $column_type => $columns ) {

            foreach ( $columns as $column_name => $column ) {
                $value = '';

                switch ( $column_type ) {
                    case 'post_fields':
                        $value = $this->listing->{$column['name']};
                        if ( $column['name'] === 'post_author' ) {
                            $author = get_userdata( $value );
                            $value  = $author ? $author->user_login : '';
                        }
                        break;
                    case 'terms':
                        $value = $this->prepare_categories();
                        break;
                    case 'metadata':
                        $value = $this->prepare_meta_value( $column );
                        break;
                    case 'region_fields':
                        $value = isset( $this->listing_data['regions'][0][ $column_name ] ) ? $this->listing_data['regions'][0][ $column_name ] : '';
                        break;
                    case 'custom':
                        if ( $column_name === 'images' ) {
                            $value = $this->prepare_images();
                            break;
                        }
                }

                if ( ! is_string( $value ) && ! is_array( $value ) ) {
                    $value = strval( $value );
                }

                $data[ $column_name ] = '"' . str_replace( '"', '""', $value ) . '"';
            }
        }

        return $data;
    }
}
