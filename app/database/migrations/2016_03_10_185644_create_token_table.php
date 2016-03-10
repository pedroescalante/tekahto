<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTokenTable extends Migration {

	public function up()
	{
		Schema::create('tokens', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('accessToken', 25);
			$table->string('refreshToken', 25);
			$table->integer('endOfLife');
			$table->string('token_type', 25);
			$table->string('scope', 50);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('tokens');
	}

}
