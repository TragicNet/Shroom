<?php
/**
 * @package AWPCP\Framework
 */

/**
 * @since 4.0.0
 */
trait AWPCP_ModuleInstaller {

    /**
     * @since 4.0.0
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function install_or_upgrade( $module ) {
        if ( $this->is_new_installation( $module ) ) {
            $this->install_module( $module );
        } else {
            $this->upgrade_module( $module );
        }

        $this->after_install_or_upgrade( $module );
    }

    /**
     * @since 4.0.0
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function is_new_installation( $module ) {
        return true;
    }

    /**
     * @since 4.0.0
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function install_module( $module ) {
    }

    /**
     * @since 4.0.0
     */
    protected function upgrade_module( $module ) {
        $installed_version = $module->get_installed_version();

        foreach ( $this->get_upgrade_routines() as $version => $routines ) {
            if ( version_compare( $installed_version, $version ) >= 0 ) {
                continue;
            }

            foreach ( (array) $routines as $routine ) {
                if ( method_exists( $this, $routine ) ) {
                    $this->{$routine}( $installed_version );
                }
            }
        }
    }

    /**
     * @since 4.0.0
     */
    protected function get_upgrade_routines() {
        return [];
    }

    /**
     * @since 4.0.0
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function after_install_or_upgrade( $module ) {
    }
}
