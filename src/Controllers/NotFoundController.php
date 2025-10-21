<?php

namespace App\Controllers;

class NotFoundController
{
    public function index()
    {
        header("HTTP/1.0 404 Not Found");
        echo '404 Not Found';
    }
}
