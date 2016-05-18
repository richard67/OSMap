<?php
/**
 * @package   OSMap
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016 Open Source Training, LLC. All rights reserved.
 * @contact   www.alledia.com, support@alledia.com
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

JHtml::_('behavior.core');
JHtml::_('behavior.tabstate');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

JHtml::stylesheet('media/com_osmap/css/admin.css');
?>
<script type="text/javascript">
    ;(function(Joomla, document, $) {
        Joomla.submitbutton = function (task) {
            if (task == 'sitemap.cancel' || document.formvalidator.isValid(document.id('item-form'))) {
                // Convert the ordering of sortable in a serialized value to indentify the ordering of menus and values
                var ordering = $('#ul_menus').sortable('toArray').toString();
                $('#menus_ordering').val(ordering);

                Joomla.submitform(task, document.getElementById('item-form'));
            }
        }
    })(Joomla, document, jQuery);
</script>

<form
    action="<?php echo JRoute::_('index.php?option=com_osmap&layout=edit&id=' . (int)$this->item->id); ?>"
    method="post"
    name="adminForm"
    id="item-form"
    class="form-validate">

    <div class="form-inline form-inline-header">
        <?php echo $this->form->renderField('name'); ?>
    </div>

    <div class="form-horizontal">
        <div class="row-fluid">
            <div class="span9">
                <?php echo $this->form->getControlGroup('menus'); ?>
            </div>

            <div class="span3">
                <?php
                // Set main fields.
                $this->fields = array(
                    'published',
                    'is_default'
                );
                ?>
                <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
            </div>
        </div>
    </div>

    <input type="hidden" id="menus_ordering" name="jform[menus_ordering]" value=""/>
    <input type="hidden" name="task" value=""/>
    <?php echo JHtml::_('form.token'); ?>
</form>
