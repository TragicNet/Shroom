<?php
/**
 * @package AWPCP\UI
 */

// phpcs:disable

class AWPCP_FormStepsComponent {

    /**
     * @var FormSteps
     */
    private $form_steps;

    public function __construct( AWPCP_FormSteps $form_steps ) {
        $this->form_steps = $form_steps;
    }

    /**
     * @since 4.0.0     $transaction parameter was replaced by an optional $params array.
     */
    public function render( $selected_step, $params = [] ) {
        return $this->render_steps( $selected_step, $this->form_steps->get_steps( $params ) );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    private function render_steps( $selected_step, $steps ) {
        $form_steps = $this->prepare_steps( $steps, $selected_step );

        ob_start();
        include( AWPCP_DIR . '/templates/components/form-steps.tpl.php' );
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function prepare_steps( $steps, $selected_step ) {
        $form_steps = array();

        $previous_steps = array();
        $steps_count = 0;

        foreach ( $steps as $step => $name ) {
            $steps_count = $steps_count + 1;

            if ( $selected_step == $step ) {
                $step_class = 'current';
            } else if ( ! in_array( $selected_step, $previous_steps ) ) {
                $step_class = 'completed';
            } else {
                $step_class = 'pending';
            }

            $form_steps[ $step ] = array( 'number' => $steps_count, 'name' => $name, 'class' => $step_class );

            $previous_steps[] = $step;
        }

        return $form_steps;
    }
}
