<?php
/*
Plugin Name: Quotes Collection
Plugin URI: http://srinig.com/wordpress/plugins/quotes-collection/
Description: Quotes Collection plugin with Ajax powered Random Quote sidebar widget helps you collect and display your favourite quotes on your WordPress blog.
Version: 1.5.9
Author: Srini G
Author URI: http://srinig.com/wordpress/
License: GPL2
*/

/*  Copyright 2007-2012 Srini G (email : srinig.com@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



/*	The 'Next quote »' link text
	By default, this is 'Next quote »' (or the corresponding translation).
	You can change it if you wish */
$quotescollection_next_quote = "";



/*	The maximum number iterations for the 'auto refresh'. Set this number to 0 
	if you want the auto refresh to happen infinitely. */
$quotescollection_auto_refresh_max = 30;


/*  Refer http://codex.wordpress.org/Roles_and_Capabilities */
$quotescollection_admin_userlevel = 'edit_posts'; 

$quotescollection_version = '1.5.7';
$quotescollection_db_version = '1.4'; 


require_once('quotes-collection-ajax.php');
require_once('quotes-collection-widget.php');
require_once('quotes-collection-admin.php');
require_once('quotes-collection-shortcodes.php');

function quotescollection_get_randomquote($exclude = 0)
{
	if($exclude) $condition = "quote_id <> ".$exclude;
	else $condition = "";
	return quotescollection_get_quote($condition);
}

function quotescollection_get_quotes($condition = "")
{
	global $wpdb;
	$sql = "SELECT quote_id, quote, author, source, tags, public
		FROM " . $wpdb->prefix . "quotescollection"
		. $condition;
	
	if($quotes = $wpdb->get_results($sql, ARRAY_A))
		return $quotes;	
	else
		return array();

}

function quotescollection_get_quote($condition = '', $random = 1, $current = 0)
{
	global $wpdb;
	$sql = "SELECT quote_id, quote, author, source
		FROM " . $wpdb->prefix . "quotescollection";
	if ($condition)
		$sql .= $condition;
	if(!$random) {
		if($current)
			$sql .= " AND quote_id < {$current}";
		$sql .= " ORDER BY quote_id DESC";
	}
	else
		$sql .= " ORDER BY RAND(UNIX_TIMESTAMP(NOW()))";
	$sql .= " LIMIT 1";
	$random_quote = $wpdb->get_row($sql, ARRAY_A);
	if ( empty($random_quote) ) {
		if(!$random && $current)
			return quotescollection_get_quote($condition, 0, 0);
		else
			return 0;
	}
	else
		return $random_quote;
}


function quotescollection_count($condition = "")
{
	global $wpdb;
	$sql = "SELECT COUNT(*) FROM " . $wpdb->prefix . "quotescollection ".$condition;
	$count = $wpdb->get_var($sql);
	return $count;
}

function quotescollection_pagenav($total, $current = 1, $format = 0, $paged = 'paged', $url = "")
{
	if($total == 1 && $current == 1) return "";
	
	if(!$url) {
		$url = 'http';
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$url .= "s";}
		$url .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
		} else {
			$url .= $_SERVER["SERVER_NAME"];
		}

		if ( get_option('permalink_structure') != '' ) {
			if($_SERVER['REQUEST_URI']) {
				$request_uri = explode('?', $_SERVER['REQUEST_URI']);
				$url .= $request_uri[0];
			}
			else $url .= "/";
		}
		else {
			$url .= $_SERVER["PHP_SELF"];
		}
		
		if($query_string = $_SERVER['QUERY_STRING']) {
			$parms = explode('&', $query_string);
			$y = '';
			foreach($parms as $parm) {
				$x = explode('=', $parm);
				if($x[0] == $paged) {
					$query_string = str_replace($y.$parm, '', $query_string);
				}
				else $y = '&';
			}
			if($query_string) {
				$url .= '?'.$query_string;
				$a = '&';
			}
			else $a = '?';	
		}
		else $a = '?';
	}
	else {
		$a = '?';
		if(strpos($url, '?')) $a = '&';	
	}
	
	if(!$format || $format > 2 || $format < 0 || !is_numeric($format)) {	
		if($total <= 8) $format = 1;
		else $format = 2;
	}
	
	
	if($current > $total) $current = $total;
		$pagenav = "";

	if($format == 2) {
		$first_disabled = $prev_disabled = $next_disabled = $last_disabled = '';
		if($current == 1)
			$first_disabled = $prev_disabled = ' disabled';
		if($current == $total)
			$next_disabled = $last_disabled = ' disabled';

		$pagenav .= "<a class=\"first-page{$first_disabled}\" title=\"".__('Go to the first page', 'quotes-collection')."\" href=\"{$url}\">&laquo;</a>&nbsp;&nbsp;";

		$pagenav .= "<a class=\"prev-page{$prev_disabled}\" title=\"".__('Go to the previous page', 'quotes-collection')."\" href=\"{$url}{$a}{$paged}=".($current - 1)."\">&#139;</a>&nbsp;&nbsp;";

		$pagenav .= '<span class="paging-input">'.$current.' of <span class="total-pages">'.$total.'</span></span>';

		$pagenav .= "&nbsp;&nbsp;<a class=\"next-page{$next_disabled}\" title=\"".__('Go to the next page', 'quotes-collection')."\" href=\"{$url}{$a}{$paged}=".($current + 1)."\">&#155;</a>";

		$pagenav .= "&nbsp;&nbsp;<a class=\"last-page{$last_disabled}\" title=\"".__('Go to the last page', 'quotes-collection')."\" href=\"{$url}{$a}{$paged}={$total}\">&raquo;</a>";
	
	}
	else {
		$pagenav = __("Goto page:", 'quotes-collection');
		for( $i = 1; $i <= $total; $i++ ) {
			if($i == $current)
				$pagenav .= "&nbsp;<strong>{$i}</strong>";
			else if($i == 1)
				$pagenav .= "&nbsp;<a href=\"{$url}\">{$i}</a>";
			else 
				$pagenav .= "&nbsp;<a href=\"{$url}{$a}{$paged}={$i}\">{$i}</a>";
		}
	}
	return $pagenav;
}

function quotescollection_txtfmt($quotedata = array())
{
	if(!$quotedata)
		return;

	foreach($quotedata as $key => $value){
		$value = make_clickable($value); 
		$value = wptexturize(str_replace(array("\r\n", "\r", "\n"), '', nl2br(trim($value))));
		$quotedata[$key] = $value;
	}
	
	return $quotedata;	
}

function quotescollection_output_format( $quote_data, $options = array('show_author' => 1, 'show_source' => 1) )
{
	$display = "";

	$quote_data = quotescollection_txtfmt($quote_data);

	$display .= "<p>".$quote_data['quote']."</p>";
	
	$cite = "";

	if($options['show_author'] && $quote_data['author'])
		$cite = '<span class="quotescollection_author">'. $quote_data['author'] .'</span>';

	if($options['show_source'] && $quote_data['source']) {
		if($cite) $cite .= ", ";
		$cite .= '<span class="quotescollection_source">'. $quote_data['source'] .'</span>';
	}

	if($cite) $cite = " {$cite}";
	$display .= $cite;

	return apply_filters( 'quotescollection_output_format', $display );
}


function quotescollection_display_randomquote($show_author = 1, $show_source = 1, $ajax_refresh = 1, $random_quote = array()) 
{
	$args = "show_author={$show_author}&show_source={$show_source}&ajax_refresh={$ajax_refresh}&char_limit={$char_limit}&echo=1";
	return quotescollection_quote($args);
}


function quotescollection_quote($args = '') 
{
	global $quotescollection_instances, $quotescollection_next_quote;
	if(!$quotescollection_next_quote) $quotescollection_next_quote = __('Next quote', 'quotes-collection')."&nbsp;&raquo;";
	if(!($instance = $quotescollection_instances))
		$instance = $quotescollection_instances = 0;
	
		$key_value = explode('&', $args);
	$options = array();
	foreach($key_value as $value) {
		$x = explode('=', $value);
		$options[$x[0]] = $x[1]; // $options['key'] = 'value';
	}
	
	$options_default = array(
		'show_author' => 1,
		'show_source' => 1,
		'ajax_refresh' => 1,
		'auto_refresh' => 0,
		'tags' => '',
		'char_limit' => 500,
		'echo' => 1,
		'random' => 1,
		'exclude' => '',
		'current' => 0
	);
	
	$options = array_merge($options_default, $options);
	
	$condition = " WHERE public = 'yes'";
	
	if($options['random'])
		$current = 0;
	else $current = $options['current'];
	
	if($options['char_limit'] && is_numeric($options['char_limit']))
		$condition .= " AND CHAR_LENGTH(quote) <= ".$options['char_limit'];
	
	else $options['char_limit'] = 0;
	
	if($options['exclude'])
		$condition .=" AND quote_id <> ".$options['exclude'];
		
	if($options['tags']) {
		$taglist = explode(',', $options['tags']);
		$tag_condition = "";
		foreach($taglist as $tag) {
			$tag = mysql_real_escape_string(strip_tags(trim($tag)));
			if($tag_condition) $tag_condition .= " OR ";
			$tag_condition .= "tags = '{$tag}' OR tags LIKE '{$tag},%' OR tags LIKE '%,{$tag},%' OR tags LIKE '%,{$tag}'";
		}
		$condition .= " AND ({$tag_condition})";
	}
	$random_quote = quotescollection_get_quote($condition, $options['random'], $current);

	if(!$random_quote)
		return;
	
	$random_quote  = quotescollection_txtfmt($random_quote);
				
	$display = quotescollection_output_format($random_quote, $options);
	
	// We don't want to display the 'next quote' link if there is no more than 1 quote
	$quotes_count = quotescollection_count($condition); 
	
	if($options['ajax_refresh'] == 1 && $quotes_count > 1) {
		if($options['auto_refresh'])
			$display .= "<script type=\"text/javascript\">quotescollection_timer(".$instance.", ".$random_quote["quote_id"].", ". $options['show_author'] .", ".$options['show_source'].", '".$options['tags']."', ".$options['char_limit'].", ".$options['auto_refresh'].", ".$options['random'].");</script>";
		else {		
			$display .= "<script type=\"text/javascript\">\n<!--\ndocument.write(\"";
			$display .= '<p class=\"quotescollection_nextquote\" id=\"quotescollection_nextquote-'.$instance.'\"><a class=\"quotescollection_refresh\" style=\"cursor:pointer\" onclick=\"quotescollection_refresh('.$instance.', '.$random_quote["quote_id"].', '. $options['show_author'] .', '.$options['show_source'].', \''.$options['tags'].'\', '.$options['char_limit'].', 0, '.$options['random'].');\">'.$quotescollection_next_quote.'<\/a><\/p>';
			$display .= "\")\n//-->\n</script>\n";
		}
	}
	else if ($options['ajax_refresh'] == 2 && $quotes_count) {
		if($options['auto_refresh'])
			$display .= "<script type=\"text/javascript\">quotescollection_timer(".$instance.", ".$random_quote["quote_id"].", ". $options['show_author'] .", ".$options['show_source'].", '".$options['tags']."', ".$options['char_limit'].", ".$options['auto_refresh'].", ".$options['random'].");</script>";
		else
			$display .= "<p class=\"quotescollection_nextquote\" id=\"quotescollection_nextquote-".$_REQUEST['refresh']."\"><a class=\"quotescollection_refresh\" style=\"cursor:pointer\" onclick=\"quotescollection_refresh(".$_REQUEST['refresh'].", ".$random_quote['quote_id'].', '. $options['show_author'] .', '.$options['show_source'].', \''.$options['tags'].'\', '.$options['char_limit'].", 0, ".$options['random'].");\">".$quotescollection_next_quote."</a></p>";
		return $display;
	}
	$display = "<div id=\"quotescollection_randomquote-".$instance."\" class=\"quotescollection_randomquote\">{$display}</div>";
	$quotescollection_instances++;
	if($options['echo'])
		echo $display;
	else
		return $display;
}



function quotescollection_install()
{
	global $wpdb;
	$table_name = $wpdb->prefix . "quotescollection";

	if(!defined('DB_CHARSET') || !($db_charset = DB_CHARSET))
		$db_charset = 'utf8';
	$db_charset = "CHARACTER SET ".$db_charset;
	if(defined('DB_COLLATE') && $db_collate = DB_COLLATE) 
		$db_collate = "COLLATE ".$db_collate;


	// if table name already exists
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
   		$wpdb->query("ALTER TABLE `{$table_name}` {$db_charset} {$db_collate}");

   		$wpdb->query("ALTER TABLE `{$table_name}` MODIFY quote TEXT {$db_charset} {$db_collate}");

   		$wpdb->query("ALTER TABLE `{$table_name}` MODIFY author VARCHAR(255) {$db_charset} {$db_collate}");

   		$wpdb->query("ALTER TABLE `{$table_name}` MODIFY source VARCHAR(255) {$db_charset} {$db_collate}");

   		if(!($wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'tags'"))) {
   			$wpdb->query("ALTER TABLE `{$table_name}` ADD `tags` VARCHAR(255) {$db_charset} {$db_collate} AFTER `source`");
		}
   		if(!($wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'public'"))) {
   			$wpdb->query("ALTER TABLE `{$table_name}` CHANGE `visible` `public` enum('yes', 'no') DEFAULT 'yes' NOT NULL");
		}
	}
	else {
		//Creating the table ... fresh!
		$sql = "CREATE TABLE " . $table_name . " (
			quote_id mediumint(9) NOT NULL AUTO_INCREMENT,
			quote TEXT NOT NULL,
			author VARCHAR(255),
			source VARCHAR(255),
			tags VARCHAR(255),
			public enum('yes', 'no') DEFAULT 'yes' NOT NULL,
			time_added datetime NOT NULL,
			time_updated datetime,
			PRIMARY KEY  (quote_id)
		) {$db_charset} {$db_collate};";
		$results = $wpdb->query( $sql );
	}
	
	global $quotescollection_db_version;
	$options = get_option('quotescollection');
	$options['db_version'] = $quotescollection_db_version;
	update_option('quotescollection', $options);

}


function quotescollection_css_head()
{
	global $quotescollection_version;
	if ( !is_admin() ) {
		wp_register_style( 'quotescollection-style', plugins_url('quotes-collection.css', __FILE__), false, $quotescollection_version );
		wp_enqueue_style( 'quotescollection-style' );
	}
}
add_action( 'wp_enqueue_scripts', 'quotescollection_css_head' );


register_activation_hook( __FILE__, 'quotescollection_install' );
?>
