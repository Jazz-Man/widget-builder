<?php

    /**
     * WordPress Widgets Helper Class.
     * https://github.com/Jazz-Man/wp-widgets-helper
     * @author       JazzMan
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
         * @param $args
         */
        public function __construct($args)
        {
            $this->label   = $args['label'] ?? '';
            $this->slug    = isset($this->label) ? sanitize_title($this->label) : '';
            $this->fields  = $args['fields'] ?? [];
            $this->options = [
                'classname'   => $this->slug,
                'description' => $args['description'] ?? ''
            ];
            if ( ! empty($options)) {
                $this->options = array_merge($this->options, $options);
            }
            parent::__construct($this->slug, $this->label, $this->options);
        }

        /**
         * @param $instance
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
            if ( ! empty($this->fields)) {
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
        public function create_field($key, $out = '')
        {
            $key['std'] = $key['std'] ?? '';
            if (isset($this->instance[$key['id']])) {
                $key['value'] = empty($this->instance[$key['id']]) ? '' : strip_tags($this->instance[$key['id']]);
            } else {
                unset($key['value']);
            }
            $key['_id']   = $this->get_field_id($key['id']);
            $key['_name'] = $this->get_field_name($key['id']);
            if ( ! isset($key['type'])) {
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
                $slug = $key['id'];
                if (isset($key['validate'])) {
                    if (false === $this->validate($key['validate'], $new_instance[$slug])) {
                        return $this->instance;
                    }
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
            $rules = explode('|', $rules);
            if (empty($rules) || count($rules) < 1) {
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
                    return is_bool($value);
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
                    if ( ! preg_match('/^[0-9]+$/', $value)) {
                        return false;
                    }
                    if ($value == 0) {
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
            $filters = explode('|', $filters);
            if (empty($filters) || count($filters) < 1) {
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
            if (isset($key['class'])) {
                $out .= 'class="' . esc_attr($key['class']) . '" ';
            }
            $value = isset($key['value']) ? $key['value'] : $key['std'];
            $out .= 'id="' . esc_attr($key['_id']) . '" name="' . esc_attr($key['_name']) . '" value="' . esc_attr__($value) . '" ';
            if (isset($key['size'])) {
                $out .= 'size="' . esc_attr($key['size']) . '" ';
            }
            $out .= ' />';
            if (isset($key['desc'])) {
                $out .= '<br/><small class="description">' . esc_html($key['desc']) . '</small>';
            }

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
            return '<label for="' . esc_attr($id) . '">' . esc_html($name) . ':</label>';
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
            if (isset($key['class'])) {
                $out .= 'class="' . esc_attr($key['class']) . '" ';
            }
            if (isset($key['rows'])) {
                $out .= 'rows="' . esc_attr($key['rows']) . '" ';
            }
            if (isset($key['cols'])) {
                $out .= 'cols="' . esc_attr($key['cols']) . '" ';
            }
            $value = isset($key['value']) ? $key['value'] : $key['std'];
            $out .= 'id="' . esc_attr($key['_id']) . '" name="' . esc_attr($key['_name']) . '">' . esc_html($value);
            $out .= '</textarea>';
            if (isset($key['desc'])) {
                $out .= '<br/><small class="description">' . esc_html($key['desc']) . '</small>';
            }

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
            if (isset($key['class'])) {
                $out .= 'class="' . esc_attr($key['class']) . '" ';
            }
            $out .= 'id="' . esc_attr($key['_id']) . '" name="' . esc_attr($key['_name']) . '" value="1" ';
            if ((isset($key['value']) && $key['value'] == 1) or ( ! isset($key['value']) && $key['std'] == 1)) {
                $out .= ' checked="checked" ';
            }
            $out .= ' /> ';
            if (isset($key['desc'])) {
                $out .= '<br/><small class="description">' . esc_html($key['desc']) . '</small>';
            }

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
            $out .= '<select id="' . esc_attr($key['_id']) . '" name="' . esc_attr($key['_name']) . '" ';
            if (isset($key['class'])) {
                $out .= 'class="' . esc_attr($key['class']) . '" ';
            }
            $out .= '> ';
            $selected = isset($key['value']) ? $key['value'] : $key['std'];
            foreach ($key['fields'] as $field => $option) {
                $out .= '<option value="' . esc_attr__($option['value']) . '" ';
                if (esc_attr($selected) == $option['value']) {
                    $out .= ' selected="selected" ';
                }
                $out .= '> ' . esc_html($option['name']) . '</option>';
            }
            $out .= ' </select> ';
            if (isset($key['desc'])) {
                $out .= '<br/><small class="description">' . esc_html($key['desc']) . '</small>';
            }

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
            $out .= '<select id="' . esc_attr($key['_id']) . '" name="' . esc_attr($key['_name']) . '" ';
            if (isset($key['class'])) {
                $out .= 'class="' . esc_attr($key['class']) . '" ';
            }
            $out .= '> ';
            $selected = isset($key['value']) ? $key['value'] : $key['std'];
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
            if (isset($key['desc'])) {
                $out .= '<br/><small class="description">' . esc_html($key['desc']) . '</small>';
            }

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
            if (isset($key['class'])) {
                $out .= 'class="' . esc_attr($key['class']) . '" ';
            }
            $value = isset($key['value']) ? $key['value'] : $key['std'];
            $out .= 'id="' . esc_attr($key['_id']) . '" name="' . esc_attr($key['_name']) . '" value="' . esc_attr__($value) . '" ';
            if (isset($key['size'])) {
                $out .= 'size="' . esc_attr($key['size']) . '" ';
            }
            $out .= ' />';
            if (isset($key['desc'])) {
                $out .= '<br/><small class="description">' . esc_html($key['desc']) . '</small>';
            }

            return $out;
        }
    }
