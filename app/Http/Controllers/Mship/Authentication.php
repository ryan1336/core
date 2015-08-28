<?php

namespace App\Http\Controllers\Mship;

use Config;
use Auth;
use Request;
use URL;
use Input;
use Session;
use Redirect;
use VatsimSSO;
use App\Models\Mship\Account;
use App\Models\Mship\Qualification as QualificationType;

class Authentication extends \App\Http\Controllers\BaseController {

    public function getRedirect() {
        // If there's NO basic auth, send to login.
        if (!Auth::check()) {
            return Redirect::route("mship.auth.login");
        }

        // Has this user logged in from a similar IP as somebody else?
        $check = Account::withIp($this->_account->last_login_ip)
                        ->where("last_login", ">=", \Carbon\Carbon::now()->subHours(4))
                        ->where("account_id", "!=", $this->_account->account_id)
                        ->count();

        if($check > 0 && !Session::get("auth_duplicate_ip", false)){
            $this->_account->auth_extra = 0;
            $this->_account->auth_extra_at = null;
            $this->_account->save();
            Session::set("auth_duplicate_ip", true);
        }

        // If there's NO secondary, but it's needed, send to secondary.
        if (!$this->_account->auth_extra && $this->_account->current_security && !Session::has("auth_override")) {
            return Redirect::route("mship.security.auth");
        }

        // What about if there's secondary, but it's expired?
        if (!Session::has("auth_override") && $this->_account->auth_extra && (!$this->_account->auth_extra_at OR $this->_account->auth_extra_at->addHours(4)->isPast())) {
            $this->_account->auth_extra = 0;
            $this->_account->auth_extra_at = null;
            $this->_account->save();
            return Redirect::route("mship.auth.redirect");
        }

        // What about if there's no secondary? We can set this to not required!
        if(!$this->_account->current_security){
            $this->_account->auth_extra = 2;
            $this->_account->save();
        }

        // Send them home!
        Session::forget("auth_duplicate_ip");

        $returnURL = Session::pull("auth_return", URL::route("mship.manage.dashboard"));
        if($returnURL == URL::route("mship.manage.dashboard") && $this->_account->has_unread_notifications){
            Session::put("force_notification_read_return_url", $returnURL);
            $returnURL = URL::route("mship.notification.list");
        }
        return Redirect::to($returnURL);
    }

    public function getLoginAlternative() {
        if (!Session::has("cert_offline")) {
            return Redirect::route("mship.auth.login");
        }

        // Display an alternative login form.
        $this->_pageTitle = "Alternative Login";
        return $this->viewMake("mship.authentication.login_alternative");
    }

    public function postLoginAlternative() {
        if (!Session::has("cert_offline")) {
            return Redirect::route("mship.auth.login");
        }

        if (!Input::get("cid", false) OR ! Input::get("password", false)) {
            return Redirect::route("mship.auth.loginAlternative")->withError("You must enter a cid and password.");
        }

        // Let's find the member.
        $account = Account::find(Input::get("cid"));

        if (!$account) {
            return Redirect::route("mship.auth.loginAlternative")->withError("You must enter a valid cid and password combination.");
        }

        // Let's get their current security and verify...
        if (!$account->current_security OR !$account->current_security->verifyPassword(Input::get("password"))) {
            return Redirect::route("mship.auth.loginAlternative")->withError("You must enter a valid cid and password combination.");
        }

        // We're in!
        // Let's do lots of logins....
        $account->last_login = \Carbon\Carbon::now();
        $account->last_login_ip = array_get($_SERVER, 'REMOTE_ADDR', '127.0.0.1');
        $account->auth_extra = 1;
        $account->auth_extra_at = \Carbon\Carbon::now();
        $account->save();

        Auth::login($account, true);
        Session::forget("cert_offline");

        // Let's send them over to the authentication redirect now.
        return Redirect::route("mship.auth.redirect");
    }

    public function getLogin() {
        Session::set("auth_return", Input::get("returnURL", URL::route("mship.manage.dashboard")));

        // Do we already have some kind of CID? If so, we can skip this bit and go to the redirect!
        if (Auth::check() OR Auth::viaRemember()) {

            // Let's just check we're not demanding forceful re-authentication via secondary!
            if(Request::query("force", false)){
                $user = Auth::user();
                $user->auth_extra = 0;
                $user->auth_extra_at = null;
                $user->save();
            }

            return Redirect::route("mship.auth.redirect");
        }

        // Just, native VATSIM.net SSO login.
        return VatsimSSO::login(
                        [URL::route("mship.auth.verify"), "suspended" => true, "inactive" => true], function($key, $secret, $url) {
                    Session::put('vatsimauth', compact('key', 'secret'));
                    return Redirect::to($url);
                }, function($error) {
                    Session::set("cert_offline", true);
                    return Redirect::route("mship.auth.loginAlternative");
                }
        );
    }

    public function getVerify() {

        if (Input::get('oauth_cancel') !== null) {
            return Redirect::away('http://vatsim-uk.co.uk');
        }

        if (!Session::has('vatsimauth')) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $session = Session::get('vatsimauth');

        if (Input::get('oauth_token') !== $session['key']) {
            throw new \AuthException('Returned token does not match');
        }

        if (!Input::has('oauth_verifier')) {
            throw new \AuthException('No verification code provided');
        }

        return VatsimSSO::validate($session['key'], $session['secret'], Input::get('oauth_verifier'), function($user, $request) {
                    Session::forget('vatsimauth');

                    // At this point WE HAVE data in the form of $user;
                    $account = Account::find($user->id);
                    if (is_null($account)) {
                        $account = new Account();
                        $account->account_id = $user->id;
                    }
                    $account->name_first = $user->name_first;
                    $account->name_last = $user->name_last;
                    $account->addEmail($user->email, true, true);

                    // Sort the ATC Rating out.
                    $atcRating = $user->rating->id;
                    if ($atcRating > 7) {
                        // Store the admin/ins rating.
                        if ($atcRating >= 11) {
                            $account->addQualification(QualificationType::ofType("admin")->networkValue($atcRating)->first());
                        } else {
                            $account->addQualification(QualificationType::ofType("training_atc")->networkValue($atcRating)->first());
                        }

                        $atcRatingInfo = \VatsimXML::getData($user->id, "idstatusprat");
                        if (isset($atcRatingInfo->PreviousRatingInt)) {
                            $atcRating = $atcRatingInfo->PreviousRatingInt;
                        }
                    }
                    $account->addQualification(QualificationType::ofType("atc")->networkValue($atcRating)->first());

                    for ($i = 1; $i <= 256; $i*=2) {
                        if ($i & $user->pilot_rating->rating) {
                            $account->addQualification(QualificationType::ofType("pilot")->networkValue($i)->first());
                        }
                    }

                    $account->determineState($user->region->code, $user->division->code);

                    $account->last_login = \Carbon\Carbon::now();
                    $account->last_login_ip = array_get($_SERVER, 'REMOTE_ADDR', '127.0.0.1');
                    if ($user->rating->id == 0) {
                        $account->is_inactive = 1;
                    } else {
                        $account->is_inactive = 0;
                    }
                    if ($user->rating->id == -1) {
                        $account->is_network_banned = 1;
                    } else {
                        $account->is_network_banned = 0;
                    }
                    $account->session_id = Session::getId();
                    $account->experience = $user->experience;
                    $account->joined_at = $user->reg_date;
                    $account->auth_extra = 0;
                    $account->determineState($user->region->code, $user->division->code);
                    $account->save();

                    Auth::login($account, true);

                    // Let's send them over to the authentication redirect now.
                    return Redirect::route("mship.auth.redirect");
                }, function($error) {
                    throw new \AuthException($error['message']);
                }
        );
    }

    public function getLogout($force = false) {
        Session::set("logout_return", Input::get("returnURL", "/mship/manage/dashboard"));

        if ($force) {
            return $this->postLogout($force);
        }
        return $this->viewMake("mship.authentication.logout");
    }

    public function postLogout($force = false) {
        if (Auth::check() && (Input::get("processlogout", 0) == 1 OR $force)) {
            $user = Auth::user();
            $user->auth_extra = 0;
            $user->auth_extra_at = NULL;
            $user->save();
            Auth::logout();
        }
        return Redirect::to(Session::pull("logout_return", "/mship/manage/landing"));
    }

    public function getInvisibility() {
        // Toggle
        if (Auth::user()->is_invisible) {
            Auth::user()->is_invisible = 0;
        } else {
            Auth::user()->is_invisible = 1;
        }
        Auth::user()->save();

        return Redirect::route("mship.manage.landing");
    }

}
