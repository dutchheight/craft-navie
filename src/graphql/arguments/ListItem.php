<?php

namespace dutchheight\navie\graphql\arguments;

use craft\gql\base\StructureElementArguments;
use GraphQL\Type\Definition\Type;

class ListItem extends StructureElementArguments
{
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'list' => [
                'name' => 'list',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the list the list item belong to per the list’s handles.'
            ],
            'listId' => [
                'name' => 'listId',
                'type' => Type::int(),
                'description' => 'Narrows the query results based on the list the list item belong to, per the lists’ IDs.'
            ]
        ]);
    }
}