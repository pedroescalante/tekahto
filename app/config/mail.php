<?php

return array(
     'driver' => 'smtp',
     'host' => 'smtp-relay.gmail.com',
     'port' => 25, //25, 465 or 587
     'from' => array('address' => 'xxx@gmail.com', 'name' => 'myname'),
     'encryption' => '',
     'username' => 'xxx@gmail.com',
     'password' => '',
     'sendmail' => '/usr/sbin/sendmail -bs',
     'pretend' => false,
  );
