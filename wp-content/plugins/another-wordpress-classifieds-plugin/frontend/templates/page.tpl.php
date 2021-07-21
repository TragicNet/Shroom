<?php awpcp_print_messages() ?>

<div class="<?php echo esc_attr( $page->page ); ?> awpcp-page" id="classiwrapper">

    <?php if ( $page->show_menu_items ): ?>
        <?php echo awpcp_render_classifieds_bar( $page->classifieds_bar_components ); ?>
    <?php endif; ?>

	<?php echo $content ?>
</div>
