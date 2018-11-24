<?php namespace Neonbug\Gallery\Repositories;

use Neonbug\Common\Models\Language;
use Neonbug\Common\Repositories\ResourceRepository;

class GalleryRepository {
	
	const CONFIG_PREFIX = 'neonbug.gallery';
	
	protected $latest_items_limit = 20;
	protected $model;
	
	protected $language;
	protected $resource_repository;
	
	public function __construct(Language $language = null, ResourceRepository $resource_repository = null)
	{
		$this->model = config(static::CONFIG_PREFIX . '.model');
		
		$this->language            = $language;
		$this->resource_repository = $resource_repository;
	}
	
	public function getLatest()
	{
		$model = $this->model;
		return $model::orderBy('updated_at', 'DESC')
			->limit($this->latest_items_limit)
			->get();
	}
	
	public function getForAdminList()
	{
		$model = $this->model;
		return $model::all();
	}
	
	public function getStructuredForDropdown()
	{
		$model = $this->model;
		
		$latest_items = $model::orderBy('updated_at', 'DESC')
			->limit(5)
			->get();
		$this->resource_repository->inflateObjectsWithValues($latest_items, $this->language->id_language);
		
		$all_items = $model::get();
		$this->resource_repository->inflateObjectsWithValues($all_items, $this->language->id_language);
		
		$all_items_arr = $all_items->all();
		usort($all_items_arr, function($a, $b) {
			return strcasecmp($a->title, $b->title);
		});
		
		$items = [];
		$ids = []; //keep track of which ids we've already done, since we can't have two or more of the same id in our list
		foreach ($latest_items as $item)
		{
			$items[$item->id_gallery] = $item->title;
			$ids[] = $item->id_gallery;
		}
		
		$filtered_all_items = [];
		foreach ($all_items_arr as $item)
		{
			if (in_array($item->id_gallery, $ids)) continue;
			$filtered_all_items[$item->id_gallery] = $item->title;
		}
		
		if (sizeof($filtered_all_items) > 0)
		{
			$items[-1] = '-------';
			$items = array_merge($items, $filtered_all_items);
		}
		
		return $items;
	}
	
	public function getListForDropdown()
	{
		$model = $this->model;
		
		$latest_items = $model::orderBy('updated_at', 'DESC')
			->limit(5)
			->get();
		$this->resource_repository->inflateObjectsWithValues($latest_items, $this->language->id_language);
		
		$all_items = $model::get();
		$this->resource_repository->inflateObjectsWithValues($all_items, $this->language->id_language);
		
		$all_items_arr = $all_items->all();
		usort($all_items_arr, function($a, $b) {
			return strcasecmp($a->title, $b->title);
		});
		
		$items = [];
		foreach ($latest_items as $item)
		{
			$items[] = [ 'id_gallery' => $item->id_gallery, 'title' => $item->title ];
		}
		
		if (sizeof($all_items_arr) > sizeof($latest_items))
		{
			$items[] = [ 'id_gallery' => -1, 'title' => '-------' ];
			foreach ($all_items_arr as $item)
			{
				$items[] = [ 'id_gallery' => $item->id_gallery, 'title' => $item->title ];
			}
		}
		
		return $items;
	}
	
	public function getImages($table_name, $column_name, $id_row, $id_language = null)
	{
		$query = \Neonbug\Gallery\Models\GalleryImage::where('table_name', $table_name)
			->where('column_name', $column_name)
			->where('id_row', $id_row);
		
		if ($id_language === null)
		{
			$query = $query->whereNull('id_language');
		}
		else
		{
			$query = $query->where('id_language', $id_language);
		}
		
		$images = $query->orderBy('ord')->get();
		
		return $images;
	}
	
	public function replaceEmbeddedGalleries($contents, $view_callback)
	{
		$gallery_repo = $this;
		$model        = $this->model;
		$new_contents = $contents;
		
		$embed_gallery_regex = '/<div class="meexogallery-embed" data-id-gallery="(\d+)">.*<\/div>/';
		preg_match_all($embed_gallery_regex, $new_contents, $embed_gallery_output);
		if (sizeof($embed_gallery_output) > 0 && sizeof($embed_gallery_output[0]) > 0)
		{
			$new_contents = preg_replace_callback($embed_gallery_regex, function($match)
				use ($gallery_repo, $model, $view_callback) {
				$gallery = $model::where('id_gallery', $match[1])->first();
				if ($gallery == null)
				{
					return '';
				}
				
				$gallery_images = $gallery_repo->getImages(
					'gallery', 
					'images', 
					$gallery->id_gallery, 
					null
				);
				
				return $view_callback($gallery, $gallery_images);
			}, $new_contents);
		}
		
		return $new_contents;
	}
	
}
