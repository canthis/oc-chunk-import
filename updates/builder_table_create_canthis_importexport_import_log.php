<?php namespace CanThis\ImportExport\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCanthisImportexportImportLog extends Migration
{
    public function up()
    {
        Schema::create('canthis_importexport_import_log', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('author_id')->unsigned();
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('canthis_importexport_import_log');
    }
}