<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateKeywordsTable.
 */
class CreateKeywordsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('keywords', function(Blueprint $table) {
            $table->increments('id');
            $table->string('device_id');
            $table->string('keyword');
            $table->boolean('notification_status')->default(true);
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
		Schema::drop('keywords');
	}
}
