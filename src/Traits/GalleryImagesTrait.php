<?php namespace Neonbug\Gallery\Traits;

use App;
use Route;
use Config;

use Neonbug\Gallery\Models\GalleryImage as GalleryImage;

trait GalleryImagesTrait {
	
	protected static $booted_gallery_images_trait = false;
	
	public static function bootGalleryImagesTrait()
	{
		if (self::$booted_gallery_images_trait === true) return;
		self::$booted_gallery_images_trait = true;
		
		$admin_language = App::make('AdminLanguage');
		$admin_locale = ($admin_language == null ? Config::get('app.admin_default_locale') : $admin_language->locale);
		
		Route::group([ 'prefix' => $admin_locale . '/admin/' . static::PREFIX, 'middleware' => [ 'auth.admin' ], 
			'role' => static::ROLE ], function($router)
		{
			$router->get('upload-gallery-file/{upload_dir}', [
				'as' => static::PREFIX . '::admin::upload-gallery-file-check', 
				function($upload_dir) {
					static::adminUploadGalleryFile($upload_dir);
				}
			]);
			
			$router->post('upload-gallery-file/{upload_dir}', [
				'as' => static::PREFIX . '::admin::upload-gallery-file', 
				function($upload_dir) {
					static::adminUploadGalleryFilePost($upload_dir);
				}
			]);
		});
	}
	
	protected static function adminUploadGalleryFile($upload_dir)
	{
		$upload_dir = str_replace([ '..', '/', '\\' ], '-', $upload_dir);
		
		$temp_dir = '../storage/app/temp/' . static::PREFIX . '/' . $upload_dir;
		if (!file_exists($temp_dir))
		{
			// create dir, but suppress possible errors
			// since we're checking for dir existance, errors should occur, but they do, 
			//    because of concurrent requests (request A notices this dir doesn't exist yet, 
			//    but before it can create it, request B does so; so when request A tries to create it, 
			//    it fails miserably)
			@mkdir($temp_dir, 0777, true);
		}
		
		$config = new \Flow\Config();
		$config->setTempDir($temp_dir);
		$file = new \Neonbug\Gallery\Models\FlowFile($config);
		
		if ($file->checkChunk()) {
			header('HTTP/1.1 200 Ok');
			exit();
		} else {
			header('HTTP/1.1 204 No Content');
			exit();
		}
	}
	protected static function adminUploadGalleryFilePost($upload_dir)
	{
		$upload_dir = str_replace([ '..', '/', '\\' ], '-', $upload_dir);
		
		$temp_dir = '../storage/app/temp/' . static::PREFIX . '/' . $upload_dir;
		if (!file_exists($temp_dir))
		{
			// create dir, but suppress possible errors
			// since we're checking for dir existance, errors should occur, but they do, 
			//    because of concurrent requests (request A notices this dir doesn't exist yet, 
			//    but before it can create it, request B does so; so when request A tries to create it, 
			//    it fails miserably)
			@mkdir($temp_dir, 0777, true);
		}
		
		$config = new \Flow\Config();
		$config->setTempDir($temp_dir);
		$file = new \Neonbug\Gallery\Models\FlowFile($config);
		
		if ($file->validateChunk()) {
			$file->saveChunk();
		} else {
			// error, invalid chunk upload request, retry
			header('HTTP/1.1 400 Bad Request');
			exit();
		}
		
		if ($file->validateFile()) {
			// file upload was completed
			
			$dir = 'uploads/' . static::PREFIX . '/temp/' . $upload_dir;
			$filename = str_replace([ '..', '/', '\\' ], '-', $file->getFileName()); //very basic filename validation
			
			if (!file_exists($dir))
			{
				// create dir, but suppress possible errors
				// since we're checking for dir existance, errors should occur, but they do, 
				//    because of concurrent requests (request A notices this dir doesn't exist yet, 
				//    but before it can create it, request B does so; so when request A tries to create it, 
				//    it fails miserably)
				@mkdir($dir, 0777, true);
			}
			
			if (!$file->save($dir . '/' . $filename))
			{
				//TODO throw an error or sth
			}
		} else {
			// this is not a final chunk, continue to upload
		}
	}
	
}
