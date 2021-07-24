<?php
/**
 * @package AWPCP\Settings\Renderers
 */

/**
 * Renders an email template setting.
 */
class AWPCP_EmailTemplateSettingsRenderer {

    /**
     * @since 4.0.0
     */
    public function __construct( $setting, $template_renderer ) {
        $this->settings          = $setting;
        $this->template_renderer = $template_renderer;
    }

    /**
     * @since 4.0.0
     */
    public function render_setting( $setting ) {
        $value = $this->settings->get_option( $setting['id'] );

        $params = [
            'subject'            => $value['subject'],
            'body'               => $value['body'],
            'version'            => $value['version'],
            'placeholders'       => $setting['placeholders'],

            'setting_id'         => $setting['id'],
            'subject_field_name' => "{$this->settings->setting_name}[{$setting['id']}][subject]",
            'body_field_name'    => "{$this->settings->setting_name}[{$setting['id']}][body]",
            'version_field_name' => "{$this->settings->setting_name}[{$setting['id']}][version]",
        ];

        $template = '/admin/settings/email-template.tpl.php';

        echo $this->template_renderer->render_template( $template, $params ); // XSS Ok.
    }
}
