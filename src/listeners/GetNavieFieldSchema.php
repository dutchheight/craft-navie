<?php

namespace dutchheight\navie\listeners;

use dutchheight\navie\craftql\arguments\ListItemQueryArguments;
use dutchheight\navie\craftql\factories\ListFactory;
use dutchheight\navie\craftql\repositories\ListRepository;
use dutchheight\navie\craftql\types\ItemInterface;
use dutchheight\navie\craftql\types\ListType;

use markhuot\CraftQL\Events\AlterQuerySchema;

class GetNavieFieldSchema
{
    public static function handle(AlterQuerySchema $event)
    {
        $repository = new ListRepository();
        $repository->load();

        $factory = new ListFactory($repository, $event->query->getRequest());

        foreach ($factory->all() as $list) {
            $event->query->addConcreteType($list->getRawGraphQLObject());
        }

        $navieType = $event->query->createObjectType('Navie');

        $navieType
            ->addField('items')
            ->lists()
            ->type(ItemInterface::class)
            ->use(new ListItemQueryArguments)
            ->resolve(function ($root, $args, $context, $info) {
                return ItemInterface::criteriaResolver($root, $args, $context, $info);
            });

        $navieType
            ->addField('lists')
            ->lists()
            ->type(ListType::class)
            ->resolve(function ($root, $args, $context, $info) {
                return ListType::criteriaResolver($root, $args, $context, $info);
            });

        $event->query
            ->addField('navie')
            ->type($navieType)
            ->resolve(function ($root) {
                return [];
            });
    }
}