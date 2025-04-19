<?php
require_once "openid.php";
session_start();

class SteamAuth {
    public function redirect() {
        $openid = new LightOpenID($_SERVER['HTTP_HOST']);
        $openid->identity = "http://steamcommunity.com/openid";
        $openid->returnUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/userInfo.php";
        header("Location: " . $openid->authUrl());
        exit;
    }

    public function validate() {
        $openid = new LightOpenID($_SERVER['HTTP_HOST']);
        if ($openid->mode == 'cancel') {
            return false;
        } elseif ($openid->validate()) {
            $id = $openid->identity;
            preg_match("/\\/(\\d{17,25})$/", $id, $matches);
            $_SESSION['steamid'] = $matches[1];
            return true;
        }
        return false;
    }
}
