<?php
/* 
Plugin Name: Twidger
Plugin URI: http://mesparolessenvolent.com/twidger
Description: Display messages with associated usernames and avatars from a Twitter search through a widget.
Version: 0.4.0
Author: Laurent LaSalle
Author URI: http://laurentlasalle.com
License: GPL

ABOUT TWIDGER
This plugin reuses code from Antonio "Woork" Lupetti, Ryan Faerman and David Billingham :
http://woork.blogspot.com/2009/06/simple-php-twitter-search-ready-to-use.html
http://ryanfaerman.com/twittersearch/
http://davidbillingham.name

This plugin requires cURL to be running on the server. I am NOT a programmer, if you 
want to fix things, suit yourself. Special thanks to Marc Boivin.

THINGS TO DO (please help)
1. Add cache functionality — DONE by Marc Boivin (0.3.0)
2. Add display settings in the widget options (no avatars, enable links inside the tweet, 
   etc.) — DONE by Laurent LaSalle (0.4.0)

LICENSE
This program is free software; you can redistribute it and/or modify it under the terms 
of the GNU General Public License as published by the Free Software Foundation; either 
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this 
program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, 
Fifth Floor, Boston, MA  02110-1301, USA.

http://www.gnu.org/licenses/gpl.html
*/


// Define some constants
define('TWIDGER_CACHE_TIMEOUT', 120); // In seconds
define('TWIDGER_CACHE_FOLDER', '/tmp/'); // Don't forget the trailling slash (/)


// Add function to widgets_init that'll load our widget.
add_action('widgets_init', 'twidger_load_widgets');


// Register our widget.
function twidger_load_widgets() {
	register_widget('twidger_Widget');
}


// Twidger Widget class.
class Twidger_Widget extends WP_Widget {

	// Widget setup.
	function Twidger_Widget() {

		// Widget settings.
		$widget_ops = array('classname' => 'twidger', 'description' => __('Display a list of tweets based on a keyword search', 'twidger'));

		// Widget control settings.
		$control_ops = array('width' => 226, 'height' => 350, 'id_base' => 'twidger-widget');

		// Create the widget.
		$this->WP_Widget('twidger-widget', __('Twidger', 'twidger'), $widget_ops, $control_ops);
		
	}

	// How to display the widget on the screen.
	function widget($args, $instance) {
		extract($args);
		
		// Our variables from the widget settings.
		$title = apply_filters('widget_title', $instance['title']);
		$msg = $instance['msg'];
		$msglocation = $instance['msglocation'];
		$term = $instance['term'];
		$stylesheet = $instance['stylesheet'];
		$avatars = $instance['avatars'];
		$global = $instance['global'];
		$cache = $instance['cache'];
		$number = $instance['number'];
		
		// Load the default stylesheet when necessary.
		if ($stylesheet)
			add_action('wp_head', 'add_twidgerstyle'); 

		// Before widget (defined by themes).
		echo "\r".$before_widget;

		// Display the widget title if one was input (before and after defined by themes).
		if ($title)
			echo "\r\t".$before_title.$title.$after_title;

		// Display message text from widget settings if one was input.
		if ($msg && ($msglocation == 'intro'))
			echo "\r\t<p>".$msg."</p>";

		if ($cache) {
			include('libs/cache/Lite.php');
			$cache_id = $widget_id;

			$options = array(
				'cacheDir' => TWIDGER_CACHE_FOLDER,
				'lifeTime' => TWIDGER_CACHE_TIMEOUT
			);

			$Cache_Lite = new Cache_Lite($options);

			if ($data = $Cache_Lite->get($cache_id)) {
				echo $data; // Cache hit
			} else {
				$data = $this->get_query_html($term, $instance['number'], $instance['avatars'], $instance['global']);
				$Cache_Lite->save($data);
				echo $data;
			}
			
		} else {
			echo $this->get_query_html($term, $instance['number'], $instance['avatars'], $instance['global']); // No cache means we run the query every time.
		}
		
		if ($msg && ($msglocation == 'outro'))
			echo "\r\t<p>".$msg."</p>";
		
		// After widget (defined by themes).
		echo $after_widget;
	}

	// Update the widget settings.
	function update($new_instance, $old_instance) {
		$instance = $new_instance;
		$instance['title'] = strip_tags($new_instance['title']); // Strip tags for title and name to remove HTML.
		return $instance;
	}

	// Displays the widget settings controls on the widget panel.
	function form($instance) {

		// Set up some default widget settings.
		$defaults = array('title' => __('Twitter', 'twidger'), 'msg' => __('Follow @laurentlasalle on Twitter...', 'twidger'), 'msglocation' => 'intro', 'term' => 'laurentlasalle', 'stylesheet' => 1, 'avatars' => 1, 'global' => 1, 'cache' => 0, 'number' => '10');
		$instance = wp_parse_args((array) $instance, $defaults); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'twidger'); ?></label>
			<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" class="widefat" type="text" />
		</p>

		<!-- Message: Textarea -->
		<p>
			<label for="<?php echo $this->get_field_id('msg'); ?>"><?php _e('Message:', 'twidger'); ?></label>
			<textarea id="<?php echo $this->get_field_id('msg'); ?>" name="<?php echo $this->get_field_name('msg'); ?>" class="widefat"><?php echo $instance['msg']; ?></textarea>
			<input type="radio" name="<?php echo $this->get_field_name('msglocation'); ?>" value="intro" <?php checked($instance['msglocation'], 'intro' ); ?>> Intro &emsp;
			<input type="radio" name="<?php echo $this->get_field_name('msglocation'); ?>" value="outro" <?php checked($instance['msglocation'], 'outro' ); ?>> Outro<br>
		</p>

		<!-- Search query: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id('term'); ?>"><?php _e('Search query:', 'twidger'); ?></label>
			<input id="<?php echo $this->get_field_id('term'); ?>" name="<?php echo $this->get_field_name('term'); ?>" value="<?php echo $instance['term']; ?>" class="widefat" type="text" />
		</p>

		<!-- Enable default stylesheet: Checkbox Input -->
		<p>
			<input id="<?php echo $this->get_field_id('stylesheet'); ?>" name="<?php echo $this->get_field_name('stylesheet'); ?>" <?php checked($instance['stylesheet'], 'on' ); ?> type="checkbox" />
			<label for="<?php echo $this->get_field_id('stylesheet'); ?>"><?php _e('Load default stylesheet', 'twidger'); ?></label><br />
			
		<!-- Enable avatars: Checkbox Input -->
			<input id="<?php echo $this->get_field_id('avatars'); ?>" name="<?php echo $this->get_field_name('avatars'); ?>" <?php checked($instance['avatars'], 'on' ); ?> type="checkbox" />
			<label for="<?php echo $this->get_field_id('avatars'); ?>"><?php _e('Display avatars', 'twidger'); ?></label><br />

		<!-- Global link: Checkbox Input -->
			<input id="<?php echo $this->get_field_id('global'); ?>" name="<?php echo $this->get_field_name('global'); ?>" <?php checked($instance['global'], 'on' ); ?> type="checkbox" />
			<label for="<?php echo $this->get_field_id('global'); ?>"><?php _e('The whole tweet as one global link', 'twidger'); ?></label><br />

		<!-- Enable cache: Checkbox Input -->
			<input id="<?php echo $this->get_field_id('cache'); ?>" name="<?php echo $this->get_field_name('cache'); ?>" <?php checked($instance['cache'], 'on' ); ?> type="checkbox" />
			<label for="<?php echo $this->get_field_id('cache'); ?>"><?php _e('Enable cache', 'twidger'); ?></label>
		</p>

		<!-- Maximum of tweets: Text Input -->	
		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Maximum of tweets:', 'twidger'); ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo $instance['number']; ?>" />
		</p>

	<?php
	}
	
	function add_twidgerstyle() {
		echo "\r\t<link rel=\"stylesheet\" href=\"".plugins_url('/twidger/twidger-style.css', __FILE__)."\" type=\"text/css\" media=\"screen\" />\r";
	}

	// Display the search results.
	function get_query_html($query, $max = false, $img = false, $glink = false) {
		$return = "\r\t<ul>";
		require_once('twidger-search.php');
		$search = new TwitterSearch($query);
		$results = $search->results();
		$i = 1;
		
		foreach($results as $result) {
			$return .= "\r\t\t<li class=\"message-".$i."\">";
			if ($glink) 
				$return .= "\r\t\t\t<a href=\"http://twitter.com/".$result->from_user."/status/".$result->id."\" class=\"tweet\">";
			if ($img)
				$return .= "\r\t\t\t\t<img src=\"".$result->profile_image_url."\" class=\"twitter_image\" alt=\"".$result->from_user."'s avatar\" />";
			$return .= "\r\t\t\t\t<strong>".$result->from_user."&nbsp;: </strong>";
			$text_n = toLink($result->text);
			$return .= $text_n;
			if ($glink) 
				$return .= "\r\t\t\t</a>";
			$return .= "\r\t\t</li>";
			
			// Implement fixed number of tweets
			if ($i == $max)
				break;
			$i++;
		}
		$return .= "\r\t</ul>";
		return $return; 
	}
}
