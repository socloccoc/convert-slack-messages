<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use Google_Client;
use Google_Service_Sheets;
use Exception;
use Log;


class HomeController extends Controller
{
    const MONTH = '3月';
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function convert(Request $request){
        $server = config('constants.servers.'.$request['server']);
        $rule = $server['rule'];
        $replate = $server['replace'];
        $body = str_replace(["\n"], "", $request['body']);
        $body = str_replace($rule, $replate, $body);
        $messages = explode("\n", $body);
        $input = [];
        foreach ($messages as $message){
            if(empty($message)) continue;
            if(strlen($message) > 10000){
                $message = substr($message, 0, 10000);
            }

            $msg = str_replace($rule, "", $message);
            $time = substr($msg, 0, 5);
            $date = '';
            // dam
            if($request['server'] == 'dam'){
                if (strpos($message, 'dam email') !== false) {
                    $msgEx = explode(self::MONTH, $message);
                    if(isset( $msgEx[1])){
                        $msgEx2 = explode('日', $msgEx[1]);
                        $date = '2021-03-'.$msgEx2[0];
                    }
                    $msgEx = explode('昨日の', $message);
                    if(isset( $msgEx[1])){
                        $day = Carbon::now()->subDay()->format('d');
                        $date = '2021-03-'.$day;
                    }
                    $msgEx = explode('今日の', $message);
                    if(isset( $msgEx[1])){
                        $day = Carbon::now()->format('d');
                        $date = '2021-03-'.$day;
                    }
                }

            }

            // dwjp
            if($request['server'] == 'dwjp'){
                if (strpos($message, 'cloudwatch-logs-alert-bot') !== false) {
                    $msgEx = explode('/Mar/2021', $message);
                    if(isset( $msgEx[1])){
                        $day = substr($msgEx[0], -2, 2);
                        $date = '2021-03-'.$day;
                    }
                }

            }

            // jpstore
            if($request['server'] == 'jpstore'){
                if (strpos($message, 'emailアプリ') !== false) {
                    $msgEx = explode(self::MONTH, $message);
                    if(isset( $msgEx[1])){
                        $msgEx2 = explode('日', $msgEx[1]);
                        $date = '2021-03-'.$msgEx2[0];
                    }
                    $msgEx = explode('昨日の', $message);
                    if(isset( $msgEx[1])){
                        $day = Carbon::now()->subDay()->format('d');
                        $date = '2021-03-'.$day;
                    }
                    $msgEx = explode('今日の', $message);
                    if(isset( $msgEx[1])){
                        $day = Carbon::now()->format('d');
                        $date = '2021-03-'.$day;
                    }
                }

            }

            // saas
            if($request['server'] == 'saas'){
                if (strpos($message, 'saas emailアプリ') !== false) {
                    $msgEx = explode(self::MONTH, $message);

                    if(isset( $msgEx[1])){
                        $msgEx2 = explode('日', $msgEx[1]);
                        $date = '2021-03-'.$msgEx2[0];
                    }

                    $msgEx = explode('昨日の', $message);
                    if(isset( $msgEx[1])){
                        $day = Carbon::now()->subDay()->format('d');
                        $date = '2021-03-'.$day;
                    }

                    $msgEx = explode('今日の', $message);
                    if(isset( $msgEx[1])){
                        $day = Carbon::now()->format('d');
                        $date = '2021-03-'.$day;
                    }
                }
            }

            if($request['server'] == 'sumo'){
                if (strpos($message, 'cloudwatch-logs-alert-bot') !== false) {
                    $msgEx = explode('2021-03-', $message);

                    if(isset($msgEx[1])){
                        $day = substr($msgEx[1], 0, 2);
                        $date = '2021-03-'.$day;
                    }

                }
            }

            $input[] = [$date, $time, $message, $this->getStatus($message, $request['server'])];
        }
        $this->updateSheet($request['sheet_id'], $request['sheet_name'], $input);
//        return redirect()->route('home')->with('status', 'Convert success !');
    }

    public function getTime($msg, $rule){
        $msg = str_replace($rule, "", $msg);
        return substr($msg, 0, 5);
    }

    public function updateSheet($sheetId, $sheetName, $data){
        $client = $this->getGooogleClient();
        $service = new Google_Service_Sheets($client);
        $spreadsheetId = $sheetId;
        $range = $sheetName.'!A2:D';

        // get values
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();

        print_r($values);

        $requestBody = new \Google_Service_Sheets_ValueRange([
            'values' => $data
        ]);

        $params = [
            'valueInputOption' => 'RAW'
        ];

        $service->spreadsheets_values->update($spreadsheetId, $range, $requestBody, $params);
    }

    public function getGooogleClient()
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig(config_path('credentials.json'));
        $client->setAccessType('offline');

        $tokenPath = storage_path('app/token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }

            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }

        return $client;
    }

    public function getStatus($message, $server){

        if($server == 'sumo'){
            if (strpos($message, 'saas email') !== false) {
                return 'email';
            }

            if (strpos($message, 'incoming-webhook') !== false) {
                return 'error';
            }

            if ( (strpos($message, "opened") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false )) {
                return 'error';
            }

            if (strpos($message, "closed") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false ) {
                return 'ignore';
            }

            if (strpos($message, "/sumo/production/dski-tool-error") !== false ||
                strpos($message, "/sumo/production/urushi-app") !== false ||
                strpos($message, "/sumo/production/data-replica-heartbeat/alarm") !== false ||
                strpos($message, "/sumo/production/batch-heartbeat/alarm") !== false ||
                strpos($message, "/sumo/production/dski-web-application-error") !== false ||
                strpos($message, "/sumo/production/urushi-app") !== false ||
                strpos($message, "WordpressFeedColumnRepository.scala") !== false
            )
            {
                return 'ignore';
            }

            return "error";
        }

        if($server == 'saas'){
            if (strpos($message, 'saas email') !== false) {
                return 'email';
            }

            if ( (strpos($message, "opened") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false ) || strpos($message, "ログ全文は上記リンクから") !== false) {
                return 'error';
            }

            if (strpos($message, "closed") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false ) {
                return 'ignore';
            }

            if (strpos($message, "3時間以上ジャケ写の同期が行われていません。") !== false ||
                strpos($message, "バッチが停止していないか確認してください。") !== false ||
                strpos($message, "MOSログインポートバッチ") !== false ||
                strpos($message, "Service_SpAffiliate_SendConversion") !== false ||
                strpos($message, "Pegasus向けのログをS3へアップロード(月次整合)") !== false ||
                strpos($message, "DLランキング集計(素材別ランキング生成)") !== false ||
                strpos($message, "DLランキング集計(楽曲別ランキング生成)") !== false ||
                strpos($message, "DLランキング集計(CRBTログ集計2)") !== false ||
                strpos($message, "DLランキング集計(CRBTログ集計)") !== false ||
                strpos($message, "DLランキング集計(MOSログ集計)") !== false ||
                strpos($message, "DLランキング集計(タイアップ別ランキング生成)") !== false
            )
            {
                return 'ignore';
            }

            return "error";
        }

        if($server == 'baas'){
            if (strpos($message, 'emailアプリ') !== false) {
                return 'email';
            }

            if ( (strpos($message, "opened") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false )) {
                return 'error';
            }

            if (strpos($message, "closed") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false ) {
                return 'ignore';
            }

            if (strpos($message, "Processor load is too high cds-prod") !== false ||
                strpos($message, "/connect/production/connect-web-error/connect-web-application-errors") !== false ||
                strpos($message, "/sintral/production/sinwa-app-error") !== false ||
                strpos($message, "ess-db-secondary.aws-in.dwango.jp") !== false ||
                strpos($message, "baas-ess-api ess-admin.in.dwango.jp/api/v1/_factory/buffer") !== false ||
                strpos($message, "Monitor failed for location dwango_internal") !== false ||
                strpos($message, "DLランキング集計(楽曲別ランキング生成)") !== false ||
                strpos($message, "DLランキング集計(CRBTログ集計2)") !== false ||
                strpos($message, "DLランキング集計(CRBTログ集計)") !== false ||
                strpos($message, "DLランキング集計(MOSログ集計)") !== false ||
                strpos($message, "DLランキング集計(タイアップ別ランキング生成)") !== false
            )
            {
                return 'ignore';
            }

            return "error";
        }

        if($server == 'jpstore'){
            if (strpos($message, 'emailアプリ') !== false) {
                return 'email';
            }

            if ( (strpos($message, "opened") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false )) {
                return 'error';
            }

            if ( (strpos($message, "Google Cloud Monitoring") !== false)) {
                return 'error';
            }

            if (strpos($message, "closed") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false ) {
                return 'ignore';
            }

            return "error";
        }

        if($server == 'dam'){
            if (strpos($message, 'dam email') !== false) {
                return 'email';
            }

            if (
                strpos($message, 'dam/production/difference_reporter') !== false ||
                strpos($message, 'インポーターの取込失敗監視') !== false

            ) {
                return 'ignore';
            }

            if ( (strpos($message, "opened") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false ) || strpos($message, "ログ全文は上記リンクから") !== false) {
                return 'error';
            }

            if (strpos($message, "closed") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false ) {
                return 'ignore';
            }

            return "error";
        }

        if($server == 'dwjp'){
            if (strpos($message, 'dwjp notification') !== false) {
                return 'email';
            }

            if ( (strpos($message, "opened") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false )) {
                return 'error';
            }

            if (strpos($message, "job noren/production_batch") !== false && strpos($message, "cloudwatch-logs-alert-bot") !== false ) {
                return 'ignore';
            }

            if (strpos($message, "/dwango-jp/production/noren-web-app") !== false && strpos($message, "cloudwatch-logs-alert-bot") !== false ) {
                return 'ignore';
            }

            if (strpos($message, "noren/production_batch") !== false && strpos($message, "incoming-webhook") !== false ) {
                return 'ignore';
            }

            if (strpos($message, "closed") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false ) {
                return 'ignore';
            }

            if (strpos($message, "/aws/lambda/prod-dwjp-ranking-check-cluster-step-status") !== false && strpos($message, "cloudwatch-logs-alert-bot") !== false ) {
                return 'ignore';
            }

            if (strpos($message, "/dwango-jp/production/cot-app") !== false && strpos($message, "cloudwatch-logs-alert-bot") !== false ) {
                return 'ignore';
            }

            if (strpos($message, "/job noren/production_batch") !== false && strpos($message, "cloudwatch-logs-alert-bot") !== false ) {
                return 'ignore';
            }

            return "error";
        }
    }
}
