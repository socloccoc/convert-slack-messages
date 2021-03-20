<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateProductsTable.
 */
class CreateProductsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('products', function(Blueprint $table) {
            $table->increments('id');
            $table->string('keyword_id');
            $table->string('device_id');
            $table->string('product_id');
            $table->string('product_name')->nullable();
            $table->string('product_image')->nullable();
            $table->string('product_price')->nullable();
            $table->string('timestamp')->nullable();
            $table->string('status')->comment('0: pending, 1: sent');
            $table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('products');
	}
}
