<?php namespace CanThis\ImportExport\Models;

use Model;

/**
 * Model
 */
class Product extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;
    
    protected $fillable = ['name', 'sku', 'price'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'canthis_importexport_products';
}