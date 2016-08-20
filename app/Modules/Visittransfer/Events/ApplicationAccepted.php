<?php

namespace App\Modules\Visittransfer\Events;

use App\Events\Event;

use App\Modules\Visittransfer\Models\Application;
use Illuminate\Queue\SerializesModels;

class ApplicationAccepted extends Event {
    use SerializesModels;

    public $application = null;

    public function __construct(Application $application){
        $this->application = $application;
    }
}