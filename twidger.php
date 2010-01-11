<?php
/* 
Plugin Name: Twidger
Plugin URI: http://mesparolessenvolent.com/twidger
Description: Display messages with associated usernames and avatars from a Twitter search through a widget.
Version: 0.3.1
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
2. Add display settings in the widget options (no avatars, enable links inside the tweet, etc.)

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
define('TWEDGER_CACHE_TIMEOUT', 120); // In seconds
define('TWEDGER_CACHE_FOLDER', '/tmp/'); // Don't forget the trailling slash (/)

function add_twidgerstyle() {
	echo "\r\t<link rel=\"stylesheet\" href=\"";
	bloginfo('siteurl');
	echo "/wp-content/plugins/twidger/twidger-style.css\" type=\"text/css\" media=\"screen\" />\r";
}

// Add function to widgets_init that'll load our widget.
add_action('widgets_init', 'twidger_load_widgets');

// Comment this line to remove the plugin's stylesheet.
add_action('wp_head', 'add_twidgerstyle');	

// Register our widget.
function twidger_load_widgets() {
	register_widget('twidger_Widget');
}

// twidger Widget class.
class twidger_Widget extends WP_Widget {

	// Widget setup.
	function twidger_Widget() {

		// Widget settings.
		$widget_ops = array('classname' => 'twidger', 'description' => __('Display a list of tweets based on a keyword search', 'twidger'));

		// Widget control settings.
		$control_ops = array('width' => 300, 'height' => 350, 'id_base' => 'twidger-widget');

		// Create the widget.
		$this->WP_Widget('twidger-widget', __('Twidger', 'twidger'), $widget_ops, $control_ops);
		
	}

	// How to display the widget on the screen.
	function widget($args, $instance) {
	
		extract($args);

		// Our variables from the widget settings.
		$title = apply_filters('widget_title', $instance['title']);
		$intro = $instance['intro'];
		$term = $instance['term'];
        $cache = $instance['cache'];

		// Before widget (defined by themes).
		echo "\r".$before_widget;

		// Display the widget title if one was input (before and after defined by themes).
		if ($title)
			echo "\r\t".$before_title.$title.$after_title;

		// Display introduction text from widget settings if one was input.
		if ($intro)
			echo "\r\t<p>".$intro."</p>";
		if($cache){
            include('libs/cache/Lite.php');
            $cache_id = $widget_id;

            $options = array(
                'cacheDir' => TWEDGER_CACHE_FOLDER,
                'lifeTime' => TWEDGER_CACHE_TIMEOUT
            );

            $Cache_Lite = new Cache_Lite($options);

            if ($data = $Cache_Lite->get($id)) {
                // Cache hit
                echo $data;
            }else{
                $data = $this->get_query_html($term);
                $Cache_Lite->save($data);
                echo $data;
            }
            
        }else{
            // No caching we run the query every time
            echo $this->get_query_html($term);
        }	
        
        

		// After widget (defined by themes).
		echo $after_widget;
	}

	// Update the widget settings.
	function update($new_instance, $old_instance) {
		$instance = $new_instance;

		// Strip tags for title and name to remove HTML (important for text inputs).
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

	// Displays the widget settings controls on the widget panel.
	function form($instance) {

		// Set up some default widget settings.
		$defaults = array('title' => __('Twitter', 'twidger'), 'intro' => __('Follow @laurent on Twitter...', 'twidger'), 'term' => '@laurent ', 'cache' => 0);
		$instance = wp_parse_args((array) $instance, $defaults); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'twidger'); ?></label>
			<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width: 100%;" />
		</p>

		<!-- Introduction: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id('intro'); ?>"><?php _e('Intro:', 'twidger'); ?></label>
			<input id="<?php echo $this->get_field_id('intro'); ?>" name="<?php echo $this->get_field_name('intro'); ?>" value="<?php echo $instance['intro']; ?>" style="width: 100%;" />
		</p>

		<!-- Search query: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id('term'); ?>"><?php _e('Query:', 'twidger'); ?></label>
			<input id="<?php echo $this->get_field_id('term'); ?>" name="<?php echo $this->get_field_name('term'); ?>" value="<?php echo $instance['term']; ?>" style="width: 100%;" />
		</p>

        <!-- Enable cache: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id('cache'); ?>"><?php _e('Enable cache:', 'twidger'); ?></label>
			<input id="<?php echo $this->get_field_id('cache'); ?>" name="<?php echo $this->get_field_name('cache'); ?>" <?php checked( $instance['cache'], true ); ?> type="checkbox" />
		</p>

    <?php
	}

    function get_query_html($query){
        // Display the search results.
		$return = "\r\t<ul>";
		require_once('twidger-search.php');

		$search = new TwitterSearch($query);
		$results = $search->results();
		foreach($results as $result) {
			$return .= "\r\t\t<li>\r\t\t\t<a href=\"http://www.twitter.com/".$result->from_user."/status/".$result->id."\" class=\"tweet\">";
			$return .= "\r\t\t\t\t<img src=\"".$result->profile_image_url."\" class=\"twitter_image\" alt=\"".$result->from_user."'s avatar\" />";
			$return .= "\r\t\t\t\t<strong>".$result->from_user."&nbsp;: </strong>";
			$text_n = toLink($result->text);
			$return .= $text_n;
			$return .= "\r\t\t\t</a>\r\t\t</li>";
		}
		
		$return .= "\r\t</ul>";

        return $return; 
    }
}
