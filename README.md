# October CMS Chunk Import
Experimental implementation of Chunk Importer for October CMS. Based on October's original ImportExport behavior.

**It's Work in progress repository and is not recommended for use!**

# Usage
Setup is similar to October's official [Docs](http://octobercms.com/docs/backend/import-export#introduction) on using importing & exporting, but there's few additional things You should do.

**1. Controller setup.** Follow official [Docs](http://octobercms.com/docs/backend/import-export#introduction)
```
namespace CanThis\Shop\Controllers;

class Products extends Controller
{
    public $implement = [
        'CanThis\ImportExport\Behaviors\ImportExportController',
    ];

    public $importExportConfig = 'config_import_export.yaml';

    // [...]
}
```
**2. Defining an import model.** Same as in [Docs](http://octobercms.com/docs/backend/import-export#import-model)

In addition **Your model class must define** method called *importLog()*.

Here is an example method definition without logging:

```
    /**
     * @param type $sessionKey
     */
    public function importLog($sessionKey = null) {
        return;
    }
```


Here is an advanced usage example with import logging, which gives You ability to log import author, date, import file and so on. 

```
<?php namespace CanThis\Shop\Models;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Model;
use BackendAuth;

class ProductsImport extends \CanThis\ImportExport\Models\ImportModel {

    protected $rules = [];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'canthis_shop_products_import_log';

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
                $entry = Product::where('column', '=', $data['column'])->firstOrFail();
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

```
