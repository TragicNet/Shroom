<?php
/**
 * @package AWPCP\Upgrade
 */

// @phpcs:disable Squiz.Commenting

class AWPCP_Upgrade_Task_Ajax_Handler extends AWPCP_AjaxHandler {

    /**
     * @var object
     */
    private $task_controller;

    private $request;

    public function __construct( $task_controller, $request, $response ) {
        parent::__construct( $response );

        $this->task_controller = $task_controller;
        $this->request         = $request;
    }

    public function ajax() {
        $task_slug = $this->request->param( 'action' );
        $context   = $this->request->param( 'context' );

        try {
            list( $records_count, $records_left ) = $this->task_controller->run_task( $task_slug, $context );
        } catch ( AWPCP_Exception $e ) {
            return $this->error_response( $e->getMessage() );
        }

        return $this->progress_response( $records_count, $records_left );
    }
}
