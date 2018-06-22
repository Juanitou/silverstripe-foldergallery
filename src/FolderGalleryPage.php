<?php
/**
 * A lightweight folder based gallery module for the CMS SilverStripe
 *
 * Implements the main functionality of the foldergallery module.
 *
 * LICENSE: GNU General Public License 3.0
 *
 * @platform    CMS SilverStripe 4 (or higher)
 * @package     juanitou-foldergallery
 * @author      cwsoft (http://cwsoft.de)
 * @author      Juanitou (http://juanmolina.eu)
 * @copyright   cwsoft
 * @copyright   Juanitou
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
*/

class FolderGalleryPage extends Page {
	private static $allowed_children = array('FolderGalleryPage');
	private static $db = array('AlbumFolderID' => 'Int');
	private static $icon = 'images/page-tree-icon.gif';
	private static $plural_name = 'Foldergalleries';
	private static $singular_name = 'Foldergallery';
	private static $description = 'Folder based gallery';

	/**
	 * Adds dropdown field for album folders (subfolders inside assets/foldergallery)
	 *
	 * @return modified backend fields
	 */
	function getCMSFields() {
		// create folder assets/foldergallery if not already exists
		Folder::find_or_make('foldergallery');

		// get default CMS fields
		$fields = parent::getCMSFields();

		// get "foldergallery" folder object
		$album = Folder::get()->filter('Filename', 'assets/foldergallery/')->First();
		if (! $album) return $fields;

		// add dropdown field with album folders (subfolders of assets/foldergallery)
		$tree = new TreeDropdownField(
			'AlbumFolderID',
			_t(
				'FolderGalleryPage.CHOOSE_IMAGE_FOLDER',
				'Choose image folder (subfolder assets/foldergallery/)'
			),
			'Folder'
		);
		$tree->setTreeBaseID((int) $album->ID);
		$fields->addFieldToTab('Root.Main', $tree, 'Content');

		return $fields;
	}

	/**
	 * Updates the Image.ExifDate database column of image objects when page is saved
	 *
	 * @return void
	 */
	function onAfterWrite() {
		parent::onAfterWrite();

		// update Image.ExifDate database fields of all images assigned to actual page if image sort option is set "4:ExifDate"
		// Todo: execute DB update on URL request instead page write to avoid timing issues when dealing with lots of big images
		if (FolderGalleryPage_Controller::getImageSortOption() == "ExifDate") {
			FolderGalleryImageExtension::writeExifDates($this->AlbumFolderID);
		}
	}
}

class FolderGalleryPage_Controller extends Page_Controller {
	/**
	 * Inlcudes the CSS and Javascript files required by the foldergallery module
	 *
	 * @return void
	 */
	 function init() {
		parent::init();

		// include i18n Javascript library and lang files
		// it doesn't work without the meta-tag (see http://open.silverstripe.org/ticket/7949)
		Requirements::insertHeadTags('<meta http-equiv="Content-language" content="' . i18n::get_locale() . '" />');
		Requirements::javascript(FRAMEWORK_DIR . "/javascript/i18n.js");
		Requirements::add_i18n_javascript('javascript/lang');

		// load foldergallery Javascript files into head
		Requirements::set_write_js_to_body(false);

		// include required foldergallery CSS and Javascript files
		Requirements::css('thirdparty/colorbox/colorbox.css');
		Requirements::css('css/foldergallery.css');
		Requirements::javascript('thirdparty/jquery/jquery.min.js');
		Requirements::javascript('thirdparty/colorbox/jquery.colorbox-min.js');
		Requirements::javascript('javascript/foldergallery.js');
	}

	/**
	 * Creates paginated list of all album pages linked to the actual page via $AlbumFolderID.
	 * Includes extras like album cover image, available album images and album page link.
	 *
	 * @return paginated list of folder objects
	 */
	public function AlbumFolders() {
		// extract all subpage objects (album pages)
		$pages = $this->Children();
		if (! $pages->exists()) return false;

		// store subpage data in array for further usage
		$data = $pages->toNestedArray();

		// add additional information to $data array
		$albumData = new ArrayList();
		foreach($data as $index => $pageData) {
			// extract number of assigned sub albums (child pages below actual page)
			$subAlbums = SiteTree::get()->filter('ID', $pageData['ID'])->First()->Children();
			$data[$index]['AlbumNumberSubAlbums'] = ($subAlbums) ? $subAlbums->Count() : 0;

			// extract all image objects matching $page->AlbumFolderID
			$albumImages = Image::get()->filter('ParentID', $pageData['AlbumFolderID']);

			// add extra information to data array
			$data[$index]['AlbumNumberImages'] = $albumImages->Count();
			$data[$index]['AlbumCoverImage'] = ($albumImages) ? $albumImages->First() : false;
			$data[$index]['AlbumURL'] = $pages[$index]->RelativeLink();

			// add modified subpage data to ArrayList object
			$albumData->push(new ArrayData($data[$index]));
		}
		// return paginated list of album pages
		$albumList = new PaginatedList($albumData, $this->request);

		// set page limit of displayed images to value defined in _config.php
		if ($albumList) {
			$albumsPerPage = (int) Config::inst()->get('FolderGallery', 'ALBUMS_PER_PAGE');
			$albumList->setPageLength($albumsPerPage);
		}

		return $albumList;
	}

	/**
	 * Creates a paginated list of all image objects contained in page/album matching $AlbumFolderID
	 *
	 * @return paginated list with image objects of the actual album
	 */
	public function AlbumImages() {
		// get album folder matching assigned albumFolderID
		$albumFolder = Folder::get()->filter('ID', (int) $this->AlbumFolderID);
		if (! $albumFolder->exists()) return false;

		// fetch all images objects of actual folder and wrap it into paginated list
		$images = Image::get()->filter('ParentID', $albumFolder->First()->ID)->sort($this->getImageSortOption(), $this->getImageSortOrder());
		$imageList = ($images->exists()) ? new PaginatedList($images, $this->request) : false;

		// set page limit of displayed images to value defined in _config.php
		if ($imageList) {
			$imagesPerPage = (int) Config::inst()->get('FolderGallery', 'IMAGES_PER_PAGE');
			$imageList->setPageLength($imagesPerPage);
		}

		return $imageList;
	}

	/**
	 * Extracts maximum jQuery preview image size in pixel defined in _config/settings.yml
	 *
	 * @return integer Maximum preview image size in pixel
	 */
	public static function getPreviewImageMaxSize() {
		return (int) Config::inst()->get('FolderGallery', 'PREVIEW_IMAGE_MAX_SIZE');
	}

	/**
	 * Extracts breadcrumb settings defined in _config/settings.yml
	 *
	 * @return bool Flag indicating if breadcrumbs are displayed or not
	 */
	public static function getShowBreadcrumbs() {
		return (bool) Config::inst()->get('FolderGallery', 'SHOW_BREADCRUMBS');
	}

	/**
	 * Extracts thumbnail height in pixel defined in _config/settings.yml
	 *
	 * @return integer Thumbnail height in pixel
	 */
	public static function getThumbnailHeight() {
		return (int) Config::inst()->get('FolderGallery', 'THUMBNAIL_IMAGE_HEIGHT');
	}

	/**
	 * Extracts thumbnail width in pixel defined in _config/settings.yml
	 *
	 * @return integer Thumbnail width in pixel
	 */
	public static function getThumbnailWidth() {
		return Config::inst()->get('FolderGallery', 'THUMBNAIL_IMAGE_WIDTH');
	}

	/**
	 * Extracts the image sort option defined in _config/settings.yml
	 *
	 * @return string (Filename, Created, LastEdited, ExifDate)
	 */
	public static function getImageSortOption() {
		$key = (int) Config::inst()->get('FolderGallery', 'IMAGE_SORT_OPTION');
		$sort_options = array(
			1 => 'Filename',
			2 => 'Created',
			3 => 'LastEdited',
			4 => 'ExifDate',
		);

		return (array_key_exists($key, $sort_options)) ? $sort_options[$key] : $sort_options[1];
	}

	/**
	 * Extracts the image sort order defined in _config/settings.yml
	 *
	 * @return string (ASC, DESC)
	 */
	public static function getImageSortOrder() {
		$key = (int) Config::inst()->get('FolderGallery', 'IMAGE_SORT_ORDER');
		$sort_order = array(
			1 => 'ASC',
			2 => 'DESC',
		);

		return (array_key_exists($key, $sort_order)) ? $sort_order[$key] : $sort_order[1];
	}
}
