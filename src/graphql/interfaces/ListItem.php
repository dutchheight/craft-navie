<?php

namespace dutchheight\navie\graphql\interfaces;

use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Structure;
use craft\gql\TypeLoader;
use dutchheight\navie\elements\ListItem as ListItemElement;

use dutchheight\navie\graphql\arguments\ListItem as ListItemArguments;
use dutchheight\navie\graphql\interfaces\ListItem as ListItemInterface;
use dutchheight\navie\graphql\types\generators\ListItemType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class ListItem extends Structure
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return ListItemType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::class)) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::class, new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all list items.',
            'resolveType' => function (ListItemElement $value) {
                return GqlEntityRegistry::getEntity($value->getGqlTypeName());
            }
        ]));

        foreach (ListItemType::generateTypes() as $typeName => $generatedType) {
            TypeLoader::registerType($typeName, function () use ($generatedType) {
                return $generatedType;
            });
        }

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'ListItemInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array {
        return array_merge(parent::getFieldDefinitions(), [
            'listId' => [
                'name' => 'listId',
                'type' => Type::int(),
                'description' => 'The ID of the list that contains the list item.'
            ],
            'listHandle' => [
                'name' => 'listHandle',
                'type' => Type::string(),
                'description' => 'The handle of the list that contains the list item.'
            ],
            'listName' => [
                'name' => 'listName',
                'type' => Type::string(),
                'description' => 'The name of the list that contains the list item.'
            ],
            'maxLevels' => [
                'name' => 'maxLevels',
                'type' => Type::int(),
                'description' => 'The maximum number of levels that this list can have.'
            ],
            'propagate' => [
                'name' => 'propagate',
                'type' => Type::boolean(),
                'description' => 'If this option is enabled, all items will be distributed across all websites in this list. If this option is disabled, each item only belongs to the website where it was created.'
            ],
            'url' => [
                'name' => 'url',
                'type' => Type::string(),
                'description' => 'The url of the list item.'
            ],
            'target' => [
                'name' => 'target',
                'type' => Type::string(),
                'description' => 'The target of the list item.'
            ],
            'parent' => [
                'name' => 'parent',
                'type' => ListItemInterface::getType(),
                'description' => 'The list item’s parent.'
            ],
            'children' => [
                'name' => 'children',
                'args' => ListItemArguments::getArguments(),
                'type' => Type::listOf(ListItemInterface::getType()),
                'description' => 'The list item’s children.'
            ]
        ]);
    }
}
