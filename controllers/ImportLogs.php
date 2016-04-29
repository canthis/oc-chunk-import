<?php namespace CanThis\ImportExport\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class ImportLogs extends Controller
{
    public $implement = ['Backend\Behaviors\ListController'];
    
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('CanThis.ImportExport', 'importexport', 'import-log');
    }
}