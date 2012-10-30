<?php
/**
 * A lightweight folder based gallery module for the CMS SilverStripe
 *
 * Contains global settings for the SilverStripe CMS foldergallery module.
 * 
 * LICENSE: GNU General Public License 3.0
 * 
 * @platform    CMS SilverStripe 3
 * @package     cwsoft-foldergallery
 * @version     2.6.0
 * @author      cwsoft (http://cwsoft.de)
 * @copyright   cwsoft
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
*/

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// CWSOFT-FOLDERGALLERY SETTINGS
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// defines image quality of created thumbnails
GD::set_default_quality(95);

// defines pixel size of cropped album cover and album image thumbnails
// Note: adapt "css/cwsoft-foldergallery.css" if you change the dimensions below
define('CWS_FOLDERGALLERY_THUMBNAIL_IMAGE_WIDTH', 150);
define('CWS_FOLDERGALLERY_THUMBNAIL_IMAGE_HEIGHT', 115);

// defines max. pixel dimension of resized jQuery preview image (original image ratio kept)
define('CWS_FOLDERGALLERY_PREVIEW_IMAGE_MAX_SIZE', 800);

// defines number of albums and images displayed per page (pagination limit)
define('CWS_FOLDERGALLERY_ALBUMS_PER_PAGE', 16);
define('CWS_FOLDERGALLERY_IMAGES_PER_PAGE', 12);

// defines sort option by which images are displayed (1:Filename, 2:Created, 3:LastEdited)
define('CWS_FOLDERGALLERY_IMAGE_SORT_OPTION', 1);

// defines sort order by which images are displayed (1:Ascending, 2:Descending)
define('CWS_FOLDERGALLERY_IMAGE_SORT_ORDER', 1);

// defines if breadcrumbs are shown at the top (only if at least one parent page exists)
define('CWS_FOLDERGALLERY_SHOW_BREADCRUMBS', true);

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// DO NOT CHANGE ANYTHING BELOW THIS LINE UNLESS YOU KNOW WHAT YOU ARE DOING :-)
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// ensure module is stored in folder "cwsoft-foldergallery"
$moduleName = 'cwsoft-foldergallery';
$folderName = basename(dirname(__FILE__));

if ($folderName != $moduleName) {
	user_error(
		_t(
			'_config.WRONG_MODULE_FOLDER', 
			'Please rename the folder "{folderName}" into "{moduleName}" to get the {moduleName} module working properly.',
			array('moduleName' => $moduleName, 'folderName' => $folderName)
		),
		E_USER_ERROR
	);
}

// extend image object to allow extraction of image description from it's filename
Object::add_extension('Image', 'cwsFolderGalleryImageExtension');