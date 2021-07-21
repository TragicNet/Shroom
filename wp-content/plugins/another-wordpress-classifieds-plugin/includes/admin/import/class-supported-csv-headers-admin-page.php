<?php
/**
 * @package AWPCP\Admin\Import
 */

/**
 * Handler for the Supported CSV Headers admin page.
 */
class AWPCP_SupportedCSVHeadersAdminPage {

    /**
     * @var string
     */
    private $template = '/admin/import/supported-csv-headers.tpl.php';

    /**
     * @var CSVImporterColumns
     */
    private $columns;

    /**
     * @var TemplateRenderer
     */
    private $template_renderer;

    /**
     * @since 4.0.0
     */
    public function __construct( $columns, $template_renderer ) {
        $this->columns           = $columns;
        $this->template_renderer = $template_renderer;
    }

    /**
     * @since 4.0.0
     */
    public function dispatch() {
        $params = [
            'columns' => $this->get_supported_columns(),
        ];

        echo $this->template_renderer->render_template( $this->template, $params ); // XSS Ok.
    }

    /**
     * @since 4.0.0
     */
    private function get_supported_columns() {
        $supported_columns = [];

        foreach ( $this->columns->get_supported_columns() as $columns ) {
            foreach ( $columns as $header => $column ) {
                $supported_columns[ $header ] = $column;
            }
        }

        return $supported_columns;
    }
}
