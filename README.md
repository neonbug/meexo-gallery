# Gallery module for Meexo CMS

## Use gallery images as data type in other modules

1. Open your module's model (e.g. `/Models/Shop.php`) and implement `\Neonbug\Gallery\Traits\GalleryImagesTraitInterface` interface.

    Example:
```
<?php namespace App\Packages\Shop\Models;

class Shop extends \Neonbug\Common\Models\BaseModel implements \Neonbug\Gallery\Traits\GalleryImagesTraitInterface {
	
	public $gallery_images = []; // keys are id languages, then field names
	public static function getTableNameForGalleryImages() { return 'shop'; } // from GalleryImagesTraitInterface
	public static function getUploadsFolderNameForGalleryImages() { return 'shop'; } // from GalleryImagesTraitInterface
```

2. Use trait `\Neonbug\Gallery\Traits\GalleryImagesTrait` in your module's ServiceProvider (e.g. `/Providers/ServiceProvider.php`.
    
    Example:
```
<?php namespace App\Packages\Shop\Providers;

class ServiceProvider extends \Neonbug\Common\Providers\BaseServiceProvider {
	
	use \Neonbug\Gallery\Traits\GalleryImagesTrait;
```

3. Add a field (or multiple) with type `gallery_admin::add_fields.gallery_images` in your module's config file (e.g. `/config/shop.php`).
    
    Example:
```
<?php
return [
    ...
    'add' => [
        [
            'name' => 'images', 
            'type' => 'gallery_admin::add_fields.gallery_images', 
            'value' => '', 
        ], 
    ], 
    ...
];
```

4. To load images, use `getImages` method on `GalleryRepository`. It returns a collection of `GalleryImage` objects.
    
    Example:
```
<?php
...
$images = App::make('\Neonbug\Gallery\Repositories\GalleryRepository')->getImages(
	'shop', // table name
	'images', // field name
	$item->id_shop, // item id
	null // language id (or null, if this field is language independent)
);
```

5. To display images in views, use `getPath` method on `GalleryImage` to get the image path.
    
    Example:
```
@foreach ($images as $image)
    <?php
	$image_path = $image->getPath(
        'shop', // table name
        'images', // 
        $item->id_shop, // item id
        0 // language id (or 0, if this field is language independent)
    );
	if (!file_exists($image_path))
	{
		continue;
	}
    ?>
    <img src="{!! Croppa::url($image_path, 420, 280) !!}" />
@endforeach
```

## License
Available under the [MIT license](LICENSE).
