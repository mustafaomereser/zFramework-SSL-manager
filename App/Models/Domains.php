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
        return $this->findRelation(Certificates::class, $data['fulldomain'], 'domain')->orderBy(['id' => 'DESC'])->get();
    }

    public function subdomains(array $data)
    {
        return $this->hasMany(Domains::class, $data['id'], 'main_domain');
    }

    public function parent(array $data)
    {
        return $this->hasOne(Domains::class, $data['main_domain'], 'id');
    }
}
