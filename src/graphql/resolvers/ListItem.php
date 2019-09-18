<?php

namespace dutchheight\navie\graphql\resolvers;

use craft\gql\base\ElementResolver;
use dutchheight\navie\elements\ListItem as ListItemElement;
use GraphQL\Type\Definition\ResolveInfo;

class ListItem extends ElementResolver 
{
    public static function prepareQuery($source, array $arguments, $fieldName = null)
    {
        if ($source === null) {
            $query = ListItemElement::find();
        } else {
            $query = $source->$fieldName;
        }

        if (is_array($query)) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        return $query;
    }
}