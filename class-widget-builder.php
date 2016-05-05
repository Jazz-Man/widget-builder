<?php
    namespace Upages_Objects;

    /**
     * WordPress Widgets Helper Class.
     * https://github.com/Jazz-Man/wp-widgets-helper
     *
     * @author JazzMan
     */
class Widget_Builder extends \WP_Widget
{
    /**
         * @type string
         */
    public $label;
    /**
         * @type string
         */
    public $slug;

    /**
         * @type array
         */
    public $fields = [];
    /**
         * @type array
         */
    public $options = [];
    /**
         * @type
         */
    public $instance;

    /**
         * @param string $args
         * @param array  $options
         */
    public function __construct($args, array $options = null)
    {
        $this->label   = $args['label'] ?? '';
        $this->slug    = sanitize_title($this->label);
        $this->fields  = $args['fields'] ?? [];
        $this->options = [
            'classname'   => $this->slug,
            'description' => $args['description'] ?? ''
        ];
        if (! empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        parent::__construct($this->slug, $this->label, $this->options);
        add_action(
            'widgets_init', function () {
                register_widget(get_called_class());
            }
        );
    }

    /**
         * @param $instance
         *
         * @return string|void
         */
    public function form($instance)
    {
        $this->setInstance($instance);
        $form = $this->create_fields();
        echo $form;
    }

    /**
         * @param mixed $instance
         */
    public function setInstance($instance)
    {
        $this->instance = $instance;
    }

    /**
         * @param string $out
         *
         * @return string
         */
    public function create_fields($out = '')
    {
        $out = $this->before_create_fields($out);
        if ($this->fields !== null) {
            foreach ($this->fields as $key) {
                $out .= $this->create_field($key);
            }
        }
        $out = $this->after_create_fields($out);

        return $out;
    }

    /**
         * @param string $out
         *
         * @return string
         */
    public function before_create_fields($out = '')
    {
        return $out;
    }

    /**
         * @param        $key
         * @param string $out
         *
         * @return string
         */
    public function create_field($key)
    {
        $field_id   = ! isset($key['id']) ? sanitize_title($key['name']) : $key['id'];
        $key['std'] = $key['std'] ?? '';
        if (isset($this->instance[$field_id])) {
            $key['value'] = empty($this->instance[$field_id]) ? '' : strip_tags($this->instance[$field_id]);
        } else {
            unset($key['value']);
        }
        $key['_id']   = $this->get_field_id($field_id);
        $key['_name'] = $this->get_field_name($field_id);
        if (! isset($key['type'])) {
            $key['type'] = 'text';
        }
        $field_method = 'create_field_' . str_replace('-', '_', $key['type']);
        $p            = isset($key['class-p']) ? '<p class="' . $key['class-p'] . '">' : '<p>';
        if (method_exists($this, $field_method)) {
            return $p . $this->$field_method($key) . '</p>';
        }
    }

    /**
         * @param string $out
         *
         * @return string
         */
    public function after_create_fields($out = '')
    {
        return $out;
    }

    /**
         * @param $new_instance
         * @param $old_instance
         *
         * @return string
         */
    public function update($new_instance, $old_instance)
    {
        $this->instance = $old_instance;
        $this->before_update_fields();
        foreach ($this->fields as $key) {
            $slug = ! isset($key['id']) ? sanitize_title($key['name']) : $key['id'];
            if (isset($key['validate']) && false === $this->validate($key['validate'], $new_instance[$slug])) {
                return $this->instance;
            }
            if (isset($key['filter'])) {
                $this->instance[$slug] = $this->filter($key['filter'], $new_instance[$slug]);
            } else {
                $this->instance[$slug] = strip_tags($new_instance[$slug]);
            }
        }

        return $this->after_validate_fields($this->instance);
    }

    /**
         * @return string
         */
    public function before_update_fields()
    {
        return (string)'';
    }

    /**
         * @param $rules
         * @param $value
         *
         * @return bool
         */
    public function validate($rules, $value)
    {
        $rules       = explode('|', $rules);
        $rules_count = count($rules);
        if (empty($rules) || $rules_count < 1) {
            return true;
        }
        foreach ((array)$rules as $rule) {
            if (false === $this->do_validation($rule, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
         * @param        $rule
         * @param string $value
         *
         * @return bool|int|void
         */
    public function do_validation($rule, $value = '')
    {
        switch ($rule) {
        case 'alpha':
            return ctype_alpha($value);
                break;
        case 'alpha_numeric':
            return ctype_alnum($value);
                break;
        case 'alpha_dash':
            return preg_match('/^[a-z0-9-_]+$/', $value);
                break;
        case 'numeric':
            return ctype_digit($value);
                break;
        case 'integer':
            return (bool)preg_match('/^[\-+]?[0-9]+$/', $value);
                break;
        case 'boolean':
            //return is_bool($value);
            return (bool)$value;
                break;
        case 'email':
            return is_email($value);
                break;
        case 'decimal':
            return (bool)preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $value);
                break;
        case 'natural':
            return (bool)preg_match('/^[0-9]+$/', $value);
        case 'natural_not_zero':
            if (! preg_match('/^[0-9]+$/', $value) && $value == 0) {
                return false;
            }

            return true;
        default:
            if (method_exists($this, $rule)) {
                return $this->$rule($value);
            } else {
                return false;
            }
            break;

        }
    }

    /**
         * @param $filters
         * @param $value
         *
         * @return string
         */
    public function filter($filters, $value)
    {
        $filters       = explode('|', $filters);
        $filters_count = count($filters);
        if (empty($filters) || $filters_count < 1) {
            return $value;
        }
        foreach ((array)$filters as $filter) {
            $value = $this->do_filter($filter, $value);
        }

        return $value;
    }

    /**
         * @param        $filter
         * @param string $value
         *
         * @return string
         */
    public function do_filter($filter, $value = '')
    {
        switch ($filter) {
        case 'strip_tags':
            return strip_tags($value);
                break;
        case 'wp_strip_all_tags':
            return wp_strip_all_tags($value);
                break;
        case 'esc_attr':
            return esc_attr($value);
                break;
        case 'esc_url':
            return esc_url($value);
                break;
        case 'esc_textarea':
            return esc_textarea($value);
                break;
        default:
            if (method_exists($this, $filter)) {
                return $this->$filter($value);
            } else {
                return $value;
            }
            break;
        }
    }

    /**
         * @param string $instance
         *
         * @return string
         */
    public function after_validate_fields($instance = '')
    {
        return $instance;
    }

    /**
         * @param        $key
         * @param string $out
         *
         * @return string
         */
    public function create_field_text($key, $out = '')
    {
        $out .= $this->create_field_label($key['name'], $key['_id']) . '<br/>';
        $out .= '<input type="text" ';
        $out .= $this->create_field_class($key);
        $value = $key['value'] ?? $key['std'];
        $out .= $this->create_field_id_name($key);
        $out .= 'value="' . esc_attr__($value) . '"';
        if (isset($key['size'])) {
            $out .= 'size="' . esc_attr($key['size']) . '" ';
        }
        $out .= ' />';
        $out .= $this->create_field_description($key);

        return $out;
    }

    /**
         * @param string $name
         * @param string $id
         *
         * @return string
         */
    public function create_field_label($name = '', $id = '')
    {
        return '<label for="' . esc_attr($id) . '">' . esc_html($name) . '</label>';
    }

    /**
         * @param $class
         *
         * @return string
         */
    public function create_field_class($class)
    {
        $field_class = ! isset($class['class']) ? 'class="widefat"' : 'class="' . $class['class'] . '"';

        return $field_class;
    }

    /**
         * @param $id_name
         *
         * @return string
         */
    public function create_field_id_name($id_name)
    {
        $field_id_name = 'id="' . esc_attr($id_name['_id']) . '" name="' . esc_attr($id_name['_name']) . '"';

        return $field_id_name;

    }

    /**
         * @param $desc
         *
         * @return string
         */
    public function create_field_description($desc)
    {
        $field_description = ! isset($desc['desc'])
            ? '<br/><small class="description">' . esc_html($desc['name']) . '</small>'
            : '<br/><small class="description">' . esc_html($desc['desc']) . '</small>';

        return $field_description;
    }

    /**
         * @param        $key
         * @param string $out
         *
         * @return string
         */
    public function create_field_image($key, $out = '')
    {
        $out .= $this->create_field_label($key['name'], $key['_id']) . '<br/>';
        $out .= '<input type="text" ';
        $out .= $this->create_field_class($key);
        $value = $key['value'] ?? $key['std'];
        $out .= $this->create_field_id_name($key);
        $out .= 'value="' . esc_url($value) . '"';
        $out .= ' />';
        $out .= $this->upload_image_button();
        $out .= $this->create_field_description($key);

        return $out;
    }

    /**
         * @return string
         */
    public function upload_image_button()
    {
        $button = '<button class="upload_image_button button button-primary">Upload Image</button>';

        return $button;
    }

    /**
         * @param        $key
         * @param string $out
         *
         * @return string
         */
    public function create_field_textarea($key, $out = '')
    {
        $out .= $this->create_field_label($key['name'], $key['_id']) . '<br/>';
        $out .= '<textarea ';
        $out .= $this->create_field_class($key);
        $out .= ! isset($key['rows']) ? 'rows="3"' : 'rows="' . $key['rows'] . '"';
        if (isset($key['cols'])) {
            $out .= 'cols="' . esc_attr($key['cols']) . '" ';
        }
        $value = $key['value'] ?? $key['std'];
        $out .= $this->create_field_id_name($key);
        $out .= '>' . esc_html($value);
        $out .= '</textarea>';
        $out .= $this->create_field_description($key);

        return $out;
    }

    /**
         * @param        $key
         * @param string $out
         *
         * @return string
         */
    public function create_field_checkbox($key, $out = '')
    {
        $out .= $this->create_field_label($key['name'], $key['_id']);
        $out .= ' <input type="checkbox" ';
        $out .= $this->create_field_class($key);
        $out .= $this->create_field_id_name($key);
        $out .= '" value="1" ';
        if ((isset($key['value']) && $key['value'] == 1) or ( ! isset($key['value']) && $key['std'] == 1)) {
            $out .= ' checked="checked" ';
        }
        $out .= ' /> ';
        $out .= $this->create_field_description($key);

        return $out;
    }

    /**
         * @param        $key
         * @param string $out
         *
         * @return string
         */
    public function create_field_select($key, $out = '')
    {
        $out .= $this->create_field_label($key['name'], $key['_id']) . '<br/>';
        $out .= '<select ';
        if (isset($key['multiple']) && $key['multiple'] === true) {
            $out .= 'multiple ';
            $out .= 'size="' . count($key['fields']) . '"';
        }
        $out .= $this->create_field_id_name($key);
        $out .= $this->create_field_class($key);
        $out .= '> ';
        $selected = $key['value'] ?? $key['std'];
        foreach ($key['fields'] as $field => $option) {
            $out .= '<option value="' . esc_attr__($option['value']) . '" ';
            if (esc_attr($selected) == $option['value']) {
                $out .= ' selected="selected" ';
            }
            $out .= '> ' . esc_html($option['name']) . '</option>';
        }
        $out .= ' </select> ';
        $out .= $this->create_field_description($key);

        return $out;
    }

    /**
         * @param        $key
         * @param string $out
         *
         * @return string
         */
    public function create_field_select_group($key, $out = '')
    {
        $out .= $this->create_field_label($key['name'], $key['_id']) . '<br/>';
        $out .= '<select ';
        $out .= $this->create_field_id_name($key);
        $out .= $this->create_field_class($key);
        $out .= '> ';
        $selected = $key['value'] ?? $key['std'];
        foreach ($key['fields'] as $group => $fields) {
            $out .= '<optgroup label="' . $group . '">';
            foreach ($this->fields as $field => $option) {
                $out .= '<option value="' . esc_attr($option['value']) . '" ';
                if (esc_attr($selected) == $option['value']) {
                    $out .= ' selected="selected" ';
                }
                $out .= '> ' . esc_html($option['name']) . '</option>';
            }
            $out .= '</optgroup>';
        }
        $out .= '</select>';
        $out .= $this->create_field_description($key);

        return $out;
    }

    /**
         * @param        $key
         * @param string $out
         *
         * @return string
         */
    public function create_field_number($key, $out = '')
    {
        $out .= $this->create_field_label($key['name'], $key['_id']) . '<br/>';
        $out .= '<input type="number" ';
        $out .= $this->create_field_class($key);
        $value = $key['value'] ?? $key['std'];
        $out .= $this->create_field_id_name($key);
        $out .= 'value="' . esc_attr__($value) . '" ';
        if (isset($key['max'])) {
            $out .= 'max="' . esc_attr($key['max']) . '" ';
        }
        if (isset($key['min'])) {
            $out .= 'min="' . esc_attr($key['min']) . '" ';
        }
        if (isset($key['step'])) {
            $out .= 'step="' . esc_attr($key['step']) . '" ';
        }
        if (isset($key['size'])) {
            $out .= 'size="' . esc_attr($key['size']) . '" ';
        }
        $out .= ' />';
        $out .= $this->create_field_description($key);

        return $out;
    }

    /**
         * @return array
         */
    public function add_advanced_options()
    {
        $advanced_options = [
            [
                'name' => __('Background Color', 'superlist'),
                'id'   => 'background_color',
                'type' => 'text'
            ],
            [
                'name' => __('Background Image', 'superlist'),
                'id'   => 'background_image',
                'type' => 'text'
            ],
            [
                'name' => __('Padding Top', 'superlist'),
                'id'   => 'padding_top',
                'type' => 'checkbox',
                'std'  => 1
            ],
            [
                'name' => __('Padding Bottom', 'superlist'),
                'id'   => 'padding_bottom',
                'type' => 'checkbox',
                'std'  => 1
            ],
            [
                'name' => __('Classes', 'superlist'),
                'id'   => 'classes',
                'type' => 'text'
            ]
        ];

        return $advanced_options;
    }

    /**
         * @param $instance
         *
         * @return string
         */
    public function advanced_style($instance)
    {
        $widget_class = [
            'widget-inner',
            empty($instance['padding_top']) ? '' : 'widget-pt',
            empty($instance['padding_bottom']) ? '' : 'widget-pb'
        ];
        if (! empty($instance['classes'])) {
            $widget_class[] = esc_attr($instance['classes']);
        }
        $out = 'class="' . implode(' ', $widget_class) . '" ';
        if (! empty($instance['background_color']) || ! empty($instance['background_image'])) {
            $background = '';
            if (! empty($instance['background_color'])) {
                $background = 'background-color:' . esc_attr($instance['background_color']);
            } elseif (! empty($instance['background_image'])) {
                $background = 'background-image: url(' . esc_attr($instance['background_image']) . ')';
            }
            $out .= 'style="' . $background . '"';
        }

        return $out;
    }

    /**
         * @param $instance
         *
         * @return string
         */
    public function advanced_widget_title_and_description($instance)
    {
        $out = '';
        if (! empty($instance['title'])) {
            $out .= '<h2 class="widgettitle">';
            $out .= wp_kses($instance['title'], wp_kses_allowed_html('post'));
            $out .= '</h2>';
        }
        if (! empty($instance['description'])) {
            $out .= '<div class="description">';
            $out .= wp_kses($instance['description'], wp_kses_allowed_html('post'));
            $out .= '</div>';
        }

        return $out;
    }

    /**
         * @return array
         */
    public function getPageList()
    {
        $pages_list = [];
        $pages      = get_pages();
        foreach ((array)$pages as $page) {
            $pages_list[] = [
                'name'  => $page->post_title,
                'value' => get_page_link($page->ID)
            ];
        }

        return $pages_list;
    }

    /**
         * @return array
         */
    public function getFieldsFilter()
    {
        $fields_filter = [];
        $fields        = \Inventor_Filter::get_fields();
        foreach ((array)$fields as $key => $val) {
            $fields_filter[] = [
                'name'  => __($val, 'superlist'),
                'value' => $key
            ];
        }

        return $fields_filter;
    }
}
