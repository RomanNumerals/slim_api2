<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require './vendor/autoload.php';

$config['db']['host'] = 'localhost';
$config['db']['user'] = 'root';
$config['db']['pass'] = 'root';
$config['db']['dbname'] = 'slim_db';

$app = new \Slim\App(['settings' => $config]);

$app->get('/hello/{name}',
  function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->("Hello, $name !");
    return $response;
  }
);

$app->run();

$container = $app->getContainer();
$container['logger'] = function($c) {
  $logger = new \Monolog\Logger('my_logger');
  $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
  $logger->pushHandler($file_handler);
  return $logger;
};

$app->get('/people', function(Request $request, Response $response){
  $this->logger->addInfo("GET /people");
  $people = $this->db->query('SELECT * FROM people')->fetchAll();
  $jsonResponse = $response->withJson($people);

  return $jsonResponse;
});

$app->put('/people/{id}', function (Request $request, Response $response, array $args) {
  $id = $args['id'];
  $this->logger->addInfo("PUT /people/".$id);

  // build query string
  $updateString = "UPDATE people SET ";
  $fields = $request->getParsedBody();
  $keysArray = array_keys($fields);
  $last_key = end($keysArray);
  foreach($fields as $field => $value) {
    $updateString = $updateString . "$field = '$value'";
    if ($field != $last_key) {
      // conditionally add a comma to avoid sql syntax problems
      $updateString = $updateString . ", ";
    }
  }
  $updateString = $updateString . " WHERE id = $id;";

  // execute query
  $this->db->exec($updateString);
  // return updated record
  $person = $this->db->query('SELECT * from people where id='.$id)->fetch();
  $jsonResponse = $response->withJson($person);

   return $jsonResponse;
 });

$app->delete('/people/{id}', function(Request $request, Response $response, array $args) {
  $id = $args['id'];
  $this->logger->addInfo("DELETE /people/" .$id);
  $person = $this->db->exec('DELETE FROM people WHERE id=' .$id);
  $jsonResponse = $response->withJson($person);

  return;
});
