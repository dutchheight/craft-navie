<?php

namespace dutchheight\navie\craftql\types;

use dutchheight\navie\craftql\arguments\ListItemQueryArguments;
use dutchheight\navie\elements\ListItem;
use markhuot\CraftQL\Builders\InterfaceBuilder;

class ItemInterface extends InterfaceBuilder
{
    function boot()
    {
        $this->addIntField('id')->nonNull();
        $this->addIntField('listId');
        $this->addIntField('elementId');
        $this->addIntField('level');

        $this->addStringField('title')->nonNull();
        $this->addStringField('type');
        $this->addStringField('url');
        $this->addStringField('target');

        $this->addField('children')
            ->type(ItemInterface::class)
            ->lists()
            ->use(new ListItemQueryArguments)
            ->resolve(function ($root, $args, $context, $info) {
                return ItemInterface::criteriaResolver($root, $args, $context, $info, $root->getChildren());
            });

        $this->addField('parent')->type(ItemInterface::class);
        $this->addField('next')->type(ItemInterface::class);
        $this->addField('nextSibling')->type(ItemInterface::class);
        $this->addField('prev')->type(ItemInterface::class);
        $this->addField('prevSibling')->type(ItemInterface::class);
    }

    function getResolveType()
    {
        return function ($item) {
            return ucfirst($item->list->handle) . 'List';
        };
    }

    static function criteriaResolver($root, $args, $context, $info, $criteria = null, $asArray = true) {
        $criteria = $criteria ?: ListItem::find();

        if (isset($args['list'])) {
            $args['listId'] = $args['list'][0];
            unset($args['list']);
        }

        foreach ($args as $key => $value) {
            $criteria = $criteria->{$key}($value);
        }

        return $asArray ? $criteria->all() : $criteria;
    }
}