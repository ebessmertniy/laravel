<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Redirect;
use Sentinel;
use Activation;
use Reminder;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Validator;
use Mail;
use Storage;
use CurlHttp;
use Dingo\Api\Routing\Helpers;
use GuzzleHttp\Client;

class Api1Ccontroller extends Controller
{
    private static function errorMessages(array $messages = []){
        static $error_messages = [];
        if(!empty($messages)){
            $error_messages = array_merge($error_messages, $messages);
        }
        return $error_messages;
    }

    private static function successMessages(array $messages = []){
        static $success_messages = [];
        if(!empty($messages)){
            $success_messages = array_merge($success_messages, $messages);
        }
        return $success_messages;
    }

    private static function call($uri, $method = 'GET', $params = [], $custom_error_messages = []) {

        $client = new Client();

        $common_params = [
            'verify' => false,  //todo eb убрать после того как 1сники пофиксят ssl
            'auth' => [config('app.api_1c_login'), config('app.api_1c_pass')]
        ];

        $params = array_merge($params, $common_params);

        $uri = config('app.api_1c_main_url') . $uri;

        try {
            $res = $client->request($method, $uri, $params);
            $response = json_decode($res->getBody());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $error_response_json = (string)$e->getResponse()->getBody();
            $error_response = json_decode($error_response_json, 1);
            if($error_response){
                $custom_error_messages = array_merge($custom_error_messages, $error_response);
            }
            $custom_error_messages[] = $e->getMessage();
            self::errorMessages($custom_error_messages);
            $response = [];
        }

        $output = self::getOutput($response);

        return $output;
    }

    public static function getFranchises() {
        return self::call('getFranchise/Get', 'GET', [], ['Ошибка при попытке получить справочник франшиз']);
    }

    public static function getCoverageDays() {
        return self::call('getCoverageDays/Get', 'GET', [], ['Ошибка при попытке получить справочник сроков действия']);
    }

    public static function getTerritories() {
        return self::call('getCoverageTerritory/Get', 'GET', [], ['Ошибка при попытке получить справочник територий']);
    }

    public static function getRisks() {
        return self::call('getRisks/Get', 'GET', [], ['Ошибка при попытке получить справочник рисков']);
    }

    public static function getId(){
        return self::call('getID/Get', 'GET', [], ['Ошибка при попытке получить идентификатор']);
    }

    public static function api1CPost($method, $json){

        \Log::useDailyFiles(storage_path().'/logs/api1CPost.log');

        \Log::info('method');
        \Log::info($method);

        \Log::info('json');
        \Log::info($json);

        $arr = json_decode($json, 1);
        $params = [
            'json' => $arr
        ];

        $res = self::call($method, 'POST', $params, ['Ошибка при попытке выполнить запрос к методу API 1C ' . $method]);
        \Log::info('response');
        \Log::info(print_r($res, 1));

        return $res;
    }

    private static function getOutput($response = null){

        if(isset($response->OTP)){
            unset($response->OTP);
        }

        if(!empty(self::errorMessages())){
            $output = [
                'messages' => self::errorMessages()
            ];
        }elseif(is_null($response)){
            $output = [
                'messages' => self::errorMessages(['no_response'])
            ];
        }else{
            $output = [
                'response' => $response,
                'messages' => self::successMessages(['status' => 'success'])
            ];
        }

        return $output;

    }


}