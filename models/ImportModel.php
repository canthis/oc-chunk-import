<?php namespace CanThis\ImportExport\Models;

use Str;
use Lang;
use Model;
use League\Csv\Reader as CsvReader;
use Session;

/**
 * Model used for importing data
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class ImportModel extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * The attributes that aren't mass assignable.
     * @var array
     */
    protected $guarded = [];

    /**
     * Relations
     */
    public $attachOne = [
        'import_file' => ['System\Models\File']
    ];

    /**
     * @var array Import statistics store.
     */
    protected $resultStats = [
        'processed' => 0, // Added this for overall progress percentage calculation
        'updated' => 0,
        'created' => 0,
        'errors' => [],
        'warnings' => [],
        'skipped' => []
    ];
    
    /**
     * Called once to have ability for import logging, 
     * such as which user, when, what file imported and so on,
     * cause our entryModel can have db table and relations
     * 
     * With default, non chunked Import we were able to do the same in
     * our entryModel's importData() function
     * 
     * 
     * Example entryModel:
     *   
     *   //Define a table, where you want to log all the stuff
     *   public $table = 'vendor_plugin_entry_import_log';
     *   
     *   public $belongsTo = ['author' => ['Backend\Models\User']];   
     *   public $attachOne = ['import_file' => ['System\Models\File']];
     * 
     *   Add this import to log (entryModel)
     *   
     *   public function importLog($sessionKey = null) {
     *      $importLog = new entryModel;
     *   
     *      Get current backend user and imported CSV file
     *      $user = BackendAuth::getUser();
     *      $file = $this->import_file()->withDeferred($sessionKey)->first();
     *   
     *      $importLog->author_id = $user->id;              
     *      $importHistory->save();
     *      $importHistory->import_file()->add($file);
     *   }
     */ 
     
    /** 
     *  !!! IN CASE IF LOGGING IS NOT USED !!! 
     *  
     *  importLog() function should still present in entryModel to avoid exception
     * 
     *  public function importLog($sessionKey = null) {
     *      return;
     *  } 
     */
    abstract public function importLog($sessionKey = null);
    
    /**
     * Called when data is being imported.
     * The $results array should be in the format of:
     *
     *    [
     *        'db_name1' => 'Some value',
     *        'db_name2' => 'Another value'
     *    ],
     *    [...]
     *
     */
    abstract public function importData($results, $sessionKey = null);

    /**
     * Import data based on column names matching header indexes in the CSV.
     * The $matches array should be in the format of:
     *
     *    [
     *        0 => [db_name1, db_name2],
     *        1 => [db_name3],
     *        ...
     *    ]
     *
     * The key (0, 1) is the column index in the CSV and the value
     * is another array of target database column names.
     */
    public function import($matches, $options = [])
    {
        $sessionKey = array_get($options, 'sessionKey');
        $path = $this->getImportFilePath($sessionKey);
        $data = $this->processImportData($path, $matches, $options);
        
        if($options['offset'] == 0) {
            $this->importLog($sessionKey);
        }
        
        return $this->importData($data, $sessionKey);
    }

    /**
     * Converts column index to database column map to an array containing
     * database column names and values pulled from the CSV file. Eg:
     *
     *   [0 => [first_name], 1 => [last_name]]
     *
     * Will return:
     *
     *   [first_name => Joe, last_name => Blogs],
     *   [first_name => Harry, last_name => Potter],
     *   [...]
     *
     * @return array
     */
    protected function processImportData($filePath, $matches, $options)
    {
        /*
         * Parse options
         */
        $defaultOptions = [
            'firstRowTitles' => true,
            'delimiter' => null,
            'enclosure' => null,
            'escape' => null
        ];

        $options = array_merge($defaultOptions, $options);

        /*
         * Read CSV
         */
        $reader = CsvReader::createFromPath($filePath, 'r');

        // Filter out empty rows
        $reader->addFilter(function(array $row) {
            return count($row) > 1 || reset($row) !== null;
        });

        if ($options['delimiter'] !== null) {
            $reader->setDelimiter($options['delimiter']);
        }

        if ($options['enclosure'] !== null) {
            $reader->setEnclosure($options['enclosure']);
        }

        if ($options['escape'] !== null) {
            $reader->setEscape($options['escape']);
        }
        
        /**
         * Get chunked results depending on iteration and chunk size 
         */
        
        $calcOffset = $options['offset'] * $options['chunkSize'];
        if ($options['firstRowTitles']) {
            $calcOffset++;
        }


        $result = [];        
        $chunked = $reader->setOffset($calcOffset)->setLimit($options['chunkSize'])->fetchAll();
   
        foreach ($chunked as $row) {
            $result[] = $this->processImportRow($row, $matches);
        }

        return $result;
    }

    /**
     * Converts a single row of CSV data to the column map.
     * @return array
     */
    protected function processImportRow($rowData, $matches)
    {
        $newRow = [];

        foreach ($matches as $columnIndex => $dbNames) {
            $value = array_get($rowData, $columnIndex);
            foreach ((array) $dbNames as $dbName) {
                $newRow[$dbName] = $value;
            }
        }

        return $newRow;
    }

    /**
     * Explodes a string using pipes (|) to a single dimension array
     * @return array
     */
    protected function decodeArrayValue($value, $delimeter = '|')
    {
        if (strpos($value, $delimeter) === false) return [$value];

        $data = preg_split('~(?<!\\\)' . preg_quote($delimeter, '~') . '~', $value);
        $newData = [];

        foreach ($data as $_value) {
            $newData[] = str_replace('\\'.$delimeter, $delimeter, $_value);
        }

        return $newData;
    }

    /**
     * Returns an attached imported file local path, if available.
     * @return string
     */
    public function getImportFilePath($sessionKey = null)
    {
        $file = $this
            ->import_file()
            ->withDeferred($sessionKey)
            ->first()
        ;

        if (!$file) {
            return null;
        }

        return $file->getLocalPath();
    }

    /**
     * Returns all available encodings values from the localization config
     * @return array
     */
    public function getFormatEncodingOptions()
    {
        $options = [
            'utf-8',
            'us-ascii',
            'iso-8859-1',
            'iso-8859-2',
            'iso-8859-3',
            'iso-8859-4',
            'iso-8859-5',
            'iso-8859-6',
            'iso-8859-7',
            'iso-8859-8',
            'iso-8859-0',
            'iso-8859-10',
            'iso-8859-11',
            'iso-8859-13',
            'iso-8859-14',
            'iso-8859-15',
            'Windows-1251',
            'Windows-1252'
        ];

        $translated = array_map(function($option){
            return Lang::get('backend::lang.import_export.encodings.'.Str::slug($option, '_'));
        }, $options);

        return array_combine($options, $translated);
    }

    //
    // Result logging
    //

    public function getResultStats()
    {
        //Get whole stats from session
        $resultStats = Session::get('importResults');

        $this->resultStats['errorCount'] = isset($resultStats['errors']) ? count($resultStats['errors']) : 0;
        $this->resultStats['warningCount'] = isset($resultStats['warnings']) ? count($resultStats['warnings']) : 0;
        $this->resultStats['skippedCount'] = isset($resultStats['skipped'] )? count($resultStats['skipped']) : 0;

        $this->resultStats['hasMessages'] = (
            $this->resultStats['errorCount'] > 0 ||
            $this->resultStats['warningCount'] > 0 ||
            $this->resultStats['skippedCount'] > 0
        );
        $this->resultStats['updated'] = isset($resultStats['updated']) ? $resultStats['updated'] : 0;
        $this->resultStats['created'] = isset($resultStats['created']) ? $resultStats['created'] : 0;
        
        // Calculate how much rows were processed in this importing iteration
        $this->resultStats['processed'] = 
                //Sum them all
                $this->resultStats['created'] + 
                $this->resultStats['updated'] +
                $this->resultStats['errorCount'] +
                $this->resultStats['warningCount'] +
                $this->resultStats['skippedCount'];
                        
       
        //Calculate overal progress status
        $this->resultStats['progress'] = ($this->resultStats['processed'] / $resultStats['totalRows']) * 100;       
        
        unset($resultStats);
        return (object) $this->resultStats;
    }

    protected function logUpdated()
    {
        $updated = (int) Session::get('importResults.updated', 0);
        Session::put('importResults.updated', $updated+1);
    }

    protected function logCreated()
    {
        $created = (int) Session::get('importResults.created', 0);
        Session::put('importResults.created', $created+1);
    }

    protected function logError($rowIndex, $message)
    {
        Session::push('importResults.errors.' . $rowIndex, $message);
    }

    protected function logWarning($rowIndex, $message)
    {
        Session::push('importResults.warnings.' . $rowIndex, $message);
    }

    protected function logSkipped($rowIndex, $message)
    {
        Session::push('importResults.skipped.' . $rowIndex, $message);
    }
    
}
