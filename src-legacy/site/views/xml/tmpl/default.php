<?php
/**
 * @version             $Id$
 * @copyright           Copyright (C) 2005 - 2009 Joomla! Vargas. All rights reserved.
 * @license             GNU General Public License version 2 or later; see LICENSE.txt
 * @author              Guillermo Vargas (guille@vargas.co.cr)
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

$params   = $this->item->params;
$liveSite = substr_replace(JURI::root(), "", -1, 1);

if ($this->item->params->get('debug_osmap', "0") == "0") {
    @ini_set('display_errors', 0);

    header('Content-type: text/xml; charset=utf-8');
} else {
    @error_reporting(E_ALL);
    @ini_set('display_errors', 1);
    @ini_set('display_startup_errors', 1);

    header('Content-type: text/txt; charset=utf-8');
}

echo '<?xml version="1.0" encoding="UTF-8"?>',"\n";
if (($this->item->params->get('beautify_xml', 1) == 1)) {
    $params  = '&amp;filter_showtitle='.JRequest::getBool('filter_showtitle', 0);
    $params .= '&amp;filter_showexcluded='.JRequest::getBool('filter_showexcluded', 0);
    $params .= (JRequest::getCmd('lang')?'&amp;lang='.JRequest::getCmd('lang'):'');

    echo '<?xml-stylesheet type="text/xsl" href="'. $liveSite.'/index.php?option=com_osmap&amp;view=xml&amp;layout=xsl&amp;tmpl=component&amp;id='.$this->item->id.($this->isImages?'&amp;images=1':'').$params.'"?>'."\n";
}
?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"<?php echo ($this->displayer->isImages? ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"':''); ?>>

<?php echo $this->loadTemplate('items'); ?>

</urlset>