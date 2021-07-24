			<div class="metabox-holder">
				<div class="postbox">
					<h3 class="hndle"><span><?php _e('Restore AWPCP Pages', 'another-wordpress-classifieds-plugin') ?></span></h3>
					<div class="inside">

			<?php
				if ( ! empty( $restored_pages ) ){
					$message = __( 'The following pages were restored: <pages-list>.', 'another-wordpress-classifieds-plugin' );
					$pages_names = array_map( 'awpcp_get_option', awpcp_get_properties( $restored_pages, 'page' ) );
					$pages_list = '<strong>' . implode( '</strong>, <strong>', $pages_names ) . '</strong>';
					echo awpcp_print_message( str_replace( '<pages-list>', $pages_list, $message ) );
				}
			?>

			<?php if (!empty($missing)): ?>

			<div class="error">
			<?php if ( ! empty( $missing['not-found'] ) ): ?>
				<p><?php _e( "The pages listed below are missing. The plugin is looking for a page with a particular ID but it seems that the page was permanently deleted. Please a select a new one.", 'another-wordpress-classifieds-plugin' ); ?></p>

				<ul>
				<?php foreach ( $missing['not-found'] as $page ): ?>
				<?php $message = __( "%s (Default name: %s).", 'another-wordpress-classifieds-plugin' ); ?>
				<?php $message = sprintf( $message, '<strong>' . $page->label . '</strong>', $page->default_name, $page->id );  ?>
				<li><?php echo $message; ?></li>
				<?php endforeach ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $missing['not-published'] ) ): ?>
				<p><?php _e( "The following pages are not published. Did you move them to the Trash by accident or saved them as Draft?", 'another-wordpress-classifieds-plugin' ); ?></p>

				<ul>
				<?php foreach ( $missing['not-published'] as $page ): ?>
                <?php if ( 'trash' == $page->status ): ?>
                <?php $url = add_query_arg( array( 's' => $page->name, 'post_status' => 'trash', 'post_type' => 'page' ), admin_url( 'edit.php' ) ); ?>
                <?php else: ?>
                <?php $url = add_query_arg( array( 'post' => $page->id, 'action' => 'edit' ), admin_url( 'post.php' ) ); ?>
                <?php endif; ?>
                <?php $link = sprintf( '<a href="%s">%s</a>', $url, $page->name ); ?>
                <?php $message = __( "%s &mdash; Selected page: %s (%s)", 'another-wordpress-classifieds-plugin' ); ?>
                <?php $message = sprintf( $message, '<strong>' . $page->label . '</strong>', $link, $page->status ); ?>
				<li><?php echo $message; ?></li>
				<?php endforeach ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $missing['not-referenced'] ) ): ?>
				<p><?php _e( "The following pages are not currently assigned. Please select an existing page or create a new one to use as the following plugin pages:", 'another-wordpress-classifieds-plugin' ); ?></p>

				<ul>
				<?php foreach ( $missing['not-referenced'] as $page ): ?>
                <?php if ( $page->candidates ): ?>
                    <?php $candidate_pages = array(); ?>

                    <?php foreach ( $page->candidates as $candidate_page ): ?>
                        <?php $edit_page_url = add_query_arg( array( 'post' => $page->id, 'action' => 'edit' ), admin_url( 'post.php' ) ); ?>
                        <?php // $candidate_pages[] = sprintf( '<a href="%s">%s</a>', $edit_page_url, $candidate_page->post_title ); ?>
                        <?php $candidate_pages[] = sprintf( '<strong>%s</strong>', $candidate_page->post_title ); ?>
                    <?php endforeach; ?>

                    <?php $create_page_url = add_query_arg( 'post_type', 'page', admin_url( 'post-new.php' ) ); ?>

                    <?php $message = __( '<page-label> &mdash; You can select one of these pages that already include the necessary shortcode: <page-links> or <a>create a new one</a>.', 'another-wordpress-classifieds-plugin' ); ?>
                    <?php $message = str_replace( '<page-label>', '<strong>' . $page->label . '</strong>', $message ); ?>
                    <?php $message = str_replace( '<a>', '<a href="' . esc_url( $create_page_url ) . '">', $message ); ?>
                    <?php $message = str_replace( '<page-links>', implode( ', ', $candidate_pages ), $message ); ?>

                    <li><?php echo $message; ?></li>
                <?php else: ?>
                    <?php $message = __( "%s (Default name: %s).", 'another-wordpress-classifieds-plugin' ); ?>
                    <?php $message = sprintf( $message, '<strong>' . $page->label . '</strong>', $page->default_name, $page->id );  ?>

                    <li><?php echo $message; ?></li>
                <?php endif; ?>
				<?php endforeach ?>
				</ul>
			<?php endif; ?>
			</div>

			<?php endif ?>

			<form method="post">
				<?php wp_nonce_field('awpcp-restore-pages'); ?>
				<p><?php _e( 'Use the button below to have the plugin attempt to find the necessary pages. If you continue to have problems or seeing page related warnings above, you can delete affected plugin pages and use the Restore Pages button to have the plugin create them again.', 'another-wordpress-classifieds-plugin') ?></p>
				<input type="submit" value="<?php echo esc_attr( __( 'Restore Pages', 'another-wordpress-classifieds-plugin' ) ); ?>" class="button-primary" id="submit" name="restore-pages">
			</form>

					</div>
				</div>
			</div>
