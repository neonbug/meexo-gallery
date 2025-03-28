<?php namespace Neonbug\Gallery\Providers;

use App;
use Route;
use View;
use Config;
use Event;
use \Illuminate\Routing\Router as Router;

class ServiceProvider extends \Neonbug\Common\Providers\BaseServiceProvider {
	
	use \Neonbug\Common\Traits\OrdTrait;
	use \Neonbug\Gallery\Traits\GalleryImagesTrait;
	
	const PACKAGE_NAME       = 'gallery';
	const PREFIX             = 'gallery';
	const ROLE               = 'gallery';
	const TABLE_NAME         = 'gallery';
	const CONTROLLER         = '\Neonbug\Gallery\Controllers\Controller';
	const ADMIN_CONTROLLER   = '\Neonbug\Gallery\Controllers\AdminController';
	const FULL_CONFIG_PREFIX = 'neonbug.gallery';
	
	/**
	 * Define your route model bindings, pattern filters, etc.
	 *
	 * @return  void
	 */
	public function boot()
	{
		//============
		//== ASSETS ==
		//============
		$this->loadViewsFrom(__DIR__.'/../resources/views', static::PACKAGE_NAME);
		$this->publishes([
			__DIR__.'/../resources/views' => base_path('resources/views/vendor/' . static::PACKAGE_NAME),
		]);
		
		$this->loadViewsFrom(__DIR__.'/../resources/admin_views', static::PACKAGE_NAME . '_admin');
		$this->publishesAdmin([
			__DIR__.'/../resources/admin_views' => base_path('resources/views/vendor/' . static::PACKAGE_NAME . '_admin'),
		]);
		
		$this->loadTranslationsFrom('/', static::PACKAGE_NAME);
		
		$this->publishes([
			__DIR__.'/../assets/' => public_path('vendor/gallery'),
		], 'public');
		
		$this->publishes([
			__DIR__.'/../database/migrations/' => database_path('/migrations')
		], 'migrations');
		
		$this->publishes([
			__DIR__.'/../config/' . static::PACKAGE_NAME . '.php' => config_path('neonbug/' . static::PACKAGE_NAME . '.php'),
		]);
		
		//============
		//== ROUTES ==
		//============
		$language = App::make('Language');
		$languages = ($language == null ? [] : $language::all());
		$locale = ($language == null ? Config::get('app.default_locale') : $language->locale);
		
		$admin_language = App::make('AdminLanguage');
		$admin_locale = ($admin_language == null ? Config::get('app.admin_default_locale') : $admin_language->locale);
		
		$resource_repo = App::make('ResourceRepository');
		
		//frontend
		$slug_routes_at_root = Config::get(static::FULL_CONFIG_PREFIX . '.slug_routes_at_root', false);
		
		foreach ($languages as $language_item)
		{
			$slugs = 
				$language_item == null ?
					null : 
					$resource_repo->getSlugs($language_item->id_language, static::TABLE_NAME);
			
			Route::group([ 'middleware' => [ 'online' ], 'prefix' => $language_item->locale ], 
				function($router) use ($slugs, $slug_routes_at_root, $language_item, $locale)
			{
				$router->group([ 'prefix' => trans(static::PACKAGE_NAME . '::frontend.route.prefix', [], $language_item->locale) ], 
					function($router) use ($slugs, $slug_routes_at_root, $language_item, $locale)
				{
					$lang_postfix = '::' . $language_item->locale;
					$route_prefix = static::PACKAGE_NAME . '::frontend.route.';
					
					/*
					 * If language_item is current language, then we need to
					 * create all routes twice - once with lang_postfix and once without.
					 * Order matters - without language postfix should be at the start
					 * to enable Route::currentRouteName to return this route, instead of
					 * one of the routes with language postfix.
					 */
					$postfixes = 
						$language_item->locale == $locale ? 
							[ '', $lang_postfix ] : 
							[ $lang_postfix ];
					
					foreach ($postfixes as $postfix)
					{
						$router->get('/', [
							'as'   => static::PREFIX . '::index' . $postfix, 
							'uses' => static::CONTROLLER . '@index'
						]);
						$router->get('index', [
							'as'   => static::PREFIX . '::index-with-name' . $postfix, 
							'uses' => static::CONTROLLER . '@index'
						]);
						$router->get('item/{id}', [
							'as'   => static::PREFIX . '::item' . $postfix, 
							'uses' => static::CONTROLLER . '@item'
						]);
						$router->get('preview/{key}', [
							'as'   => static::PREFIX . '::preview' . $postfix, 
							'uses' => static::CONTROLLER . '@preview'
						]);
					}
					
					if ($slugs != null)
					{
						/*
						 * Order matters - route without language postfix should be at the start
						 * to enable Route::currentRouteName to return this route, instead of
						 * one of the routes with language postfix
						 */
						if ($language_item->locale == $locale) // current language
						{
							$this->setRoutesFromSlugs(
								$router, 
								$slugs, 
								($slug_routes_at_root === true ? 'default' : '')
							);
						}
						
						$this->setRoutesFromSlugs(
							$router, 
							$slugs, 
							($slug_routes_at_root === true ? 'default' : ''), 
							$language_item->locale
						);
					}
				});

				//put routes at root level (i.e. /en/contents/abc is also accessible via /en/abc)
				if ($slug_routes_at_root)
				{
					if ($slugs != null)
					{
						/*
						 * Order matters - route without language postfix should be at the start
						 * to enable Route::currentRouteName to return this route, instead of
						 * one of the routes with language postfix
						 */
						if ($language_item->locale == $locale) // current language
						{
							$this->setRoutesFromSlugs(
								$router, 
								$slugs, 
								''
							);
						}
						
						$this->setRoutesFromSlugs(
							$router, 
							$slugs, 
							'', 
							$language_item->locale
						);
					}
				}
			});
		}
		
		//admin
		$show_in_admin_menu = !Config::get('neonbug.' . static::PACKAGE_NAME . '.hide_from_admin_menu', false);
		
		Route::group([
			'prefix'     => $admin_locale . '/admin/' . static::PREFIX, 
			'middleware' => ($show_in_admin_menu ? [ 'auth.admin', 'admin.menu' ] : [ 'auth.admin' ]), 
			'role'       => static::ROLE, 
			'menu.icon'  => 'photo', 
		], function($router)
		{
			$router->get('list', [
				'as'   => static::PREFIX . '::admin::list', 
				'uses' => static::ADMIN_CONTROLLER . '@adminList'
			]);
			
			$router->get('add', [
				'as'   => static::PREFIX . '::admin::add', 
				'uses' => static::ADMIN_CONTROLLER . '@adminAdd'
			]);
			$router->post('add', [
				'as'   => static::PREFIX . '::admin::add-save', 
				'uses' => static::ADMIN_CONTROLLER . '@adminAddPost'
			]);
			
			$router->get('edit/{id}', [
				'as'   => static::PREFIX . '::admin::edit', 
				'uses' => static::ADMIN_CONTROLLER . '@adminEdit'
			]);
			$router->post('edit/{id}', [
				'as'   => static::PREFIX . '::admin::edit-save', 
				'uses' => static::ADMIN_CONTROLLER . '@adminEditPost'
			]);
		});
		
		Route::group([ 'prefix' => $admin_locale . '/admin/' . static::PREFIX, 'middleware' => [ 'auth.admin' ], 
			'role' => static::ROLE ], function($router)
		{
			$router->post('delete', [
				'as'   => static::PREFIX . '::admin::delete', 
				'uses' => static::ADMIN_CONTROLLER . '@adminDeletePost'
			]);
			
			$router->post('check-slug', [
				'as'   => static::PREFIX . '::admin::check-slug', 
				'uses' => static::ADMIN_CONTROLLER . '@adminCheckSlugPost'
			]);
			
			$router->get('list-api', [
				'as'   => static::PREFIX . '::admin::list-api', 
				'uses' => static::ADMIN_CONTROLLER . '@adminListApi'
			]);
			
			$router->post('save-item-order', [
				'as'   => static::PREFIX . '::admin::save-item-order', 
				'uses' => static::ADMIN_CONTROLLER . '@adminSaveItemOrderPost'
			]);
		});
		
		//============
		//== EVENTS ==
		//============
		Event::subscribe('\Neonbug\Gallery\Handlers\Events\GalleryImagesEventHandler');
		Event::subscribe('\Neonbug\Gallery\Handlers\Events\GalleryEventHandler');

		parent::boot();
	}
	
	protected function setRoutesFromSlugs($router, $slugs, $route_name_prefix_postfix = '', $route_name_postfix = '')
	{
		$prefix_postfix = ($route_name_prefix_postfix == '' ? '' : '-' . $route_name_prefix_postfix);
		$route_name_prefix = static::PREFIX . '::slug' . $prefix_postfix . '::';
		
		$postfix = ($route_name_postfix == '' ? '' : '::' . $route_name_postfix);
		
		foreach ($slugs as $slug)
		{
			// skip empty slugs
			if ($slug->value == '') continue;
			
			/*
			 * Order matters - route without language postfix should be at the start
			 * to enable Route::currentRouteName to return this route, instead of
			 * one of the routes with language postfix
			 */
			foreach ([
				$route_name_prefix . 'item-' . $slug->id_row . $postfix, 
				$route_name_prefix . $slug->value . $postfix, 
			] as $route_alias) {
				$router->get($slug->value, [ 'as' => $route_alias, 
					function() use ($slug) {
					$controller = App::make(static::CONTROLLER);
					return $controller->callAction('item', [ 'id' => $slug->id_row ]);
				} ]);
			}
		}
	}

	/**
	 * Register any application services.
	 *
	 * This service provider is a great spot to register your various container
	 * bindings with the application. As you can see, we are registering our
	 * "Registrar" implementation here. You can add your own bindings too!
	 *
	 * @return  void
	 */
	public function register()
	{
		//===========
		//== BINDS ==
		//===========
		if (!App::bound('\Neonbug\Gallery\Repositories\GalleryRepository'))
		{
			App::singleton('\Neonbug\Gallery\Repositories\GalleryRepository', '\Neonbug\Gallery\Repositories\GalleryRepository');
		}
	}

}
