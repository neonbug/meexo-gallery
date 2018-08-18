<tr class="top aligned field-gallery-images">
	<th class="collapsing">
		{{ $field_title }}
	</th>
	<td>
		<div class="field" data-name="gallery_image[{{ $id_language }}][{{ $field['name'] }}]"
			data-id-language="{{ $id_language }}" data-field-name="{{ $field['name'] }}">
			<div class="gallery-images-upload-container">
				<button class="ui button gallery-images-browse" type="button">
					<i class="icon upload"></i>
					{{ trans('gallery::admin.add.field-gallery-images.upload-button') }}
				</button>
				
				<div class="gallery-images-drop-target">
					<span>
						{{ trans('gallery::admin.add.field-gallery-images.drag-drop-text') }}
					</span>
				</div>
				
				<div style="clear: both;"></div>
			</div>
			
			<div class="ui basic segment gallery-images-list-container">
				<div class="ui cards gallery-images-list">
					@if ($item != null)
						@foreach ($item->gallery_images[$id_language][$field['name']] as $idx=>$image)
							@include('gallery_admin::add_fields.gallery_images-image', [
								'item'        => $item, 
								'model_name'  => $model_name, 
								'image'       => $image->image, 
								'field'       => $field, 
								'id_language' => $id_language, 
								'idx'         => $idx, 
							])
						@endforeach
					@endif
				</div>
			</div>
			
			<script type="text/template" class="gallery-images-image-template">
				@include('gallery_admin::add_fields.gallery_images-image', [ 'item' => null, 'image' => null, 'field' => $field, 
					'id_language' => $id_language, 'idx' => null ])
			</script>
		</div>
	</td>
</tr>
