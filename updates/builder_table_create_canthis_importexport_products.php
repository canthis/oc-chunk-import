<?php namespace CanThis\ImportExport\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateCanthisImportexportProducts extends Migration
{
    public function up()
    {
        Schema::create('canthis_importexport_products', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('sku');
            $table->decimal('price', 10, 2);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('canthis_importexport_products');
    }
}
