<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Google_Client;
use Google_Service_Sheets;
use Exception;
use Log;


class HomeController extends Controller
{
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
            $input[] = [$this->getTime($message, $rule), $message, $this->getStatus($message, $request['server'])];
        }
        $this->updateSheet($request['sheet_id'], $request['sheet_name'], $input);
        return redirect()->route('home')->with('status', 'Convert success !');
    }

    public function getTime($msg, $rule){
        $msg = str_replace($rule, "", $msg);
        return substr($msg, 0, 5);
    }

    public function updateSheet($sheetId, $sheetName, $data){
        $client = $this->getGooogleClient();
        $service = new Google_Service_Sheets($client);
        $spreadsheetId = $sheetId;
        $range = $sheetName.'!A2:C';

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
        if($server == 'dam'){
            if (strpos($message, 'dam email') !== false) {
                return 'email';
            }

            if ( (strpos($message, "opened") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false ) || strpos($message, "ログ全文は上記リンクから") !== false) {
                return 'error';
            }

            if (strpos($message, "closed") !== false && strpos($message, "Target") !== false && strpos($message, "New Relic") !== false ) {
                return 'ignore';
            }

            return "";
        }
    }
}
