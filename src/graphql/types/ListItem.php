<?php

namespace dutchheight\navie\graphql\types;

use craft\gql\base\ObjectType;
use craft\gql\interfaces\Element as ElementInterface;

use dutchheight\navie\elements\ListItem as ListItemElement;
use dutchheight\navie\graphql\interfaces\ListItem as ListItemInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class ListItem extends ObjectType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            ListItemInterface::getType(),
            ElementInterface::getType()
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var ListItemElement $source */
        $fieldName = $resolveInfo->fieldName;

        $list = $source->getList();
        $element = $source->getElement();

        switch ($fieldName) {
            case 'listId':
                return $source->listId;
            case 'listHandle':
                return $list->handle;
            case 'listName':
                return $list->name;
            case 'maxLevels':
                return $list->maxLevels;
            case 'propagate':
                return $list->propagate;
            case 'url':
                return $source->getUrl();
            case 'slug':
                return $element->slug ?? null;
            case 'uri':
                return $element->uri ?? null;
        }

        return $source->$fieldName;
    }
}
