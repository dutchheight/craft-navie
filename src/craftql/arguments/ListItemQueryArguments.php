<?php

namespace dutchheight\navie\craftql\arguments;

use dutchheight\navie\craftql\factories\ListFactory;
use dutchheight\navie\craftql\repositories\ListRepository;
use markhuot\CraftQL\Behaviors\FieldBehavior;

class ListItemQueryArguments extends FieldBehavior
{
    public function initListItemQueryArguments()
    {
        $repository = new ListRepository();
        $repository->load();

        $this->owner->addIntArgument('ancestorOf');
        $this->owner->addIntArgument('ancestorDist');
        $this->owner->addIntArgument('level');
        $this->owner->addIntArgument('descendantOf');
        $this->owner->addIntArgument('descendantDist');
        $this->owner->addBooleanArgument('fixedOrder');
        $this->owner->addArgument('list')->type((new ListFactory($repository, $this->owner->request))->enum())->lists();
        $this->owner->addIntArgument('listId');
        $this->owner->addIntArgument('id')->lists();
        $this->owner->addStringArgument('indexBy');
        $this->owner->addIntArgument('limit');
        $this->owner->addStringArgument('site');
        $this->owner->addIntArgument('siteId');
        $this->owner->addIntArgument('nextSiblingOf');
        $this->owner->addIntArgument('offset');
        $this->owner->addStringArgument('order');
        $this->owner->addStringArgument('orderBy');
        $this->owner->addIntArgument('positionedAfter');
        $this->owner->addIntArgument('positionedBefore');
        $this->owner->addIntArgument('prevSiblingOf');
        $this->owner->addIntArgument('siblingOf');
        $this->owner->addStringArgument('title');
        $this->owner->addStringArgument('url');

        $fieldService = \Yii::$container->get('craftQLFieldService');
        $arguments = $fieldService->getQueryArguments($this->owner->getRequest());
        $this->owner->addArguments($arguments, false);
    }
}