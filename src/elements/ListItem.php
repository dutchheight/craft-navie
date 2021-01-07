<?php

/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\elements;

use Craft;
use craft\base\Element;
use craft\controllers\ElementIndexesController;
use craft\db\Query;

use craft\elements\actions\DeepDuplicate;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Edit;
use craft\elements\actions\NewChild;
use craft\elements\actions\SetStatus;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use dutchheight\navie\elements\db\ListItemQuery;
use dutchheight\navie\models\ListModel;
use dutchheight\navie\Navie;
use dutchheight\navie\records\ListItemRecord;
use yii\base\InvalidConfigException;

class ListItem extends Element
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('navie', 'List Item');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): string
    {
        return 'listitem';
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles() : bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function find(): ElementQueryInterface
    {
        return new ListItemQuery(static::class);
    }

    /**
     * Returns true if the list item or any of it's children is active.
     *
     * @return bool
     */
    public function getActive()
    {
        if ($this->_active) {
            return true;
        }

        $url = str_replace(UrlHelper::siteUrl(), '', $this->getUrl());

        // if external url, we don't have to check if the link is active.
        if (strpos($url, '://')) {
            return $this->_active = false;
        }

        $url = $this->removeSlashes($url);

        if ($url === Craft::$app->getRequest()->getPathInfo()) {
            return $this->_active = true;
        }

        return false;
    }

    public function setActive($active)
    {
        $this->_active = $active;
    }

    public function isChildActive() {
        if (!$this->hasDescendants) {
            return false;
        }

        $descendants = $this->getDescendants()->all();

        foreach ($descendants as $descendant) {
            if ($descendant->getActive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        $list = $this->getList();

        if ($list && !$list->propagate) {
            return [$this->siteId];
        }

        return Craft::$app->getSites()->getEditableSiteIds();
    }

    /**
     * @inheritdoc
     */
    public function getUrl() {
        if ($this->type === 'url') {
            return Craft::parseEnv($this->_url);
        } else {
            if ($this->_linkedElementUrl !== null) {
                return UrlHelper::siteUrl($this->_linkedElementUrl !== '__home__' ? $this->_linkedElementUrl : '');
            } else {
                $element = $this->getElement();

                if ($element) {
                    return $element->url;
                }
            }
        }

        return null;
    }

    public static function gqlTypeNameByContext($context): string
    {
        /** @var ListModel $context */
        return $context->handle . '_List';
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [];
        $lists = Navie::$plugin->getLists()->getEditableLists();

        $sources[] = ['heading' => Craft::t('navie', 'Lists')];

        foreach ($lists as $list) {
            $sources[] = [
                'key' => 'list:' . $list->uid,
                'label' => Craft::t('site', $list->name),
                'data' => ['handle' => $list->handle],
                'criteria' => ['listId' => $list->id],
                'structureId' => $list->structureId,
                'structureEditable' => Craft::$app->getUser()->checkPermission('navie:lists:edit:' . $list->uid)
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        // Get the selected site
        $controller = Craft::$app->controller;
        $currentUser = Craft::$app->getUser();
        $elementsService = Craft::$app->getElements();
        $isMultiSite = Craft::$app->getIsMultiSite();
        $actions = [];

        if ($controller instanceof ElementIndexesController) {
            /** @var ElementQuery $elementQuery */
            $elementQuery = $controller->getElementQuery();
        } else {
            $elementQuery = null;
        }

        $site = $elementQuery && $elementQuery->siteId
            ? Craft::$app->getSites()->getSiteById($elementQuery->siteId)
            : Craft::$app->getSites()->getCurrentSite();

        // Get the group we need to check permissions on
        if (preg_match('/^list:(\d+)$/', $source, $matches)) {
            $list = Navie::$plugin->getLists()->getListById((int)$matches[1]);
        } else if (preg_match('/^list:(.+)$/', $source, $matches)) {
            $list = Navie::$plugin->getLists()->getListByUid($matches[1]);
        }

        if (!empty($list) && $currentUser->checkPermission('navie:lists:' . $list->uid)) {
            // Edit
            if ($currentUser->checkPermission('navie:lists:edit:' . $list->uid)) {
                // Set status
                $actions[] = [
                    'type' => SetStatus::class,
                    'allowDisabledForSite' => ($isMultiSite && $list->propagate)
                ];

                $actions[] = $elementsService->createAction([
                    'type' => Edit::class,
                    'label' => Craft::t('navie', 'Edit list item')
                ]);
            }

            if ($currentUser->checkPermission('navie:lists:create:' . $list->uid)) {
                // New Child
                $structure = Craft::$app->getStructures()->getStructureById($list->structureId);

                if ($structure) {
                    $newChildUrl = 'navie/' . $list->handle . '/new';

                    if ($isMultiSite) {
                        $newChildUrl .= '/' . $site->handle;
                    }

                    $actions[] = $elementsService->createAction([
                        'type' => NewChild::class,
                        'label' => Craft::t('navie', 'Create a new child list item'),
                        'maxLevels' => $structure->maxLevels,
                        'newChildUrl' => $newChildUrl,
                    ]);
                }

                // Duplicate
                $actions[] = Duplicate::class;

                if ($list->maxLevels != 1) {
                    $actions[] = DeepDuplicate::class;
                }
            }

            if ($currentUser->checkPermission('navie:lists:delete:' . $list->uid)) {
                // Delete
                $actions[] = $elementsService->createAction([
                    'type' => Delete::class,
                    'confirmationMessage' => Craft::t('navie', 'Are you sure you want to delete the selected list items?'),
                    'successMessage' => Craft::t('navie', 'List items deleted.'),
                ]);
            }
        }

        return $actions;
    }

    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        $types = ['entry', 'category', 'asset'];

        if (!in_array($handle, $types)) {
            return parent::eagerLoadingMap($sourceElements, $handle);
        }

        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        $map = (new Query())
            ->select(['id as source', 'elementId as target'])
            ->from(['{{%navie_listitems}}'])
            ->where(['id' => $sourceElementIds])
            ->andWhere(['type' => $handle])
            ->andWhere(['not', ['elementId' => null]])
            ->all();

        switch ($handle) {
            case 'entry':
                $elementType = Entry::class;
                break;
            case 'category':
                $elementType = Category::class;
                break;
            case 'asset':
                $elementType = Asset::class;
        }

        return [
            'elementType' => $elementType,
            'map' => $map
        ];
    }

    public function setEagerLoadedElements(string $handle, array $elements)
    {
        $types = ['entry', 'category', 'asset'];

        if (!in_array($handle, $types) || !count($elements)) {
            return parent::setEagerLoadedElements($handle, $elements);
        }

        $element = $elements[0];
        $this->setElement($element);
    }

    protected function removeSlashes($str): string
    {
        return trim($str, '/');
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes() : array
    {
        return [
            'title' => \Craft::t('app', 'Title'),
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    // Public Properties
    // =========================================================================

    /**
     * @var int ID
     */
    public $id;

    /**
     * @var int List ID
     */
    public $listId;

    /**
     * @var int|null New parent ID
     */
    public $newParentId;

    /**
     * @var int Site ID
     */
    public $siteId;

    /**
     * @var int Element ID
     */
    public $elementId;

    /**
     * @var string Type of the list item
     */
    public $type;

    /**
     * @var string HTML target attribute
     */
    public $target;

    /**
     * @var string Url of this list item
     */
    private $_url;

    /**
     * @var bool|null
     * @see _hasNewParent()
     */
    private $_hasNewParent;

    /**
     * @var Element
     */
    private $_element;

    /**
     * @var string
     */
    private $_linkedElementUrl;

    /**
     * @var bool
     */
    private $_active;

    // Public Methods
    // =========================================================================

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['listId', 'newParentId'], 'number', 'integerOnly' => true];
        return $rules;
    }

    public function getElement()
    {
        if ($this->_element !== null) {
            return $this->_element;
        }

        if ($this->elementId !== null) {
            return $this->_element = Craft::$app->elements->getElementById($this->elementId, null, $this->siteId);
        }

        return null;
    }

    public function setUrl($url)
    {
        $this->_url = $url;
    }

    public function setElement($element)
    {
        $this->_element = $element;
    }

    public function setLinkedElementUrl($linkedElementUrl)
    {
        $this->_linkedElementUrl = $linkedElementUrl;
    }

    public function getIsEditable(): bool
    {
        return Craft::$app->user->checkPermission('navie:lists:edit' . $this->getList()->uid);
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): string
    {
        $list = $this->getList();

        $path = 'navie/' . $list->handle . '/' . $this->id;

        $params = [];
        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

     /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        return parent::getFieldLayout() ?? $this->getList()->getFieldLayout();
    }

    /**
     * Returns the list item's list
     *
     * @return ListModel
     * @throws InvalidConfigException if listId is missing or invalid
     */
    public function getList(): ListModel
    {
        if ($this->listId === null) {
            throw new InvalidConfigException(Craft::t('navie', 'List item is missing its list ID'));
        }

        $list = Navie::$plugin->getLists()->getListById($this->listId);

        if (!$list) {
            throw new InvalidConfigException(Craft::t('navie', 'Invalid list ID: ' . $this->listId));
        }

        return $list;
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Title'),
                'siteId' => $this->siteId,
                'id' => 'title',
                'name' => 'title',
                'value' => $this->title,
                'errors' => $this->getErrors('title'),
                'first' => true,
                'autofocus' => true,
                'required' => true
            ]
        ]);

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getGqlTypeName(): string
    {
        return self::gqlTypeNameByContext($this->getList());
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function beforeSave(bool $isNew) : bool
    {
        if ($this->_hasNewParent()) {
            if ($this->newParentId) {
                $parent = Navie::$plugin->getLists()->getListItemById($this->newParentId, $this->siteId);

                if (!$parent) {
                    throw new Exception('Invalid list item ID: ' . $this->newParentId);
                }
            } else {
                $parent = null;
            }
            $this->setParent($parent);
        }
        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        $list = $this->getList();

        // Get the List item record
        if (!$isNew) {
            $record = ListItemRecord::findOne($this->id);
            if (!$record) {
                throw new Exception('Invalid list item ID: ' . $this->id);
            }
        } else {
            $record = new ListItemRecord();
            $record->id = $this->id;
        }

        $record->listId = $this->listId;
        $record->elementId = null;
        $record->url = $this->url;
        $record->type = $this->type;
        $record->target = $this->target;

        if ($this->type !== 'url') {
            $record->elementId = $this->elementId;
            $record->url = null;
        }

        $record->save(false);

        // Has the parent changed?
        if ($this->_hasNewParent()) {
            if (!$this->newParentId) {
                Craft::$app->getStructures()->appendToRoot($list->structureId, $this);
            } else {
                Craft::$app->getStructures()->append($list->structureId, $this, $this->getParent());
            }
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        try {
            $conditions = [];
            $conditions['id'] = $this->id;
            $conditions['listId'] = $this->listId;

            ListItemRecord::deleteAll($conditions);
        } catch (\Exception $e) {
            throw $e;
        }

        parent::afterDelete();
    }

    /**
     * @inheritdoc
     */
    public function setFieldValuesFromRequest(string $paramNamespace = '')
    {
        $this->setFieldParamNamespace($paramNamespace);
        $values = Craft::$app->getRequest()->getBodyParam($paramNamespace, []);

        foreach ($this->fieldLayoutFields() as $field) {
            // Do we have any post data for this field?
            if (isset($values[$field->handle])) {
                $value = $values[$field->handle];
            } else if (!empty($this->_fieldParamNamePrefix) && UploadedFile::getInstancesByName($this->_fieldParamNamePrefix . '.' . $field->handle)) {
                // A file was uploaded for this field
                $value = null;
            } else {
                continue;
            }

            $this->setFieldValue($field->handle, $value);

            // Normalize it now in case the system language changes later
            $this->normalizeFieldValue($field->handle);
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns whether the navigation item has been assigned a new parent entry.
     *
     * @return bool
     * @see beforeSave()
     * @see afterSave()
     */
    private function _hasNewParent() : bool
    {
        if ($this->_hasNewParent !== null) {
            return $this->_hasNewParent;
        }
        return $this->_hasNewParent = $this->_checkForNewParent();
    }

    /**
     * Checks if a navigation item was submitted with a new parent navigation item selected.
     *
     * @return bool
     */
    private function _checkForNewParent() : bool
    {
        // Is it a brand new category?
        if ($this->id === null) {
            return true;
        }

        // Was a new parent ID actually submitted?
        if ($this->newParentId === null) {
            return false;
        }

        // Is it set to the top level now, but it hadn't been before?
        if (!$this->newParentId && $this->level != 1) {
            return true;
        }

        // Is it set to be under a parent now, but didn't have one before?
        if ($this->newParentId && $this->level == 1) {
            return true;
        }

        // Is the newParentId set to a different navigation item ID than its previous parent?
        $oldParentQuery = self::find();
        $oldParentQuery->ancestorOf($this);
        $oldParentQuery->ancestorDist(1);
        $oldParentQuery->status(null);
        $oldParentQuery->siteId($this->siteId);
        $oldParentQuery->select('elements.id');
        $oldParentId = $oldParentQuery->scalar();

        return $this->newParentId != $oldParentId;
    }
}
