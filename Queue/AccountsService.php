<?php 

namespace Queue;

use Log;

class AccountsService {

    public function fire($job, $data)
    {
        Log::info("Shoot!");
    }

}
