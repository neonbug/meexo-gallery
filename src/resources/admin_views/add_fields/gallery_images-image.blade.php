<div class="ui card gallery-images-image">
	<input type="hidden" 
		@if ($field == null || $id_language == null)
			name="" 
		@else
			name="gallery_image[{{ $id_language }}][{{ $field['name'] }}][images][]" 
		@endif
		
		@if ($image == null)
			value="" 
		@else
			value="{{ $image }}" 
		@endif
		/>
	<a class="image" target="_blank" 
		@if ($image == null)
			data-id="" 
			href="#" 
		@else
			data-id="{{ $image }}" 
			href="{{ Croppa::url(implode('/', [
					'uploads', 
					$model_name::getUploadsFolderNameForGalleryImages(), 
					$item->{$item->getKeyName()}, 
					($id_language == -1 ? 0 : $id_language), 
					$field['name'], 
					$image, 
				])) }}" 
			style="background-image: url('{{ Croppa::url(implode('/', [
					'uploads', 
					$model_name::getUploadsFolderNameForGalleryImages(), 
					$item->{$item->getKeyName()}, 
					($id_language == -1 ? 0 : $id_language), 
					$field['name'], 
					$image, 
				]), 180, 120) }}');" 
		@endif
		>
		
		@if ($image == null)
			<div class="gallery-images-image-upload-overlay-progress"></div>
			<div class="gallery-images-image-upload-overlay">
				<span class="gallery-images-image-progress">0 %</span>
			</div>
		@endif
	</a>
	<div class="content">
		<div class="description">
			<div class="gallery-images-image-name">
				{{ $image == null ? '' : $image }}
			</div>
			
			<?php
			$data_fields = (array_key_exists('data_fields', $field) ? $field['data_fields'] : []);
			if (sizeof($data_fields) > 0) {
			?>
				<div class="ui hidden divider"></div>
				
				<?php
				foreach ($data_fields as $data_field_name=>$data_field_title)
				{
					$title = trans($data_field_title);
					$name  = 'gallery_image_data[' . $field['name'] . '][' . $id_language . '][' . $data_field_name . '][]';
					$value = ($idx !== null && $field !== null && $idx < sizeof($field['data']) && 
									array_key_exists($data_field_name, $field['data'][$idx]) ? 
									$field['data'][$idx][$data_field_name] : '');
					
					?>
					<div>
						<label>{{ $title }}</label>
						<br />
						<input type="text"
							name="{{ $name }}"
							value="{{ $value }}"
							data-name="{{ $data_field_name }}"
							class="gallery-images-image--data-field" />
					</div>
					<?php
				}
			}
			?>
		</div>
	</div>
	<div class="ui bottom attached red button 
		{{ $image == null ? 'gallery-images-image-cancel' : 'gallery-images-image-remove' }}">
		<i class="trash icon"></i>
		{{ trans('gallery::admin.add.field-gallery-images.image-remove') }}
	</div>
</div>
