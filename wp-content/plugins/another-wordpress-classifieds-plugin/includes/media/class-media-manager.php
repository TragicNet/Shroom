<?php
/**
 * @package AWPCP\Media
 */

/**
 * Constructor function.
 */
function awpcp_new_media_manager() {
    static $instance = null;

    if ( is_null( $instance ) ) {
        $container = awpcp()->container;

        $instance = new AWPCP_Media_Manager(
            $container['FileHandlersManager'],
            awpcp_uploaded_file_logic_factory(),
            awpcp()->settings
        );
    }

    return $instance;
}

/**
 * A File Logic object exposes methods to access information about the uploaded file.
 *
 * This class offers methods that create a File Logic object for the uploaded file
 * and then attempt to find a File Handler for the kind of file that was uploaded.
 *
 * The File Handler object is then used to determine whether the file is valid and
 * to add the file to the system.
 *
 * We currently support Images and Attachments (other files excluding videos).
 * Videos were also supported for a short time through the now abandoned Videos
 * module.
 */
class AWPCP_Media_Manager {

    /**
     * @var object
     */
    private $file_handlers;

    /**
     * @var object
     */
    private $uploaded_file_logic_factory;

    /**
     * @var object
     */
    private $settings;

    /**
     * @param object $file_handlers                 An instance of File Handlers.
     * @param object $uploaded_file_logic_factory   An instance of Uploaded File Logic Factory.
     * @param object $settings                      An instance of Settings.
     */
    public function __construct( $file_handlers, $uploaded_file_logic_factory, $settings ) {
        $this->file_handlers               = $file_handlers;
        $this->uploaded_file_logic_factory = $uploaded_file_logic_factory;
        $this->settings                    = $settings;
    }

    /**
     * Uses a File Handler to determine whether the uploaded file is valid or not.
     *
     * @param object $listing           An instance of WP_Post.
     * @param object $uploaded_file     An object with information about the file
     *                                  that is being added.
     * @since 4.0.0
     */
    public function validate_file( $listing, $uploaded_file ) {
        $file_logic   = $this->uploaded_file_logic_factory->create_file_logic( $uploaded_file );
        $file_handler = $this->file_handlers->get_handler_for_file( $file_logic );

        return $file_handler->validate_file( $listing, $file_logic );
    }

    /**
     * Uses a File Handler to add the uploaded file to the system and associate
     * it with the listing.
     *
     * @param object $listing           An instance of WP_Post.
     * @param object $uploaded_file     An object with information about the file
     *                                  that is being added.
     */
    public function add_file( $listing, $uploaded_file ) {
        $file_logic   = $this->uploaded_file_logic_factory->create_file_logic( $uploaded_file );
        $file_handler = $this->file_handlers->get_handler_for_file( $file_logic );

        return $file_handler->handle_file( $listing, $file_logic );
    }
}
