<?php
/*
 * Page Tags
 * Author: Denis de Bernardy <http://www.mesoconcepts.com>
 * Version: 2.0
 */


/**
 * page_tags
 *
 * @package Page Tags
 **/

add_action('admin_print_scripts', array('page_tags', 'scripts'));

class page_tags {
	/**
	 * scripts()
	 *
	 * @return void
	 **/

	function scripts() {
		$plugin_path = plugin_dir_url(__FILE__);
		
		wp_enqueue_script( 'page_tags', $plugin_path . 'js/page-tags.js', array('suggest', 'jquery-ui-tabs', 'wp-lists'), '20090520' );

		wp_localize_script( 'page_tags', 'page_tagsL10n', array(
			'tagsUsed' =>  __('Tags used on this page:', page_tags_textdomain),
			'addTag' => esc_attr(__('Add new tag', page_tags_textdomain)),
		));
	} # scripts()
	
	
	/**
	 * meta_boxes()
	 *
	 * @return void
	 **/
	
	function meta_boxes() {
		static $done = false;
		
		if ( $done )
			return;
		
		add_meta_box('tagsdiv', __('Tags', page_tags_textdomain), array('page_tags', 'display_page_tags'), 'page', 'normal');
		
		if ( class_exists('autotag') )
			add_meta_box('autotag', __('Autotag', page_tags_textdomain), array('autotag', 'entry_editor'), 'page', 'normal');
		
		$done = true;
	} # meta_boxes()
	
	
	/**
	 * display_page_tags()
	 *
	 * @param object $post
	 * @param array $box
	 * @return void
	 **/
	
	function display_page_tags($post, $box) {
		$tax_name = 'post_tag';
		$taxonomy = get_taxonomy($tax_name);
		$helps = isset($taxonomy->helps)
			? attribute_escape($taxonomy->helps)
			: __('Separate tags with commas.', page_tags_textdomain);
		?>
		<div class="tagsdiv" id="<?php echo $tax_name; ?>">
			<div class="jaxtag">
			<div class="nojs-tags hide-if-js">
			<p><?php _e('Add or remove tags'); ?></p>
			<textarea name="<?php echo "tax_input[$tax_name]"; ?>" class="the-tags" id="tax-input[<?php echo $tax_name; ?>]"><?php echo esc_attr(get_terms_to_edit( $post->ID, $tax_name )); ?></textarea></div>

			<span class="ajaxtag hide-if-no-js">
				<label class="screen-reader-text" for="new-tag-<?php echo $tax_name; ?>"><?php echo $box['title']; ?></label>
				<input type="text" id="new-tag-<?php echo $tax_name; ?>" name="newtag[<?php echo $tax_name; ?>]" class="newtag form-input-tip" size="16" autocomplete="off" value="<?php esc_attr_e('Add new tag', page_tags_textdomain); ?>" />
				<input type="button" class="button tagadd" value="<?php esc_attr_e('Add', page_tags_textdomain); ?>" tabindex="3" />
			</span></div>
			<p class="howto"><?php echo $helps; ?></p>
			<div class="tagchecklist"></div>
		</div>
		<p class="tagcloud-link hide-if-no-js"><a href="#titlediv" class="tagcloud-link" id="link-<?php echo $tax_name; ?>"><?php printf( __('Choose from the most used tags in %s', page_tags_textdomain), $box['title'] ); ?></a></p>
		<?php
	} # display_page_tags()
} # page_tags
?>