<?php
/**
 * @package   OSMap
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016 Open Source Training, LLC. All rights reserved.
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace Alledia\OSMap\Sitemap;

use Alledia\OSMap;

defined('_JEXEC') or die();

/**
 * Sitemap item
 */
class BaseItem extends \JObject
{
    /**
     * @var int;
     */
    public $id;

    /**
     * @var string;
     */
    public $uid;

    /**
     * @var string
     */
    public $link;

    /**
     * @var string
     */
    public $fullLink;

    /**
     * @var \JRegistry
     */
    public $params;

    /**
     * @var string
     */
    public $priority;

    /**
     * @var string
     */
    public $changefreq;

    /**
     * @var string
     */
    public $modified;

    /**
     * The component associated to the option URL param
     *
     * @var string
     */
    public $component;

    /**
     * @var bool
     */
    public $ignore = false;

    /**
     * @var bool
     */
    public $duplicate = false;

    /**
     * @var int
     */
    public $browserNav = null;

    /**
     * @var bool
     */
    public $isInternal = true;

    /**
     * @var bool
     */
    public $home = false;

    /**
     * @var string
     */
    public $type;

    /**
     * @var bool
     */
    public $expandible = false;

    /**
     * @var bool
     */
    public $secure = false;

    /**
     * @var int
     */
    public $isMenuItem = 0;

    /**
     * @var bool
     */
    public $published = 1;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var array
     */
    public $images = array();

    /**
     * @var string
     */
    public $settingsHash;

    /**
     * @var int
     */
    public $level;

    /**
     * @var string
     */
    public $adapterName = 'Generic';

    /**
     * @var object
     */
    public $adapter;

    /**
     * If true, says the item is visible for robots
     *
     * @var bool
     */
    public $visibleForRobots = true;

    /**
     * Stores a list of notes generated by the collector to display in the admin
     *
     * @var array
     */
    public $adminNotes;

    /**
     * @var bool
     */
    public $visibleForXML = true;

    /**
     * @var bool
     */
    public $visibleForHTML = true;

    /**
     * @var int
     */
    public $menuItemId = 0;

    /**
     * @var string
     */
    public $menuItemName;

    /**
     * @var string
     */
    public $menuItemType;

    /**
     * The constructor
     *
     * @param object  $itemData
     *
     * @return void
     */
    public function __construct(&$itemData, $currentMenuItemId)
    {
        $this->setProperties($itemData);
    }

    /**
     * Extract the option from the link, to identify the component called by
     * the link.
     *
     * @return void
     */
    protected function extractComponentFromLink()
    {
        $this->component = null;

        if (preg_match('#^/?index.php.*option=(com_[^&]+)#', $this->link, $matches)) {
            $this->component = $matches[1];
        }
    }

    /**
     * Adds the note to the admin note attribute and initialize the variable
     * if needed
     *
     * @param string $note
     *
     * @return void
     */
    public function addAdminNote($note)
    {
        if (!is_array($this->adminNotes)) {
            $this->adminNotes = array();
        }

        $this->adminNotes[] = \JText::_($note);
    }

    /**
     * Returns the admin notes as a string.
     *
     * @return string
     */
    public function getAdminNotesString()
    {
        if (!empty($this->adminNotes)) {
            return implode("\n", $this->adminNotes);
        }

        return '';
    }

    /**
     * Check if the current link is an internal link.
     *
     * @return bool
     */
    protected function checkLinkIsInternal()
    {
        return OSMap\Router::isInternalURL($this->link)
            || in_array(
                $this->type,
                array(
                    'separator',
                    'heading'
                )
            );
    }

    /**
     * Set the correct modification date.
     *
     * @return void
     */
    public function setModificationDate()
    {
        if (OSMap\Helper\General::isEmptyDate($this->modified)) {
            $this->modified = null;
        }

        if (!OSMap\Helper\General::isEmptyDate($this->modified)) {
            if (!is_numeric($this->modified)) {
                $date =  new \JDate($this->modified);
                $this->modified = $date->toUnix();
            }

            // Convert dates from UTC
            if (intval($this->modified)) {
                if ($this->modified < 0) {
                    $this->modified = null;
                } else {
                    $date = new \JDate($this->modified);
                    $this->modified = $date->toISO8601();
                }
            }
        }
    }

    /**
     * Check if the item's language has compatible language with
     * the current language.
     *
     * @return bool
     */
    public function hasCompatibleLanguage()
    {
        // Check the language
        if (\JLanguageMultilang::isEnabled() && isset($this->language)) {
            if ($this->language === '*' || $this->language === \JFactory::getLanguage()->getTag()) {
                return true;
            }

            return false;
        }

        return true;
    }
}
