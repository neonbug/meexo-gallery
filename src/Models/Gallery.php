<?php namespace Neonbug\Gallery\Models;

class Gallery extends \Neonbug\Common\Models\BaseModel implements \Neonbug\Common\Traits\OrdTraitInterface, 
	\Neonbug\Gallery\Traits\GalleryImagesTraitInterface {
	
	public $gallery_images = []; // keys are id languages, then field names
	public static function getTableNameForGalleryImages() { return 'gallery'; } // from GalleryImagesTraitInterface
	public static function getUploadsFolderNameForGalleryImages() { return 'gallery'; } // from GalleryImagesTraitInterface
	
	public static function getOrdFields() { return [ 'ord' ]; } // from OrdTraitInterface
	
}
