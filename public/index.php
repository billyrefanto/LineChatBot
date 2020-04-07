<?php
require __DIR__ . '/../vendor/autoload.php';
 
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
 
use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use \LINE\LINEBot\MessageBuilder\VideoMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;
 
$pass_signature = true;
 
// set LINE channel_access_token and channel_secret
$channel_access_token = "pSZbmpoqs27R1HVF/IbFtdYIhrOLrnBudlT6N+p4zXI0hGOyqJTjgb9m0F1NOs3iNQErlrF8yVyzdUzlook8LtLaMKAIZ7Ee5DtpQXS0fQtJsmFK/TE2PR5jCz6cJNuxOWpN9NC0MJPUTMLk3b+DSgdB04t89/1O/w1cDnyilFU=";
$channel_secret = "856fb55c1ec736082f03fcfb65f83b94";
 
// inisiasi objek bot
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);
 
 
$app = AppFactory::create();
$app->setBasePath("/public");
 
 
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello World!");
    return $response;
});
 
// buat route untuk webhook
$app->post('/webhook', function (Request $request, Response $response) use ($channel_secret, $bot, $httpClient, $pass_signature) {
    // get request body and line signature header
    $body = $request->getBody();
    $signature = $request->getHeaderLine('HTTP_X_LINE_SIGNATURE');
 
    // log body and signature
    file_put_contents('php://stderr', 'Body: ' . $body);
 
    if ($pass_signature === false) {
        // is LINE_SIGNATURE exists in request header?
        if (empty($signature)) {
            return $response->withStatus(400, 'Signature not set');
        }
 
        // is this request comes from LINE?
        if (!SignatureValidator::validateSignature($body, $channel_secret, $signature)) {
            return $response->withStatus(400, 'Invalid signature');
        }
    }
 
    $data = json_decode($body, true);
    if(is_array($data['events'])){
        foreach ($data['events'] as $event)
        {
            if ($event['type'] == 'message')
            {
                if($event['message']['type'] == 'text')
                {
                    // send same message as reply to user
                    $result = $bot->replyText($event['replyToken'], $event['message']['text']);
 
 
                    // or we can use replyMessage() instead to send reply message
                    // $textMessageBuilder = new TextMessageBuilder($event['message']['text']);
                    // $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
 
 
                    $response->getBody()->write(json_encode($result->getJSONDecodedBody()));
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus($result->getHTTPStatus());
                }
            }
        }
    }
 
    
});

// push messages
$app->get('/pushmessage', function ($req, $response) use ($bot) {
    // send push message to user
    $userId = 'U9449e62425c93c68c71eeb2fc465889b';
    $textMessageBuilder = new TextMessageBuilder('Halo, ini pesan push');
    $result = $bot->pushMessage($userId, $textMessageBuilder);

    // Push Message Sticker
    $userId = 'U9449e62425c93c68c71eeb2fc465889b';
    $stickerMessageBuilder = new StickerMessageBuilder(1, 106);
    $bot->pushMessage($userId, $stickerMessageBuilder);
 
    $response->getBody()->write("Pesan push berhasil dikirim!");
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($result->getHTTPStatus());
});

//Multicast Message
$app->get('/multicast', function($req, $response) use ($bot)
{
    // list of users
    $userList = [
        'U9449e62425c93c68c71eeb2fc465889b'
        // 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        // 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        // 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        // 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
    ];
 
    // send multicast message to user
    $textMessageBuilder = new TextMessageBuilder('Halo, ini pesan multicast');
    $result = $bot->multicast($userList, $textMessageBuilder);
 
 
    $response->getBody()->write("Pesan multicast berhasil dikirim");
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($result->getHTTPStatus());
});

//get userid
$app->get('/profile', function ($req, $response) use ($bot)
{
    // get user profile
    $userId = 'U9449e62425c93c68c71eeb2fc465889b';
    $result = $bot->getProfile($userId);
 
    $response->getBody()->write(json_encode($result->getJSONDecodedBody()));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($result->getHTTPStatus());
});
$app->run();