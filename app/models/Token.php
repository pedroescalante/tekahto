<?php

class Token extends Eloquent {

	protected $table = 'tokens';

	public function fill($token){
		$this->token = $token;
	}

}
