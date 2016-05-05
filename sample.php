<?php
  /**
   * Created by PhpStorm.
   * User: jazzman
   * Date: 14.04.16
   * Time: 23:52
   */
  namespace Upages_Widgets;

  use Upages_Objects\Widget_Builder;

  /**
   * Class Widgets_Boxes
   *
   * @package Upages_Widgets
   */
class Widget_Boxes extends Widget_Builder
{
    /**
     * @param $args
     */
    public $boxes_fields = [];

    /**
     * Widgets_Boxes constructor.
     */
    public function __construct()
    {
        $this->setBoxesFields();
        $args             = [
        'label'       => __('Boxes', 'superlist'),
        'description' => __('Text boxes with icons.', 'superlist'),
        ];
        $args['fields']   = [
        [
          'name' => __('Title', 'superlist'),
          'id'   => 'title',
          'type' => 'text',
        ],
        [
          'name' => __('Description', 'superlist'),
          'id'   => 'description',
          'type' => 'textarea',
        ],
        [
          'name' => '1.' . __('Title:', 'superlist'),
          'type' => 'text',
        ],
        [
          'name' => '1.' . __('Content:', 'superlist'),
          'type' => 'textarea'
        ],
        [
          'name' => '1.' . __('Icon Class:', 'superlist'),
          'type' => 'text'
        ],
        [
          'name'   => '1.' . __('Read More Link:', 'superlist'),
          'type'   => 'select',
          'fields' => $this->getPageList()
        ],
        [
          'name' => '2.' . __('Title:', 'superlist'),
          'type' => 'text',
        ],
        [
          'name' => '2.' . __('Content:', 'superlist'),
          'type' => 'textarea'
        ],
        [
          'name' => '2.' . __('Icon Class:', 'superlist'),
          'type' => 'text'
        ],
        [
          'name'   => '2.' . __('Read More Link:', 'superlist'),
          'type'   => 'select',
          'fields' => $this->getPageList()
        ],
        [
          'name' => '3.' . __('Title:', 'superlist'),
          'type' => 'text',
        ],
        [
          'name' => '3.' . __('Content:', 'superlist'),
          'type' => 'textarea'
        ],
        [
          'name' => '3.' . __('Icon Class:', 'superlist'),
          'type' => 'text'
        ],
        [
          'name'   => '3.' . __('Read More Link:', 'superlist'),
          'type'   => 'select',
          'fields' => $this->getPageList()
        ],
        ];
        $advanced_options = $this->add_advanced_options();
        foreach ($advanced_options as $option) {
            $args['fields'][] = $option;
        }
        parent::__construct($args);
    }

    /**
     * @param mixed $boxes_fields
     */
    public function setBoxesFields()
    {
        $boxes_fields_count = 3;
        for ($i = 1; $i <= $boxes_fields_count; $i++) {
            $this->boxes_fields[] = [
            [
            'name' => $i . '.' . __('Title:', 'superlist'),
            'type' => 'text',
            ],
          [
            'name' => $i . '.' . __('Content:', 'superlist'),
            'type' => 'textarea'
          ],
          [
            'name' => $i . '.' . __('Icon Class:', 'superlist'),
            'type' => 'text'
          ],
          [
            'name'   => $i . '.' . __('Read More Link:', 'superlist'),
            'type'   => 'select',
            'fields' => $this->getPageList()
          ]
            ];
        }

        return $this->boxes_fields;
    }

    /**
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance)
    {
        echo wp_kses($args['before_widget'], wp_kses_allowed_html('post')); ?>
      <div <?php echo $this->advanced_style($instance) ?>>
        <?= $this->advanced_widget_title_and_description($instance) ?>
        <div class="row">
            <?php for ($i = 1; $i <= 3; $i++) : ?>
            <?php $title_id = $i . '-nazva'; ?>
            <?php $content_id = $i . '-vmist'; ?>
            <?php $icon_id = $i . '-icon-class'; ?>
            <?php $link_id = $i . '-read-more-link'; ?>
            <div class="col-sm-4">
              <div class="box">
                <div class="box-icon">
                  <i class="fa <?php echo wp_kses($instance[$icon_id], wp_kses_allowed_html('post')); ?>"></i>
                </div>
                <div class="box-body">
                  <h4 class="box-title"><?php echo wp_kses($instance[$title_id], wp_kses_allowed_html('post')); ?></h4>
                  <div class="box-content">
                    <?php echo wp_kses($instance[$content_id], wp_kses_allowed_html('post')); ?>
                  </div>
                    <?php $read_more = $instance[$link_id]; ?>
                    <?php if (! empty($read_more)) : ?>
                    <a href="<?php echo wp_kses($read_more, wp_kses_allowed_html('post')); ?>" class="box-read-more">
                        <?php echo esc_attr__('Read More', 'superlist'); ?>
                      <i class="fa fa-angle-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endfor; ?>
        </div>
      </div>
        <?php echo wp_kses($args['after_widget'], wp_kses_allowed_html('post'));
    }

}
