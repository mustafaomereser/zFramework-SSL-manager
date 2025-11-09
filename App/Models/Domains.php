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
        return $this->findRelation(Certificates::class, $data['id'], 'domain_id')->orderBy(['id' => 'DESC'])->get();
    }
}
