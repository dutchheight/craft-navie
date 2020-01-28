<?php

namespace dutchheight\navie\graphql\types\generators;

use craft\base\Field;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use dutchheight\navie\elements\ListItem as ListItemElement;
use dutchheight\navie\graphql\interfaces\ListItem as ListItemInterface;

use dutchheight\navie\graphql\types\ListItem;
use dutchheight\navie\models\ListModel;
use dutchheight\navie\Navie;

class ListItemType implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $lists = Navie::$plugin->getLists()->getAllLists();
        $gqlTypes = [];

        foreach ($lists as $list) {
            /** @var ListModel $list */
            $typeName = ListItemElement::gqlTypeNameByContext($list);

            $fields = $list->getFields();
            $contentFieldGqlTypes = [];

            /** @var Field $field */
            foreach ($fields as $field) {
                $contentFieldGqlTypes[$field->handle] = $field->getContentGqlType();
            }

            $listFields = array_merge(ListItemInterface::getFieldDefinitions(), $contentFieldGqlTypes);

            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ListItem([
                'name' => $typeName,
                'fields' => function () use ($listFields) {
                    return $listFields;
                }
            ]));
        }

        return $gqlTypes;
    }
}
