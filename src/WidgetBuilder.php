<?php

namespace JazzMan\Widget;

use CMB2;
use CMB2_Field;
use CMB2_hookup;

/**
 * WordPress Widgets Helper Class.
 * https://github.com/Jazz-Man/wp-widgets-helper.
 *
 * @author JazzMan
 */
abstract class WidgetBuilder extends \WP_Widget
{
    /**
     * @var array
     */
    protected $_instance = [];
    /**
     * @var array
     */
    protected $fields;
    /**
     * @var array
     */
    protected $defaults;

    /**
     * CMB2_Widget constructor.
     *
     * @param       $class
     * @param       $title
     * @param array $widget_options
     * @param array $control_options
     */
    public function __construct(string $class, $title, $widget_options = [], $control_options = [])
    {
        $class = str_replace('\\', '-', $class);

        parent::__construct(// Base ID of widget
            $class, // Widget name will appear in UI
            $title, // Widget options
            array_merge([
                'classname' => $class,
                'customize_selective_refresh' => true,
                'description' => __('A CMB2 widget boilerplate description.', 'cmb2-widget'),
            ], $widget_options), // Control Options
            $control_options);

        if (null !== $this->fields) {
            $this->process_fields($this->fields);
        }

        add_filter('cmb2_show_on', [$this, 'show_on'], 10, 2);
        add_action('admin_init', [$this, 'admin_init']);
    }

    /**
     * @param array $fields
     */
    protected function process_fields(array $fields)
    {
        // Supporting either defining the fields in the typical CMB2 style
        // but also by having the keys as id's instead of as a field for
        // greater readability
        if ($this->is_assoc($fields)) {
            foreach ($fields as $id => $field) {
                $fields[$id]['id'] = $id;
            }
        } else {
            foreach ($fields as $key => $field) {
                unset($fields[$key]);
                $fields[$field['id']] = $field;
            }
        }

        foreach ($fields as $id => $field) {
            // Extract default value
            if (isset($field['default'])) {
                if (!isset($this->defaults[$id])) {
                    $this->defaults[$id] = $field['default'];
                }

                // Remove default from field definition (messes up widget)
                unset($fields[$id]['default']);
            }
        }

        // Build a keymap and set id_key
        foreach ($fields as $id => $field) {
            // Add id_key if it hasn't been set
            if (!isset($field['id_key'])) {
                $fields[$id]['id_key'] = $id;
            }
        }

        // Set fields
        $this->fields = $fields;
    }

    /**
     * @param array $arr
     *
     * @return bool
     */
    private function is_assoc(array $arr)
    {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, \count($arr) - 1);
    }

    public function admin_init()
    {
        if (\defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (\defined('CMB2_LOADED')) {
            // Enqueue CMB assets
            CMB2_hookup::enqueue_cmb_css();
            CMB2_hookup::enqueue_cmb_js();
        }
    }

    /**
     * @param string|mixed $value
     * @param string|mixed $object_id
     * @param array|null   $args
     * @param CMB2_Field   $field
     *
     * @return mixed
     */
    public function cmb2_override_meta_value($value, $object_id, array $args = null, CMB2_Field $field)
    {
        // FIXME: Workaround for issue: https://github.com/CMB2/CMB2-Snippet-Library/issues/66
        if ($field->group || 'group' === $field->type()) {
            if (isset($field->args['id_key'])) {
                $id_key = $field->args['id_key'];

                if (isset($this->_instance[$id_key])) {
                    $value = $this->_instance[$id_key];
                }
            }
        }

        return $value;
    }

    /**
     * @param string|mixed $display
     * @param array|null   $meta_box
     *
     * @return bool
     */
    public function show_on($display, array $meta_box = null)
    {
        if (!isset($meta_box['show_on']['key'], $meta_box['show_on']['value'])) {
            return $display;
        }
        if ('widget' !== !$meta_box['show_on']['key']) {
            return $display;
        }

        if ($meta_box['show_on']['value'] === $this->option_name) {
            return true;
        }

        return $display;
    }

    /**
     * @param array $new_instance
     * @param array $old_instance
     *
     * @return array|mixed
     */
    public function update($new_instance, $old_instance)
    {
        $fields = $this->get_fields();
        $sanitized = $this->cmb2(true)->get_sanitized_values($new_instance);

        // FIXME: Workaround for file id fields not saving properly
        foreach ($new_instance as $id => $value) {
            if ('file' === $fields[$id]['type']) {
                $sanitized[$id.'_id'] = $file_id = (int) $value;
                $sanitized[$id] = wp_get_attachment_url($file_id);
            }
        }

        return $sanitized;
    }

    /**
     * @return array
     */
    protected function get_fields()
    {
        return $this->fields;
    }

    /**
     * @param bool $saving
     *
     * @return \CMB2
     */
    public function cmb2($saving = false)
    {
        // Create a new box in the class
        $cmb2 = new CMB2([
            'id' => $this->get_cmb2_id(), // Option name is taken from the WP_Widget class.
            'hookup' => false,
            'show_on' => [
                'key' => 'options-page', // Tells CMB2 to handle this as an option
                'value' => [$this->option_name],
            ],
        ], $this->option_name);

        // Add fields to form
        foreach ($this->get_fields() as $field) {
            // Translate the id to a widget form field name if we're saving the data
            if (!$saving) {
                $field['id'] = $this->get_field_name($field['id']);
            }

            // Add classes
            if (isset($field['classes']) && !\is_array($field['classes'])) {
                $field['classes'] = [$field['classes']];
            }

            $field['classes'][] = 'cmb2-widgets';

            // FIXME: Workaround for issue: https://github.com/CMB2/CMB2-Snippet-Library/issues/66
            if ('group' === $field['type']) {
                // Update group fields default_cb
                foreach ($field['fields'] as $group_field_index => $group_field) {
                    $group_field['default_cb'] = [$this, 'default_cb'];

                    $field['fields'][$group_field_index] = $group_field;
                }
            }

            // Add callback and then add the field
            $field['default_cb'] = [$this, 'default_cb'];
            $cmb2->add_field($field);
        }

        return $cmb2;
    }

    /**
     * @return string
     */
    protected function get_cmb2_id()
    {
        return $this->option_name.'_box';
    }

    /**
     * Back-end widget form with defaults.
     *
     * @param array $instance current settings
     */
    public function form($instance)
    {
        // FIXME: Workaround for issue: https://github.com/CMB2/CMB2-Snippet-Library/issues/66
        add_filter('cmb2_override_meta_value', [$this, 'cmb2_override_meta_value'], 11, 4);

        // If there are no settings, set up defaults
        $this->_instance = wp_parse_args((array) $instance, $this->get_defaults());
        $cmb2 = $this->cmb2();
        $cmb2->object_id($this->option_name);
        $cmb2->show_form();

        remove_filter('cmb2_override_meta_value', [$this, 'cmb2_override_meta_value'], 11);
    }

    /**
     * @return array
     */
    protected function get_defaults()
    {
        return $this->defaults;
    }

    /**
     * @param array|null  $field_args
     * @param \CMB2_Field $field
     *
     * @return mixed|null
     */
    public function default_cb(array $field_args = null, CMB2_Field $field)
    {
        $field_id = $field->args('id');
        $field_id_key = $field->args('id_key');

        // FIXME: Workaround for issue: https://github.com/CMB2/CMB2-Snippet-Library/issues/66
        if ($field->group) {
            if (isset($this->_instance[$field_id_key])) {
                $data = $this->_instance[$field_id_key];

                return (\is_array($data) && isset($data[$field->group->index][$field_id_key])) ? $data[$field->group->index][$field_id_key] : null;
            }

            return null;
        }

        $restored_id = $this->restore_field_id($field_id);

        if (!empty($this->_instance[$restored_id])) {
            return $this->_instance[$restored_id];
        }

        return $this->_instance[$field_id_key] ?? null;
    }

    /**
     * @param string $field_id
     *
     * @return string
     */
    private function restore_field_id(string $field_id = '')
    {
        return trim(str_replace(['[]', '[', ']'], ['', '-', ''], $field_id), '-');
    }
}
