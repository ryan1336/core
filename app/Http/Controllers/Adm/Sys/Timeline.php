<?php

namespace App\Http\Controllers\Adm\Sys;

use AuthException;
use Input;
use Session;
use Response;
use View;
use VatsimSSO;
use Config;
use Redirect;
use App\Models\Sys\Timeline\Entry;

class Timeline extends \App\Http\Controllers\Adm\AdmController {

    public function getIndex(){
        $entries = Entry::orderBy("created_at", "DESC")
                        ->with("owner")
                        ->with("extra")
                        ->with("action")
                        ->limit(100)
                        ->get();
        return $this->viewMake("adm.sys.timeline")->with("entries", $entries);
    }
}
