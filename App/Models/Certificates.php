<?php

namespace App\Models;

use zFramework\Core\Abstracts\Model;

#[\AllowDynamicProperties]
class Certificates extends Model
{
    public $table = "certificates";

    public function beginQuery() 
    {
        // return $this->where();
    }
}
