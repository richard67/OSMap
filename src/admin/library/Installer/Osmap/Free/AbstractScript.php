<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2021 Joomlashack.com. All rights reserved.
 * @license   https://www.gnu.org/licenses/gpl.html GNU/GPL
 *
 * This file is part of OSMap.
 *
 * OSMap is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * OSMap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OSMap.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Alledia\Installer\OSMap\Free;

use Alledia\Installer\OSMap\XmapConverter;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die();

class AbstractScript extends \Alledia\Installer\AbstractScript
{
    /**
     * @var bool
     */
    protected $isXmapDataFound = false;

    /**
     * @inheritDoc
     */
    public function postFlight($type, $parent)
    {
        parent::postFlight($type, $parent);

        if ($type == 'uninstall') {
            return;
        }

        // Check if XMap is installed, to start a migration
        $xmapConverter = new XmapConverter();

        // This attribute will be used by the custom template to display the option to migrate legacy sitemaps
        $this->isXmapDataFound = $this->tableExists('#__xmap_sitemap') && $xmapConverter->checkXmapDataExists();

        // If Xmap plugins are still available, and we don't have the OSMap plugins yet,
        // save Xmap plugins params to re-apply after install OSMap plugins
        $xmapConverter->saveXmapPluginParamsIfExists();

        // Load Alledia Framework
        require_once JPATH_ADMINISTRATOR . '/components/com_osmap/include.php';

        switch ($type) {
            case 'install':
            case 'discover_install':
                // New installation [discover_install|install]
                $this->createDefaultSitemap();

                $app = Factory::getApplication();

                $link = HTMLHelper::_(
                    'link',
                    'index.php?option=com_plugins&view=plugins&filter.search=OSMap',
                    Text::_('COM_OSMAP_INSTALLER_PLUGINS_PAGE')
                );
                $app->enqueueMessage(Text::sprintf('COM_OSMAP_INSTALLER_GOTOPLUGINS', $link), 'warning');
                break;

            case 'update':
                $this->migrateLegacySitemaps();
                $this->fixXMLMenus();
                $this->clearLanguageFiles();
                break;


        }

        $xmapConverter->moveXmapPluginsParamsToOSMapPlugins();
        $this->checkDbScheme();
    }

    /**
     * Creates a default sitemap if no one is found.
     *
     * @return void
     */
    protected function createDefaultSitemap()
    {
        $db = Factory::getDbo();

        // Check if we have any sitemaps, otherwise let's create a default one
        $query      = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__osmap_sitemaps');
        $noSitemaps = ((int)$db->setQuery($query)->loadResult()) === 0;

        if ($noSitemaps) {
            // Get all menus

            // Search for home menu and language if exists
            $subQuery = $db->getQuery(true)
                ->select('b.menutype, b.home, b.language, l.image, l.sef, l.title_native')
                ->from('#__menu AS b')
                ->leftJoin('#__languages AS l ON l.lang_code = b.language')
                ->where('b.home != 0')
                ->where('(b.client_id = 0 OR b.client_id IS NULL)');

            // Get all menu types with optional home menu and language
            $query = $db->getQuery(true)
                ->select('a.id, a.asset_id, a.menutype, a.title, a.description, a.client_id')
                ->select('c.home, c.language, c.image, c.sef, c.title_native')
                ->from('#__menu_types AS a')
                ->leftJoin('(' . $subQuery . ') c ON c.menutype = a.menutype')
                ->order('a.id');

            $db->setQuery($query);

            $menus = $db->loadObjectList();

            if (!empty($menus)) {
                $data = [
                    'name'       => 'Default Sitemap',
                    'is_default' => 1,
                    'published'  => 1
                ];

                // Create the sitemap
                Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_osmap/tables');
                $row = Table::getInstance('Sitemap', 'OSMapTable');
                $row->save($data);

                $i = 0;
                foreach ($menus as $menu) {
                    $menuTypeId = $this->getMenuTypeId($menu->menutype);

                    $query = $db->getQuery(true)
                        ->set('sitemap_id = ' . $db->quote($row->id))
                        ->set('menutype_id = ' . $db->quote($menuTypeId))
                        ->set('priority = ' . $db->quote('0.5'))
                        ->set('changefreq = ' . $db->quote('weekly'))
                        ->set('ordering = ' . $db->quote($i++))
                        ->insert('#__osmap_sitemap_menus');
                    $db->setQuery($query)->execute();
                }
            }
        }
    }

    /**
     * In case we are updating from a legacy version, cleanup
     * the new tables to get a clean start for the data migration
     *
     * @return void
     */
    protected function cleanupDatabase()
    {
        $db = Factory::getDbo();

        $db->setQuery('DELETE FROM ' . $db->quoteName('#__osmap_items_settings'))->execute();
        $db->setQuery('DELETE FROM ' . $db->quoteName('#__osmap_sitemap_menus'))->execute();
        $db->setQuery('DELETE FROM ' . $db->quoteName('#__osmap_sitemaps'))->execute();
    }

    /**
     * Check if there are sitemaps in the old table. After migrate, remove
     * the table.
     *
     * @return void
     * @throws \Exception
     */
    protected function migrateLegacySitemaps()
    {
        $db = Factory::getDbo();

        if ($this->tableExists('#__osmap_sitemap')) {
            try {
                $db->transactionStart();

                // For the migration, as we only have new tables, make sure to have a clean start
                $this->cleanupDatabase();

                // Get legacy sitemaps
                $query    = $db->getQuery(true)
                    ->select([
                        'id',
                        'title',
                        'is_default',
                        'state',
                        'created',
                        'selections',
                        'excluded_items'
                    ])
                    ->from('#__osmap_sitemap');
                $sitemaps = $db->setQuery($query)->loadObjectList();

                // Move the legacy sitemaps to the new table
                if (!empty($sitemaps)) {
                    foreach ($sitemaps as $sitemap) {
                        // Make sure we have a creation date
                        if ($sitemap->created === $db->getNullDate()) {
                            $sitemap->created = Factory::getDate()->toSql();
                        }

                        $query = $db->getQuery(true)
                            ->insert('#__osmap_sitemaps')
                            ->set([
                                'id = ' . $db->quote($sitemap->id),
                                'name = ' . $db->quote($sitemap->title),
                                'is_default = ' . $db->quote($sitemap->is_default),
                                'published = ' . $db->quote($sitemap->state),
                                'created_on = ' . $db->quote($sitemap->created)
                            ]);
                        $db->setQuery($query)->execute();

                        // Add the selected menus to the correct table
                        $menus = json_decode($sitemap->selections, true);

                        if (!empty($menus)) {
                            foreach ($menus as $menuType => $menu) {
                                $menuTypeId = $this->getMenuTypeId($menuType);

                                // Check if the menutype still exists
                                if (!empty($menuTypeId)) {
                                    // Convert the selection of menus into a row
                                    $query = $db->getQuery(true)
                                        ->insert('#__osmap_sitemap_menus')
                                        ->columns([
                                            'sitemap_id',
                                            'menutype_id',
                                            'priority',
                                            'changefreq',
                                            'ordering'
                                        ])
                                        ->values(
                                            implode(
                                                ',',
                                                [
                                                    $db->quote($sitemap->id),
                                                    $db->quote($menuTypeId),
                                                    $db->quote($menu['priority']),
                                                    $db->quote($menu['changefreq']),
                                                    $db->quote($menu['ordering'])
                                                ]
                                            )
                                        );
                                    $db->setQuery($query)->execute();
                                }
                            }
                        }

                        if (!empty($sitemap->excluded_items)) {
                            // Convert settings about excluded items
                            $excludedItems = json_decode($sitemap->excluded_items, true);

                            if (!empty($excludedItems)) {
                                foreach ($excludedItems as $item) {
                                    $uid = $this->convertItemUID($item[0]);

                                    // Check if the item was already registered
                                    $query = $db->getQuery(true)
                                        ->select('COUNT(*)')
                                        ->from('#__osmap_items_settings')
                                        ->where([
                                            'sitemap_id = ' . $db->quote($sitemap->id),
                                            'uid = ' . $db->quote($uid)
                                        ]);
                                    $count = $db->setQuery($query)->loadResult();

                                    if ($count == 0) {
                                        // Insert the settings
                                        $query = $db->getQuery(true)
                                            ->insert('#__osmap_items_settings')
                                            ->columns([
                                                'sitemap_id',
                                                'uid',
                                                'published',
                                                'changefreq',
                                                'priority'
                                            ])
                                            ->values(
                                                implode(
                                                    ',',
                                                    [
                                                        $sitemap->id,
                                                        $db->quote($uid),
                                                        0,
                                                        $db->quote('weekly'),
                                                        $db->quote('0.5')
                                                    ]
                                                )
                                            );
                                        $db->setQuery($query)->execute();
                                    } else {
                                        // Update the setting
                                        $query = $db->getQuery(true)
                                            ->update('#__osmap_items_settings')
                                            ->set('published = 0')
                                            ->where([
                                                'sitemap_id = ' . $db->quote($sitemap->id),
                                                'uid = ' . $db->quote($uid)
                                            ]);
                                        $db->setQuery($query)->execute();
                                    }
                                }
                            }
                        }

                        // Convert custom settings for items
                        if ($this->tableExists('#__osmap_items')) {
                            $query         = $db->getQuery(true)
                                ->select([
                                    'uid',
                                    'properties'
                                ])
                                ->from('#__osmap_items')
                                ->where('sitemap_id = ' . $db->quote($sitemap->id))
                                ->where('view = ' . $db->quote('xml'));
                            $modifiedItems = $db->setQuery($query)->loadObjectList();

                            if (!empty($modifiedItems)) {
                                foreach ($modifiedItems as $item) {
                                    $item->properties = str_replace(';', '&', $item->properties);
                                    parse_str($item->properties, $properties);

                                    $item->uid = $this->convertItemUID($item->uid);

                                    // Check if the item already exists to update, or insert
                                    $query  = $db->getQuery(true)
                                        ->select('COUNT(*)')
                                        ->from('#__osmap_items_settings')
                                        ->where([
                                            'sitemap_id = ' . $db->quote($sitemap->id),
                                            'uid = ' . $db->quote($item->uid)
                                        ]);
                                    $exists = (bool)$db->setQuery($query)->loadResult();


                                    if ($exists) {
                                        $fields = [];

                                        // Check if the changefreq is set and set to update
                                        if (isset($properties['changefreq'])) {
                                            $fields = 'changefreq = ' . $db->quote($properties['changefreq']);
                                        }

                                        // Check if the priority is set and set to update
                                        if (isset($properties['priority'])) {
                                            $fields = 'priority = ' . $db->quote($properties['priority']);
                                        }

                                        // Update the item
                                        $query = $db->getQuery(true)
                                            ->update('#__osmap_items_settings')
                                            ->set($fields)
                                            ->where([
                                                'sitemap_id = ' . $db->quote($sitemap->id),
                                                'uid = ' . $db->quote($item->uid)
                                            ]);
                                        $db->setQuery($query)->execute();
                                    }

                                    if (!$exists) {
                                        $columns = [
                                            'sitemap_id',
                                            'uid',
                                            'published'
                                        ];

                                        $values = [
                                            $db->quote($sitemap->id),
                                            $db->quote($item->uid),
                                            1
                                        ];

                                        // Check if the changefreq is set and set to update
                                        if (isset($properties['changefreq'])) {
                                            $columns[] = 'changefreq';
                                            $values[]  = 'changefreq = ' . $db->quote($properties['changefreq']);
                                        }

                                        // Check if the priority is set and set to update
                                        if (isset($properties['priority'])) {
                                            $columns[] = 'priority';
                                            $values[]  = 'priority = ' . $db->quote($properties['priority']);
                                        }

                                        // Insert a new item
                                        $query = $db->getQuery(true)
                                            ->insert('#__osmap_items_settings')
                                            ->columns($columns)
                                            ->values(implode(',', $values));
                                        $db->setQuery($query)->execute();
                                    }
                                }
                            }
                        }
                    }
                }

                // Remove the old table
                $query = 'DROP TABLE IF EXISTS ' . $db->quoteName('#__osmap_items');
                $db->setQuery($query)->execute();

                // Remove the old table
                $query = 'DROP TABLE IF EXISTS ' . $db->quoteName('#__osmap_sitemap');
                $db->setQuery($query)->execute();

                $db->transactionCommit();
            } catch (\Exception $e) {
                Factory::getApplication()->enqueueMessage(
                    Text::sprintf('COM_OSMAP_INSTALLER_ERROR_MIGRATING_DATA', $e->getMessage()),
                    'error'
                );
                $db->transactionRollback();
            }
        }
    }

    /**
     * Returns the id of the menutype.
     *
     * @param string $menuType
     *
     * @return int
     */
    protected function getMenuTypeId(string $menuType): int
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__menu_types')
            ->where('menutype = ' . $db->quote($menuType));

        return (int)$db->setQuery($query)->loadResult();
    }

    /**
     * Converts a legacy UID to the new pattern. Instead of "com_contenta25",
     * "joomla.article.25". Returns the new UID
     *
     * @param string $uid
     *
     * @return string
     */
    protected function convertItemUID(string $uid): string
    {
        // Joomla articles in categories
        if (preg_match('#com_contentc[0-9]+a([0-9]+)#', $uid, $matches)) {
            return 'joomla.article.' . $matches[1];
        }

        // Joomla categories
        if (preg_match('#com_contentc([0-9]+)#', $uid, $matches)) {
            return 'joomla.category.' . $matches[1];
        }

        // Joomla articles
        if (preg_match('#com_contenta([0-9]+)#', $uid, $matches)) {
            return 'joomla.article.' . $matches[1];
        }

        // Joomla featured
        if (preg_match('#com_contentfeatureda([0-9]+)#', $uid, $matches)) {
            return 'joomla.featured.' . $matches[1];
        }

        // Menu items
        if (preg_match('#itemid([0-9]*)#', $uid, $matches)) {
            return 'menuitem.' . $matches[1];
        }

        return $uid;
    }

    /**
     * Check the database scheme
     */
    protected function checkDbScheme()
    {
        $existentColumns = $this->getColumnsFromTable('#__osmap_items_settings');

        $db = Factory::getDbo();

        if (in_array('url_hash', $existentColumns)) {
            $db->setQuery('ALTER TABLE `#__osmap_items_settings`
                CHANGE `url_hash` `settings_hash` CHAR(32)
                CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL DEFAULT ""');
            $db->execute();
        }

        if (!in_array('format', $existentColumns)) {
            $db->setQuery('ALTER TABLE `#__osmap_items_settings`
                ADD `format` TINYINT(1) UNSIGNED DEFAULT NULL
                COMMENT \'Format of the setting: 1) Legacy Mode - UID Only; 2) Based on menu ID and UID\'');
            $db->execute();
        }
    }

    /**
     * Adds new format=xml to existing xml menus
     *
     * @since v4.2.25
     */
    protected function fixXMLMenus()
    {
        $db      = Factory::getDbo();
        $siteApp = SiteApplication::getInstance('site');

        $query = $db->getQuery(true)
            ->select('id, link')
            ->from('#__menu')
            ->where([
                'client_id = ' . $siteApp->getClientId(),
                sprintf('link LIKE %s', $db->quote('%com_osmap%')),
                sprintf('link LIKE %s', $db->quote('%view=xml%')),
                sprintf('link NOT LIKE %s', $db->quote('%format=xml%'))
            ]);

        $menus = $db->setQuery($query)->loadObjectList();
        foreach ($menus as $menu) {
            $menu->link .= '&format=xml';
            $db->updateObject('#__menu', $menu, ['id']);
        }
    }

    /**
     * Clear localized language files from core folders
     *
     * @return void
     */
    protected function clearLanguageFiles()
    {
        $files = array_merge(
            Folder::files(JPATH_ADMINISTRATOR . '/language', '_osmap', true, true),
            Folder::files(JPATH_SITE . '/language', '_osmap', true, true)
        );

        foreach ($files as $file) {
            if (is_file($file)) {
                File::delete($file);
            }
        }
    }
}
