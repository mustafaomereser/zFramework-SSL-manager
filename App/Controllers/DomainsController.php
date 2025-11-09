<?php

namespace App\Controllers;

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
        abort(404);
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
            'domain'     => ['required'],
            'public_dir' => ['required'],
            'ftp'        => ['nullable'],
            'cpanel'     => ['nullable'],
        ]);

        $validate['ftp']    = json_encode($validate['ftp'] ?? [], JSON_UNESCAPED_UNICODE);
        $validate['cpanel'] = json_encode($validate['cpanel'] ?? [], JSON_UNESCAPED_UNICODE);

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
