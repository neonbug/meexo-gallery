<?php namespace Neonbug\Gallery\Models;

class GalleryImage extends \Neonbug\Common\Models\BaseModel {
	
	public function getPath($table_name, $column_name, $id_row, $id_language = 0)
	{
		return implode('/', [ 'uploads', $table_name, $id_row, $id_language, $column_name, $this->image ]);
	}
	
}
