# Navie plugin for Craft CMS 3.x

Simple navigation plugin for Craft CMS 3

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 3.0.0 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require dutchheight/craft-navie

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Navie.

## Navie Overview

-Insert text here-

## Configuring Navie

-Insert text here-

## Using Navie

### Display a navie list
`craft.navie.render(handle, options)` is used to display a list. You can provide styling options.

| Attribute | Type | Required | Description |
|:----------|:-----|:---------|:------------|
|handle|string|true|handle specified in the settings|
|options|object|false|For more info see [Available Options](#available-options)|

#### Available Options
```
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
```
{{ craft.navie.render('main', {
	ulChildAttributes: {
		'style': 'margin-top: 0.25rem;',
		'data-option': 'test',
	}
}) }}
```
---

## Navie Roadmap

Some things to do, and ideas for potential features:

* Release it

Brought to you by [Dutch Height](https://www.dutchheight.com)
