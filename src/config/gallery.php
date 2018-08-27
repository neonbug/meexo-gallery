<?php return [
	
	'model' => '\Neonbug\Gallery\Models\Gallery', 
	'supports_preview' => false, 
	'hide_from_admin_menu' => false, 
	
	'list' => [
		'fields' => [
			'id_gallery' => [
				'type' => 'text'
			], 
			'title' => [
				'type' => 'text'
			], 
			'slug' => [
				'type' => 'text', 
				'important' => false
			], 
			'updated_at' => [
				'type' => 'date', 
				'important' => false
			], 
			'published' => [
				'type' => 'boolean', 
				'important' => false
			], 
			'ord' => [
				'type' => 'text', 
				'important' => false
			]
		]
	], 
	
	'add' => [
		'language_dependent_fields' => [
			[
				'name' => 'title', 
				'type' => 'single_line_text', 
				'value' => '', 
				'required' => true
			], 
			[
				'name' => 'slug', 
				'type' => 'slug', 
				'value' => '', 
				'generate_from' => 'title'
			], 
			[
				'name' => 'contents', 
				'type' => 'rich_text', 
				'value' => ''
			]
		], 
		'language_independent_fields' => [
			[
				'name' => 'main_image', 
				'type' => 'image', 
				'value' => '', 
				'required' => true
			], 
			[
				'name' => 'published', 
				'type' => 'boolean', 
				'value' => true
			], 
			[
				'name' => 'ord', 
				'type' => 'integer', 
				'value' => '1'
			], 
			[
				'name' => 'images', 
				'type' => 'gallery_admin::add_fields.gallery_images', 
				'value' => '', 
				/*'save_to_field' => 'gallery_image_data', 
				'data_fields' => [
					'title' => 'gallery::admin.add_fields.gallery_images.data_fields.title', 
				], 
				'data' => [], */
			]
		]
	], 
	
	'edit' => [
		'language_dependent_fields' => [
			[
				'name' => 'title', 
				'type' => 'single_line_text', 
				'value' => '', 
				'required' => true
			], 
			[
				'name' => 'slug', 
				'type' => 'slug', 
				'value' => '', 
				'generate_from' => 'title'
			], 
			[
				'name' => 'contents', 
				'type' => 'rich_text', 
				'value' => ''
			]
		], 
		'language_independent_fields' => [
			[
				'name' => 'main_image', 
				'type' => 'image', 
				'value' => '', 
				'required' => true
			], 
			[
				'name' => 'published', 
				'type' => 'boolean', 
				'value' => true
			], 
			[
				'name' => 'ord', 
				'type' => 'integer', 
				'value' => '1'
			], 
			[
				'name' => 'images', 
				'type' => 'gallery_admin::add_fields.gallery_images', 
				'value' => '', 
				/*'save_to_field' => 'gallery_image_data', 
				'data_fields' => [
					'title' => 'gallery::admin.add_fields.gallery_images.data_fields.title', 
				], 
				'data' => [], */
			]
		]
	]
	
];
