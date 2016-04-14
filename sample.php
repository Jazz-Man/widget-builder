<?php
    include_once __DIR__ . '/class-widget-builder.php';
    // Check if the custom class exists
    if ( ! class_exists('MV_My_Recent_Posts_Widget')) {
        // Create custom widget class extending WPH_Widget
        class MV_My_Recent_Posts_Widget extends Widget_Builder
        {
            public function __construct()
            {
                // Configure widget array
                $args = [
                    // Widget Backend label
                    'label'       => __('My Recent Posts', 'mv-my-recente-posts'),
                    // Widget Backend Description
                    'description' => __('My Recent Posts Widget Description', 'mv-my-recente-posts'),
                ];
                // Configure the widget fields
                // Example for: Title ( text ) and Amount of posts to show ( select box )
                // fields array
                $args['fields'] = [
                    // Title field
                    [
                        // field name/label
                        'name'  => __('Title', 'mv-my-recente-posts'),
                        // field description
                        'desc'  => __('Enter the widget title.', 'mv-my-recente-posts'),
                        // field id
                        'id'    => 'title',
                        // field type ( text, checkbox, textarea, select, select-group )
                        'type'  => 'text',
                        // class, rows, cols
                        'class' => 'widefat',
                        // default value
                        'std'   => __('Recent Posts', 'mv-my-recente-posts'),
                        /*
                            Set the field validation type/s
                            ///////////////////////////////

                            'alpha_dash'
                            Returns FALSE if the value contains anything other than alpha-numeric characters, underscores or dashes.

                            'alpha'
                            Returns FALSE if the value contains anything other than alphabetical characters.

                            'alpha_numeric'
                            Returns FALSE if the value contains anything other than alpha-numeric characters.

                            'numeric'
                            Returns FALSE if the value contains anything other than numeric characters.

                            'boolean'
                            Returns FALSE if the value contains anything other than a boolean value ( true or false ).

                            ----------

                            You can define custom validation methods. Make sure to return a boolean ( TRUE/FALSE ).
                            Example:

                            'validate' => 'my_custom_validation',

                            Will call for: $this->my_custom_validation( $value_to_validate );

                        */
                        'validate' => 'alpha_dash',
                        /*

                            Filter data before entering the DB
                            //////////////////////////////////

                            strip_tags ( default )
                            wp_strip_all_tags
                            esc_attr
                            esc_url
                            esc_textarea

                        */
                        'filter' => 'strip_tags|esc_attr',
                    ],
                    // Amount Field
                    [
                        'name'     => __('Amount'),
                        'desc'     => __('Select how many posts to show.', 'mv-my-recente-posts'),
                        'id'       => 'amount',
                        'type'     => 'select',
                        // selectbox fields
                        'fields'   => [
                            [
                                // option name
                                'name'  => __('1 Post', 'mv-my-recente-posts'),
                                // option value
                                'value' => '1',
                            ],
                            [
                                'name'  => __('2 Posts', 'mv-my-recente-posts'),
                                'value' => '2',
                            ],
                            [
                                'name'  => __('3 Posts', 'mv-my-recente-posts'),
                                'value' => '3',
                            ],
                            // add more options
                        ],
                        'validate' => 'my_custom_validation',
                        'filter'   => 'strip_tags|esc_attr',
                    ],
                    // Output type checkbox
                    [
                        'name'   => __('Output as list', 'mv-my-recente-posts'),
                        'desc'   => __('Wraps posts with the <li> tag.', 'mv-my-recente-posts'),
                        'id'     => 'list',
                        'type'   => 'checkbox',
                        // checked by default:
                        'std'    => 1, // 0 or 1
                        'filter' => 'strip_tags|esc_attr',
                    ],
                    // add more fields
                ]; // fields array
                // create widget
                $this->create_widget($args);
            }

            // Custom validation for this widget
            public function my_custom_validation($value)
            {
                if (strlen($value) > 1) {
                    return false;
                } else {
                    return true;
                }
            }

            // Output function
            public function widget($args, $instance)
            {
                // And here do whatever you want
                $out = $args['before_title'];
                $out .= $instance['title'];
                $out .= $args['after_title'];
                // here you would get the most recent posts based on the selected amount: $instance['amount']
                // Then return those posts on the $out variable ready for the output
                $out .= '<p>Hey There! </p>';
                echo $out;
            }
        } // class
        // Register widget
        if ( ! function_exists('mv_my_register_widget')) {
            function mv_my_register_widget()
            {
                register_widget('MV_My_Recent_Posts_Widget');
            }

            add_action('widgets_init', 'mv_my_register_widget', 1);
        }
    }
