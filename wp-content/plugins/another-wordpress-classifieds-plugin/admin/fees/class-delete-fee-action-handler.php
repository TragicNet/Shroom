<?php

function awpcp_delete_fee_ajax_handler() {
    return new AWPCP_TableEntryActionAjaxHandler(
        new AWPCP_Delete_Fee_Action_Handler(
            awpcp_fees_admin_page(),
            awpcp_template_renderer(),
            awpcp_request()
        ),
        awpcp_ajax_response()
    );
}

class AWPCP_Delete_Fee_Action_Handler implements AWPCP_Table_Entry_Action_Handler {

    private $page;
    private $template_renderer;
    private $request;

    public function __construct( $page, $template_renderer, $request ) {
        $this->page = $page;
        $this->template_renderer = $template_renderer;
        $this->request = $request;
    }

    public function process_entry_action( $ajax_handler ) {
        $fee = AWPCP_Fee::find_by_id( $this->request->post( 'id' ) );

        if ( is_null( $fee ) ) {
            $message = __( "The specified Fee doesn't exists.", 'another-wordpress-classifieds-plugin' );
            return $ajax_handler->error( array( 'message' => $message ) );
        }

        $errors = array();

        if ( $this->request->post( 'remove' ) ) {
            if ( AWPCP_Fee::delete( $fee->id, $errors ) ) {
                return $ajax_handler->success();
            } else {
                return $ajax_handler->error( array( 'message' => join( '<br/>', $errors ) ) );
            }
        } else {
            $params = array( 'columns' => count( $this->page->get_table()->get_columns() ) );
            $template = AWPCP_DIR . '/admin/templates/delete_form.tpl.php';
            return $ajax_handler->success( array( 'html' => awpcp_render_template( $template, $params ) ) );
        }
    }
}
