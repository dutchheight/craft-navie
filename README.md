# Navie plugin for Craft CMS 3.x

Navigation plugin for Craft CMS 3 with GraphQL and custom field support.
Navie allow's you to add custom fields to your navigation items.

![Screenshot](resources/img/plugin-logo.png)

![Add custom fields](resources/img/add-custom-fields.png)

Add custom fields


![Add link](resources/img/create-new-list-item.png)

Add link to list

![Use custom fields](resources/img/create-a-new-list-tem-custom-field.png)

Use custom fields

## Requirements

This plugin requires Craft CMS 3.0.0 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require dutchheight/craft-navie

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Navie.

4. Create a list

5. Render in template. See [Display a navie list](#display-a-navie-list)

## Navie Features
- Multiple menus/lists
- Multisite support
- GraphQL support
- Default linktypes (Entry, Url, Category, Asset)
- Add custom linktypes per menu by adding fields
- Rich API
- Editable plugin name
- Permissions (manage lists and edit settings)
- Permissions for each list (show, create, edit and delete)
- Eager loading (Entry, Category, Asset)

## Using Navie with Twig

### Display a navie list
`craft.navie.render(handle, options)` is used to display a list. You can provide styling options.

| Attribute | Type | Required | Description |
|:----------|:-----|:---------|:------------|
|handle|string|true|handle specified in the settings|
|options|object|false|For more info see [Available Options](#available-options)|

#### Available Options
```twig
{
    ulClass: 'class',
    ulAttributes: {
        'style': 'margin-top: 10;'
    },
    ulChildClass: 'class',
    ulChildAttributes: {
        'style': 'margin-top: 10;'
    },
    listClass: 'class',
    listAttributes: {
        'style': 'margin-top: 10;'
    },
    linkClass: 'class',
    linkAttributes: {
        'style': 'margin-top: 10;'
    },
    linkActiveClass: 'active'
}

```

#### Examples
Render a navie list:
```twig
{{ 
    craft.navie.render('main', {
        ulChildAttributes: {
            'style': 'margin-top: 0.25rem;',
            'data-option': 'custom data option',
        }
    }) 
}}
```
---

### Get navie list items raw
`craft.navie.items(criteria)` is used to get a list items. This allows you  [Check Craft Element queries for more info](https://docs.craftcms.com/v3/dev/element-queries/). Each item 

| Attribute | Type | Required | Description |
|:----------|:-----|:---------|:------------|
|criteria|array|false|Craft entry criteria|


#### Examples
Get all items
```twig
{% 
    set items = craft.navie.items()
        .list('main')
        .all()
%}
```

Get items limited by level
```twig
{% 
    set items = craft.navie.items()
        .list('main')
        .level(1)
%}
```

Get all items Eager loaded
```twig
{%
    set items = craft.navie.items()
        .list('main')
        .with(['entry', 'category', 'asset'])
        .all() 
%}
```

Now you are able to use `items.getElement().title` without extra queries. This is also posible without eager loading but at a peformance cost.

---

## Using Navie with GraphQL
Navie has built-in support for accessing lists and items via GraphQL use native [Craft CMS 3.3 GraphQL](https://docs.craftcms.com/v3/graphql.html).
```graphql
{
  listItems(list: "main", level: 1) {
    id,
    title,
    url,
    target,
    children {
      id,
      url,
      title,
      target,
      ... on main { # Main is list handle
        classes
      }
    }
  }
}
```
## Using Navie with CraftQL Plugin
Navie has built-in support for accessing lists and items via GraphQL use the [CraftQL plugin](https://github.com/markhuot/craftql).

You can retrieve all the lists with their fields:

```graphql
{
  navie {
    lists {
      id,
      structureId,
      fieldLayoutId,
      maxLevels,
      name,
      handle,
      uid,
      propagate
    }
  }
}
```
You can also retrieve all list items with their fields and even the custom fields on the list
```graphql
{
  navie {
    items(list: main) {
      title,
      url,
      target,
      children {
        title,
        url,
        target
        ... on MainList {
          classes
        }
      },
      ... on MainList {
        classes
      }
    }
  }
}
```
---

## Navie Roadmap

Some things to do, and ideas for potential features:

* Settings for available link types
* Support Project.yaml
* Custom link type registration

Brought to you by [Dutch Height](https://www.dutchheight.com)
