<?php
$app->get('/empresa/', function(){
  //Chama a view passando para o contrutor o nome da pasta e chama o metodo render passando o nome do arquivo
  (new App\Mvc\View('Empresa'))->render('index');
});
