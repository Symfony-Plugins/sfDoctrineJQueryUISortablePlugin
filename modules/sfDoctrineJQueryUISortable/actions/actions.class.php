<?php

class sfDoctrineJQueryUISortableActions extends sfActions
{
  public function executeSaveOrder(sfWebRequest $request)
  { 
    $model = $request->getParameter('model');
    $order = 'sfDoctrineJQueryUISortable' . $model;
    $parentModel = $request->getParameter('parent_model');
    if($request->getParameter('parent_super_class'))
    {
      $parentModel = $request->getParameter('parent_super_class');
    }
    $parentId = $request->getParameter('parent_id');
    
    if (empty($model) || empty($order) || empty($parentModel) || empty($parentId)
        || !is_array($request->getParameter($order)))
    {
      return sfView::NONE;
    }

    foreach ($request->getParameter($order) as $rank => $objectId)
    {
      
      $query = Doctrine_Query::create()
        ->from($model . ' m')
        ->innerJoin('m.' . $parentModel . ' p')
        ->where('m.id=? AND p.id=?', array($objectId, $parentId));

      $object = $query->fetchOne();

      if ($object instanceOf $model)
      {
        $object->setRank($rank);
        $object->save();
      }
    }

    return sfView::HEADER_ONLY;
  }
}