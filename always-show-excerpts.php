<?php
/*
Plugin Name: Always Show Excerpts
Version: 0.1
Plugin URI: http://drakard.com/
Description: Allows you to force themes to show excerpts on selected index pages. Select which types of archive pages you want this to happen for in Settings-><strong>Reading</strong>.
Author: Keith Drakard
Author URI: http://drakard.com/
*/

class AlwaysShowExcerptsPlugin {

	public function __construct() {
		load_plugin_textdomain('AlwaysShowExcerpts', false, plugin_dir_path(__FILE__).'/languages');

		$this->settings = get_option('AlwaysShowExcerptsPlugin', array(
			'author'	=> false,
			'category'	=> false,
			'cpt'		=> false,
			'date'		=> false,
			'home'		=> true,
			'search'	=> true,
			'tag'		=> false,
			'tax'		=> false,
		));

		// TODO: combine this afterthought with the $settings, probably when adding capability to select particular terms in taxonomies
		$this->nicenames = array(
			'author'	=> __('Author', 'AlwaysShowExcerpts'),
			'category'	=> __('Category', 'AlwaysShowExcerpts'),
			'cpt'		=> __('Custom Post Types (All)', 'AlwaysShowExcerpts'),
			'date'		=> __('Date Archives', 'AlwaysShowExcerpts'),
			'home'		=> __('Home Page', 'AlwaysShowExcerpts'),
			'search'	=> __('Search Results', 'AlwaysShowExcerpts'),
			'tag'		=> __('Post Tags', 'AlwaysShowExcerpts'),
			'tax'		=> __('Custom Taxonomies (All)', 'AlwaysShowExcerpts'),
		);
		asort($this->nicenames);

		if (is_admin()) {
			add_action('init', array($this, 'load_admin'));
		} else {
			add_filter('the_content', array($this, 'excerpt_not_content'), 99);
		}
	}


	public function activation_hook() {
		update_option('AlwaysShowExcerptsPlugin', $this->settings, false);
	}

	public function deactivation_hook() {
		delete_option('AlwaysShowExcerptsPlugin');
	}



	public function load_admin() {
		// in general, don't bother adding anything if we can't use it
		if (current_user_can('manage_options')) {
			add_action('admin_init', array($this, 'settings_init'));
		}
	}


	/******************************************************************************************************************************************************************/

	public function settings_init() {
		register_setting('reading', 'AlwaysShowExcerptsPlugin', array($this, 'validate_settings'));
		add_settings_section('AlwaysShowExcerptsSettings', '', array($this, 'settings_form'), 'reading');
	}



	public function settings_form() {
		$output = '<table class="form-table" role="presentation"><tbody>';

		$output.= '<tr>'
				. '<th scope="row">'.__('Always show an excerpt on these types of indexes', 'AlwaysShowExcerpts').'</th>'
				. '<td><fieldset><legend class="screen-reader-text"><span>'.__('Always show an excerpt on these types of indexes', 'AlwaysShowExcerpts').'</span></legend>';

		foreach ($this->nicenames as $type => $text) {
			$checked = (isset($this->settings[$type]) AND $this->settings[$type] != false) ? ' checked="checked"' : '';
			$field	= '<input type="checkbox" id="'.$type.'" name="AlwaysShowExcerptsPlugin['.$type.']" value="true" '.$checked.'>';

			$output.= '<label for="'.$type.'">'.$field.' '.$text.'</label><br>';
		}

		$output.= '<p class="description">'.__('Force themes to show just the Post Excerpt instead of the full post on these kinds of archive indexes.', 'AlwaysShowExcerpts').'</p>'
				. '</fieldset></td></tr>';

		$output.= '</tbody></table>';

		echo $output;
	}


	public function validate_settings($input) {
		if (! isset($input) OR ! isset($_POST['AlwaysShowExcerptsPlugin']) OR
			! is_array($input) OR ! is_array($_POST['AlwaysShowExcerptsPlugin'])
		) return false;

		// reset our settings to no options chosen
		$settings = array_fill_keys(array_keys($this->settings), false);

		foreach ($input as $type => $value) {
			if (isset($settings[$type])) {
				$settings[$type] = (bool) $value;
			}
		}

		return $settings;
	}




	/******************************************************************************************************************************************************************/

	public function excerpt_not_content($output) {
		$excerpt = false;

		foreach ($this->settings as $type => $active) {
			if ($active != false) {
				// TODO: use $active to potentially filter taxonomies etc
				switch ($type) {
					case 'author':		if (is_author()) $excerpt = true;
										break;
					case 'category':	if (is_category()) $excerpt = true;
										break;
					case 'cpt':			if (is_post_type_archive()) $excerpt = true;
										break;
					case 'date':		if (is_date()) $excerpt = true;
										break;
					case 'home':		if (is_home()) $excerpt = true;
										break;
					case 'search':		if (is_search()) $excerpt = true;
										break;
					case 'tag':			if (is_tag()) $excerpt = true;
										break;
					case 'tax':			if (is_tax()) $excerpt = true;
										break;
				}
				if ($excerpt) break; // don't bother continuing to test if we're already going to show the excerpt
			}
		}		

		if ($excerpt) {
			// http://wordpress.stackexchange.com/a/77947/25187
			remove_filter('the_content', array($this, 'excerpt_not_content'), 99);
			$output = apply_filters('the_excerpt', get_the_excerpt());
			add_filter('the_content', array($this, 'excerpt_not_content'), 99);
		}

		return $output;
	}

}


$AlwaysShowExcerpts = new AlwaysShowExcerptsPlugin();
register_activation_hook(__FILE__, array($AlwaysShowExcerpts, 'activation_hook'));
register_deactivation_hook(__FILE__, array($AlwaysShowExcerpts, 'deactivation_hook'));