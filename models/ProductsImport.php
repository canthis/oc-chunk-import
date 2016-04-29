<?php namespace CanThis\ImportExport\Models;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Model;
use BackendAuth;

class ProductsImport extends \CanThis\ImportExport\Models\ImportModel {

    protected $rules = [];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'canthis_importexport_import_log';

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'author' => ['Backend\Models\User']
    ];
    
    public $attachOne = [
        'import_file' => ['System\Models\File']
    ];
    
    /**
     * Adds this import to products import log table
     * @param type $sessionKey
     */
    public function importLog($sessionKey = null) {   
        $importLog = new ProductsImport;
        
        //Get current backend user
        $user = BackendAuth::getUser();
        $importLog->author_id = $user->id;
        
        $file = $this->import_file()->withDeferred($sessionKey)->first();        
        $importLog->save();
        $importLog->import_file()->add($file);    
    }
    
    public function importData($results, $sessionKey = null) {       
        foreach ($results as $row => $data) {   
            try {
                //If product is found, Update it
                $entry = Product::where('sku', '=', $data['sku'])->firstOrFail();
                                
                $entry->fill($data);
                $entry->save();
                $this->logUpdated();
            } catch (ModelNotFoundException $ex) {
                //If product is not found, create it
                $entry = new Product;
                $entry->fill($data);
                $entry->save();
                $this->logCreated();
            } catch (\Exception $ex) {
                $this->logError($row, $ex->getMessage());
            }
        }
        
    }
}
