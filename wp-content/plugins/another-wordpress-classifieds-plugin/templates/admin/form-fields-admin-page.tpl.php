<?php
/**
 * @package AWPCP\Templates\Admin
 */

?><form method="post" action="<?php echo esc_attr( $page->url( array( 'action' => false ) ) ); ?>">
    <p><?php echo esc_html( __( 'The table below shows all the form fields that users may need to fill to create a listing. Use the six-dots icons at the end of each row to drag the form fields around and modify the order in which those fields appear in the Ad Details form.', 'another-wordpress-classifieds-plugin' ) ); ?></p>
    <p>
    <?php
        $settings_url  = awpcp_get_admin_settings_url( [ 'sg' => 'form-fields-settings' ] );
        $settings_link = sprintf( '<a href="%s">%s</a>', $settings_url, __( 'Form', 'another-wordpress-classifieds-plugin' ) );

        $message = __( 'Go to the <form-fields-settings-link> settings section to control which of the standard fields appear and if the user is required to enter a value. If you have the Extra Fields module, the rest of the fields can be configured from the Extra Fields admin section.', 'another-wordpress-classifieds-plugin' );
        $message = str_replace( '<form-fields-settings-link>', $settings_link, $message );

        echo $message; // XSS Ok.
    ?>
    </p>

    <?php echo $table->views(); // XSS Ok. ?>
    <?php echo $table->display(); // XSS Ok. ?>
</form>
