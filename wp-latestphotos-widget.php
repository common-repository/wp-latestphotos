<?php
/*
 * This is a part of WP-LatestPhotos plugin
 * Description: WP-LatestPhotos Widget
 * Author: Andrew Mihaylov
 * $Id: wp-latestphotos-widget.php 551818 2012-06-01 14:26:51Z andddd $
 */

if(!function_exists('add_action')) die('Cheatin&#8217; uh?');

class WP_LatestPhotos_Widget extends WP_Widget
{
    var $defaults = array();

    function WP_LatestPhotos_Widget()
    {
        $this->defaults = array('title' => __('Latest Photos', WP_LATEST_PHOTOS_TEXTDOMAIN),
                                  'hide_title' => false,
                                  'limit' => 6, 
                                  'randomize' => false,
                                  'link' => 'thickbox');

        $widget_ops = array('classname' => 'widget_latestphotos', 'description' => __('Latest Photos widget', WP_LATEST_PHOTOS_TEXTDOMAIN) );
        parent::WP_Widget('lastphotos', __('Latest Photos', WP_LATEST_PHOTOS_TEXTDOMAIN), $widget_ops);
    }

    function widget($args, $instance)
    {
        global $wpLatestPhotos;

        extract($args);

        $instance = wp_parse_args((array)$instance, $this->defaults);
        $instance['link'] .= ',' . $instance['image_overlay'];

        $title = apply_filters('widget_title', empty($instance['title']) ? __('Latest Photos', WP_LATEST_PHOTOS_TEXTDOMAIN) : $instance['title']);
        echo $before_widget;

        if ( $title && !$instance['hide_title'] )
            echo $before_title . $title . $after_title;

        echo $wpLatestPhotos->display($instance);

        echo $after_widget;
    }

    function update($new_instance, $old_instance)
    {
        $new_instance['limit'] = abs((int)$new_instance['limit']);
        return $new_instance;
    }

    function form($instance)
    {
        global $wp_mysurveys;

        $link_types = array('none' => 'None',
                            'full' => 'Link to fullsize image',
                            'post_parent' => 'Link to post page the image is attached to',
                            'attachment' => 'Link to attachment page');

        $overlays = array('none' => 'No overlay',
                          'thickbox' => 'Thickbox',
                          'lightbox' => 'Lightbox',
                          'shadowbox' => 'Shadowbox',
                          'fancybox' => 'Fancybox');

        $instance = wp_parse_args((array)$instance, $this->defaults);

        $title = esc_attr($instance['title']);
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></label> <input class="widefat" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" value="<?php echo esc_attr($title); ?>" type="text" /></p>

        <p>
            <input class="checkbox" name="<?php echo $this->get_field_name('hide_title'); ?>" id="<?php echo $this->get_field_id('hide_title'); ?>" value="1" type="checkbox" <?php checked($instance['hide_title'], true)?>/> <label for="<?php echo $this->get_field_id('hide_title'); ?>"><?php _e('Hide title', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Number of photos to show:', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></label> <input class="widefat" name="<?php echo $this->get_field_name('limit'); ?>" id="<?php echo $this->get_field_id('limit'); ?>" value="<?php echo (int)$instance['limit']; ?>" type="text" />
        </p>

        <p>
            <input class="checkbox" name="<?php echo $this->get_field_name('randomize'); ?>" id="<?php echo $this->get_field_id('randomize'); ?>" value="1" type="checkbox" <?php checked($instance['randomize'], true)?>/> <label for="<?php echo $this->get_field_id('randomize'); ?>"><?php _e('Randomize', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('link'); ?>"><?php _e('Link:<br/>', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></label>
            <select class="widefat" name="<?php echo $this->get_field_name('link'); ?>" id="<?php echo $this->get_field_id('link'); ?>">
            <?php foreach($link_types as $link => $description) : ?>
                <option value="<?php echo $link; ?>"<?php selected($instance['link'], $link); ?>><?php echo $description;  ?></option>
            <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('image_overlay'); ?>"><?php _e('Image overlay:', WP_LATEST_PHOTOS_TEXTDOMAIN); ?></label>
            <select class="widefat" name="<?php echo $this->get_field_name('image_overlay'); ?>" id="<?php echo $this->get_field_id('image_overlay'); ?>">
            <?php foreach($overlays as $script => $description) : ?>
                <option value="<?php echo $script; ?>"<?php selected($instance['image_overlay'], $script); ?>><?php echo $description; ?></option>
            <?php endforeach; ?>
            </select>
        </p>
        <?php
    }
    
}
