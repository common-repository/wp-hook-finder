<?php
	class WP_HookFinder {
	
		var $plugin_path;
		var $plugin_url;
		var $plugin_prefix;
		var $plugin_file;
		
		var $screen;
	
		var $folder_to_scan;
		var $hooks_to_find;
		var $files_to_scan;
		var $actions_found;
		var $filters_found;
		var $custom_actions_found;
		var $custom_filters_found;
		
		var $pattern_actions;
		var $pattern_filters;
		var $pattern_custom_actions;
		var $pattern_custom_filters;
	
		function WP_HookFinder ( $plugin_prefix, $plugin_file ) {
		
			$this->plugin_path = dirname( $plugin_file );
			$this->plugin_url = trailingslashit( WP_PLUGIN_URL ) . plugin_basename( dirname( $plugin_file ) );
			$this->plugin_prefix = $plugin_prefix;
			$this->plugin_file = $plugin_file;
		
			// Run this on activation of the plugin.
			// register_activation_hook( $this->plugin_file, array( &$this, 'activation' ) );
			
			// Register the admin menu.
			add_action ( 'admin_menu', array(&$this, 'admin_screen_register') );
			
			// Load the admin notices.
			add_action ( 'admin_notices', array(&$this, 'admin_notices') );	
			
			// Add the contextual help to the admin screen.
			add_action ( 'contextual_help', array( &$this, 'contextual_help' ), 10, 3 );
			
			// Load the translations.
			add_action ( 'init', array( &$this, 'load_translations' ) );
			
			$this->init();
			
		} // End WP_HookFinder()
		
		function init () {
			
			$this->folder_to_scan = '';
			$this->hooks_to_find = array();
			$this->files_to_scan = array();
			$this->actions_found = array();
			$this->filters_found = array();
			$this->custom_actions_found = array();
			$this->custom_filters_found = array();
						
			$this->pattern_actions = '/add_action(.*?);/i';
			$this->pattern_filters = '/add_filter(.*?);/i';
			$this->pattern_custom_actions = '/do_action(.*?);/i';
			$this->pattern_custom_filters = '/apply_filters(.*?);/i';
			
		} // End init()
		
		function get_files ( $pattern, $flags = 0, $path = '' ) {
		
		    if ( ! $path && ( $dir = dirname( $pattern ) ) != '.' ) {
		    	
		        if ($dir == '\\' || $dir == '/') { $dir = ''; } // End IF Statement
		        
		        return $this->get_files(basename( $pattern ), $flags, $dir . '/' );
		    
		    } // End IF Statement
		    
		    $paths = glob( $path . '*', GLOB_ONLYDIR | GLOB_NOSORT );
		    $files = glob( $path . $pattern, $flags );
		    
		    foreach ( $paths as $p ) {
		   	
		   		$files = array_merge( $files, $this->get_files( $pattern, $flags, $p . '/' ) );
		   		
		    } // End FOREACH Loop
		    
		    return $files;
	    
	    } // End get_files()
		
		function find_actions ( $matches ) {
			
			$this->process_regex( $matches, 'actions_found' );
			
		} // End find_actions()
		
		function find_filters ( $matches ) {
			
			$this->process_regex( $matches, 'filters_found' );
			
		} // End find_filters()
		
		function find_custom_actions ( $matches ) {
		
			$this->process_regex( $matches, 'custom_actions_found', true );
			
		} // End find_custom_actions()
		
		function find_custom_filters ( $matches ) {
		
			$this->process_regex( $matches, 'custom_filters_found', true );
			
		} // End find_custom_filters()
		
		function process_regex ( $matches, $array, $is_custom = false ) {
			
			$_invalid_chars = array();
			$_invalid_chars = array( '(', ')', '"', ' ', '\'' );
		
			$filter_raw = $matches[1];
			
			if ( $is_custom ) {} else {
			
				// Cater for anonymous functions.
				$is_anonymous = strpos( $filter_raw, 'create_function' );
				
				if ( $is_anonymous === false ) {} else {
				
					$filter_raw = str_replace( ' ', '', $filter_raw );
					$filter_raw .= "')";
					
					$this->{$array}['_anonymous_functions'] = $filter_raw;
					
					return;
					
				} // End IF Statement
				
				// Cater for Class methods.
				$is_method = strpos( $filter_raw, 'array' );
				
				if ( $is_method === false ) {} else {
				
					$filter_raw = str_replace( ' ', '', $filter_raw );
					$filter_raw .= "')";
					
					$this->{$array}['_class_methods'] = $filter_raw;
					
					return;
					
				} // End IF Statement
				
			} // End IF Statement
				
				$filter = '';
				
				foreach ( $_invalid_chars as $i ) {
					
					$filter_raw = str_replace( $i, '', $filter_raw );
					
				} // End FOREACH Loop
				
				$filter = $filter_raw;
				
				// Separate the function (1), action being called on (0) and, if present, the priority (2) and number of arguments (3).
				$filter_data = explode( ',', $filter );
			
			if ( $is_custom ) {
			
				// Add the custom entry to the array, along with how many times it's called.
				if ( isset( $this->{$array}[$filter_data[0]] ) ) {
				
					$this->{$array}[$filter_data[0]] += 1;
					
				} else {
				
					$this->{$array}[$filter_data[0]] = 1;
					
				} // End IF Statement
				
			} else {
				
				$custom_data = array();
				$custom_data[$filter_data[1]]['function'] = $filter_data[1];
				
				// If there is a priority set, add it to the returned data.
				if ( array_key_exists( 2, $filter_data ) ) {
					
					$custom_data[$filter_data[1]]['priority'] = $filter_data[2];
					
				} // End IF Statement
				
				// If there is a number of arguments set, add it to the returned data.
				if ( array_key_exists( 3, $filter_data ) ) {
					
					$custom_data[$filter_data[1]]['accepted_args'] = $filter_data[3];
					
				} // End IF Statement
				
				$this->{$array}[$filter_data[0]] = $custom_data;
			
			} // End IF Statement
			
		} // End process_regex()
		
		function process_request () {
			
			// If we have one, get the PHP files from it.	
			
			if ( $this->folder_to_scan ) {
			
				$this->files_to_scan = $this->get_files( '*.php', GLOB_MARK, $this->folder_to_scan );
				
				// If the folder contains files to scan, scan them.
				if ( count( $this->files_to_scan ) ) {
					
					foreach ( $this->files_to_scan as $f ) {
						
						$handle = fopen( $f, "r" );
						
						if ( filesize( $f ) > 0 ) {
							
							$contents = fread( $handle, filesize( $f ) );
							
							fclose( $handle );
						
							if ( ( is_array( $this->hooks_to_find ) && in_array( 'action', $this->hooks_to_find ) ) || ( count( $this->hooks_to_find ) == 0 ) ) {
						
								// Scan for functions added to any actions.
								preg_replace_callback( $this->pattern_actions, array( &$this, 'find_actions' ), $contents );
								
								ksort( $this->actions_found );
							
							} // End IF Statement
							
							if ( ( is_array( $this->hooks_to_find ) && in_array( 'filter', $this->hooks_to_find ) ) || ( count( $this->hooks_to_find ) == 0 ) ) {
							
								// Scan for functions added to any filters.
								preg_replace_callback( $this->pattern_filters, array( &$this, 'find_filters' ), $contents );
								
								ksort( $this->filters_found );
							
							} // End IF Statement
							
							if ( ( is_array( $this->hooks_to_find ) && in_array( 'custom_action', $this->hooks_to_find ) ) || ( count( $this->hooks_to_find ) == 0 ) ) {
							
								// Scan for functions added to any custom actions.
								preg_replace_callback( $this->pattern_custom_actions, array( &$this, 'find_custom_actions' ), $contents );
								
								ksort( $this->custom_actions_found );
							
							} // End IF Statement
							
							if ( ( is_array( $this->hooks_to_find ) && in_array( 'custom_filter', $this->hooks_to_find ) ) || ( count( $this->hooks_to_find ) == 0 ) ) {
							
								// Scan for functions added to any custom filters.
								preg_replace_callback( $this->pattern_custom_filters, array( &$this, 'find_custom_filters' ), $contents );
								
								ksort( $this->custom_filters_found );
							
							} // End IF Statement
						
						} // End IF Statement
						
					} // End FOREACH Loop
					
				} // End IF Statement
				
			} // End IF Statement
			
			
		} // End process_request()
		
		function admin_screen_register () {
		
			if (function_exists('add_submenu_page')) {
				
				$this->screen = add_submenu_page( 'tools.php', __( 'WP Hook Finder', 'wp-hook-finder' ), __( 'Hook Finder', 'wp-hook-finder' ), 'manage_options', 'wp-hook-finder', array( &$this, 'admin_screen' ) );
				
			} // End IF Statement
			
		} // End admin_screen_register()
		
		function admin_screen () {
		
			// Separate the admin page XHTML to keep things neat and in the appropriate location.
			require_once($this->plugin_path . '/screens/screen.php');
			
		} // End admin_screen()
		
		function get_themes () {} // End get_themes()
		
		function get_plugins () {} // End get_plugins()
		
		function contextual_help ( $contextual_help, $screen_id, $screen ) {
		
			 global $title;
	  
			  // $contextual_help .= var_dump($screen); // use this to help determine $screen->id
			  
			  if ( $this->screen == $screen->id ) {
			  
			    $contextual_help =
				'<h5>' . sprintf ( __( '%s Documentation', 'wp-hook-finder' ), esc_html( $title ) ) . '</h5>' . 
				'<p><strong>' . __('So, how does this plugin work anyway?', 'wp-hook-finder') . '</strong></p>' .
				'<p>' . sprintf ( __( 'Using %s couldn\'t be any easier.', 'wp-hook-finder' ), esc_html( $title ) ) . '</p>' .
				'<p>' . sprintf ( __( 'To start using %s, select the folder you\'d like to scan and click the "Scan for Hooks" button. It\'s as easy as that!', 'wp-hook-finder' ), esc_html( $title ) ) . '</p>' .
			      '<h5>' . __('For more information:') . '</h5>' .
			      '<p>' . __('<a href="http://matty.co.za/plugins/wp-hook-finder/" target="_blank">' . esc_html( $title ) . ' Website and Documentation</a>') . '</p>' .
			      '<p>' . __('<a href="http://wordpress.org/tags/wp-hook-finder" target="_blank">' . esc_html( $title ) . ' Support Forums on WordPress.org</a>') . '</p>';
			  
			  } // End IF Statement
			  
			  return $contextual_help;
			
		} // End contextual_help()
		
		function load_translations () {
			
			$languages_dir = basename( $this->plugin_path ) . '/languages';
			
			load_plugin_textdomain( 'wp-hook-finder', false, $languages_dir );
			
		} // End load_translations()
		
		function admin_notices () {} // End admin_notices()
		
		function activation () {
		
			// Setup the version setter, for use with future upgrades, etc.
			$_data = get_plugin_data( $this->plugin_file );
			
			if ( array_key_exists( 'Version', $_data ) ) {
				
				update_option( $this->prefix . 'version', $_data['Version'] );
				
			} // End IF Statement
			
		} // End activation()
		
		function get_name_from_folder ( $folder_to_scan ) {
		
			$current_name = '';
			$themes = get_themes();
			$plugins = get_plugins();
		
			if ( count( $themes ) ) {
			
				foreach ( $themes as $name => $data ) {
				
					if ( ( $current_name == '' ) && ( $data['Stylesheet Dir'] == $folder_to_scan ) ) {
					
						$current_name = $name;
					
					} else {
						
						continue;
						
					} // End IF Statement
					
				} // End FOREACH Loop
			
			} // End IF Statement
			
			if ( count( $plugins ) ) {
			
				foreach ( $plugins as $root_file => $data ) {
				
					if ( ( $current_name == '' ) && ( trailingslashit( WP_PLUGIN_DIR ) . dirname( $root_file ) == $folder_to_scan ) ) {
					
						$current_name = $data['Name'];
					
					} else {
						
						continue;
						
					} // End IF Statement
					
				} // End FOREACH Loop
			
			} // End IF Statement
			
			return $current_name;
			
		} // End get_name_from_folder()
		
		function display_found_action_hooks () {
		
			$html = '';
		
			if ( count( $this->actions_found ) ) {
			
				$html .= '<h2 style="margin-top: 0px;">' . __( 'Functions Hooked Onto Actions', 'wp-hook-finder' ) . '</h2>' . "\n";
	
				foreach ( $this->actions_found as $k => $v ) {
				
					$html .= '<h4>' . $k . '</h4>' . "\n";
					
					$html .= '<ul>' . "\n";
					
					if ( is_array( $v ) ) {
					
						foreach ( $v as $i => $j ) {
						
							$args = '';
							
							if ( array_key_exists( 'priority', $j ) ) { $args .= ' Priority: ' . $j['priority']; } // End IF Statement
							
							if ( array_key_exists( 'accepted_args', $j ) ) { $args .= ' Number of Accepted Arguments: ' . $j['accepted_args']; } // End IF Statement
						
							if ( $args ) { $args = ' <small>(' . $args . ' )</small>'; } // End IF Statement
						
							$html .= '<li>' . $i . $args . '</li>' . "\n";
							
						} // End FOREACH Loop
					
					} else {
						
						$html .= '<li>' . $v . '</li>' . "\n";
					
					} // End IF Statement
					
					$html .= '</ul>' . "\n";
					
				} // End FOREACH Loop
				
			} // End IF Statement
			
			return $html;
			
		} // End display_found_action_hooks()
		
		function display_found_filter_hooks () {
		
			$html = '';
			
			if ( count( $this->filters_found ) ) {
			
				$html .= '<h2 style="margin-top: 0px;">' . __( 'Functions Hooked Onto Filters', 'wp-hook-finder' ) . '</h2>' . "\n";

				foreach ( $this->filters_found as $k => $v ) {
				
					$html .= '<h4>' . $k . '</h4>' . "\n";
					
					$html .= '<ul>' . "\n";
					
						if ( is_array( $v ) ) {
						
							foreach ( $v as $i => $j ) {
							
								$args = '';
								
								if ( array_key_exists( 'priority', $j ) ) { $args .= ' Priority: ' . $j['priority']; } // End IF Statement
								
								if ( array_key_exists( 'accepted_args', $j ) ) { $args .= ' Number of Accepted Arguments: ' . $j['accepted_args']; } // End IF Statement
							
								if ( $args ) { $args = ' <small>(' . $args . ' )</small>'; } // End IF Statement
							
								$html .= '<li>' . $i . $args . '</li>' . "\n";
								
							} // End FOREACH Loop
						
						} else {
							
							$html .= '<li>' . $v . '</li>' . "\n";
						
						} // End IF Statement
													
					$html .= '</ul>' . "\n";
					
				} // End FOREACH Loop
				
			} // End IF Statement
			
			return $html;
			
		} // End display_found_filter_hooks()
		
		function display_found_custom_action_hooks () {
		
			$html = '';
		
			if ( count( $this->custom_actions_found ) ) {
			
				$html .= '<h2 style="margin-top: 0px;">' . __( 'Custom Action Hooks', 'wp-hook-finder' ) . '</h2>' . "\n";

				$html .= '<ul>' . "\n";

				foreach ( $this->custom_actions_found as $k => $v ) {
				
					$number_friendly = '';
					
					switch ( $v ) {
					
						case 1:
						
							$number_friendly = __( 'once', 'wp-hook-finder' );
						
						break;
						
						case 2:
						
							$number_friendly = __( 'twice', 'wp-hook-finder' );
						
						break;
						
						default:
						
							$number_friendly = $v . ' ' . __( 'times', 'wp-hook-finder' );
						
						break;
						
					} // End SWITCH Statement
				
					$html .= '<li>' . "\n";
						$html .= '<strong>' . $k . '</strong>' . "\n";
						$html .= '<small>(' . sprintf( __( 'Used %s', 'wp-hook-finder' ), $number_friendly ) . ')</small>';
					$html .= '</li>' . "\n";
					
				} // End FOREACH Loop
				
				$html .= '</ul>' . "\n";
				
			} // End IF Statement
			
			return $html;
			
		} // End display_found_custom_action_hooks()
		
		function display_found_custom_filter_hooks () {
		
			$html = '';
			
			if ( count( $this->custom_filters_found ) ) {
			
				$html .= '<h2 style="margin-top: 0px;">' . __( 'Custom Filter Hooks', 'wp-hook-finder' ) . '</h2>' . "\n";

				$html .= '<ul>' . "\n";

				foreach ( $this->custom_filters_found as $k => $v ) {
				
					$number_friendly = '';
					
					switch ( $v ) {
					
						case 1:
						
							$number_friendly = __( 'once', 'wp-hook-finder' );
						
						break;
						
						case 2:
						
							$number_friendly = __( 'twice', 'wp-hook-finder' );
						
						break;
						
						default:
						
							$number_friendly = $v . ' ' . __( 'times', 'wp-hook-finder' );
						
						break;
						
					} // End SWITCH Statement
				
					$html .= '<li>' . "\n";
						$html .= '<strong>' . $k . '</strong>' . "\n";
						$html .= '<small>(' . sprintf( __( 'Used %s', 'wp-hook-finder' ), $number_friendly ) . ')</small>';
					$html .= '</li>' . "\n";
					
				} // End FOREACH Loop
				
				$html .= '</ul>' . "\n";
				
			} // End IF Statement
			
			return $html;
			
		} // End display_found_custom_filter_hooks()
		
	} // End Class
?>