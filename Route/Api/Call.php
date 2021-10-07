<?php

use App\Control\Auth\AuthController;
use App\Infra\Http\AdaptorRequest;
use App\Infra\Http\AdaptorResponse;
use App\Infra\Http\SlimRequest;
use App\Infra\Http\SlimResponse;
use Tuupola\Middleware\JwtAuthentication;
use function src\jwtAuth;
use App\Middleware\JwtDateTimeMiddleware;
use Slim\Http\Request;
use Slim\Http\Response;

$container = $app->getContainer();
$container['upload_directory'] = __DIR__ . '/tmp/sites';
$app->add(function ($request, $response, $next) {
  $response = $next($request, $response);
  return $response->withHeader('Content-Type', 'application/json');
});
// $app->add(new Tuupola\Middleware\CorsMiddleware([
//   "origin" => ["*"],
//   "methods" => ["GET", "POST", "OPTIONS"],    
//   "headers.allow" => ["Origin", "Content-Type", "Authorization", "Accept", "ignoreLoadingBar", "X-Requested-With", "Access-Control-Allow-Origin"],
//   "headers.expose" => [],
//   "credentials" => true,
//   "cache" => 0,        
// ]));

$app->post('/upload/{tipo_up}[/{tipo_doc}]', function ($request, $response, $args) {
  if (!isset($args['tipo_up']))
    return ['Sem tipo de upload!'];

  $info_up = [
    'tipo_up' => $args['tipo_up'],
  ];

  if (isset($args['tipo_doc']))
    $info_up['tipo_doc'] = $args['tipo_doc'];

  $directory = $this->get('upload_directory');
  $files = $request->getUploadedFiles();

  echo (new App\Control\Documento\Upload)->save($files, $info_up);
});

$app->get('/garm/document/download/{token}', function ($request, $response, $args) {
  echo (new App\Control\Documento\Upload)->download($args['token']);
});

// $app->post('/garm/api/login', AuthController::class . ':login');
$app->post('/garm/api/login', AuthController::class . ':login');


$app->post('/garm/api/refresh-token', AuthController::class . ':refreshToken');

// Consultas como dropdown
$app->post('/garm/api/{module}/{controller}/{method}[/{param}]', function (Request $request, Response $response, $args) {
  $adaptorRequest = new AdaptorRequest($request);
  $adaptorResponse = new AdaptorResponse($response);
  $module = $args['module'];
  $controller = $args['controller'];
  $method = $args['method'];
  $param = !empty($args['param']) ? $args['param'] : null;
  $newResponse = $response->withHeader('Content-type', 'application/json');
  $module = ucfirst($module);
  $filter = null;
  // Definindo o caminho do namespace das classes de controle
  $folder_controller = "\App\Control\\${module}\\";

  // Definindo o caminho completo da entidade
  $controll= "\App\Presentation\Controller\\${module}\\" . ucfirst($method);

  $controller = $folder_controller . ucfirst($controller);


  // Testando os metodos das entidades
  switch (strtolower(substr($controller, strripos($controller, "\\") + 1))) {
    case 'calc':
      switch ($method) {
        case 'item':
          $filter = json_decode($request->getBody(), false);
          echo (new $controller)->$method($filter);
          break;

        case 'total':
          $filter = json_decode($request->getBody(), false);
          echo (new $controller)->$method($filter);
          break;
      }
      break;

      // Requisição para a busca de calculo de dependencia de item
    case 'serdep':
      $filter = json_decode($request->getBody(), true);
      $newResponse = $response->withHeader('Content-type', 'application/json');
      return $newResponse->withJson((new $controller)->$method($request, $response, $filter));
      break;

      // Acronimo de servico
    case 'ser':
      $filter = json_decode($request->getBody(), true);
      $newResponse = $response->withHeader('Content-type', 'application/json');
      return $newResponse->withJson((new $controller)->$method($request, $response, $filter));
      break;
  }

  // Testando os metodos das entidades
  switch (strtolower(substr($controller, strripos($controller, "\\") + 1))) {
    case 'exec':
      switch ($method) {
        case 'filhote':
          return $response->withJson((new $controll($adaptorRequest, $adaptorResponse))->handle($adaptorRequest, $adaptorResponse));
          break;

        case 'renovar':
          return $response->withJson((new $controll($adaptorRequest, $adaptorResponse))->handle($adaptorRequest, $adaptorResponse));
          break;

        case 'versionar':
          return $response->withJson((new $controll($adaptorRequest, $adaptorResponse))->handle($adaptorRequest, $adaptorResponse));
          break;

        default:
          # code...
          break;
      }
    case 'lista':
      switch ($method) {
        case 'download':
          $filter = json_decode($request->getBody(), true);
          echo (new $controller)->download($request, $response, $filter);
          break;

        case 'filtered':
          $filter = json_decode($request->getBody(), true);
          echo (new $controller)->filtered($request, $response, $filter);
          break;

        case 'all':
          $newResponse = $response->withHeader('Content-type', 'application/json');
          return $newResponse->withJson((new $controller)->all($request, $response));
          break;

        case 'boem':
          $filter = json_decode($request->getBody(), true);
          $newResponse = $response->withHeader('Content-type', 'application/json');
          return $newResponse->withJson((new $controller)->boem($request, $response, $filter));
          break;

        case 'servico':
          $filter = json_decode($request->getBody(), true);
          $newResponse = $response->withHeader('Content-type', 'application/json');
          return $newResponse->withJson((new $controller)->servico($request, $response, $filter));

        case 'alldropdown':
          $newResponse = $response->withHeader('Content-type', 'application/json');
          return $newResponse->withJson((new $controller)->alldropdown($request, $response));

        case 'dropdownparam':
          $filter = json_decode($request->getBody());
          echo (new $controller)->dropdownParam($request, $response, $filter);
          break;

        case 'find':
          echo (new $controller)->find($request, $response, $param);
          break;

          // Itens da proposta
        case 'itens':
          $filter = json_decode($request->getBody(), true);
          echo (new $controller)->itens($request, $response, $filter);
          break;

        case 'byid':
          $filter = json_decode($request->getBody(), true);
          $newResponse = $response->withHeader('Content-type', 'application/json');
          return $newResponse->withJson((new $controller)->byid($request, $response, $filter));

          break;

          // Pesquisa por nome  
        case 'bynome':
          $filter = json_decode($request->getBody(), true);
          $newResponse = $response->withHeader('Content-type', 'application/json');
          return $newResponse->withJson((new $controller)->$method($request, $response, $filter));
          break;

        case 'byregime':
          $filter = json_decode($request->getBody(), true);
          $newResponse = $response->withHeader('Content-type', 'application/json');
          return $newResponse->withJson((new $controller)->$method($request, $response, $filter));
          break;

        case 'byregter':
          $filter = json_decode($request->getBody(), true);
          $newResponse = $response->withHeader('Content-type', 'application/json');
          return $newResponse->withJson((new $controller)->$method($request, $response, $filter));
          break;

        case 'byparam':
          $filter = json_decode($request->getBody(), true);
          $newResponse = $response->withHeader('Content-type', 'application/json');
          return $newResponse->withJson((new $controller)->$method($request, $response, $filter));
          break;

          // Pesquisa por nome  
        case 'byutilidade':
          $filter = $request->getBody()->getContents();
          if (!$filter) {
            exit();
          }
          echo (new $controller)->$method($request, $response, $filter);
          break;

          // Pesquisa por envolvidos 
        case 'byenvolvidos':
          $filter = json_decode($request->getBody()->getContents());
          if (!$filter) {
            exit();
          }
          echo (new $controller)->$method($request, $response, $filter);
          break;

          // Pesquisa por envolvidos 
        case 'bycaptacao':
          $filter = json_decode($request->getBody()->getContents());
          if (!$filter) {
            exit();
          }
          return $newResponse->withJson((new $controller)->$method($request, $response, $filter));

        case 'byoperacao':
          $filter = json_decode($request->getBody()->getContents());
          if (!$filter) {
            exit();
          }
          return $newResponse->withJson((new $controller)->$method($request, $response, $filter));

          // Pesquisa por servico 
        case 'bystatus':
          $filter = json_decode($request->getBody(), false);
          if (!$filter) {
            exit();
          }
          echo (new $controller)->$method($request, $response, $filter);
          break;

          // Pesquisa por servico 
        case 'byservico':
          $filter = json_decode($request->getBody(), true);
          if (!$filter) {
            exit();
          }
          echo (new $controller)->$method($request, $response, $filter);
          break;

        case 'bypapel':
          $filter = json_decode($request->getBody(), true);
          if (!$filter and !$param) {
            exit();
          }
          echo (new $controller)->$method($request, $response, ($filter ?? $param));
          break;

        default:
          # code...
          break;
      }
      break;


    case 'save':
      switch ($method) {
        case 'store':
          $filter = json_decode($request->getBody(), true);
          if (!$filter) {
            exit();
          }
          echo (new $controller)->store($request, $response, $filter);
          break;

        case 'filhote':
          $filter = json_decode($request->getBody(), true);
          return $response->withJson((new $controller)->$method($request, $response, $filter));
          break;

        case 'complementar':
          $filter = json_decode($request->getBody(), true);
          $newResponse = $response->withHeader('Content-type', 'text/plain');
          return $newResponse->withJson((new $controller)->$method($request, $response, $filter));
          break;

        case 'liberarcheia':
          $filter = json_decode($request->getBody(), true);
          $newResponse = $response->withHeader('Content-type', 'text/plain');
          return $newResponse->withJson((new $controller)->$method($request, $response, $filter));
          break;

        case 'recalcular':
          $filter = json_decode($request->getBody(), true);
          return $response->withJson((new $controller)->$method($request, $response, $filter));
          break;

        case 'renovar':
          $filter = json_decode($request->getBody(), true);
          return $response->withJson((new $controller)->$method($request, $response, $filter));
          break;

        case 'versionar':
          $filter = json_decode($request->getBody(), true);
          return $response->withJson((new $controller)->$method($request, $response, $filter));
          break;

        default:
          # code...
          break;
      }
      break;

    case 'recalcular':
      switch ($method) {
        case 'total':
          $filter = json_decode($request->getBody(), true);
          return $response->withJson((new $controller)->$method($request, $response, $filter));
          break;

        default:
          # code...
          break;
      }
      break;


    case 'record':
      switch ($method) {
        case 'delete':
          $filter = json_decode($request->getBody(), true);
          return $response->withJson((new $controller)->$method($request, $response, $filter));
          break;

        default:
          # code...
          break;
      }
      break;

    case 'ocorrencia':
      switch ($method) {
        case 'save':
          // print_r($request->getBody());exit();
          $filter = json_decode($request->getBody(), true);
          if (!$filter) {
            exit();
          }
          echo (new $controller)->save($request, $response, $filter);
          break;

        default:
          # code...
          break;
      }
      break;

    case 'historico':
      $id_record = json_decode($request->getBody(), true);
      if (!$id_record) {
        exit();
      }
      echo (new $controller)->all($request, $response, $id_record);
      break;

    case 'notificacao':
      switch ($method) {
        case 'solicitarbl':
          $pk_email = json_decode($request->getBody(), true);
          if (!$pk_email) {
            exit();
          }
          return $response->withJson((new $controller)->solicitarbl($request, $response, $pk_email));
          break;

        case 'enviofatura':
          $pk_email = json_decode($request->getBody(), true);
          if (!$pk_email) {
            exit();
          }
          return $response->withJson((new $controller)->enviofatura($request, $response, $pk_email));
          break;

        case 'solicitarce':
          $pk_email = json_decode($request->getBody(), true);
          if (!$pk_email) {
            exit();
          }
          return $response->withJson((new $controller)->$method($request, $response, $pk_email));
          break;

        case 'confrecbl':
          $pk_email = json_decode($request->getBody(), true);
          if (!$pk_email) {
            exit();
          }
          return $response->withJson((new $controller)->$method($request, $response, $pk_email));
          break;

        case 'confredestinacao':
          $pk_email = json_decode($request->getBody(), true);
          if (!$pk_email) {
            exit();
          }
          return $response->withJson((new $controller)->$method($request, $response, $pk_email));
          break;

          // Alterado data de atracação
        case 'altdtaatracacao':
          $pk_email = json_decode($request->getBody(), true);
          if (!$pk_email) {
            exit();
          }
          return $response->withJson((new $controller)->$method($request, $response, $pk_email));
          break;

          // Confirmar para o cliente
        case 'confcliente':
          $pk_email = json_decode($request->getBody(), true);
          if (!$pk_email) {
            exit();
          }
          return $response->withJson((new $controller)->$method($request, $response, $pk_email));
          break;

          // Confirmar para o cliente
        case 'confatracacao':
          $pk_email = json_decode($request->getBody(), true);
          if (!$pk_email) {
            exit();
          }
          return $response->withJson((new $controller)->$method($request, $response, $pk_email));
          break;
          // Confirmar para o cliente
        case 'presencacarga':
          $pk_email = json_decode($request->getBody(), true);
          if (!$pk_email) {
            exit();
          }
          return $response->withJson((new $controller)->$method($request, $response, $pk_email));
          // Confirmar para o cliente
        case 'soldidta':
          $pk_email = json_decode($request->getBody(), true);
          if (!$pk_email) {
            exit();
          }
          return $response->withJson((new $controller)->$method($request, $response, $pk_email));
          break;
      }

      $filter = json_decode($request->getBody(), true);
      return $response->withJson((new $controller)->$method($request, $response, $filter));
      break;
  }
})->add(new JwtDateTimeMiddleware())
  ->add(jwtAuth());

$app->get('/garm/api/{module}/{controller}/{method}[/{param}]', function ($request, $response, $args) {
  $module = $args['module'];
  $controller = $args['controller'];
  $method = $args['method'];
  $param = $args['param'] ?? null;
  $module = ucfirst($module);
  //Definindo o caminho do namespace das classes de controle
  $folder_controller = "\App\Control\\${module}\\";
  //Definindo o caminho completo da entidade
  $controller = $folder_controller . ucfirst($controller);
  //Testando os metodos das entidades
  switch (strtolower(substr($controller, strripos($controller, "\\") + 1))) {
    case 'lista':
      switch ($method) {
        case 'download':
          $filter = json_decode($request->getBody(), true);
          echo (new $controller)->download($request, $response, $param);
          break;

        case 'filtered':
          $filter = json_decode($request->getBody(), true);
          echo (new $controller)->filtered($request, $response, $filter);
          break;

        case 'all':
          echo (new $controller)->all($request, $response);
          break;

        case 'find':
          echo (new $controller)->find($request, $response, $param);
          break;

        case 'byid':
          if (!$param) {
            exit();
          }
          echo (new $controller)->byid($request, $response, $param);
          break;

        case 'mail':
          if (!$param) {
            exit();
          }
          echo (new $controller)->mail($request, $response, $param);
          break;

          // Pesquisa por nome  
        case 'bynome':
          $filter = $app->request->getBody();
          if (!$filter) {
            exit();
          }
          echo (new $controller)->$method($filter);
          break;

        case 'bypapel':
          if (!$param) {
            exit();
          }
          echo (new $controller)->$method($request, $response, $param);
          break;

        default:
          # code...
          break;
      }
      break;
  }
});

// Rota para view
$app->get('/garm/api/{module}/{controller}/{method}/{sort}/{order}/{limit}[/{page}[/{filter}]]', function ($request, $response, $args) {
  $module = $args['module'];
  $controller = $args['controller'];
  $method = $args['method'];
  $sort = $args['sort'];
  $order = $args['order'];
  $limit = $args['limit'];
  $page = $args['page'];
  $filter = isset($args['filter']) ? $args['filter'] : null;

  //Criando um array com os parametros 
  $param = array(
    'sort'   => $sort,
    'order'  => $order,
    'limit'  => $limit,
    'page'   => $page,
    'filter' => $filter,
    'module' => $module
  );

  $module = ucfirst($module);
  //Definindo o caminho do namespace das classes de controle
  $folder_controller = "\App\Control\\${module}\\";
  //Definindo o caminho completo da entidade
  $controller = $folder_controller . ucfirst($controller);
  //Testando os metodos das entidades
  switch (strtolower(substr($controller, strripos($controller, "\\") + 1))) {
    case 'lista':
      switch ($method) {
        case 'filtered':;
          echo (new $controller)->filtered($param);
          break;

        case 'all':
          $newResponse = $response->withHeader('Content-type', 'application/json');
          return $newResponse->withJson((new $controller)->$method($request, $response, $param));
          break;

        case 'alltotal':
          $newResponse = $response->withHeader('Content-type', 'application/json');
          return $newResponse->withJson((new $controller)->$method($request, $response, $param));
          break;

        case 'modelo':
          echo (new $controller)->modelo($request, $response, $param);
          break;

        case 'comum':
          echo (new $controller)->comum($request, $response, $param);
          break;

        case 'find':
          echo (new $controller)->find($request, $response, $param);
          break;

          // Captações monitoradas
        case 'mon':
          return $response->withJson((new $controller)->$method($request, $response, $param));
          break;

          // Captações monitoradas
        case 'ofprocesso':
          return $response->withJson((new $controller)->$method($request, $response, $param));
          break;



        case 'offatura':
          return $response->withJson((new $controller)->$method($request, $response, $param));
          break;

        case 'byid':
          echo (new $controller)->byid($request, $response, $param);
          break;

          // Pesquisa por nome  
        case 'bynome':
          echo (new $controller)->$method($request, $response, $filter);
          break;

        default:
          # code...
          break;
      }
      break;
  }
})->add(new JwtDateTimeMiddleware())
  ->add(jwtAuth());
