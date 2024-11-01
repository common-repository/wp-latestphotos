<?php
/*
 * Plugin Name: WP-LatestPhotos
 * Author URI: http://www.codeispoetry.ru/
 * Description: WP-LatestPhotos is a WordPress plugin which extends your media library and gives the ability to highlight some of your latest photos.
 * Author: Andrej Mihajlov
 * Version: 1.0.4
 * $Id: wp-latestphotos.php 551852 2012-06-01 15:01:42Z andddd $
 * Tags: latest photos, fresh photos, photos, attachment, images
 */

/*
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


if(!function_exists('add_action')) die('Cheatin&#8217; uh?');

global $wpLatestPhotos;

include('wp-latestphotos-widget.php');

define('WP_LATEST_PHOTOS_TEMP_EXPIRE', 86400);
define('WP_LATEST_PHOTOS_URL', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)));
define('WP_LATEST_PHOTOS_TEXTDOMAIN', 'latest_photos');
define('WP_LATEST_PHOTOS_ATTACH_FIELD', 'latest_photos');
define('WP_LATEST_PHOTOS_PATH_META', 'latest_photos_image_path');
define('WP_LATEST_PHOTOS_SIZE_OPTION', 'latest_photos_size');
define('WP_LATEST_PHOTOS_DEFAULTCSS_OPTION', 'latest_photos_default_css');
define('WP_LATEST_PHOTOS_WPTHICKBOX_OPTION', 'latest_photos_wpthickbox');

class WP_LatestPhotos
{

	var $thumb_size = array(64, 64);
	var $page_hooks = array();
	var $gallery_counter = 0;

	function on_init()
	{
		$size = get_option(WP_LATEST_PHOTOS_SIZE_OPTION);

		if(is_array($size))
			$this->thumb_size = $size;

		$this->__expsvnprops('WP_LATEST_PHOTOS');

		add_filter('attachment_fields_to_edit', array(&$this, 'on_attachment_edit'), 11, 2);
		add_filter('attachment_fields_to_save', array(&$this, 'on_attachment_save'), 11, 2);
		add_action('admin_menu', array(&$this, 'on_admin_menu'));
		add_action('admin_post_wp-latestphotos-settings', array(&$this, 'on_admin_post_settings'));
		add_action('delete_attachment', array(&$this, 'on_attachment_delete'));
		add_action('template_redirect', array(&$this, 'on_template_redirect'));
		add_action('wp_ajax_rebuild_imagecache', array(&$this, 'on_ajax_rebuild_imagecache'));

		add_shortcode('WP-LatestPhotos', array(&$this, 'on_shortcode'));

		load_plugin_textdomain(WP_LATEST_PHOTOS_TEXTDOMAIN, PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . '/langs/', dirname(plugin_basename(__FILE__)) . '/langs/');
	}

	function on_widgets_init()
	{
		return register_widget('WP_LatestPhotos_Widget');
	}

	function on_template_redirect() {
		if(get_option(WP_LATEST_PHOTOS_DEFAULTCSS_OPTION)) {
			wp_enqueue_style('wp-latestphotos', WP_LATEST_PHOTOS_URL . '/default.css', array(), WP_LATEST_PHOTOS_REV);
		}

		if(get_option(WP_LATEST_PHOTOS_WPTHICKBOX_OPTION)) {
			wp_enqueue_style('thickbox');
			wp_enqueue_script('thickbox');
		}
	}

	function on_attachment_edit($form_fields, $post)
	{
		$wud = wp_upload_dir();
		$name = 'attachments[' . $post->ID . '][' . WP_LATEST_PHOTOS_ATTACH_FIELD . ']';

		// add custom field only if attachment is image
		if(wp_attachment_is_image($post->ID)) {
			$preview_html = '';
			$in_photos = get_post_meta($post->ID, WP_LATEST_PHOTOS_ATTACH_FIELD, true);
			$thumb_url = get_post_meta($post->ID, WP_LATEST_PHOTOS_PATH_META, true);

			if(!empty($thumb_url))
				$preview_html = '<img src="' . $wud['baseurl'] . '/' . $thumb_url . '" alt="" style="display: block; margin: 5px 0; padding: 3px; -moz-border-radius: 3px; -khtml-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; border: 1px solid #dfdfdf;" />';

			$form_fields[WP_LATEST_PHOTOS_ATTACH_FIELD] = array(
			  'label' => __('Show in latest photos', WP_LATEST_PHOTOS_TEXTDOMAIN),
			  'value' => '',
			  'input' => 'checkbox',
			  'checkbox' => '<input type="checkbox" id="' . $name . '" name="' . $name . '"' . checked($in_photos, true, false) . '/>' . $preview_html,
			  'helps' => __('This photo will appear in Latest Photos gallery if checked.', WP_LATEST_PHOTOS_TEXTDOMAIN)
			);
		}

		return $form_fields;
	}

	function on_attachment_save($post, $attachment)
	{
		if(wp_attachment_is_image($post['ID'])) {
			$wud = wp_upload_dir();

			if(isset($attachment[WP_LATEST_PHOTOS_ATTACH_FIELD])) {
				$thumb_file = $this->__crop_image($post['ID'], $this->thumb_size[0], $this->thumb_size[1]);

				//$thumb_file = image_resize($file, $this->thumb_size[0], $this->thumb_size[1], true);

				if(!is_wp_error($thumb_file)) {
					$thumb_file = str_replace($wud['basedir'] . '/', '', $thumb_file);
					update_post_meta($post['ID'], WP_LATEST_PHOTOS_PATH_META, $thumb_file);
				}

				update_post_meta($post['ID'], WP_LATEST_PHOTOS_ATTACH_FIELD, true);
			} else {
				$file = get_post_meta($post['ID'], WP_LATEST_PHOTOS_PATH_META, true);
				
				if(!empty($file)) {
					$file = $wud['basedir'] . '/' . $file;
					
					if(@file_exists($file))
						@unlink($file);
				}

				delete_post_meta($post['ID'], WP_LATEST_PHOTOS_ATTACH_FIELD);
				delete_post_meta($post['ID'], WP_LATEST_PHOTOS_PATH_META);
			}
		}

		return $post;
	}

	function on_attachment_delete($post_id)
	{
		$wud = wp_upload_dir();
		$path = get_post_meta($post_id, WP_LATEST_PHOTOS_PATH_META, true);

		if(!empty($path)) {
			$path = $wud['basedir'] . '/' . $path;

			if(@file_exists($path))
				@unlink($path);
		}
	}

	function on_shortcode($args = array()) {
		$args = wp_parse_args($args);
		$args['echo'] = 0;
		return $this->display($args);
	}

	function on_ajax_rebuild_imagecache() {
		global $wpdb;

		if ( !current_user_can('manage_options') )
			wp_die( __('Cheatin&#8217; uh?') );

		if(check_ajax_referer('wp_ajax_rebuild_imagecache')) {

			switch($_REQUEST['subaction']) {
				case 'count':
					$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(meta_id) FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=2", WP_LATEST_PHOTOS_ATTACH_FIELD));
					die("$count");
				break;

				case 'process':
					$wud = wp_upload_dir();

					$rows = $wpdb->get_results($wpdb->prepare(
							"SELECT t1.post_id AS post_id, t2.meta_value AS image_path
							FROM $wpdb->postmeta AS t1 
							LEFT JOIN $wpdb->postmeta AS t2 ON
							(t2.post_id=t1.post_id AND t2.meta_key='%s')
							WHERE (t1.meta_key='%s' AND t1.meta_value=2)
							LIMIT 10",
							WP_LATEST_PHOTOS_PATH_META,
							WP_LATEST_PHOTOS_ATTACH_FIELD));

					foreach($rows as $row) {
						$file = $wud['basedir'] . '/' . $row->image_path;

						if(@file_exists($file))
							@unlink($file);

						$file = $this->__crop_image($row->post_id, $this->thumb_size[0], $this->thumb_size[1]);
						$file = str_replace($wud['basedir'] . '/', '', $file);

						update_post_meta($row->post_id, WP_LATEST_PHOTOS_ATTACH_FIELD, 1);
						update_post_meta($row->post_id, WP_LATEST_PHOTOS_PATH_META, $file);
					}

					if(sizeof($rows) < 1) die(-4);
					$k = sizeof($rows);
					die("$k");

					die('-3');
				break;
			}

			die('-2');
		}

		die('-1');
	}
	
	function __expsvnprops($var_base)
	{
		$revId = '';

		if(preg_match('/\d+/', '$Rev: 551852 $', $m))
			$revId = array_pop($m);

		define($var_base . '_REV', $revId);
	}

	function __crop_image($id_or_file, $width, $height, $jpeg_quality = 90)
	{
		$file = '';

		if(is_numeric($id_or_file))
			$file = get_attached_file($id_or_file);
		else
			$file = $id_or_file;

		$image = wp_load_image($file);
		if(!is_resource($image))
			return new WP_Error('error_loading_image', $image);

		$size = @getimagesize($file);
		if(!$size)
			return new WP_Error('invalid_image', __('Could not read image size'), $file);
		list($orig_w, $orig_h, $orig_type) = $size;

		$newimage = imagecreatetruecolor($width, $height);

		imagealphablending($newimage, false);
		imagesavealpha($newimage, true);

		if(function_exists('imageantialias'))
			imageantialias($newimage, true);

		$aspect_ratio = $orig_w / $orig_h;
		$new_w = min($width, $orig_w);
		$new_h = min($height, $orig_h);

		if(!$new_w)
			$new_w = (int)($new_h * $aspect_ratio);

		if(!$new_h)
			$new_h = (int)($new_w / $aspect_ratio);

		$size_ratio = max($new_w / $orig_w, $new_h / $orig_h);

		$crop_w = round($new_w / $size_ratio);
		$crop_h = round($new_h / $size_ratio);

		$src_x = floor( ($orig_w - $crop_w) / 2 );
		$src_y = floor( ($orig_h - $crop_h) / 2 );

		$dst_x = 0;
		$dst_y = 0;

		if($orig_w < $width){
			$src_x = 0;
			$dst_x = ($width*0.5) - abs($orig_w * 0.5);
		}

		if($orig_h < $height){
			$src_y = 0;
			$dst_y = ($height*0.5) - abs($orig_h * 0.5);
		}

		$topleftpixel = imagecolorat($image, 0, 0);
		imagefill($newimage, 0, 0, $topleftpixel);

		imagecopyresampled($newimage, $image, $dst_x, $dst_y, $src_x, $src_y, $new_w, $new_h, $crop_w, $crop_h);

		// convert from full colors to index colors, like original PNG.
		if($orig_type == IMAGETYPE_PNG && !imageistruecolor($image))
			imagetruecolortopalette($newimage, false, imagecolorstotal($image));

		imagedestroy($image);

		// $suffix will be appended to the destination filename, just before the extension
		$suffix = "{$width}x{$height}";

		$info = pathinfo($file);
		$dir = $info['dirname'];
		$ext = $info['extension'];
		$name = basename($file, ".{$ext}");
		$destfilename = "{$dir}/{$name}-{$suffix}.{$ext}";

		if($orig_type == IMAGETYPE_GIF) {
			if (!imagegif($newimage, $destfilename))
				return new WP_Error('resize_path_invalid', __('Resize path invalid'));
		} else if($orig_type == IMAGETYPE_PNG) {
			if (!imagepng( $newimage, $destfilename ) )
				return new WP_Error('resize_path_invalid', __('Resize path invalid'));
		} else {
			// all other formats are converted to jpg
			$destfilename = "{$dir}/{$name}-{$suffix}.jpg";
			if(!imagejpeg($newimage, $destfilename, apply_filters( 'jpeg_quality', $jpeg_quality, 'image_resize')))
				return new WP_Error('resize_path_invalid', __('Resize path invalid'));
		}

		imagedestroy($newimage);

		// Set correct file permissions
		$stat = stat(dirname($destfilename));
		$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
		@chmod($destfilename, $perms);

		return $destfilename;
	}

	function display($args = array())
	{
		extract(shortcode_atts( array('limit' => 6,
									  'echo' => false,
									  'randomize' => false,
									  'before' => '',
									  'after' => '',
									  'id' => '',
									  'link' => 'thickbox'
									// post_parent, attachment, full, thickbox, shadowbox, fancybox, lightbox
								), $args ));

		$link_array = preg_split('#[,]#', preg_replace('#[^a-z,_]#i', '', strtolower($link)));
		$photos = $this->get_photos($limit, $randomize);
		$num_photos = sizeof($photos);
		$this->gallery_counter++;
		$out = '';

		for($i = 0; $i < $num_photos; $i++) {
			$p = $photos[$i];
			$a_before = '';
			$a_after = '';
			$li_class = array();
			$li_class_str = '';

			if($i == 0) $li_class[] = 'first';
			if($i == $num_photos-1) $li_class[] = 'last';

			if(!empty($li_class))
				$li_class_str = ' class="' . implode(' ', $li_class) . '"';

			for($j = 0; $j < sizeof($link_array); $j++) {
				switch($link_array[$j]) {
					case 'attachment':
						if($p->post_parent != 0) {
							$a_before = '<a href="' . get_permalink($p->ID) . '" title="' . esc_attr(trim(strip_tags($p->post_title))) . '">';
							$a_after = '</a>';
							break 2;
						}
					break;

					case 'post_parent':
						if($p->post_parent != 0) {
							$a_before =	 '<a href="' . get_permalink($p->post_parent) . '" title="' . esc_attr(trim(strip_tags($p->post_title))) . '">';
							$a_after = '</a>';
							break 2;
						}
					break;

					case 'full':
					case 'thickbox':
					case 'lightbox':
					case 'fancybox':
							$a_before =	 '<a class="' . $link_array[$j] . '" rel="' . $link_array[$j] . '_' . $this->gallery_counter . '" href="' . esc_url($p->guid) . '" title="' . esc_attr(trim(strip_tags($p->post_title))) . '">';
							$a_after = '</a>';
						break;

					case 'shadowbox':
							$a_before =	 '<a rel="shadowbox[' . $this->gallery_counter . ']" href="' . esc_url($p->guid) . '" title="' . esc_attr(trim(strip_tags($p->post_title))) . '">';
							$a_after = '</a>';
						break;
				}
			}

			if($i == 0) {
				if(!empty($id))
					$id = ' id="' . $id . '"';

				$out .= '<ul' . $id . ' class="wp-latestphotos">' . "\n";
			}

			$out .= '<li' . $li_class_str . '>' . $before . $a_before . '<img src="' . $p->photo_url . '" alt="' . esc_attr(trim(strip_tags($p->post_excerpt))) . '" class="attachment-' . $this->thumb_size[0] . '-' . $this->thumb_size[1] . '" width="' . $this->thumb_size[0] . '" height="' . $this->thumb_size[1] . '" />' . $a_after . $after . "</li>\n";
		
			if($i == $num_photos-1)
				$out .= "</ul>\n";
		}

		if($echo)
			echo $out;

		return $out;
	}

	function get_photos($limit, $randomize)
	{
		global $wpdb;

		$limit = absint((int)$limit);
		$attachments = array();

		$wud = wp_upload_dir();
		$options = array('post_type' => 'attachment',
						 'meta_key' => WP_LATEST_PHOTOS_ATTACH_FIELD);
		
		if($randomize) {
			$num_attachments = $wpdb->get_var("SELECT COUNT(t1.ID)
					FROM $wpdb->posts AS t1
					LEFT JOIN $wpdb->postmeta AS t2 ON
					t1.ID = t2.post_id 
					WHERE t2.meta_key='" . WP_LATEST_PHOTOS_ATTACH_FIELD . "' AND t2.meta_value=1");

			$options['post__in'] = array();
			$randoms = array();

			if($limit < 1) $limit = 1;
			if($limit > 1000) $limit = 1000;

			if($limit > $num_attachments)
				$limit = $num_attachments;

			// return if no attachments found
			if($num_attachments < 1)
				return $attachments;

			for($i = 0; $i < $limit; $i++) {
				$rnd = 0;
				
				do { 
					$rnd = rand(0, $num_attachments-1);
				} while(in_array($rnd, $randoms));

				$randoms[] = $rnd;
				$id = $wpdb->get_var("SELECT t1.ID
						FROM $wpdb->posts AS t1
						LEFT JOIN $wpdb->postmeta AS t2 ON
						t1.ID = t2.post_id
						WHERE t2.meta_key='" . WP_LATEST_PHOTOS_ATTACH_FIELD . "' AND t2.meta_value=1
						LIMIT $rnd, 1");

				if($id) {
					$options['post__in'][] = $id;
				}
			}
		}

		$options['numberposts'] = $limit;

		$attachments = get_posts($options);

		for($i = 0; $i < sizeof($attachments); $i++) {
			$src = get_post_meta($attachments[$i]->ID, WP_LATEST_PHOTOS_PATH_META, true);
			$attachments[$i]->photo_url = $wud['baseurl'] . '/' . $src;
		}

		return $attachments;
	}

	function on_activate() {
		add_option(WP_LATEST_PHOTOS_SIZE_OPTION, $this->thumb_size);
		add_option(WP_LATEST_PHOTOS_DEFAULTCSS_OPTION, 1);
		add_option(WP_LATEST_PHOTOS_WPTHICKBOX_OPTION, 1);
	}

	function on_deactivate() {
	}

	function on_admin_menu()
	{
		$this->page_hooks['settings'] = add_options_page(__('Latest Photos Settings', WP_LATEST_PHOTOS_TEXTDOMAIN), __('Latest Photos', WP_LATEST_PHOTOS_TEXTDOMAIN), 'manage_options', 'wp-latestphotos-settings', array($this, 'on_settings'));
		
		add_action('load-' . $this->page_hooks['settings'], array(&$this, 'on_settings_load'));
	}

	function on_settings_load()
	{
		global $wpdb;

		$save_cookie = '_wp_latestphotos_settings_saved';
		$need_rebuild = $wpdb->get_var($wpdb->prepare("SELECT COUNT(meta_id) FROM $wpdb->postmeta WHERE (meta_key=%s AND meta_value=2)", WP_LATEST_PHOTOS_ATTACH_FIELD));

		if(isset($_COOKIE[$save_cookie])) {
			setcookie($save_cookie, false, time()-WP_LATEST_PHOTOS_TEMP_EXPIRE);
		}

		if($need_rebuild) {
			wp_enqueue_script('wp-latestphotos-rebuildcache', WP_LATEST_PHOTOS_URL . '/rebuild-imagecache.js', array(), WP_LATEST_PHOTOS_REV);
			wp_localize_script('wp-latestphotos-rebuildcache',
				'wpLatestPhotosL10n',
				array(
					'cacheRebuilt' => __('Image cache successfully rebuilt.', WP_LATEST_PHOTOS_TEXTDOMAIN),
					'pleaseWait' => __('Please wait, rebuilding image cache.', WP_LATEST_PHOTOS_TEXTDOMAIN),
					'unknownError' => __('Unknown error:', WP_LATEST_PHOTOS_TEXTDOMAIN),
					'tryAgain' => __('Click F5 to continue process.', WP_LATEST_PHOTOS_TEXTDOMAIN),
					'pluginURL' => WP_LATEST_PHOTOS_URL
				)
			);
		}
	}

	function on_admin_post_settings()
	{
		global $wpdb;

		if ( !current_user_can('manage_options') )
			wp_die( __('Cheatin&#8217; uh?') );

		check_admin_referer('wp-latestphotos-settings');

		if(isset($_POST['thumb_size']) &&
		is_array($_POST['thumb_size']) &&
		sizeof($_POST['thumb_size']) == 2)
		{
			$size = $this->thumb_size;
			$x = (int)$_POST['thumb_size'][0];
			$y = (int)$_POST['thumb_size'][1];

			if($x < 1) $x = 1;
			else if($x > 1024) $x = 1024;
			
			if($y < 1) $y = 1;
			else if($y > 1024) $y = 1024;

			if($x != $size[0] || $y != $size[1])
				$wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value=2 WHERE (meta_key=%s AND meta_value=1)", WP_LATEST_PHOTOS_ATTACH_FIELD));

			$size[0] = $x;
			$size[1] = $y;

			update_option(WP_LATEST_PHOTOS_SIZE_OPTION, $size);
		}

		if(isset($_POST['use_default_css'])) {
			update_option(WP_LATEST_PHOTOS_DEFAULTCSS_OPTION, 1);
		} else {
			delete_option(WP_LATEST_PHOTOS_DEFAULTCSS_OPTION);
		}

		if(isset($_POST['use_wpthickbox'])) {
			update_option(WP_LATEST_PHOTOS_WPTHICKBOX_OPTION, 1);
		} else {
			delete_option(WP_LATEST_PHOTOS_WPTHICKBOX_OPTION);
		}

		setcookie('_wp_latestphotos_settings_saved', true);

		wp_redirect($_POST['_wp_http_referer']);
	}

	function on_settings()
	{
?>

<?php if(isset($_COOKIE['_wp_latestphotos_settings_saved'])) : ?>
	<div class="updated fade"><p><strong><?php _e('Settings saved.', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></strong></p></div>
<?php endif; ?>
		
	<form id="wp_latestphotos_ajax"><?php wp_nonce_field('wp_ajax_rebuild_imagecache'); ?></form>

	<div class="wrap">
		<h2><?php _e('Latest Photos Settings', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></h2>

		<form method="post" action="admin-post.php">
			<?php wp_nonce_field('wp-latestphotos-settings'); ?>
			<input type="hidden" name="action" value="wp-latestphotos-settings" />

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Thumb size', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e('Thumb size', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></span></legend>
							<input name="thumb_size[0]" type="text" id="thumb_size[0]" value="<?php echo (int)$this->thumb_size[0]; ?>" class="small-text" /> x <input name="thumb_size[1]" type="text" id="thumb_size[1]" value="<?php echo (int)$this->thumb_size[1]; ?>" class="small-text" />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e('Use default CSS', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e('Use default CSS', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></span></legend>
							<input name="use_default_css" type="checkbox" id="use_default_css" <?php checked((int)get_option(WP_LATEST_PHOTOS_DEFAULTCSS_OPTION), 1); ?> />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e('Built-in Thickbox support', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e('Built-in Thickbox support', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></span></legend>
							<input name="use_wpthickbox" type="checkbox" id="use_wpthickbox" <?php checked((int)get_option(WP_LATEST_PHOTOS_WPTHICKBOX_OPTION), 1); ?> />
						</fieldset>
						<p class="description">
							<?php _e("Thickbox is bundled with WordPress so you can use it without any additional installations, just mark this checkbox and save changes.<br/>
									 You also can use shadowbox, fancybox or lightbox but for that you need to install appropriate WordPress plugin.", WP_LATEST_PHOTOS_TEXTDOMAIN); ?>
						</p>
					</td>
				</tr>

			</table>


			<p class="submit">
				<input name="Submit" class="button-primary" value="<?php _e('Save Changes', WP_LATEST_PHOTOS_TEXTDOMAIN); ?>" type="submit" />
			</p>
		</form>
	</div>
<?php
	}

}

/*
 * Helper function for those who using php in templates
 */

function wp_latestphotos($args = array()) {
	global $wpLatestPhotos;
	
	return $wpLatestPhotos->display($args);
}

/*
 * Uninstall function should stay outside a class because of php or wp (i am not sure) bug
 */

function wp_latestphotos_uninstall() {
	global $wpdb;

	delete_option(WP_LATEST_PHOTOS_SIZE_OPTION);
	delete_option(WP_LATEST_PHOTOS_DEFAULTCSS_OPTION);
	delete_option(WP_LATEST_PHOTOS_WPTHICKBOX_OPTION);

	$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key IN(%s, %s)", WP_LATEST_PHOTOS_ATTACH_FIELD, WP_LATEST_PHOTOS_PATH_META));
}

/*
 * Initialization
 */

$wpLatestPhotos = new WP_LatestPhotos();

add_action('init', array(&$wpLatestPhotos, 'on_init'));
add_action('widgets_init', array(&$wpLatestPhotos, 'on_widgets_init'));

register_activation_hook( __FILE__, array(&$wpLatestPhotos, 'on_activate'));
register_deactivation_hook( __FILE__, array(&$wpLatestPhotos, 'on_deactivate'));
register_uninstall_hook(__FILE__, 'wp_latestphotos_uninstall');
