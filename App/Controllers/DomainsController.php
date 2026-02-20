<?php

namespace App\Controllers;

use App\Helpers\API;
use App\Models\Domains;
use zFramework\Core\Abstracts\Controller;
use zFramework\Core\Facades\Alerts;
use zFramework\Core\Facades\Cookie;
use zFramework\Core\Facades\Response;
use zFramework\Core\Validator;

#[\AllowDynamicProperties]
class DomainsController extends Controller
{

    public function __construct()
    {
        $this->domains = new Domains;
    }

    /** Index page | GET: /
     * @return mixed
     */
    public function index()
    {
        Cookie::set('domain', request('key'));
    }

    /** Show page | GET: /id
     * @param integer $id
     * @return mixed
     */
    public function show($id)
    {
        $item            = $this->domains->where('id', $id)->firstOrFail();
        $domain_status   = API::getSSLStatus($item['fulldomain']);
        $item_subdomains = $item['subdomains']();
        return view('app.pages.domains.show', compact('item', 'domain_status', 'item_subdomains'));
    }

    /** Create page | GET: /create
     * @return mixed
     */
    public function create()
    {
        return view('app.modals.domains.edit-or-create');
    }

    /** Edit page | GET: /id/edit
     * @param integer $id
     * @return mixed
     */
    public function edit($id)
    {
        $item = $this->domains->where('id', $id)->first();
        return view('app.modals.domains.edit-or-create', compact('item'));
    }


    public function setAll()
    {
        $validate = Validator::validate($_REQUEST, [
            'domain'      => ['required'],
            'cpanel'      => ['nullable'],
            'main_domain' => ['nullable'],
        ]);

        $validate['fulldomain'] = $validate['domain'];
        $validate['cpanel'] = json_encode($validate['cpanel'] ?? [], JSON_UNESCAPED_UNICODE);

        if (!$validate['main_domain']) unset($validate['main_domain']);
        else {
            $parent = $this->domains->findOrFail($validate['main_domain']);
            $validate['fulldomain'] = $validate['domain'] . "." . $parent['domain'];
        }

        return $validate;
    }

    /** POST page | POST: /
     * @return mixed
     */
    public function store()
    {
        $this->domains->insert($this->setAll());
        Alerts::success('Domain eklendi.');
        return Response::json(['status' => 1]);
    }

    /** Update page | PATCH/PUT: /id
     * @param integer $id
     * @return mixed
     */
    public function update($id)
    {
        $this->domains->where('id', $id)->update($this->setAll());
        Alerts::success('Domain düzenlendi.');
        return Response::json(['status' => 1]);
    }

    /** Delete page | DELETE: /id
     * @param integer $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->domains->where('id', $id)->delete();
        Alerts::success('Domain silindi.');
        return Response::json(['status' => 1]);
    }
}
