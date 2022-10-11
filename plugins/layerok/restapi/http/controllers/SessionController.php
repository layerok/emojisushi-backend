<?php
namespace Layerok\Restapi\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Session;

class SessionController extends Controller
{
    public function create() {
        Session::setId(null);
        Session::put([
            'is_authorized' => false
        ]);
        Session::save();
        $data = Session::all();
        $resp = collect([
            'id' => Session::getId()
        ]);

        $resp->add($data);

        return response()->json($resp->toArray());
    }

    public function get($id) {
        $handler = Session::getHandler();
        $data = $handler->read($id);

        if(!$data) {
            return response('Cannot find session for id ' . $id, 404);
        }
        Session::setId($id);
        $data = Session::all();

        return response()->json(array_merge([
            'id' => Session::getId()
        ], $data));
    }
}
