<?php
/*
Plugin Name: Multiple Penote Widgets
Description: Allows for multiple penote widgets to be displayed. Penote.com is a social web service that allows the subscriber to broadcast short messages or share notes to other subscribers of the service.
Version: 2.0
Author URI: http://patrick.bloggles.info/
Plugin URI: http://patrick.bloggles.info/2010/07/28/wordpress-penote-widgets/
License: GPLv2
Donate: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=mypatricks@hotmail.com&item_name=Donate%20to%20Patrick%20Chia&item_number=1242543308&amount=3.00&no_shipping=0&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8&return=http://patrick.bloggles.info/ 
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( !function_exists('wpcom_time_since') ) :
/*
 * Time since function taken from WordPress.com
 */

function wpcom_time_since( $original, $do_more = 0 ) {
        // array of time period chunks
        $chunks = array(
                array(60 * 60 * 24 * 365 , 'year'),
                array(60 * 60 * 24 * 30 , 'month'),
                array(60 * 60 * 24 * 7, 'week'),
                array(60 * 60 * 24 , 'day'),
                array(60 * 60 , 'hour'),
                array(60 , 'minute'),
        );

        $today = time();
        $since = $today - $original;

        for ($i = 0, $j = count($chunks); $i < $j; $i++) {
                $seconds = $chunks[$i][0];
                $name = $chunks[$i][1];

                if (($count = floor($since / $seconds)) != 0)
                        break;
        }

        $print = ($count == 1) ? '1 '.$name : "$count {$name}s";

        if ($i + 1 < $j) {
                $seconds2 = $chunks[$i + 1][0];
                $name2 = $chunks[$i + 1][1];

                // add second item if it's greater than 0
                if ( (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) && $do_more )
                        $print .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
        }
        return $print;
}
endif;

function penote_style(){
	echo '<style type="text/css">
.widget_penote{border:none;background-color:#FBF5E6;-moz-border-radius:5px;-khtml-border-radius:5px;-webkit-border-radius:5px;border-radius:5px;padding:1px;}
.p .notes{background-color:#fff;margin:1px;padding:6px 11px 6px 11px;border:none;}
.p .notes:hover{-moz-box-shadow:inset 0 0 8px #ccc;-webkit-box-shadow:inset 0 0 8px #ccc;box-shadow:inset 0 0 8px #ccc;}
.widget_penote .widget-title,.widget_penote .widgettitle{background-color:#FBF5E6;height:48px;padding:9px 12px 8px 12px;margin-bottom:0px;border:none;}
img.p{border:none;float:left;margin-right:10px;-moz-border-radius:5px;-khtml-border-radius:5px;-webkit-border-radius:5px;border-radius:5px;-webkit-box-shadow:2px 2px 4px rgba(0,0, 0, 0.3);-moz-box-shadow:2px 2px 4px rgba(0,0, 0, 0.3);box-shadow:2px 2px 4px rgba(0,0, 0, 0.3);}
img.p:hover{margin-right:6px;-moz-border-radius:40px;-khtml-border-radius:40px;-webkit-border-radius:40px;border-radius:40px;border:solid 2px #fff;}span.note {display:block;color:#ccc;}
span.c{font-size:12px;text-align:right;margin-top:5px;margin-right:11px;margin-bottom:6px;background-color:#FBF5E6;display:block;}
a.time{display:block;font-size:10px;text-align:right;text-decoration:none;}
</style>';
}

class Penote_Widget extends WP_Widget {

	function Penote_Widget() {
		$widget_ops = array('classname' => 'widget_penote', 'description' => __( 'Display your notes from Penote') );
		parent::WP_Widget('penote', __('Penote Share'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );

		$account = trim( urlencode( $instance['account'] ) );
		if ( empty($account) ) return;
		$items = absint( $instance['show'] );
		if ( $items > 10 )
			$items = 10;

		$url = "http://penote.com/". $account ."/feed/";
		include_once(ABSPATH . WPINC . '/rss.php');
		$note = fetch_feed( $url );

		if ( is_wp_error($note) ) {
			if ( is_admin() || current_user_can('manage_options') ) {
				echo '<!--';
				printf(__('<strong>RSS Error</strong>: %s'), $note->get_error_message());
				echo '-->';
			}
			return;
		}

		if ( !$note->get_item_quantity() ) {
			echo '<!--' . esc_html__('Error: Penote did not respond. Please wait a few minutes and refresh this page.') . '-->';
			$note->__destruct();
			unset($note);
			return;
		}

		if ( ! is_wp_error($note) )
			$title = $note->get_title();
			$pavatar = str_replace( '?s=96', '?s=40', $note->get_image_url() );
			echo "{$before_widget}{$before_title}<a title='" . esc_html($title) . "' href='" . esc_url( "http://penote.com/{$account}/" ) . "'><img title='" . esc_html($title) . "' class='p' src='". $pavatar."' alt='penote' height='40' width='40' /> " . esc_html($title) . "</a><span class='note'>". $title ."</span>{$after_title}";

		echo '<div class="p">' . "\n";

		if ( !isset($items) )
			$items = 10;

		foreach ( $note->get_items(0, $items) as $item ) {
			$link = esc_url( strip_tags( $item->get_link() ) );
			$title = $item->get_title();
			$description = $item->get_description();
			$description = make_clickable( esc_html( $description ) );
			$description = preg_replace_callback('/(^|[^0-9A-Z&\/]+)(#|\xef\xbc\x83)([0-9A-Z_]*[A-Z_]+[a-z0-9_\xc0-\xd6\xd8-\xf6\xf8\xff]*)/iu',  array($this, '_penote_hashtag'), $description);
			$description = preg_replace_callback('/([^a-zA-Z0-9_]|^)([@\xef\xbc\xa0]+)([a-zA-Z0-9_]{1,20})(\/[a-zA-Z][a-zA-Z0-9\x80-\xff-]{0,79})?/u', array($this, '_penote_username'), $description);

			$date = esc_html( strip_tags( $item->get_date() ) );
			$date = strtotime( $date );
			$date = gmdate( get_option( 'date_format' ), $date );
			$time = wpcom_time_since(strtotime($item->get_date()));

			echo "\t<div class='notes'>" . $description . " <a title='". $date ."' href='" . esc_url($link) . "' class='time'>" . $time . "&nbsp;ago</a></div>\n";
		}

		echo "<span class='c'><a title='Share and Says' href='http://penote.com/'>Share Your Notes</a></span><!--Powered by Patrick/Google+ Plus Wordpress Widgets-->";

		echo "</div>\n";
		$note->__destruct();
		unset($note);

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['account'] = trim( strip_tags( stripslashes( $new_instance['account'] ) ) );
		$instance['account'] = str_replace('http://penote.com/', '', $instance['account']);
		$instance['account'] = str_replace('/', '', $instance['account']);
		$instance['show'] = absint($new_instance['show']);

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array('account' => '', 'show' => 3) );

		$account = esc_attr($instance['account']);
		$show = absint($instance['show']);
		if ( $show < 1 || 10 < $show )
			$show = 5;

		echo '<p><label for="' . $this->get_field_id('account') . '">' . esc_html__('Username:') . ' 
		<input class="widefat" id="' . $this->get_field_id('account') . '" name="' . $this->get_field_name('account') . '" type="text" value="' . $account . '" />
		</label></p>
		<p><label for="' . $this->get_field_id('show') . '">' . esc_html__('Maximum number of updates to show:') . '
			<select id="' . $this->get_field_id('show') . '" name="' . $this->get_field_name('show') . '">';

		for ( $i = 1; $i <= 10; ++$i )
			echo "<option value='$i' " . ( $show == $i ? "selected='selected'" : '' ) . ">$i</option>";

		echo '		</select>
		</label></p>';

		echo '<p>Credit: <a href="http://penote.com/patrick/">Patrick Chia</a></p>';
	}

	/**
	 * Link a Twitter user mentioned in the tweet text to the user's page on Twitter.
	 *
	 * @param array $matches regex match
	 * @return string Tweet text with inserted @user link
	 */
	function _penote_username( $matches ) { // $matches has already been through wp_specialchars
		return "$matches[1]@<a href='" . esc_url( 'http://penote.com/' . urlencode( $matches[3] ) ) . "'>$matches[3]</a>";
	}

	/**
	 * Link a Twitter hashtag with a search results page on Penote.com
	 *
	 * @param array $matches regex match
	 * @return string Tweet text with inserted #hashtag link
	 */
	function _penote_hashtag( $matches ) { // $matches has already been through wp_specialchars
		return "$matches[1]<a href='" . esc_url( 'http://penote.com/?q=%23' . urlencode( $matches[3] ) ) . "'>#$matches[3]</a>";
	}

}

add_action( 'widgets_init', 'wickett_penote_widget_init' );
add_action( 'wp_head', 'penote_style' );

function wickett_penote_widget_init() {
	register_widget('Penote_Widget');
}