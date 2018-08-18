<?php namespace Neonbug\Gallery\Handlers\Events;

use App;
use Event;
use Route;
use Request;

class GalleryEventHandler
{
	/**
	* Register the listeners for the subscriber.
	*
	* @param  Illuminate\Events\Dispatcher  $events
	* @return void
	*/
	public function subscribe($events)
	{
		$events->listen([
			'Neonbug\Common\Events\AdminAddPreparedFields', 
			'Neonbug\Common\Events\AdminEditPreparedFields'
		], function($event) {
			$this->handleAddEditPreparedFields($event);
		});
		
		$events->listen([
			'Neonbug\Common\Events\AdminAddSavePreparedFields', 
			'Neonbug\Common\Events\AdminEditSavePreparedFields'
		], function($event) {
			$this->handleAddEditSavePreparedFields($event);
		});
	}
	
	protected function handleAddEditPreparedFields($event)
	{
		$fields = $event->fields['language_independent'];
		for ($i=0; $i<sizeof($fields); $i++)
		{
			$field = $fields[$i];
			
			if ($field['type'] == 'gallery_admin::add_fields.gallery_images')
			{
				$value = ($event->item === null || !array_key_exists('save_to_field', $field) ? 
					null : json_decode($event->item->{$field['save_to_field']}, true));
				if ($value === null)
				{
					$value = [];
				}
				
				$event->fields['language_independent'][$i]['data'] = $value;
			}
		}
	}
	
	protected function handleAddEditSavePreparedFields($event)
	{
		//first level keys are field names, second are language ids, third are data keys, fourth are indexes
		$gallery_image_data = Request::input('gallery_image_data', []);
		
		$language_independent_fields = $event->all_language_independent_fields;
		$fields = $event->fields;
		
		// translate arrays to json strings
		for ($i=0; $i<sizeof($language_independent_fields); $i++)
		{
			$field = $language_independent_fields[$i];
			
			if ($field['type'] == 'gallery_admin::add_fields.gallery_images')
			{
				foreach ($fields as $id_language=>$field_array)
				{
					if (!array_key_exists($field['name'], $gallery_image_data) || 
						!array_key_exists($id_language, $gallery_image_data[$field['name']]))
					{
						continue;
					}
					
					// restructure data from [title][idx], [size][idx], [price][idx] -> [idx][title, size, price]
					$restructured_data = [];
					foreach ($gallery_image_data[$field['name']][$id_language] as $data_key=>$data_arr)
					{
						foreach ($data_arr as $idx=>$value)
						{
							$restructured_data[$idx][$data_key] = $value;
						}
					}
					
					$event->item->{$field['save_to_field']} = json_encode($restructured_data);
				}
			}
		}
	}
}
