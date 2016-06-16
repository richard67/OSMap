<?php
/**
 * @package   OSMap
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016 Open Source Training, LLC. All rights reserved.
 * @contact   www.alledia.com, support@alledia.com
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

use Alledia\OSMap;

defined('_JEXEC') or die();

JHtml::addIncludePath(OSMAP_ADMIN_PATH . '/helpers/html');

JHtml::_('bootstrap.tooltip');
JHtml::_('formbehavior.chosen', 'select');

JHtml::stylesheet('media/com_osmap/css/admin.css');

$baseUrl   = JUri::root();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDir   = $this->escape($this->state->get('list.direction'));
?>
<form
    action="<?php echo JRoute::_('index.php?option=com_osmap&view=sitemaps');?>"
    method="post"
    name="adminForm"
    id="adminForm">

<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

<?php if (empty($this->items)) : ?>
    <div class="alert alert-no-items">
        <?php echo JText::_('COM_OSMAP_NO_MATCHING_RESULTS'); ?>
    </div>
<?php else : ?>
    <div id="j-main-container">
        <table class="adminlist table table-striped" id="sitemapList">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JHtml::_('grid.checkall'); ?>
                    </th>

                    <th width="1%" style="min-width:55px" class="nowrap center">
                        <?php
                        echo JHtml::_(
                            'grid.sort',
                            'COM_OSMAP_HEADING_STATUS',
                            'sitemap.published',
                            $listDir,
                            $listOrder
                        );
                        ?>
                    </th>

                    <th class="title">
                        <?php
                        echo JHtml::_(
                            'grid.sort',
                            'COM_OSMAP_HEADING_NAME',
                            'sitemap.name',
                            $listDir,
                            $listOrder
                        ); ?>
                    </th>

                    <th width="8%" style="min-width: 63px" class="center">
                        <?php echo JText::_('COM_OSMAP_HEADING_SITEMAP_EDIT_LINKS'); ?>
                    </th>

                    <th width="260" class="center">
                        <?php echo JText::_('COM_OSMAP_HEADING_SITEMAP_LINKS'); ?>
                    </th>

                    <th width="8%" class="nowrap center">
                        <?php echo JText::_('COM_OSMAP_HEADING_NUM_LINKS'); ?>
                    </th>

                    <th width="1%" class="nowrap">
                        <?php
                        echo JHtml::_(
                            'grid.sort',
                            'COM_OSMAP_HEADING_ID',
                            'sitemap.id',
                            $listDir,
                            $listOrder
                        ); ?>
                    </th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <tr class="<?php echo 'row' . ($i % 2); ?>">
                    <td class="center">
                        <?php echo JHtml::_('grid.id', $i, $item->id); ?>
                    </td>

                    <td class="center">
                        <div class="btn-group">
                            <?php
                            echo JHtml::_(
                                'jgrid.published',
                                $item->published,
                                $i,
                                'sitemaps.'
                            );
                            ?>
                            <a href="#" onclick="return listItemTask('cb<?php echo $i; ?>','sitemap.setAsDefault')" class="btn btn-micro hasTooltip" title="" data-original-title="Toggle default status.">
                                <span class="icon-<?php echo $item->is_default ? 'featured' : 'unfeatured'; ?>"></span>
                            </a>
                        </div>
                    </td>

                    <td>
                        <a href="<?php echo JRoute::_('index.php?option=com_osmap&task=sitemap.edit&id=' . $item->id);?>">
                            <?php echo $this->escape($item->name); ?>
                        </a>
                    </td>

                    <td class="center">
                        <a href="<?php echo JRoute::_('index.php?option=com_osmap&view=sitemapitems&id=' . $item->id);?>">
                            <span class="icon-edit"></span>
                        </a>
                    </td>

                    <td class="center osmap-links">
                        <?php $link = isset($item->menuIdList['xml'])
                            ? 'index.php?Itemid=' . $item->menuIdList['xml']
                            : 'index.php?option=com_osmap&amp;view=xml&tmpl=component&id=' . $item->id;
                        ?>
                        <a
                            href="<?php echo $baseUrl . OSMap\Router::routeURL($link); ?>"
                            target="_blank"
                            title="<?php echo JText::_('COM_OSMAP_XML_LINK_TOOLTIP', true); ?>">

                            <?php echo JText::_('COM_OSMAP_XML_LINK'); ?>
                            <span class="icon-out-2"></span>
                        </a>
                        &nbsp;&nbsp;
                        <?php $link = isset($item->menuIdList['html'])
                            ? 'index.php?Itemid=' . $item->menuIdList['html']
                            : 'index.php?option=com_osmap&amp;view=html&id=' . $item->id;
                        ?>
                        <a
                            href="<?php echo $baseUrl . OSMap\Router::routeURL($link); ?>"
                            target="_blank"
                            title="<?php echo JText::_('COM_OSMAP_HTML_LINK_TOOLTIP', true); ?>">

                            <?php echo JText::_('COM_OSMAP_HTML_LINK'); ?>
                            <span class="icon-out-2"></span>
                        </a>
                        &nbsp;&nbsp;
                        <?php $link = isset($item->menuIdList['xml'])
                            ? 'index.php?Itemid=' . $item->menuIdList['xml'] . '&tmpl=component&news=1&id=' . $item->id
                            : 'index.php?option=com_osmap&amp;view=xml&tmpl=component&news=1&id=' . $item->id;
                        ?>
                        <a
                            href="<?php echo $baseUrl . OSMap\Router::routeURL($link); ?>"
                            target="_blank"
                            title="<?php echo JText::_('COM_OSMAP_NEWS_LINK_TOOLTIP', true); ?>">

                            <?php echo JText::_('COM_OSMAP_NEWS_LINK'); ?>
                            <span class="icon-out-2"></span>
                        </a>
                        &nbsp;&nbsp;
                        <?php $link = isset($item->menuIdList['xml'])
                            ? 'index.php?Itemid=' . $item->menuIdList['xml'] . '&tmpl=component&images=1&id=' . $item->id
                            : 'index.php?option=com_osmap&amp;view=xml&tmpl=component&images=1&id=' . $item->id;
                        ?>
                        <a
                            href="<?php echo $baseUrl . OSMap\Router::routeURL($link); ?>"
                            target="_blank"
                            title="<?php echo JText::_('COM_OSMAP_IMAGES_LINK_TOOLTIP', true); ?>">

                            <?php echo JText::_('COM_OSMAP_IMAGES_LINK'); ?>
                            <span class="icon-out-2"></span>
                        </a>
                    </td>

                    <td class="center">
                        <?php echo (int) $item->links_count; ?>
                    </td>

                    <td class="center">
                        <?php echo (int) $item->id; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="filter_order" value="<?php echo $this->state->get('list.ordering'); ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $this->state->get('list.direction'); ?>" />
    <?php echo JHtml::_('form.token'); ?>
</form>
