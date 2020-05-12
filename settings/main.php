<?php
/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */

/**
 * custom option and settings
 */
function staticprops_settings_init()
{
	// register a new setting for "staticprops" page
	register_setting('staticprops', 'staticprops_options');

	// register a new section in the "staticprops" page
	add_settings_section(
		'staticprops_section_developers',
		__('Main Settings.', 'staticprops'),
		'staticprops_section_developers_cb',
		'staticprops'
	);

	// register a new field in the "staticprops_section_developers" section, inside the "staticprops" page
	add_settings_field(
		'staticprops_field_pill', // as of WP 4.6 this value is used only internally
		// use $args' label_for to populate the id inside the callback
		__('Pill', 'staticprops'),
		'staticprops_field_pill_cb',
		'staticprops',
		'staticprops_section_developers',
		[
			'label_for' => 'staticprops_field_pill',
			'class' => 'staticprops_row',
			'staticprops_custom_data' => 'custom',
		]
	);

	// register a new field in the "staticprops_section_developers" section, inside the "staticprops" page
	add_settings_field(
		'staticprops_field_name', // as of WP 4.6 this value is used only internally
		// use $args' label_for to populate the id inside the callback
		__('Your name', 'staticprops'),
		'staticprops_field_name_cb',
		'staticprops',
		'staticprops_section_developers',
		[
			'label_for' => 'staticprops_field_name',
			'class' => 'staticprops_row',
			'staticprops_custom_data' => 'custom',
		]
	);
}

/**
 * register our staticprops_settings_init to the admin_init action hook
 */
add_action('admin_init', 'staticprops_settings_init');

/**
 * custom option and settings:
 * callback functions
 */

// developers section cb

// section callbacks can accept an $args parameter, which is an array.
// $args have the following keys defined: title, id, callback.
// the values are defined at the add_settings_section() function.
function staticprops_section_developers_cb($args)
{
	?>
 <p id="<?php echo esc_attr(
 	$args['id']
 ); ?>"><?php esc_html_e('Follow the white rabbit.', 'staticprops'); ?></p>
 <?php
}

// pill field cb

// field callbacks can accept an $args parameter, which is an array.
// $args is defined at the add_settings_field() function.
// wordpress has magic interaction with the following keys: label_for, class.
// the "label_for" key value is used for the "for" attribute of the <label>.
// the "class" key value is used for the "class" attribute of the <tr> containing the field.
// you can add custom key value pairs to be used inside your callbacks.
function staticprops_field_pill_cb($args)
{
	// get the value of the setting we've registered with register_setting()
	$options = get_option('staticprops_options');// output the field
	?>
 <select id="<?php echo esc_attr($args['label_for']); ?>"
 data-custom="<?php echo esc_attr($args['staticprops_custom_data']); ?>"
 name="staticprops_options[<?php echo esc_attr($args['label_for']); ?>]"
 >
 <option value="red" <?php echo isset($options[$args['label_for']])
 	? selected($options[$args['label_for']], 'red', false)
 	: ''; ?>>
 <?php esc_html_e('red pill', 'staticprops'); ?>
 </option>
 <option value="blue" <?php echo isset($options[$args['label_for']])
 	? selected($options[$args['label_for']], 'blue', false)
 	: ''; ?>>
 <?php esc_html_e('blue pill', 'staticprops'); ?>
 </option>
 </select>
 <p class="description">
 <?php esc_html_e(
 	'You take the blue pill and the story ends. You wake in your bed and you believe whatever you want to believe.',
 	'staticprops'
 ); ?>
 </p>
 <p class="description">
 <?php esc_html_e(
 	'You take the red pill and you stay in Wonderland and I show you how deep the rabbit-hole goes.',
 	'staticprops'
 ); ?>
 </p>
 <?php
}

function staticprops_field_name_cb($args)
{
	// get the value of the setting we've registered with register_setting()
	$options = get_option('staticprops_options');// output the field
	?>
  <input type='text' value="<?php echo $options[$args['label_for']]; ?>" 
  name="staticprops_options[<?php echo esc_attr($args['label_for']); ?>]"/>
    <p class="description">
      <?php esc_html_e(
      	'You take the blue pill and the story ends. You wake in your bed and you believe whatever you want to believe.',
      	'staticprops'
      ); ?>
    </p>
    <p class="description">
      <?php esc_html_e(
      	'You take the red pill and you stay in Wonderland and I show you how deep the rabbit-hole goes.',
      	'staticprops'
      ); ?>
    </p>
 <?php
}

/**
 * top level menu
 */
function staticprops_options_page()
{
	// add top level menu page
	add_menu_page(
		'Staticprops Settings',
		'Staticprops',
		'manage_options',
		'staticprops',
		'staticprops_options_page_html'
	);
}

/**
 * register our staticprops_options_page to the admin_menu action hook
 */
add_action('admin_menu', 'staticprops_options_page');

/**
 * top level menu:
 * callback functions
 */
function staticprops_options_page_html()
{
	// check user capabilities
	if (!current_user_can('manage_options')) {
		return;
	}

	// add error/update messages

	// check if the user have submitted the settings
	// wordpress will add the "settings-updated" $_GET parameter to the url
	if (isset($_GET['settings-updated'])) {
		// add settings saved message with the class of "updated"
		add_settings_error(
			'staticprops_messages',
			'staticprops_message',
			__('Settings Saved', 'staticprops'),
			'updated'
		);
	}

	// show error/update messages
	settings_errors('staticprops_messages');
	?>
 <div class="wrap">
 <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
 <form action="options.php" method="post">
 <?php
 // output security fields for the registered setting "staticprops"
 settings_fields('staticprops');
 // output setting sections and their fields
 // (sections are registered for "staticprops", each field is registered to a specific section)
 do_settings_sections('staticprops');
 // output save settings button
 submit_button('Save Settings');?>
 </form>
 </div>
 <?php
}
