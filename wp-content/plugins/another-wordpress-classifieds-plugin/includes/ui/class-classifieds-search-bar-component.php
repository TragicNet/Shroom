<?php

function awpcp_classifieds_search_bar_component() {
    return new AWPCP_Classifieds_Search_Bar_Component();
}

class AWPCP_Classifieds_Search_Bar_Component {

    public function render() {
        $template = AWPCP_DIR . '/templates/components/classifieds-search-bar.tpl.php';

        $params = array(
            'action_url' => url_searchads(),
        );

        if ( ! get_option('permalink_structure') ) {
            $params['page_id'] = awpcp_get_page_id_by_ref( 'search-ads-page-name' );
        }

        return awpcp_render_template( $template, $params );
    }
}

