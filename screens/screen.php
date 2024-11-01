<?php
	global $title;
	
	$themes = get_themes();
	$plugins = get_plugins();
	
	$button_title = __( 'Scan for Hooks', 'wp-hook-finder' );
	
	if ( isset( $_POST['folder_to_scan'] ) ) {
		
		// Get the name of the file currently being scanned.
		$current_name = '';	
		$current_name = $this->get_name_from_folder( $_POST['folder_to_scan'] );
	
		$this->folder_to_scan = trim( strtolower( strip_tags( $_POST['folder_to_scan'] ) ) );
		
		if ( isset( $_POST['hooks_to_find'] ) ) {
			
			$this->hooks_to_find = $_POST['hooks_to_find'];
		
		} // End IF Statement
		
		$this->process_request();
		
		$button_title = sprintf( __( 'Re-Scan %s for Hooks', 'wp-hook-finder' ), $current_name );
		
	} // End IF Statement
?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php echo esc_html( $title ); ?></h2>
	<div id="poststuff">
		<form id="wp-hook-finder" action="" method="post">
			<div id="col-container">
				<div id="col-right">
				<?php
					
					if ( $this->folder_to_scan ) {
					
						$html = '';
					
						// Actions hooked onto internal hooks.
					
						$html .= $this->display_found_action_hooks();
						
						// Actions hooked onto internal hooks.
					
						$html .= $this->display_found_filter_hooks();
						
						// Custom action hooks.
					
						$html .= $this->display_found_custom_action_hooks();
						
						// Custom filter hooks.
					
						$html .= $this->display_found_custom_filter_hooks();
						
						echo $html;
						
					} // End IF Statement
				?>
				</div><!--/#col-right-->
				
				<div id="col-left">
					<div id="folder-selection" class="postbox">
						<h3 class="hndle"><span><?php echo __( 'Folder Selection', 'wp-hook-finder' ); ?></span></h3>
						<div class="inside">
							<p class="form-required">
								<label class="alignleft" for="folder_to_scan"><?php _e('Select a theme or plugin to scan', 'wp-hook-finder'); ?></label>
								<span class="alignright">
									<select name="folder_to_scan">
										<?php
											$html = '';
											
											// Process the theme folders.
											if ( count( $themes ) ) {
												
												$html .= '<optgroup label="' . __( 'Themes', 'wp-hook-finder' ) . '">' . "\n";
												
												foreach ( $themes as $name => $data ) {
												
													$selected = '';
													
													if ( $this->folder_to_scan == trim( strtolower( strip_tags(  $data['Stylesheet Dir'] ) ) ) ) {
													
														$selected = ' selected="selected"';
														
													} // End IF Statement
												
													$html .= '<option value="' . $data['Stylesheet Dir'] . '"' . $selected . ' test="' . $this->folder_to_scan . '">' . $name . '</option>' . "\n";
													
												} // End FOREACH Loop
												
												$html .= '</optgroup>' . "\n";
											
											} // End IF Statement
											
											// Process the plugin folders.
											if ( count( $plugins ) ) {
												
												$html .= '<optgroup label="' . __( 'Plugins', 'wp-hook-finder' ) . '">' . "\n";
												
												foreach ( $plugins as $root_file => $data ) {
												
													$plugin_folder = trailingslashit( WP_PLUGIN_DIR ) . dirname( $root_file );
													
													$selected = '';
													
													if ( $this->folder_to_scan == trim( strtolower( strip_tags( $plugin_folder ) ) ) ) {
													
														$selected = ' selected="selected"';
														
													} // End IF Statement
												
													$html .= '<option value="' . $plugin_folder . '"' . $selected . '>' . $data['Name'] . '</option>' . "\n";
													
												} // End FOREACH Loop
												
												$html .= '</optgroup>' . "\n";
											
											} // End IF Statement
											
											echo $html;
										?>
									</select>
								</span>
								<br class="clear" />
								<p><em><?php echo __( '(The selected folder will be scanned, with the hooks found being displayed to the right.)', 'wp-hook-finder' ); ?></em></p>
							</p><!--/.form-required-->
							<p class="form-required">
								<label class="alignleft" for="hooks_to_find"><?php _e('Select the types of hooks to scan for', 'wp-hook-finder'); ?></label>
								<span class="alignright">
									<?php
										$hooks = array(
														'action' => __( 'Actions', 'wp-hook-finder' ), 
														'filter' => __( 'Filters', 'wp-hook-finder' ), 
														'custom_action' => __( 'Custom Actions', 'wp-hook-finder' ), 
														'custom_filter' => __( 'Custom Filters', 'wp-hook-finder' )
													  );
										
										$html = '';
										
										$html .= '<ul class="alignright">' . "\n";
										
										foreach ( $hooks as $k => $v ) {
										
											$checked = '';
											
											if ( is_array( $this->hooks_to_find ) && in_array( $k, $this->hooks_to_find ) ) { $checked = ' checked="checked"'; } // End IF Statement
										
											$html .= '<li><input type="checkbox" value="' . $k . '" name="hooks_to_find[]"' . $checked . ' /> ' . $v . '</li>' . "\n";
											
										} // End FOREACH Loop
										
										$html .= '</ul>' . "\n";
										
										echo $html;
									?>
								</span>
								<br class="clear" />
								<p><em><?php echo __( '(Select specific types of hooks you\'d like to scan for.)', 'wp-hook-finder' ); ?></em></p>
							</p><!--/.form-required-->
						</div><!--/.inside-->
					</div><!--/#folder-selection .postbox-->

					<div class="button-set">
						<button type="submit" name="wphf_submit" class="button-primary alignright"><?php echo $button_title; ?> &rarr;</button>
						<br class="clear" />
					</div><!--/.button-set-->
				</div><!--/#col-left-->
				<div class="clear"></div><!--/.clear-->
			</div><!--/#col-container-->
		</form><!--/#wp-hook-finder-->
	</div><!--/#poststuff-->
</div><!--/.wrap-->