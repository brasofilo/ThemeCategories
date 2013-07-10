<?php

/**
 * Plugin Name: Theme Folders Categories
 * Plugin URI: http://wordpress.stackexchange.com/q/96361/12615
 * Version: 2013.07.10
 * Author: Rodolfo Buaiz
 * Author URI: http://wordpress.stackexchange.com/users/12615/brasofilo
 * 
 * License: GPLv2 or later
 *
 * 
 * This program is free software; you can redistribute it and/or modify it 
 * under the terms of the GNU General Public License version 2, 
 * as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty 
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
class B5F_Theme_Folders
{   
	/**
	 * Store list of parent theme folders
	 * @var array 
	 */
	protected $folders_array = array();

	protected $total_themes;
	/**
	 * Available categories colors
	 * built manually until interface is made to handle this
	 * @var array 
	 */
	protected $folders_colors = array( 
		array('#DCFEDE','#CBEBCD'),
		array('#F4FFAD','#FFFFD9'),
		array('#FFC9B7','#FFF4E8'),
		array('#FED6FF','#FCE6FF'),		
		array('#DAF7FE','#D1ECF2'),
		array('#FFB8DE','#FFDEEE'),
		array('#A8E3FF','#D9FBFF'),
		array('#B7FFB4','#D8FFD7'),		
	);

	public function __construct()
    {
        add_action( 'plugins_loaded', array( $this, 'start_up' ) );
    }    

	/**
	 * Hooks for Network themes and Single Site themes
	 * Nothing happens on sub-sites of a Network 
	 */
    public function start_up()
    {
		$this->total_themes = count( $this->core_count_themes() );
        if( is_network_admin() )
        {
			$this->core_build_folders_array();
            add_filter( 'manage_themes-network_columns', array( $this, 'network_column_register' ) );
            add_action( 'manage_themes_custom_column', array( $this, 'network_column_display' ), 10, 3 );
            add_action( 'admin_head-themes.php', array( $this, 'network_theme_category_css' ) );
        } 
        elseif( is_super_admin() ) 
        {
			$this->core_build_folders_array();
            add_filter( 'theme_action_links', array( $this, 'single_theme_folder' ), 10, 2 );
            add_action( 'admin_footer-themes.php', array( $this, 'single_theme_category_css' ), 999 );
        } 
    }

	
	private function core_build_folders_array()
	{
		$theme_directories = search_theme_directories();
		foreach( $theme_directories as $k => $v )
		{
			$dir = explode('/', $k);
			if( isset( $dir[1]) )
			{
				if( !in_array( $dir[0], $this->folders_array ) )
					$this->folders_array[] = $dir[0];
			}
			else
			{
				if( !in_array( 'root', $this->folders_array ) )
					$this->folders_array[] = 'root';
			}
		}
	}

	private function core_count_themes()
	{
		if ( ! class_exists( 'WP_Theme' ) )
			return get_themes();

		global $wp_themes;
		if ( isset( $wp_themes ) )
			return $wp_themes;

		$themes = wp_get_themes();
		$wp_themes = array();

		foreach ( $themes as $theme ) {
			$name = $theme->get('Name');
			if ( isset( $wp_themes[ $name ] ) )
				$wp_themes[ $name . '/' . $theme->get_stylesheet() ] = $theme;
			else
				$wp_themes[ $name ] = $theme;
		}

		return $wp_themes;	
	}
	/**
	 * Add custom category (folder) column in network themes
	 * 
	 * @param array $columns
	 * @return array
	 */
    public function network_column_register( $columns ) 
    {
        $columns['theme_folder'] = 'Category';
        return $columns;
    }

	/**
	 * Display custom row in network themes
	 * $stylesheet contains a string "folder/theme_name"
	 * $theme is a WP_Theme object
	 * 
	 * @param string $column_name
	 * @param string $stylesheet 
	 * @param object $theme 
	 * @return string
	 */
    public function network_column_display( $column_name, $stylesheet, $theme ) 
    {
        if( 'theme_folder' != $column_name  )
            return;

        echo $this->core_button_make( $stylesheet );
    }

	/**
	 * Adjust column width and button style in Multisite screen
	 */
    public function network_theme_category_css()
    {   
        echo PHP_EOL . "<style type='text/css'>
            #theme_folder { width: 10% }
            {$this->core_button_style()}
            </style>" . PHP_EOL;
    }

	/**
	 * Show theme category (folder) in single site theme action row
	 * $theme is a WP_Theme object
	 * 
	 * @param array $actions
	 * @param object $theme
	 * @return array
	 */
    public function single_theme_folder( $actions, $theme )
    {
        array_unshift( $actions, $this->core_button_make( $theme->stylesheet ) );
        return $actions;
    }

	/**
	 * Adjust button style in Single site screen
	 */
    public function single_theme_category_css()
    {   
		$folders = array();
		foreach( $this->folders_array as $f )
			$folders[] = sprintf( 
            '<a href="javascript:void(0)" group="theme-folder-%1$s" class="button-secondary theme-folder theme-folder-%1$s" title="%1$s">%1$s</a>',
            $f
        );
		$folders[] = sprintf( 
            '<a href="javascript:void(0)" group="theme-folder" class="button-secondary theme-folder theme-folder-%1$s" title="%1$s">%1$s</a>',
            'All'
				);
		$reset_themes = $this->total_themes > 36 ? '<a  href="javascript:void(0)" id="reset-theme-buttons" class="tooltip"> reset<span class="classic">Use after loading more themes (infinite loading)</span></a>' : '';
		$folders_buttons = '<ul class="t-list"><li>'. implode('</li><li>', $folders ) . '</li></ul>' . $reset_themes;
		
		$this->echo_css( $this->core_button_style() );
		$this->echo_js( $folders_buttons );

    }

	private function echo_css( $var )
	{
		// http://sixrevisions.com/css/css-only-tooltips/
        echo <<<CSS
<style type='text/css'>
	{$var}
	.t-list { clear: both }
	.t-list li {float:left; margin: 0 10px; }
	.tooltip {
		border-bottom: 1px dotted #000000; color: #000000; outline: none;
		cursor: help; text-decoration: none;
		position: relative;
	}
	.tooltip span {
		margin-left: -999em;
		position: absolute;
	}
	.tooltip:hover span {
		border-radius: 5px 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px; 
		box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.1); -webkit-box-shadow: 5px 5px rgba(0, 0, 0, 0.1); -moz-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);
		font-family: Calibri, Tahoma, Geneva, sans-serif;
		position: absolute; left: 1em; top: 2em; z-index: 99;
		margin-left: 0; width: 250px;
	}
	.tooltip:hover img {
		border: 0; margin: -10px 0 0 -55px;
		float: left; position: absolute;
	}
	.tooltip:hover em {
		font-family: Candara, Tahoma, Geneva, sans-serif; font-size: 1.2em; font-weight: bold;
		display: block; padding: 0.2em 0 0.6em 0;
	}
	.classic { padding: 0.8em 1em; }
	.custom { padding: 0.5em 0.8em 0.8em 2em; }
	* html a:hover { background: transparent; }
	.classic {background: #FFFFAA; border: 1px solid #FFAD33; }
	
</style>
CSS;
	}


	private function echo_js( $var )
	{
        echo <<<HTML
<script type='text/javascript'>
jQuery(document).ready(function ($){
	// Top row theme selectors
	$('.available-themes').after('{$var}');
		
	// Theme filtering
	$('.button-secondary.theme-folder').click(function(){
		var to_find = 'div.theme-author a.' + $(this).attr('group');
		var classe = $(this).attr('group');
		//if( classe == 'theme-folder-all' ) classe = 'theme-folder';
		var visible = 0;
		$( '#availablethemes .available-theme' ).each( 
			function(){
				var av_theme = $(this); 
				var where = av_theme.find( to_find ); 
				if( where.hasClass(classe) ) 
				{
					visible++;
					$('.displaying-num').text(visible+' items');
					$(this).show();
				}
				else 
					$(this).hide();
			}
		);

	});
	function b5f_render_buttons()
	{
		$( '#availablethemes .available-theme' ).each( 
			function(){
				var av_theme = $(this); 
				var where = av_theme.find( 'div.theme-author' ); 
				var what = av_theme.find( '.action-links li:first-child' ); 
				what.css( 'list-style', 'none' );
				where.prepend( what );
			}
		);
	}
	b5f_render_buttons();
	$('#reset-theme-buttons').click(b5f_render_buttons);
});
</script>
HTML;
	}
	
	/**
	 * Common button for Multi and Single sites
	 * The category name is extracted from a string "folder/themefolder"
	 * 
	 * @param object $theme
	 * @return string
	 */
	private function core_button_make( $stylesheet )
	{
		$dirname = ( '.' == dirname( $stylesheet ) ) ? 'root' : dirname( $stylesheet );
		
		$button_category = sprintf( 
            '<a href="javascript:void(0)" class="button-secondary theme-folder theme-folder-%1$s" title="%1$s">%1$s</a>',
            $dirname
        );
		return $button_category;
	}
	
	/**
	 * Common style for Multi and Single sites
	 * 
	 * @return string
	 */
    private function core_button_style()
    {
        $css = "
	.theme-folder { 
		cursor: default !important;
		line-height: 15px !important;
		height: 17px !important;
	}";
		$count = 0;
		$tot_colors = count( $this->folders_colors )-1;
		foreach( $this->folders_array as $t )
		{
			$hex_a = $this->folders_colors[$count][0];
			$hex_b = $this->folders_colors[$count][1];
			$css .= "
	.theme-folder-$t {	
		background-image: -webkit-gradient(linear, left top, left bottom, from($hex_a), to($hex_b)) !important;
		background-image: -webkit-linear-gradient(top, $hex_a, $hex_b) !important;
		background-image: -moz-linear-gradient(top, $hex_a, $hex_b) !important;
		background-image: -o-linear-gradient(top, $hex_a, $hex_b) !important;
		background-image: linear-gradient(to bottom, $hex_a, $hex_b) !important;
	}"; 
			
			$count = ( $count == $tot_colors ) ? 0 : $count + 1;
		}
		return $css;
    }
}

new B5F_Theme_Folders;