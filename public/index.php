<?php

// Habilita exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\ErrorMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Carrega as variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Cria o container DI
$container = new Container();

// Cria a aplicação
$app = AppFactory::createFromContainer($container);

// Define o caminho base
$app->setBasePath('/dimob_api');

// Adiciona middleware para parsing do body
$app->addBodyParsingMiddleware();

// Adiciona middleware de roteamento
$app->addRoutingMiddleware();

// Configura o middleware de erro para mostrar detalhes
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Adiciona middleware para CORS
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Rota principal - serve o index.html
$app->get('/', function (Request $request, Response $response) {
    $html = file_get_contents(__DIR__ . '/index.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// Rota para download de arquivos
$app->get('/output/{filename}', function (Request $request, Response $response, $args) {
    $filename = $args['filename'];
    $filepath = $_ENV['OUTPUT_DIR'] . $filename;
    
    if (!file_exists($filepath)) {
        $response->getBody()->write(json_encode(['error' => 'Arquivo não encontrado']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $file = file_get_contents($filepath);
    $response->getBody()->write($file);
    
    return $response
        ->withHeader('Content-Type', 'text/plain')
        ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
        ->withHeader('Content-Length', strlen($file));
});

// Rota para verificar se a API está funcionando
$app->get('/status', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'message' => 'API DIMOB está funcionando!'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Rota para conversão de arquivos
$app->post('/convert', function (Request $request, Response $response) {
    try {
        $controller = new \App\Controllers\ConversionController();
        return $controller->convert($request, $response);
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

// Executa a aplicação
$app->run();
