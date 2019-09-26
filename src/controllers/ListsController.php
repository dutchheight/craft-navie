<?php

/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\controllers;

use dutchheight\navie\Navie;
use dutchheight\navie\models\ListModel;
use dutchheight\navie\elements\ListItem;
use dutchheight\navie\assets\cp\Bundle as NavieBundle;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\helpers\UrlHelper;

use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

/**
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
class ListsController extends BaseListItemsController
{
    // Public Methods
    // =========================================================================

    /**
     * @return yii\web\Response
     */
    public function actionListIndex(): Response
    {
        $this->requireAdmin();

        $variables['lists'] = Navie::$plugin->getLists()->getAllLists();
        $variables['selectedNavItem'] = 'lists';
        $variables['crumbs'] = [
            ['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::cpUrl('settings')]
        ];

        return $this->renderTemplate('navie/settings/lists', $variables);
    }

    /**
     * Edit a list
     *
     * @param int|null $listId The list's ID, if editing an existing list.
     * @param dutchheight\navie\models\ListModel|null $list The list being edited, if there were any validation errors.
     * @return yii\web\Response
     * @throws yii\web\NotFoundHttpException if the requested list cannot be found.
     */
    public function actionEditList(int $listId = null, ListModel $list = null): Response
    {
        $this->requireAdmin();

        $variables = [];

        // Breadcrumbs
        $variables['crumbs'] = [
            [ 'label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::cpUrl('settings') ],
            [ 'label' => Craft::t('navie', 'Lists'), 'url' => UrlHelper::cpUrl('navie/settings/lists') ]
        ];

        $variables['isBrandNew'] = false;

        if (!$list) {
            $list = $this->getListModel($listId);
        }

        if (!$list->id) {
            $variables['isBrandNew'] = true;
            $variables['title'] = Craft::t('navie', 'Create a new list');
        } else {
            $variables['title'] = $list->name;
        }

        $variables['tabs'] = [
            'settings' => [
                'label' => Craft::t('app', 'Settings'),
                'url' => '#list-settings'
            ],
            'fieldLayout' => [
                'label' => Craft::t('app', 'Field Layout'),
                'url' => '#list-fieldlayout'
            ]
        ];

        $variables['listId'] = $listId;
        $variables['list'] = $list;
        $variables['fieldLayout'] = $list->getFieldLayout();

        return $this->renderTemplate('navie/settings/lists/_edit', $variables);
    }

    /**
     * Saves a navigation
     *
     * @return Response|null
     */
    public function actionSaveList()
    {
        $this->requireAdmin();
        $this->requirePostRequest();

        $session = Craft::$app->getSession();

        $list = $this->getListModel();
        $this->populateListModel($list);

        if (!Navie::$plugin->getLists()->saveList($list)) {
            $session->setError(Craft::t('navie', 'Couldn’t save list.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'list' => $list
            ]);
            return null;
        }

        $session->setNotice(Craft::t('navie', 'List saved.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Deletes a navigation.
     *
     * @return yii\web\Response
     */
    public function actionDeleteList(): Response
    {
        $this->requireAdmin();
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        return $this->asJson(['success' => Navie::$plugin->getLists()->deleteListById(
            Craft::$app->getRequest()->getRequiredBodyParam('id')
        )]);
    }

    /**
     * Switches between link types.
     *
     * @return Response
     */
    public function actionSwitchLinkType(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $listItem = $this->getListItemModel();
        $this->populateListItemModel($listItem);

        $variables = [];
        $variables['listId'] = $listItem->listId;
        $variables['listItem'] = $listItem;
        $variables['listItemChanged'] = true;

        $this->prepEditListItemVariables($variables);

        $view = $this->getView();
        $fieldsHtml = $view->renderTemplate( 'navie/listitems/_fields', $variables);
        $headHtml = $view->getHeadHtml();
        $bodyHtml = $view->getBodyHtml();

        return $this->asJson(compact('fieldsHtml', 'headHtml', 'bodyHtml'));
    }

    // List Items
    // -------------------------------------------------------------------------

    /**
     * Displays the list item index page.
     *
     * @param string $listHandle
     * @return yii\web\Response
     */
    public function actionListItemIndex(string $listHandle = null): Response
    {
        $this->requirePermission('navie:lists');

        $lists = Navie::$plugin->getLists()->getEditableLists();

        if (empty($lists) && Craft::$app->getUser()->getIdentity()->admin) {
            return $this->redirect('navie/settings/lists/new');
        }

        return $this->renderTemplate('navie/listitems/_index', [
            'listHandle' => $listHandle,
            'lists' => $lists
        ]);
    }

    /**
     * Edit a list item
     *
     * @param string $listHandle The list's handle
     * @param int|null $listItemId The list item's ID, if editing an existing list item.
     * @param string|null $siteHandle The site handle, if specified.
     * @param ListItem|null $listItem The list item being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested navigation cannot be found.
     */
    public function actionEditListItem(string $listHandle = null, string $siteHandle = null, int $listItemId = null, ListItem $listItem = null) : Response
    {
        $variables = [
            'listHandle' => $listHandle,
            'listItemId' => $listItemId,
            'listItem' => $listItem
        ];

        if ($siteHandle !== null) {
            $variables['site'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$variables['site']) {
                throw new NotFoundHttpException(Craft::t('app', 'Invalid site handle: ' . $siteHandle));
            }
        }

        $this->prepEditListItemVariables($variables);

        /** @var Site $site */
        $site = $variables['site'];
        /** @var ListItem $listItem */
        $listItem = $variables['listItem'];

        $this->enforceEditListItemPermissions($listItem);

        // Parent Category selector variables
        // ---------------------------------------------------------------------
        if ((int)$variables['list']->maxLevels !== 1) {
            $variables['elementType'] = ListItem::class;

            // Define the parent options criteria
            $variables['parentOptionCriteria'] = [
                'siteId' => $site->id,
                'listId' => $variables['list']->id,
                'status' => null,
                'enabledForSite' => false
            ];

            if ($variables['list']->maxLevels) {
                $variables['parentOptionCriteria']['level'] = '< ' . $variables['list']->maxLevels;
            }

            if ($listItem->id !== null) {
                // Prevent the current list item, or any of its descendants, from being options
                $excludeIds = ListItem::find()
                    ->descendantOf($listItem)
                    ->status(null)
                    ->enabledForSite(false)
                    ->ids();

                $excludeIds[] = $listItem->id;
                $variables['parentOptionCriteria']['where'] = [
                    'not in',
                    'elements.id',
                    $excludeIds
                ];
            }

            // Get the initially selected parent
            $parentId = Craft::$app->getRequest()->getParam('parentId');
            if ($parentId === null && $listItem->id !== null) {
                $parentId = $listItem
                    ->getAncestors(1)
                    ->status(null)
                    ->enabledForSite(false)
                    ->ids();
            }

            if (is_array($parentId)) {
                $parentId = reset($parentId) ? : null;
            }

            if ($parentId) {
                $variables['parent'] = Navie::$plugin->getLists()->getListItemById($parentId, $site->id);
            }
        }

        if ($listItem->id === null) {
            $variables['title'] = Craft::t('navie', 'Create a new list item');
        } else {
            $variables['docTitle'] = $variables['title'] = $listItem->title;
        }

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Navie::$settings->pluginName,
                'url' => UrlHelper::cpUrl('navie')
            ],
            [
                'label' => Craft::t('navie', 'List items'),
                'url' => UrlHelper::cpUrl('navie/' . $variables['list']->handle)
            ]
        ];

        // Set the base CP edit URL
        $variables['baseCpEditUrl'] = "navie/{$variables['listHandle']}/{id}";
        // Set the "Continue Editing" URL
        $siteSegment = Craft::$app->getIsMultiSite() ? "/{$site->handle}" : '';
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'] . $siteSegment;
        // Set the "Save and add another" URL
        $variables['nextCategoryUrl'] = "navie/{$variables['listHandle']}/new{$siteSegment}";

        $this->getView()->registerAssetBundle(NavieBundle::class);

        return $this->renderTemplate('navie/listitems/_edit', $variables);
    }

    /**
     * Saves a navigation item
     *
     * @return Response|null
     * @throws ServerErrorHttpException
     */
    public function actionSaveListItem()
    {
        $this->requirePostRequest();

        $listItem = $this->getListItemModel();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        // Populate the navigation item with post data
        $this->populateListItemModel($listItem);

        if ($listItem->enabled && $listItem->enabledForSite) {
            $listItem->setScenario(Element::SCENARIO_LIVE);
        }

        if (!Craft::$app->getElements()->saveElement($listItem, true, false)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $listItem->getErrors()
                ]);
            }

            $session->setError(Craft::t('navie', 'Couldn’t save list item.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'listItem' => $listItem
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $listItem->id,
                'title' => $listItem->title,
                'status' => $listItem->status,
                'cpEditUrl' => $listItem->getCpEditUrl()
            ]);
        }

        $session->setNotice(Craft::t('navie', 'List item saved.'));
        return $this->redirectToPostedUrl($listItem);
    }


    /**
     * Deletes a list item.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested list item cannot be found
     */
    public function actionDeleteListItem()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $listItemId = $request->getRequiredBodyParam('listItemId');
        $siteId = $request->getBodyParam('siteId');
        $listItem = Navie::$plugin->getLists()->getListItemById($listItemId, $siteId);

        if (!$listItem) {
            throw new NotFoundHttpException('List item not found');
        }

        $this->requirePermission('navie:lists:delete:' . $listItem->getList()->uid);

        // Delete it
        if (!Craft::$app->getElements()->deleteElement($listItem, true)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('navie', 'Couldn’t delete list item.'));

            // Send the category back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'listItem' => $listItem
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('navie', 'List item deleted.'));

        return $this->redirectToPostedUrl($listItem);
    }

    // Private Methods
    // =========================================================================

    /**
     * Fetches or creates a list.
     *
     * @param integer|null $listId
     * @return dutchheight\navie\models\ListModel
     * @throws yii\web\NotFoundHttpException if the requested navigation cannot be found
     */
    private function getListModel($listId = null): ListModel
    {
        $request = Craft::$app->getRequest();
        $listId = $listId !== null ? $listId : $request->getBodyParam('listId');

        if ($listId) {
            $list = Navie::$plugin->getLists()->getListById($listId);

            if (!$list) {
                throw new NotFoundHttpException(Craft::t('navie', 'List not found.'));
            }
        } else {
            $list = new ListModel();
        }

        return $list;
    }

    /**
     * Fetches or creates a list item
     *
     * @return ListItem
     * @throws BadRequestHttpException if the requested list item doesn't exist
     * @throws NotFoundHttpException if the requested list item cannot be found
     */
    private function getListItemModel() : ListItem
    {
        $request = Craft::$app->getRequest();
        $listItemId = $request->getBodyParam('listItemId');
        $siteId = $request->getBodyParam('siteId');

        if ($listItemId) {
            $listItem = Navie::$plugin->getLists()->getListItemById($listItemId, $siteId);

            if (!$listItem) {
                throw new NotFoundHttpException(Craft::t('navie', 'List item not found.'));
            }
        } else {
            $listId = $request->getRequiredBodyParam('listId');
            $list = Navie::$plugin->getLists()->getListById($listId);

            if (!$list->id) {
                throw new BadRequestHttpException(Craft::t('navie', 'Invalid list id: {id}', ['id' => $listId]));
            }

            $listItem = new ListItem();
            $listItem->listId = $list->id;

            if ($siteId) {
                $listItem->siteId = $siteId;
            }
        }

        return $listItem;
    }

    /**
     * Populates a List model with post data
     *
     * @param ListModel $list
     */
    private function populateListModel(ListModel $list)
    {
        $request = Craft::$app->getRequest();

        // Main list settings
        $list->id = $request->getBodyParam('listId');
        $list->name = $request->getBodyParam('name');
        $list->handle = $request->getBodyParam('handle');
        $list->maxLevels = (int)$request->getBodyParam('maxLevels');
        $list->propagate = (bool)$request->getBodyParam('propagate');

        // List the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = ListItem::class;
        $list->setFieldLayout($fieldLayout);
    }

    /**
     * Populates a List item model with post data
     *
     * @param ListItem $listItem
     */
    private function populateListItemModel(ListItem $listItem)
    {
        $request = Craft::$app->getRequest();

        $listItem->listId = $request->getBodyParam('listId', $listItem->listId);
        $listItem->enabled = (bool)$request->getBodyParam('enabled', $listItem->enabled);
        $listItem->enabledForSite = (bool)$request->getBodyParam('enabledForSite', $listItem->enabledForSite);
        $listItem->title = $request->getBodyParam('title', $listItem->title);
        $listItem->url = $request->getBodyParam('url', $listItem->url);
        $listItem->type = $request->getBodyParam('linkType', $listItem->type);
        $listItem->target = $request->getBodyParam('target', $listItem->target);

        $elementId = $request->getBodyParam('elementId', $listItem->elementId);
        $listItem->elementId = $elementId ? (int)$elementId[0] : null;

        $fieldsLocation = Craft::$app->getRequest()->getParam('fieldsLocation', 'fields');
        $listItem->setFieldValuesFromRequest($fieldsLocation);

        // Parent
        $parentId = $request->getBodyParam('parentId');
        if ($parentId !== null) {
            if (is_array($parentId)) {
                $parentId = reset($parentId) ? : '';
            }

            $listItem->newParentId = $parentId ? : '';
        }
    }

    /**
     * Preps list item variables.
     *
     * @param array &$variables
     * @throws NotFoundHttpException if the requested list or list item cannot be found
     * @throws ForbiddenHttpException if the user is not permitted to edit content in the requested site
     */
    private function prepEditListItemVariables(array &$variables)
    {
        if (!empty($variables['listHandle'])) {
            $variables['list'] = Navie::$plugin->getLists()->getListByHandle($variables['listHandle']);
        } else {
            $variables['list'] = Navie::$plugin->getLists()->getListById($variables['listId']);
        }

        if (empty($variables['list'])) {
            throw new NotFoundHttpException(Craft::t('navie', 'List not found.'));
        }

        $variables['showSiteStatus'] = Craft::$app->getIsMultiSite();

        $sites = Craft::$app->getSites();

        if ($variables['showSiteStatus']) {
            $variables['siteIds'] = $sites->getEditableSiteIds();
        } else {
            $variables['siteIds'] = [$sites->getPrimarySite()->id];
        }

        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites');
        }

        if (empty($variables['site'])) {
            $variables['site'] = Craft::$app->getSites()->getCurrentSite();
            if (!in_array($variables['site']->id, $variables['siteIds'], false)) {
                $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }
            $site = $variables['site'];
        } else {
            $site = $variables['site'];
            if (!in_array($site->id, $variables['siteIds'], false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        if (empty($variables['listItem'])) {
            if (!empty($variables['listItemId'])) {
                $variables['listItem'] = Navie::$plugin->getLists()->getListItemById($variables['listItemId'], $site->id);

                if (!$variables['listItem']) {
                    throw new NotFoundHttpException(Craft::t('navie', 'List item not found.'));
                }
            } else {
                $variables['listItem'] = new ListItem();
                $variables['listItem']->listId = $variables['list']->id;
                $variables['listItem']->enabled = true;
                $variables['listItem']->siteId = $site->id;
            }
        }

        $variables['tabs'] = [];
        $variables['tabs'][] = [
            'label' => Craft::t('navie', 'Common'),
            'url' => '#tab-navie-common',
            'htmlId' => 'tab-navie-common',
        ];

        foreach ($variables['list']->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;
            $fields = $tab->getFields();
            $htmlId = $tab->getHtmlId();

            if ($variables['listItem']->hasErrors()) {
                foreach ($fields as $field) {
                    /** @var Field $field */
                    if ($hasErrors = $variables['listItem']->hasErrors($field->handle . '.*')) {
                        break;
                    }
                }
            }

            $variables['tabs'][] = [
                'label' => Craft::t('site', $tab->name),
                'htmlId' => $htmlId,
                'url' => '#' . $htmlId,
                'class' => $hasErrors ? 'error' : null,
                'fields' => $fields
            ];
        }

        $types = Navie::$plugin->getLists()->getListItemTypes();

        $variables['showLinkTypes'] = true;
        $variables['linkTypes'] = $types;

        foreach ($types as $key => $type) {
            if ($key === $variables['listItem']->type) {
                $variables['linkType'] = $type;
            }

            $variables['linkTypeOptions'][] = [
                'label' => $type['label'],
                'value' => $key
            ];
        }

        if (!isset($variables['listItemElement']) && !isset($variables['listItemChanged'])) {
            $variables['listItemElement'] = $variables['listItem']->getElement();
        } else {
            $variables['listItemElement'] = null;
            $variables['listItem']->url = '';
        }

        if (!isset($variables['linkType'])) {
            $variables['linkType'] = $types['entry'];
        }

        $this->getView()->registerJs('new Craft.LinkTypeSwitcher();');
    }
}
