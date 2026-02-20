<?php

namespace App\Models;

use zFramework\Core\Abstracts\Model;

#[\AllowDynamicProperties]
class Domains extends Model
{
    public $table = "domains";

    public function beginQuery()
    {
        // return $this->where();
    }

    public function certificates(array $data)
    {
        return $this->findRelation(Certificates::class, $data['domain'], 'domain')->orderBy(['id' => 'DESC'])->get();
    }
}
