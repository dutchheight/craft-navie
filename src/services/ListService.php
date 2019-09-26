<?php
/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\services;

use dutchheight\navie\Navie;
use dutchheight\navie\records\ListRecord;
use dutchheight\navie\models\ListModel;
use dutchheight\navie\events\ListEvent;
use dutchheight\navie\events\RegisterLinkTypesEvent;
use dutchheight\navie\elements\ListItem;

use Craft;
use craft\base\Component;
use craft\models\Structure;
use craft\models\FieldLayout;
use craft\db\Table;
use craft\db\Query;
use craft\elements\Entry;
use craft\elements\Category;
use craft\elements\Asset;
use craft\commerce\elements\Product;
use craft\events\SiteEvent;
use craft\helpers\ArrayHelper;
use craft\queue\jobs\ResaveElements;



/**
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
class ListService extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event ListEvent the event that is triggered before a navigation is saved.
     */
    const EVENT_BEFORE_SAVE_LIST = 'beforeSaveList';

    /**
     * @event ListEvent the event that is triggered after a navigation is saved.
     */
    const EVENT_AFTER_SAVE_LIST = 'afterSaveList';

    /**
     * @event ListEvent the event that is triggered before a navigation is deleted.
     */
    const EVENT_BEFORE_DELETE_LIST = 'beforeDeleteList';

    /**
     * @event ListEvent the event that is triggered after a navigation is deleted.
     */
    const EVENT_AFTER_DELETE_LIST = 'afterDeleteList';

    /**
     * @event RegisterGqlTypesEvent The event that is triggered when registering GraphQL types.
     *
     * ```php
     * use dutchheight\navie\events\RegisterLinkTypesEvent;
     * use dutchheight\navie\services\ListService;
     * use yii\base\Event;
     *
     * Event::on(ListService::class, ListService::EVENT_REGISTER_LINK_TYPES, function(RegisterLinkTypesEvent $event) {
     *     // Add my custom Link Type
     *     $event->types[] = [
     *          'custom' => [
     *              'label' => Craft::t('app', 'Custom'),
     *              'button' => Craft::t('app', 'Add a custom'),
     *              'instructions' => Craft::t('navie', 'Please choose a single custom to link to from this link item.'),
     *              'type' => Custom::class,
     *          ]
     *      ];
     * });
     * ```
     */
    const EVENT_REGISTER_LINK_TYPES = 'registerLinkTypes';

    // Properties
    // =========================================================================

    /**
     * @var dutchheight\navie\models\ListModel[]|null
     */
    private $_listsById;

    /**
     * @var bool
     */
    private $_fetchedAllLists = false;

    /**
     * @var dutchheight\navie\elements\ListItem[]|null
     */
    private $_listItems;

    /**
     * Return all navigations
     *
     * @return dutchheight\navie\models\ListModel[]
     */
    public function getAllLists(): array
    {
        if ($this->_fetchedAllLists) {
            return array_values($this->_listsById);
        }

        $this->_listsById = [];

        /** @var ListRecord[] $records */
        $records = ListRecord::find()
            ->orderBy(['name' => SORT_ASC])
            ->with('structure')
            ->all();

        foreach ($records as $record) {
            $this->_listsById[$record->id] = $this->_createListModelFromRecord($record);
        }

        $this->_fetchedAllLists = true;

        return array_values($this->_listsById);
    }

    /**
     * Returns all editable lists.
     *
     * @return dutchheight\navie\models\ListModel[]
     */
    public function getEditableLists(): array
    {
        $userSession = Craft::$app->getUser();
        return ArrayHelper::filterByValue($this->getAllLists(), function(ListModel $list) use ($userSession) {
            return $userSession->checkPermission('navie:lists:' . $list->uid);
        });
    }

    /**
     * Returns a list by its ID
     *
     * @param integer $listId
     * @return ListModel|null
     */
    public function getListById(int $listId)
    {
        if ($this->_listsById !== null && array_key_exists($listId, $this->_listsById)) {
            return $this->_listsById[$listId];
        }

        if ($this->_fetchedAllLists) {
            return null;
        }

        $record = ListRecord::find()
            ->where(['id' => $listId])
            ->with('structure')
            ->one();

        if (!$record) {
            return $this->_listsById[$listId] = null;
        }

        return $this->_listsById[$listId] = $this->_createListModelFromRecord($record);
    }

    /**
     * Returns a list by its UID.
     *
     * @param string $uid
     * @return ListModel|null
     */
    public function getListByUid(string $uid)
    {
        return ArrayHelper::firstWhere($this->getAllLists(), 'uid', $uid, true);
    }

    /**
     * Returns a list by its handle
     *
     * @param string $listHandle
     * @return dutchheight\navie\models\ListModel|null
     */
    public function getListByHandle(string $listHandle)
    {
        $record = ListRecord::find()
            ->with('structure')
            ->where(['handle' => $listHandle])
            ->one();

        if (!$record) {
            return null;
        }

        $list = $this->_createListModelFromRecord($record);
        return $this->_listsById[$list->id] = $list;
    }

    /**
     * Saves a list
     *
     * @param dutchheight\navie\models\ListModel $list
     * @param boolean $runValidation Whether the list should be validated
     * @return boolean
     * @throws \Throwable if reasons
     */
    public function saveList(ListModel $list, bool $runValidation = true): bool
    {
        $isNew = !$list->id;

        // Fire an 'beforeSaveList' event;
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_LIST)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_LIST, new ListEvent([
                'list' => $list,
                'isNew' => $isNew
            ]));
        }

        if ($runValidation && !$list->validate()) {
            Craft::info('Navigation not saved due to validation error.', __METHOD__);
            return false;
        }

        $record = new ListRecord();
        if (!$isNew) {
            $record = ListRecord::find()
                ->where(['id' => $list->id])
                ->one();
        }

        // If they've set maxLevels to 0 (don't ask why), then pretend like there are none.
        if ((int)$list->maxLevels === 0) {
            $list->maxLevels = null;
        }

        $record->name = $list->name;
        $record->handle = $list->handle;
        $record->maxLevels = $list->maxLevels;
        $record->propagate = $list->propagate;

        $transaction = Craft::$app->getDb()->beginTransaction();
        $structureService = Craft::$app->getStructures();

        try {
            // Create/update the structure
            if ($isNew) {
                $structure = new Structure();
            } else {
                $structure = $structureService->getStructureById($record->structureId);
            }

            $structure->maxLevels = $list->maxLevels;
            $structureService->saveStructure($structure);
            $record->structureId = $structure->id;
            $list->structureId = $structure->id;

            // Save the field layout
            /** @var FieldLayout $fieldLayout */
            $fieldLayout = $list->getFieldLayout();
            Craft::$app->getFields()->saveLayout($fieldLayout);
            $record->fieldLayoutId = $fieldLayout->id;
            $list->fieldLayoutId = $fieldLayout->id;

            // Save the list
            $record->save(false);

            // Now that we have a list ID, save it on the model
            if (!$list->id) {
                $list->id = $record->id;
            }

            // Might as well update our cache of the list while we have it.
            $this->_listsById[$list->id] = $list;

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Fire an 'afterSaveList' event;
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_LIST)) {
            $this->trigger(self::EVENT_AFTER_SAVE_LIST, new ListEvent([
                'list' => $list,
                'isNew' => $isNew
            ]));
        }

        return true;
    }

    /**
     * Deletes a list by its ID
     *
     * @param integer $listId the list's ID
     * @return boolean Whether the list was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteListById(int $listId): bool
    {
        if (!$listId) {
            return false;
        }

        $list = $this->getListById($listId);
        if (!$list) {
            return false;
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_LIST)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_LIST, new ListEvent([
                $list => $list
            ]));
        }

        $database = Craft::$app->getDb();
        $transaction = $database->beginTransaction();

        try {
            // Delete the field layout
            $fieldLayoutId = (new Query())
                ->select(['fieldLayoutId'])
                ->from(ListRecord::tableName())
                ->where(['id' => $list->id])
                ->scalar();

            if ($fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
            }

            // Delete all list items
            $listItems = ListItem::find()
                ->status(null)
                ->enabledForSite(false)
                ->listId($listId)
                ->all();

            foreach ($listItems as $listItem) {
                Craft::$app->getElements()->deleteElement($listItem);
            }

            // Delete the structure
            $database->createCommand()
                ->delete(Table::STRUCTURES, ['id' => $list->structureId])
                ->execute();

            // Delete the list
            $database->createCommand()
                ->delete(ListRecord::tableName(), ['id' => $list->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_LIST)) {
            $this->trigger(self::EVENT_AFTER_DELETE_LIST, new ListEvent([
                $list => $list
            ]));
        }

        return true;
    }

    // List Items
    // -------------------------------------------------------------------------

    /**
     * Returns a list item by its ID.
     *
     * @param integer $listItemId
     * @param integer|null $siteId
     * @return ListItem|null
     */
    public function getListItemById(int $listItemId, int $siteId = null)
    {
        if (!$listItemId) {
            return null;
        }

        $structureId = (new Query())
            ->select(['lists.structureId'])
            ->from(['{{%navie_listitems}} listitems'])
            ->innerJoin('{{%navie_lists}} lists', '[[lists.id]] = [[listitems.listId]]')
            ->where(['listitems.id' => $listItemId])
            ->scalar();

        // All list items are part of a structure
        if (!$structureId) {
            return null;
        }

        return ListItem::find()
            ->id($listItemId)
            ->structureId($structureId)
            ->siteId($siteId)
            ->status(null)
            ->enabledForSite(false)
            ->one();
    }

    public function getListItemsByListHandle(string $listHandle, int $siteId = null)
    {
        return ListItem::find()
            ->handle($listHandle)
            ->enabledForSite(true)
            ->all();
    }

    // List Item Types
    // =========================================================================
    public function getListItemTypes() {
        $elements = [
            'entry' => [
                'label' => Craft::t('app', 'Entries'),
                'button' => Craft::t('app', 'Add an entry'),
                'instructions' => Craft::t('navie', 'Please choose a single entry to link to from this link item.'),
                'type' => Entry::class,
            ],
            'url' => [
                'label' => Craft::t('app', 'URL'),
                'instructions' => Craft::t('navie', 'Please fill in a valid url.'),
                'type' => 'Url'
            ],
            'category' => [
                'label' => Craft::t('app', 'Categories'),
                'button' => Craft::t('app', 'Add a category'),
                'instructions' => Craft::t('navie', 'Please choose a single category to link to from this link item.'),
                'type' => Category::class,
            ],
            'asset' => [
                'label' => Craft::t('app', 'Assets'),
                'button' => Craft::t('app', 'Add an asset'),
                'instructions' => Craft::t('navie', 'Please choose a single asset to link to from this link item.'),
                'type' => Asset::class,
            ],
        ];

        if (class_exists(Product::class)) {
            $elements['product'] = [
                'label' => Craft::t('app', 'Products'),
                'button' => Craft::t('app', 'Add a product'),
                'instructions' => Craft::t('navie', 'Please choose a single product to link to from this link item.'),
                'type' => Product::class,
            ];
        }

        $event = new RegisterLinkTypesEvent([
            'types' => $elements,
        ]);

        $this->trigger(self::EVENT_REGISTER_LINK_TYPES, $event);

        return $event->types;
    }

    // Private Methods
    // =========================================================================

    /**
     * Creates a ListModel with attributes from a ListRecord.
     *
     * @param ListRecord $record
     * @return ListModel|null
     */
    private function _createListModelFromRecord(ListRecord $record = null)
    {
        if (!$record) {
            return null;
        }

        $list = new ListModel($record->toArray());

        if ($record->structure) {
            $list->maxLevels = $record->structure->maxLevels;
        }

        return $list;
    }
}
