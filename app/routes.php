<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use App\Domain;

return function (App $app) {
    $container = $app->getContainer();

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->get('/v1/list', function(Request $request, Response $response){
        $args = $request->getQueryParams();
        
        $authUser = $request->getHeaderLine('Authorization');
        if (empty($authUser)) {
            $response = $response->withStatus(401);
            return $response;
        }

        $decoded = base64_decode(str_replace ('Basic ', '', $authUser));
        $authUser = explode(':', $decoded);

        if (empty($authUser)) {
            $response = $response->withStatus(401);
            return $response;
        }
        $username = $authUser[0];
        $password = $authUser[1];

        $hostValue = isset($args['host']) ? $args['host'] : 'localhost';
        $useSSL = isset($args['ssl']) ? $args['ssl'] : '0';
        $portValue = isset($args['port']) ? $args['port'] : '21';
        $dirValue = isset($args['dir']) ? $args['dir'] : '/';
        $simpleMode = isset($args['simpleMode']) ? $args['simpleMode'] : '1';
        
        //Coonect on FTP
        $ftp = new \FtpClient\FtpClient();
        $ftp->connect($hostValue, $useSSL == '1' , $portValue);

        $ftp->login($username, $password);
        
        //If success, List all files
        $items = $ftp->scanDir($dirValue);
        $result = array();
        
        foreach ($items as $item) {
            if ($simpleMode == '1') {
                array_splice($item, 0, 4);
            } 

            array_push($result, $item);
        }

        $body = $response->withHeader('Content-Type','application/json;charset=utf-8');
        $body->getBody()->rewind(); // Replace contents instead of trying to append
        $body->getBody()->write(json_encode($result));
        
        //Return Json of this files
        return $body;

        //$response = $response->withJson(json_encode($result));


        //return $response;
    });

    // $app->group('/users', function (Group $group) use ($container) {
    //     $group->get('', ListUsersAction::class);
    //     $group->get('/{id}', ViewUserAction::class);
    // });
};
