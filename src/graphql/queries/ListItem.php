<?php

namespace dutchheight\navie\graphql\queries;


use dutchheight\navie\graphql\interfaces\ListItem as ListItemInterface;
use dutchheight\navie\graphql\arguments\ListItem as ListItemArguments;
use dutchheight\navie\graphql\resolvers\ListItem as ListItemResolver;

use craft\gql\base\Query;
use GraphQL\Type\Definition\Type;

class ListItem extends Query
{
    public static function getQueries($checkToken = true): array
    {
        return [
            'listItems' => [
                'type' => Type::listOf(ListItemInterface::getType()),
                'args' => ListItemArguments::getArguments(),
                'resolve' => ListItemResolver::class . '::resolve',
                'description' => 'This query is used to query for list items'
            ]
        ];
    }
}