<?php

function quotescollection_widget_init()
{
	if(function_exists('load_plugin_textdomain'))
		load_plugin_textdomain('quotes-collection', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;
	
	function quotescollection_widget($args) {
		$options = get_option('quotescollection');
		$title = isset($options['title'])?apply_filters('the_title', $options['title']):__('Random Quote', 'quotes-collection');
		$show_author = isset($options['show_author'])?$options['show_author']:1;
		$show_source = isset($options['show_source'])?$options['show_source']:1;
		$ajax_refresh = isset($options['ajax_refresh'])?$options['ajax_refresh']:1;
		$auto_refresh = isset($options['auto_refresh'])?$options['auto_refresh']:0;
		$random_refresh = isset($options['random_refresh'])?$options['random_refresh']:1;
		if($auto_refresh)
			$auto_refresh = isset($options['refresh_interval'])?$options['refresh_interval']:5;
		$char_limit = $options['char_limit'];
		$tags = $options['tags'];
		$parms = "echo=0&show_author={$show_author}&show_source={$show_source}&ajax_refresh={$ajax_refresh}&auto_refresh={$auto_refresh}&char_limit={$char_limit}&tags={$tags}&random={$random_refresh}";
		if($random_quote = quotescollection_quote($parms)) {
			extract($args);
			echo $before_widget;
			if($title) echo $before_title . $title . $after_title . "\n";
			echo $random_quote;
			echo $after_widget;
		}
	}
	
	function quotescollection_widget_control()
	{
		
		// default values for options
		$options = array(
			'title' => __('Random Quote', 'quotes-collection'), 
			'show_author' => 1,
			'show_source' => 0, 
			'ajax_refresh' => 1,
			'auto_refresh' => 0,
			'random_refresh' => 1,
			'refresh_interval' => 5,
			'tags' => '',
			'char_limit' => 500
		);

		if($options_saved = get_option('quotescollection'))
			$options = array_merge($options, $options_saved);
			
		// Update options in db when user updates options in the widget page
		if(isset($_REQUEST['quotescollection-submit']) && $_REQUEST['quotescollection-submit']) { 
			$options['title'] 
				= strip_tags(stripslashes($_REQUEST['quotescollection-title']));
			$options['show_author'] = (isset($_REQUEST['quotescollection-show_author']) && $_REQUEST['quotescollection-show_author'])?1:0;
			$options['show_source'] = (isset($_REQUEST['quotescollection-show_source']) && $_REQUEST['quotescollection-show_source'])?1:0;
			$options['ajax_refresh'] = (isset($_REQUEST['quotescollection-ajax_refresh']) && $_REQUEST['quotescollection-ajax_refresh'])?1:0;
			$options['auto_refresh'] = (isset($_REQUEST['quotescollection-auto_refresh']) && $_REQUEST['quotescollection-auto_refresh'])?1:0;
			$options['refresh_interval'] = $_REQUEST['quotescollection-refresh_interval'];
			$options['random_refresh'] = (isset($_REQUEST['quotescollection-random_refresh']) && $_REQUEST['quotescollection-random_refresh'])?1:0;
			$options['tags'] = strip_tags(stripslashes($_REQUEST['quotescollection-tags']));
			$options['char_limit'] = strip_tags(stripslashes($_REQUEST['quotescollection-char_limit']));
			if(!$options['char_limit'])
				$options['char_limit'] = __('none', 'quotes-collection');
			update_option('quotescollection', $options);
		}

		// Now we define the display of widget options menu
		$show_author_checked = $show_source_checked	= $ajax_refresh_checked = $auto_refresh_checked = $random_refresh_checked = '';
		$int_select = array ( '5' => '', '10' => '', '15' => '', '20' => '', '30' => '', '60' => '');
        if($options['show_author'])
        	$show_author_checked = ' checked="checked"';
        if($options['show_source'])
        	$show_source_checked = ' checked="checked"';
        if($options['ajax_refresh'])
        	$ajax_refresh_checked = ' checked="checked"';
        if($options['auto_refresh'])
        	$auto_refresh_checked = ' checked="checked"';
        if($options['random_refresh'])
        	$random_refresh_checked = ' checked="checked"';
        $int_select[$options['refresh_interval']] = ' selected="selected"';

		echo "<p style=\"text-align:left;\"><label for=\"quotescollection-title\">".__('Title', 'quotes-collection')." </label><input class=\"widefat\" type=\"text\" id=\"quotescollection-title\" name=\"quotescollection-title\" value=\"".htmlspecialchars($options['title'], ENT_QUOTES)."\" /></p>";
		echo "<p style=\"text-align:left;\"><input type=\"checkbox\" id=\"quotescollection-show_author\" name=\"quotescollection-show_author\" value=\"1\"{$show_author_checked} /> <label for=\"quotescollection-show_author\">".__('Show author?', 'quotes-collection')."</label></p>";
		echo "<p style=\"text-align:left;\"><input type=\"checkbox\" id=\"quotescollection-show_source\" name=\"quotescollection-show_source\" value=\"1\"{$show_source_checked} /> <label for=\"quotescollection-show_source\">".__('Show source?', 'quotes-collection')."</label></p>";
		echo "<p style=\"text-align:left;\"><input type=\"checkbox\" id=\"quotescollection-ajax_refresh\" name=\"quotescollection-ajax_refresh\" value=\"1\"{$ajax_refresh_checked} /> <label for=\"quotescollection-ajax_refresh\">".__('Ajax refresh feature', 'quotes-collection')."</label></p>";
		echo "<p style=\"text-align:left;\"><small><a id=\"quotescollection-adv_key\" style=\"cursor:pointer;\" onclick=\"jQuery('div#quotescollection-adv_opts').slideToggle();\">".__('Advanced options', 'quotes-collection')." &raquo;</a></small></p>";
		echo "<div id=\"quotescollection-adv_opts\" style=\"display:none\">";
		echo "<p style=\"text-align:left;\"><input type=\"checkbox\" id=\"quotescollection-random_refresh\" name=\"quotescollection-random_refresh\" value=\"1\"{$random_refresh_checked} /> <label for=\"quotescollection-random_refresh\">".__('Random refresh', 'quotes-collection')."</label><br/><span class=\"setting-description\"><small>".__('Unchecking this will rotate quotes in the order added, latest first.', 'quotes-collection')."</small></span></p>";
		echo "<p style=\"text-align:left;\"><input type=\"checkbox\" id=\"quotescollection-auto_refresh\" name=\"quotescollection-auto_refresh\" value=\"1\"{$auto_refresh_checked} /> <label for=\"quotescollection-auto_refresh\">".__('Auto refresh', 'quotes-collection')."</label> <label for=\"quotescollection-refresh_interval\">".__('every', 'quotes-collection')."</label> <select id=\"quotescollection-refresh_interval\" name=\"quotescollection-refresh_interval\"><option{$int_select['5']}>5</option><option{$int_select['10']}>10</option><option{$int_select['15']}>15</option><option{$int_select['20']}>20</option><option{$int_select['30']}>30</option><option{$int_select['60']}>60</option></select> ".__('sec', 'quotes-collection')."</p>";
		echo "<p style=\"text-align:left;\"><label for=\"quotescollection-tags\">".__('Tags filter', 'quotes-collection')." </label><input class=\"widefat\" type=\"text\" id=\"quotescollection-tags\" name=\"quotescollection-tags\" value=\"".htmlspecialchars($options['tags'], ENT_QUOTES)."\" /><br/><span class=\"setting-description\"><small>".__('Comma separated', 'quotes-collection')."</small></span></p>";
		echo "<p style=\"text-align:left;\"><label for=\"quotescollection-char_limit\">".__('Character limit', 'quotes-collection')." </label><input class=\"widefat\" type=\"text\" id=\"quotescollection-char_limit\" name=\"quotescollection-char_limit\" value=\"".htmlspecialchars($options['char_limit'], ENT_QUOTES)."\" /></p>";
		echo "</div>";
		echo "<input type=\"hidden\" id=\"quotescollection-submit\" name=\"quotescollection-submit\" value=\"1\" />";
	}

	if ( function_exists( 'wp_register_sidebar_widget' ) ) {
		wp_register_sidebar_widget( 'quotescollection', 'Random Quote', 'quotescollection_widget' );
		wp_register_widget_control( 'quotescollection', 'Random Quote', 'quotescollection_widget_control' );
	} else {
		register_sidebar_widget(array('Random Quote', 'widgets'), 'quotescollection_widget');
		register_widget_control('Random Quote', 'quotescollection_widget_control', 250, 350);
	}
}

add_action('plugins_loaded', 'quotescollection_widget_init');
?>
