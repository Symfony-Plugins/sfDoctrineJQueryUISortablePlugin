<?php
/**
 *
 * @author     Rich Birch <rich@trafficdigital.com>
 */
class sfWidgetFormDoctrineJQueryUISortable extends sfWidgetForm
{
  /**
   * Constructor.
   *
   * Available options:
   *
   *
   * @param array $options     An array of options
   * @param array $attributes  An array of default HTML attributes
   *
   */
  protected function configure($options = array(), $attributes = array())
  {
    parent::configure($options, $attributes);
    
    $this->addRequiredOption('model');
    $this->addRequiredOption('parent_object');
    
    $this->addOption('method', '__toString');
    $this->addOption('key_method', 'getPrimaryKey');
    $this->addOption('parent_key_method', 'getPrimaryKey');
    $this->addOption('order_by', null);
    $this->addOption('query', null);
    $this->addOption('table_method', null);
    $this->addOption('grid', false);
    $this->addOption('rank_field', 'rank');
  }

  /**
   * @param  string $name        The element name
   * @param  string $value       The value displayed in this widget
   * @param  array  $attributes  An array of HTML attributes to be merged with the default HTML attributes
   * @param  array  $errors      An array of errors for the field
   *
   * @return string An HTML tag string
   *
   * @see sfWidgetForm
   */
  public function render($name, $value = null, $attributes = array(), $errors = array())
  {

    if (is_null($this->getOption('table_method')))
    {
      $query = is_null($this->getOption('query')) ? Doctrine::getTable($this->getOption('model'))->createQuery() : $this->getOption('query');
      if ($order = $this->getOption('order_by'))
      {
        $query->addOrderBy($order[0] . ' ' . $order[1]);
      }
      $objects = $query->execute();
    }
    else
    {
      $tableMethod = $this->getOption('table_method');

      $results = is_array($tableMethod)
        ? call_user_func_array(array(Doctrine::getTable($this->getOption('model')), $tableMethod[0]), $tableMethod[1])
        : Doctrine::getTable($this->getOption('model'))->$tableMethod();

      if ($results instanceof Doctrine_Query)
      {
        $objects = $results->execute();
      }
      else if ($results instanceof Doctrine_Collection)
      {
        $objects = $results;
      }
      else if ($results instanceof Doctrine_Record)
      {
        $objects = new Doctrine_Collection($this->getOption('model'));
        $objects[] = $results;
      }
      else
      {
        $objects = array();
      }
    }

    $method = $this->getOption('method');
    $grid = $this->getOption('grid');

    $sortables = array();

    foreach ($objects as $object)
    {
      $item = ( $grid
        ? ''
        : '<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>') .
        ( is_array($method)
          ? call_user_func_array(array($object, $method[0]), $method[1])
          : $object->$method());
      
      $sortables[] =
        '<li class="ui-state-default" id="' . $this->getSortableContainerId($object) . '">' . $item . '</li>';
    }

    $html = '<table><tbody>%content%</tbody></table>';

    $html = str_replace('%content%', empty($sortables) ? '' : $this->getContent($sortables, $object), $html);

    return $html;
  }

  private function getContent($sortables, $object)
  {
    return '<tr><td><ul id="' . $this->getSortableContainerId() . '">' .
        implode('', $sortables) .
        '</ul></td></tr>' .
        $this->getJavascript($object) . $this->getCss();
  }

  private function getCss()
  {
    $containerId = $this->getSortableContainerId();

    $css = $this->getOption('grid')
    ? '<style type="text/css">
  #' . $containerId . ' { margin: 0; padding: 0; }
  #' . $containerId . ' li { list-style-type: none; margin: 3px 3px 3px 0; padding: 1px; float: left; width: 100px; height: 90px; font-size: 4em; text-align: center; }
</style>'
    : '<style type="text/css">
  #' . $containerId . ' { margin: 0; padding: 0; }
  #' . $containerId . ' li { list-style-type: none; margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; }
  #' . $containerId . ' li span { position: absolute; margin-left: -1.3em; }
</style>';

    return $css;
  }

  private function getJavascript()
  {
    $parentKeyMethod = $this->getOption('parent_key_method');
    $routing = sfContext::getInstance()->getRouting();

    $saveOrderUrl = $routing->generate(
      'sfDoctrineJQueryUISortable',
      array(
        'action' => 'saveOrder',
        'model' => $this->getOption('model'),
        'rank_field' => $this->getOption('rank_field'),
        'parent_model' => get_class($this->getOption('parent_object')),
        'parent_id' => $this->getOption('parent_object')->$parentKeyMethod()
        )
    );
    
    $js = '
<script type="text/javascript">
  $(function(){
    $("#' . $this->getSortableContainerId() . '").sortable({
      update : function () {
      serial = $(this).sortable("serialize");
      $.ajax({
        url: "' . $saveOrderUrl . '",
        type: "post",
        data: serial,
        error: function(){
          alert("theres an error with AJAX");
        }
      });
    }
  });

  });
</script>
';

    return $js;
  }

  private function getSortableContainerId($object = null)
  {
    $keyMethod = $this->getOption('key_method');

    return 'sfDoctrineJQueryUISortable' . $this->getOption('model') . ($object ? '-' . $object->$keyMethod() : '');
  }
}