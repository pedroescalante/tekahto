<?php

class Token extends Eloquent {

	protected $table = 'tokens';

	protected $fillable   = ['token'];

	public function fill(array $attributes){
		dd($attributes);
	}

}
