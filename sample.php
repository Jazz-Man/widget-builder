<?php

use JazzMan\Widget\WidgetBuilder;

class WidgetBoxes extends WidgetBuilder {

  /**
   * @param $args
   */
  public $boxes_fields = array();

  /**
   * Widgets_Boxes constructor.
   */
  public function __construct() {

    $args           = array(
      'label'       => __( 'Boxes', 'your_language' ),
      'description' => __( 'Text boxes with icons.', 'your_language' ),
    );
    $args['fields'] = array(
      array(
        'name' => __( 'Title', 'your_language' ),
        'id'   => 'title',
        'type' => 'text',
      ),
      array(
        'name' => __( 'Description', 'your_language' ),
        'id'   => 'description',
        'type' => 'textarea',
      ),
      array(
        'name' => '1.' . __( 'Title:', 'your_language' ),
        'type' => 'checkbox',
        'std'  => __( 'Title:', 'your_language' ),
      ),
      array(
        'name' => '1.' . __( 'Content:', 'your_language' ),
        'type' => 'textarea',
      ),
      array(
        'name' => '1.' . __( 'Icon Class:', 'your_language' ),
        'type' => 'text',
      ),
      array(
        'name'   => '1.' . __( 'Read More Link:', 'your_language' ),
        'type'   => 'select',
        'fields' => $this->getPageList(),
      ),
    );
    parent::__construct( $args );
  }

  /**
   * @return array
   */
  public function getPageList() {
    $pages_list = array();
    $pages      = get_pages();
    foreach ( (array) $pages as $page ) {
      $pages_list[] = [
        'name'  => $page->post_title,
        'value' => $page->ID,
      ];
    }

    return $pages_list;
  }

  /**
   * @param array $args
   * @param array $instance
   */
  public function widget( $args, $instance ) {
    echo wp_kses( $args['before_widget'], wp_kses_allowed_html( 'post' ) ); ?>
      <div <?php echo $this->advanced_style( $instance ) ?>>
      <?= $this->advanced_widget_title_and_description( $instance ) ?>
        <div class="row">
      <?php for ( $i = 1; $i <= 3; $i ++ ) : ?>
        <?php $title_id = $i . '-nazva'; ?>
        <?php $content_id = $i . '-vmist'; ?>
        <?php $icon_id = $i . '-icon-class'; ?>
        <?php $link_id = $i . '-read-more-link'; ?>
              <div class="col-sm-4">
                <div class="box">
                  <div class="box-icon">
                    <i class="fa <?php echo wp_kses( $instance[ $icon_id ], wp_kses_allowed_html( 'post' ) ); ?>"></i>
                  </div>
                  <div class="box-body">
                    <h4 class="box-title"><?php echo wp_kses( $instance[ $title_id ],
              wp_kses_allowed_html( 'post' ) ); ?></h4>
                    <div class="box-content">
            <?php echo wp_kses( $instance[ $content_id ], wp_kses_allowed_html( 'post' ) ); ?>
                    </div>
            <?php $read_more = $instance[ $link_id ]; ?>
            <?php if ( ! empty( $read_more ) ) : ?>
                        <a href="<?php echo wp_kses( $read_more,
              wp_kses_allowed_html( 'post' ) ); ?>" class="box-read-more">
              <?php echo esc_attr__( 'Read More', 'your_language' ); ?>
                          <i class="fa fa-angle-right"></i>
                        </a>
            <?php endif; ?>
                  </div>
                </div>
              </div>
      <?php endfor; ?>
        </div>
      </div>
    <?php echo wp_kses( $args['after_widget'], wp_kses_allowed_html( 'post' ) );
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
   * @param $instance
   *
   * @return string
   */
  public function advanced_style($instance){
    return '';
  }

}


add_action(
	'widgets_init',
	function () {

		register_widget(WidgetBoxes::class);
	}
);