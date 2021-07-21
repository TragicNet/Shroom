<?php


	if (!function_exists('wpfa_hide_additional_capabilities_section')) {

		add_filter('ure_show_additional_capabilities_section', 'wpfa_hide_additional_capabilities_section');

		function wpfa_hide_additional_capabilities_section($show) {
			if (!empty($_GET['vgfa_source'])) {
				$show = false;
			}
			return $show;
		}

	}
