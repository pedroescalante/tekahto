<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTokenTable extends Migration {

	public function up()
	{
		Schema::create('tokens', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('token');
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('tokens');
	}

}
