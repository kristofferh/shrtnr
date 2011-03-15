<?php
/**
 * Half-baked Widget Class
 */
class ShrtnrWidget extends WP_Widget {
	/** constructor */
	function __construct() {
		parent::__construct(false, $name = 'Shrtnr Widget');	
	}

	/** @see WP_Widget::widget */
	function widget($args, $instance) {
		global $post;
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		echo $before_widget;
		if ($title) {
			echo $before_title . $title . $after_title;
		}
		if($post->ID) {
			if (class_exists("Shrtnr")){
				$shrtWidget = new Shrtnr();
				echo $shrtWidget->getShrtLink($post->ID);
			}
		}
 		echo $after_widget; 
	}

	/** @see WP_Widget::update */
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}

	/** @see WP_Widget::form */
	function form($instance) {
		$title = esc_attr($instance['title']);
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
	<?php 
	}

}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("ShrtnrWidget");'));
?>