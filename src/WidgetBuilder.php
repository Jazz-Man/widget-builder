<?php

namespace JazzMan\Widget;

/**
 * WordPress Widgets Helper Class.
 * https://github.com/Jazz-Man/wp-widgets-helper.
 *
 * @author JazzMan
 */
abstract class WidgetBuilder extends \WP_Widget
{
    /**
     * @var string
     */
    public $label;
    /**
     * @var string
     */
    public $slug;

    /**
     * @var array
     */
    public $fields = array();
    /**
     * @var array
     */
    public $options = array();
    /**
     * @var
     */
    public $instance;

    /**
     * WidgetBuilder constructor.
     *
     * @param array      $args
     * @param array|null $options
     */
    public function __construct(array $args, array $options = null)
    {
        $this->label  = isset($args['label']) ? $args['label'] : '';
        $this->slug   = sanitize_title($this->label);
        $this->fields = isset($args['fields']) ? $args['fields'] : array();
        $this->options = [
            'classname'   => $this->slug,
            'description' => isset($args['description']) ? $args['description'] : '',
        ];
        if (! empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        parent::__construct($this->slug, $this->label, $this->options);
    }

    /**
     * Outputs the settings update form.
     *
     * @since  2.8.0
     * @access public
     *
     * @param array $instance Current settings.
     *
     * @return string Default return is 'noform'.
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
     * @param $key
     *
     * @return string
     *
     * @internal param string $out
     */
    public function create_field($key)
    {
        $field_id = ! isset($key['id']) ? sanitize_title($key['name']) : $key['id'];
        if (isset($key['std'])) {
            $key['std'] = $key['std'];
        } else {
            $key['std'] = '';
        }
        if (isset($this->instance[ $field_id ])) {
            $key['value'] = empty($this->instance[ $field_id ]) ? '' : strip_tags($this->instance[ $field_id ]);
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
            return $p . $this->$field_method( $key ) . '</p>';
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
     * Updates a particular instance of a widget.
     *
     * This function should check that `$new_instance` is set correctly. The newly-calculated
     * value of `$instance` should be returned. If false is returned, the instance won't be
     * saved/updated.
     *
     * @since  2.8.0
     * @access public
     *
     * @param array $new_instance New settings for this instance as input by the user via
     *                            WP_Widget::form().
     * @param array $old_instance Old settings for this instance.
     *
     * @return array|string
     */
    public function update($new_instance, $old_instance)
    {
        $this->instance = $old_instance;
        $this->before_update_fields();
        foreach ($this->fields as $key) {
            $slug = ! isset($key['id']) ? sanitize_title($key['name']) : $key['id'];
            if (isset($key['validate']) && false === $this->validate($key['validate'], $new_instance[ $slug ])) {
                return $this->instance;
            }
            if (isset($key['filter'])) {
                $this->instance[ $slug ] = $this->filter($key['filter'], $new_instance[ $slug ]);
            } else {
                $this->instance[ $slug ] = strip_tags($new_instance[ $slug ]);
            }
        }

        return $this->after_validate_fields($this->instance);
    }

    /**
     * @return string
     */
    public function before_update_fields()
    {
        return (string) '';
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
        foreach ((array) $rules as $rule) {
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
                return (bool) preg_match('/^[\-+]?[0-9]+$/', $value);
                break;
            case 'boolean':
                return (bool) $value;
                break;
            case 'email':
                return is_email($value);
                break;
            case 'decimal':
                return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $value);
                break;
            case 'natural':
                return (bool) preg_match('/^[0-9]+$/', $value);
            case 'natural_not_zero':
                return ! ( ! preg_match('/^[0-9]+$/', $value) && $value === 0 );
            default:
                if (method_exists($this, $rule)) {
                    return $this->$rule( $value );
                }

                return false;
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
        foreach ((array) $filters as $filter) {
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
                    return $this->$filter( $value );
                }

                return $value;
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
        $out   .= $this->create_field_label($key['name'], $key['_id']) . '<br/>';
        $out   .= '<input type="text" ';
        $out   .= $this->create_field_class($key);
        $value = isset($key['value']) ? $key['value'] : $key['std'];
        $out   .= $this->create_field_id_name($key);
        $out   .= 'value="' . esc_attr__($value) . '"';
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
        $out   .= $this->create_field_label($key['name'], $key['_id']) . '<br/>';
        $out   .= '<input type="text" ';
        $out   .= $this->create_field_class($key);
        $value = isset($key['value']) ? $key['value'] : $key['std'];
        $out   .= $this->create_field_id_name($key);
        $out   .= 'value="' . esc_url($value) . '"';
        $out   .= ' />';
        $out   .= $this->upload_image_button();
        $out   .= $this->create_field_description($key);

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
        $value = isset($key['value']) ? $key['value'] : $key['std'];
        $out   .= $this->create_field_id_name($key);
        $out   .= '>' . esc_html($value);
        $out   .= '</textarea>';
        $out   .= $this->create_field_description($key);

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
        if (( isset($key['value']) && $key['value'] === 1 ) || ( ! isset($key['value']) && $key['std'] === 1 )) {
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
        $selected = isset($key['value']) ? $key['value'] : $key['std'];
        foreach ((array) $key['fields'] as $field => $option) {
            $out .= '<option value="' . esc_attr__($option['value']) . '" ';
            if (esc_attr($selected) === $option['value']) {
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
        $out      .= $this->create_field_label($key['name'], $key['_id']) . '<br/>';
        $out      .= '<select ';
        $out      .= $this->create_field_id_name($key);
        $out      .= $this->create_field_class($key);
        $out      .= '> ';
        $selected = isset($key['value']) ? $key['value'] : $key['std'];
        foreach ($key['fields'] as $group => $fields) {
            $out .= '<optgroup label="' . $group . '">';
            foreach ($this->fields as $field => $option) {
                $out .= '<option value="' . esc_attr($option['value']) . '" ';
                if (esc_attr($selected) === $option['value']) {
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
        $out   .= $this->create_field_label($key['name'], $key['_id']) . '<br/>';
        $out   .= '<input type="number" ';
        $out   .= $this->create_field_class($key);
        $value = isset($key['value']) ? $key['value'] : $key['std'];
        $out   .= $this->create_field_id_name($key);
        $out   .= 'value="' . esc_attr__($value) . '" ';
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
}
