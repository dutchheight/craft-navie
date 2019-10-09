<?php
/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie;

use dutchheight\navie\base\PluginTrait;
use dutchheight\navie\variables\NavieVariable;
use dutchheight\navie\models\Settings;
use dutchheight\navie\elements\ListItem as ListItemElement;
use dutchheight\navie\graphql\queries\ListItem as ListItemQuery;
use dutchheight\navie\graphql\interfaces\ListItem as ListItemInterface;

use Craft;
use craft\base\Plugin;
use craft\helpers\UrlHelper;
use craft\services\UserPermissions;
use craft\services\Gql;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\SiteEvent;
use craft\queue\jobs\ResaveElements;
use craft\services\Sites;
use yii\base\Event;

/**
 * Class Navie
 *
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 *
 * @property  NavieService $navieService
 */
class Navie extends Plugin
{
    // Traits
    // =========================================================================
    use PluginTrait;

    // Constants
    // =========================================================================

    const FIELD_GROUP_NAME = 'Navie';
    const FIELD_ENTRY_NAME = 'Navie Entry';
    const FIELD_CATEGORY_NAME = 'Navie Category';
    const FIELD_URL_NAME = 'Navie URL';

    const FIELD_ENTRY_HANDLE = 'navieEntry';
    const FIELD_CATEGORY_HANDLE = 'navieCategory';
    const FIELD_URL_HANDLE = 'navieCustomUrl';

    // Public Properties
    // =========================================================================

    /**
     * @var boolean
     */
    public $hasCpSettings = true;

    /**
     * @var boolean
     */
    public $hasCpSection = true;

    /**
     * @var string
     */
    public $schemaVersion = '1.1.2';

    /**
     * @var bool
     */
    public static $craft31 = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;
        self::$settings = $this->getSettings();
        self::$craft31 = version_compare(Craft::$app->getVersion(), '3.1', '>=');

        $this->name = self::$settings->pluginName;

        $this->_registerComponents();
        $this->_registerEventListeners();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('navie/settings'));
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate('navie/settings/general', [
            'settings' => $this->getSettings()
        ]);
    }

    private function _registerEventListeners()
    {
        $request = Craft::$app->getRequest();

        // Install event listeners that are needed every request
        $this->_registerGlobalEventListeners();

        if (version_compare(Craft::$app->getVersion(), '3.3', '>=')) {
            $this->_registerGraphql();
        }

        // Install only for non-console Control Panel requests
        if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
            $this->_registerCpEventListeners();
        }
    }

    private function _registerGlobalEventListeners()
    {
        // Handler: CraftVariable::EVENT_INIT
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('navie', NavieVariable::class);
            }
        );
    }

    private function _registerGraphql()
    {
        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_TYPES,
            function(RegisterGqlTypesEvent $event) {
                $event->types[] = ListItemInterface::class;
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function(RegisterGqlQueriesEvent $event) {
                $event->queries = array_merge($event->queries, ListItemQuery::getQueries());
             }
        );
    }

    private function _registerCpEventListeners()
    {
        // Handler: UrlManager::EVENT_REGISTER_CP_URL_RULES
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules = array_merge(
                    $event->rules,
                    $this->_registerCpRoutes()
                );
            }
        );
        // Handler: UserPermissions::EVENT_REGISTER_PERMISSIONS
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                // Register our custom permissions
                $event->permissions[Craft::t('navie', 'Navie')] = $this->_registerCpPermissions();
            }
        );

        // Resave all lists if the list has progagate enabled
        Event::on(Sites::class, Sites::EVENT_AFTER_SAVE_SITE, function (SiteEvent $event) {
            if (!$event->isValid) return;

            $ids = [];

            foreach ($this->getLists()->getAllLists() as $list) {
                if ($list->propagate) {
                    foreach ($this->getLists()->getListItemsByListHandle($list->handle) as $listItem) {
                        $ids[] = $listItem->id;
                    }
                }
            }

            if (count($ids)) {
                Craft::$app->getQueue()->push(new ResaveElements([
                    'elementType' => ListItemElement::class,
                    'criteria' => [
                        'id' => $ids,
                        'siteId' => $event->oldPrimarySiteId
                    ]
                ]));
            }
        });
    }

    /**
     * Returns the Control Panel user permissions.
     *
     * @return array
     */
    private function _registerCpPermissions()
    {
        $lists = Navie::$plugin->getLists()->getAllLists();
        $permissions = [];

        foreach ($lists as $list) {
            $uid = $list->uid;
            $permissions["navie:lists:${uid}"] = [
                'label' => Craft::t('navie', 'List - {list}', ['list' => $list->name]),
                'nested' => [
                    "navie:lists:create:${uid}" => [
                        'label' => Craft::t('navie', 'Create list items')
                    ],
                    "navie:lists:edit:${uid}" => [
                        'label' => Craft::t('navie', 'Edit list items')
                    ],
                    "navie:lists:delete:${uid}" => [
                        'label' => Craft::t('navie', 'Delete list items')
                    ]
                ]
            ];
        }

        return [
            'navie:lists' => [
                'label' => Craft::t('navie', 'Manage lists'),
                'nested' => $permissions
            ],
            'navie:settings' => [
                'label' => Craft::t('navie', 'Edit Settings')
            ]
        ];
    }

    /**
     * Return the Control Panel routes
     *
     * @return array
     */
    private function _registerCpRoutes() : array
    {
        return [
            'navie' => 'navie/lists/list-item-index',
            'navie/settings' => 'navie/settings/plugin-settings',
            'navie/settings/lists' => 'navie/lists/list-index',
            'navie/settings/lists/new' => 'navie/lists/edit-list',
            'navie/settings/lists/<listId:\d+>' => 'navie/lists/edit-list',

            'navie/<listHandle:{handle}>' => 'navie/lists/list-item-index',
            'navie/<listHandle:{handle}>/new' => 'navie/lists/edit-list-item',
            'navie/<listHandle:{handle}>/new/<siteHandle:{handle}>' => 'navie/lists/edit-list-item',
            'navie/<listHandle:{handle}>/<listItemId:\d+>' => 'navie/lists/edit-list-item',
            'navie/<listHandle:{handle}>/<listItemId:\d+>/<siteHandle:{handle}>' => 'navie/lists/edit-list-item',
            'navie/<siteHandle:{handle}>' => 'navie/lists/list-item-index',
        ];
    }
}
