<link rel="stylesheet" type="text/css" href="{{ cached_asset('vendor/gallery/admin_assets/css/gallery_images.css') }}" />

<script src="{{ cached_asset('vendor/gallery/admin_assets/js/app/gallery_images.js') }}"></script>
<script type="text/javascript">
var trans = {
	errors: {
		upload_error: {!! json_encode(trans('gallery::admin.add.field-gallery-images.upload-error')) !!}, 
		upload_error_close: {!! json_encode(trans('gallery::admin.add.field-gallery-images.upload-error-close')) !!}
	}
};

var config = {
	upload_gallery_file_route: {!! json_encode(route($prefix . '::admin::upload-gallery-file', [ 'UPLOAD_DIR' ])) !!}, 
	temp_small_image_template_url: {!! json_encode(Croppa::url('uploads/' . 
		$model_name::getUploadsFolderNameForGalleryImages() . '/temp/{UPLOAD_DIR}/{FILENAME}.{EXT}', 
		180, 120)) !!}, 
	temp_image_template_url: {!! json_encode(Croppa::url('uploads/' . 
		$model_name::getUploadsFolderNameForGalleryImages() . '/temp/{UPLOAD_DIR}/{FILENAME}.{EXT}')) !!}
};

gallery_images.init(trans, config);

$(document).ready(function() {
	$('.field-gallery-images .field').each(function(idx, el) {
		var upload_dir = Math.floor(Math.random() * (1000000 - 1)) + 1;
		
		var flow = new Flow({
			target: config.upload_gallery_file_route.replace('UPLOAD_DIR', upload_dir), 
			headers: { }
		});
		
		flow.assignDrop($('.gallery-images-drop-target', el).get(0));
		flow.assignBrowse($('.gallery-images-browse', el).get(0));
		
		$('.gallery-images-drop-target', el)
			.on('dragenter', function() {
				$(this).addClass('drag-over');
			})
			.on('dragleave dragend drop', function() {
				$(this).removeClass('drag-over');
			});
		
		flow.on('filesSubmitted', function(file) {
			flow.opts.headers['X-XSRF-TOKEN'] = $('meta[name="csrf_token"]').attr('content');
			
			flow.upload();
		});
		
		flow.on('fileAdded', function(file) {
			var item = $($('.gallery-images-image-template', el).html())
				.appendTo($('.gallery-images-list', el));
			
			item.get(0).dataset.id = file.uniqueIdentifier;
			
			$('.gallery-images-image-name', item).text(file.name);
			
			$('.gallery-images-image-cancel', item).on('click', function () {
				file.cancel();
				item.remove();
			});
			
			// reinit sortable
			$('.gallery-images-list').sortable();
			
			// scroll to bottom
			var list = $('.gallery-images-list', el);
			list.scrollTop(list.height());
		});
		
		flow.on('fileProgress', function(file) {
			var item = $('.gallery-images-image[data-id="' + file.uniqueIdentifier + '"]', el);
			
			var percent_value = Math.floor(file.progress()*100);
			
			$('.gallery-images-image-progress', item).text(percent_value + ' %');
			$('.gallery-images-image-upload-overlay-progress', item).css('height', percent_value + '%');
		});
		
		flow.on('fileSuccess', function(file) {
			var item = $('.gallery-images-image[data-id="' + file.uniqueIdentifier + '"]', el);
			
			var image_small_url = config.temp_small_image_template_url;
			var image_url = config.temp_image_template_url;
			
			var filename = file.name;
			var ext = '';
			var pos = filename.lastIndexOf('.');
			if (pos > -1)
			{
				ext = filename.substring(pos+1);
				filename = filename.substring(0, pos);
			}
			
			var item_image = $('.image', item);
			item_image.css('background-image', 
				'url("' + image_small_url
					.replace('{FILENAME}', filename)
					.replace('{EXT}', ext)
					.replace('{UPLOAD_DIR}', upload_dir)
				 + '")');
			item_image.attr('href', image_url
				.replace('{FILENAME}', filename)
				.replace('{EXT}', ext)
				.replace('{UPLOAD_DIR}', upload_dir)
			);
			
			var input_hidden = $('input[type="hidden"]', item);
			input_hidden.attr('name', el.dataset.name + '[images][]');
			input_hidden.val(upload_dir + '/' + file.name);
			
			$('.gallery-images-image-upload-overlay', item_image).remove();
			$('.gallery-images-image-upload-overlay-progress', item_image).remove();
			
			$('.gallery-images-image-cancel', item).removeClass('gallery-images-image-cancel')
				.addClass('gallery-images-image-remove')
				.off('click')
				.on('click', function() {
					$(this).parents('.gallery-images-image').remove();
				});
		});
		
		flow.on('fileError', function(file, message) {
			file.cancel();
			$('.gallery-images-image[data-id="' + file.uniqueIdentifier + '"]', el).remove();
			
			$('<div class="ui small modal">' + 
				'<div class="content">' + 
					trans.errors.upload_error + 
				'</div>' + 
				'<div class="actions"><div class="ui cancel button red">' + 
					trans.errors.upload_error_close + 
				'</div></div>' + 
			'</div>').modal('show');
		});
	});
});
</script>
