<?php
/*
Plugin Name: WP Hook Finder
Plugin URI: http://matty.co.za/plugins/wp-hook-finder/
Description: Easily find all hooks and filters used in a WordPress theme or plugin.
Version: 1.0.0
Author: Matty
Author URI: http://matty.co.za/
*/
?>
<?php
/*  Copyright 2011  Matty  (email : nothanks@idontwantspam.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
	require_once( 'classes/hookfinder.class.php' );
	
	$wp_hook_finder = new WP_HookFinder( 'wphf_', __FILE__ );
?>