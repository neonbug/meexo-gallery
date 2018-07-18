<?php namespace Neonbug\Gallery\Handlers\Events;

use App;
use Event;
use Route;
use Request;

use Neonbug\Gallery\Models\GalleryImage as GalleryImage;

class GalleryImagesEventHandler
{
	/**
	* Register the listeners for the subscriber.
	*
	* @param  Illuminate\Events\Dispatcher  $events
	* @return void
	*/
	public function subscribe($events)
	{
		$events->listen('Neonbug\Common\Events\AdminAddEditPrepareField', function($event) {
			if ($event->item === null) return;
			if (! $event->item instanceof \Neonbug\Gallery\Traits\GalleryImagesTraitInterface) return;
			if ($event->field['type'] != 'gallery_admin::add_fields.gallery_images' && 
				(!array_key_exists('handle_as', $event->field) || $event->field['handle_as'] != 'gallery_admin::add_fields.gallery_images'))
			{
				return;
			}
			
			if (!isSet($event->item->gallery_images))
			{
				$event->item->gallery_images = array();
			}
			if (!array_key_exists($event->id_language, $event->item->gallery_images))
			{
				$event->item->gallery_images[$event->id_language] = array();
			}
			
			$query = GalleryImage::where('table_name', $event->item->getTableNameForGalleryImages())
					->where('column_name', $event->field['name'])
					->where('id_row', $event->item->{$event->item->getKeyName()});
			
			$query = ($event->id_language == -1 ? 
				$query->whereNull('id_language') : 
				$query->where('id_language', $event->id_language));
			
			$event->item->gallery_images[$event->id_language][$event->field['name']] = $query->orderBy('ord')->get();
		});
		
		$events->listen('Neonbug\Common\Events\AdminAddEditSavedItem', function($event) {
			if ($event->item === null) return;
			if (! $event->item instanceof \Neonbug\Gallery\Traits\GalleryImagesTraitInterface) return;
			
			//TODO get gallery images as an input instead of reading it directly from Request?
			
			$gallery_images              = Request::input('gallery_image'); //first level keys are language ids, 
																			// second level are field names
			$item                        = $event->item;
			$id_item                     = $item->{$item->getKeyName()};
			$language_independent_fields = $event->language_independent_fields;
			$language_dependent_fields   = $event->language_dependent_fields;
			$languages                   = $event->languages;
			
			if ($gallery_images == null)
			{
				$gallery_images = [];
			}
			
			// fill in missing fields
			foreach ([
					'independent' => $language_independent_fields, 
					'dependent' => $language_dependent_fields
				] as $type=>$fields)
			{
				foreach ($fields as $field)
				{
					if ($field['type'] == 'gallery_admin::add_fields.gallery_images' || 
						(array_key_exists('handle_as', $field) && $field['handle_as'] == 'gallery_admin::add_fields.gallery_images'))
					{
						//find if field is present in $gallery_images
						if ($type == 'independent')
						{
							$found = false;
							if (array_key_exists(-1, $gallery_images))
							{
								foreach ($gallery_images as $id_language=>$selected_fields)
								{
									if (array_key_exists($field['name'], $selected_fields))
									{
										$found = true;
										break;
									}
								}
							}
							
							if (!$found)
							{
								$gallery_images[-1][$field['name']] = [ 'images' => [] ];
							}
						}
						else if ($type == 'dependent')
						{
							foreach ($languages as $language)
							{
								$found = false;
								if (array_key_exists($language->id_language, $gallery_images))
								{
									foreach ($gallery_images as $id_language=>$selected_fields)
									{
										if (array_key_exists($field['name'], $selected_fields))
										{
											$found = true;
											break;
										}
									}
								}
								
								if (!$found)
								{
									$gallery_images[$language->id_language][$field['name']] = [ 'images' => [] ];
								}
							}
						}
					}
				}
			}
			
			$c = 1;
			foreach ($gallery_images as $id_language=>$fields)
			{
				foreach ($fields as $field_name=>$images)
				{
					// existing images
					$query = GalleryImage::where('table_name', $item->getTableNameForGalleryImages())
							->where('column_name', $field_name)
							->where('id_row', $id_item);
					
					$query = ($id_language == -1 ? 
						$query->whereNull('id_language') : 
						$query->where('id_language', $id_language));
					
					$existing_images = $query->get();
					$existing_image_names = [];
					$existing_image_name_to_objs = [];
					foreach ($existing_images as $image)
					{
						$existing_image_names[]                     = $image->image;
						$existing_image_name_to_objs[$image->image] = $image;
					}
					
					// process
					$field_name_clean = str_replace([ '..', '/', '\\' ], '-', $field_name);
					
					foreach ($images['images'] as $image)
					{
						$image_filename = $image;
						if (stripos($image_filename, '/') !== false)
						{
							$arr = explode('/', $image_filename);
							if (sizeof($arr) != 2 || !is_numeric($arr[0])) continue;
							
							$upload_dir_clean = str_replace([ '..', '/', '\\' ], '-', $arr[0]);
							$image_filename   = str_replace([ '..', '/', '\\' ], '-', $arr[1]);
							
							$file_path = 'uploads/' . $item->getUploadsFolderNameForGalleryImages() . '/temp/' . 
								$upload_dir_clean . '/' . $image_filename;
							
							$destination_dir = 'uploads/' . $item->getUploadsFolderNameForGalleryImages() . '/' . 
								$id_item . '/' . ($id_language == -1 ? 0 : $id_language) . '/' . $field_name_clean;
							$destination_path = $destination_dir . '/' . $image_filename;
							
							if (!file_exists($destination_dir))
							{
								mkdir($destination_dir, 0777, true);
							}
							rename($file_path, $destination_path);
							
							$image_item = new GalleryImage();
							$image_item->id_language = ($id_language == -1 ? null : $id_language);
							$image_item->table_name  = $item->getTableNameForGalleryImages();
							$image_item->column_name = $field_name;
							$image_item->id_row      = $id_item;
							$image_item->image       = $image_filename;
							$image_item->ord         = $c++;
							$image_item->save();
						}
						else if (array_key_exists($image_filename, $existing_image_name_to_objs))
						{
							$image_item = $existing_image_name_to_objs[$image_filename];
							$image_item->ord = $c++;
							$image_item->save();
							
							unset($existing_image_name_to_objs[$image_filename]);
						}
					}
					
					// delete missing images
					foreach ($existing_image_name_to_objs as $name=>$image_item)
					{
						$filename = 'uploads/' . $item->getUploadsFolderNameForGalleryImages() . '/' . $id_item . '/' . 
							($id_language == -1 ? 0 : $id_language) . '/' . $field_name_clean . '/' . $name;
						if (file_exists($filename)) unlink($filename);
						
						GalleryImage::where($image_item->getKeyName(), $image_item->id_gallery_image)
							->delete();
					}
				}
			}
		});
		
		$events->listen('Neonbug\Common\Events\AdminAfterDeleteItem', function($event) {
			$interfaces = class_implements($event->model);
			if (!array_key_exists('Neonbug\Gallery\Traits\GalleryImagesTraitInterface', $interfaces)) return;
			
			GalleryImage::where('table_name', $event->model::getTableNameForGalleryImages())
				->where('id_row', $event->id)
				->delete();
			
			// delete directory
			$dir = 'uploads/' . $event->model::getUploadsFolderNameForGalleryImages() . '/' . $event->id;
			$target_dir = realpath(trim($dir, './\\'));
			
			if (php_uname('s') == 'Windows NT')
			{
				system('rmdir /S /Q ' . escapeshellarg($target_dir));
			}
			else
			{
				system('rm -rf ' . escapeshellarg($target_dir));
			}
			
			/*
			//due to delays in deleting files/directories, this code works, but with an error:
			if (!file_exists($dir)) return true;
			if (!is_dir($dir))      return unlink($dir);
			
			foreach (scandir($dir) as $item)
			{
				if ($item == '.' || $item == '..') continue;
				if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
			}
			
			return rmdir($dir);
			*/
		});
	}
}
