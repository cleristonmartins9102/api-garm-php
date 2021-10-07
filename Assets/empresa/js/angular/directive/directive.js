app.directive('painelCadastroEmpresa', function() {
  return {
    restrict: 'E',
    replace: true,
    templateUrl: 'App/View/Empresa/templates/editor.html'
  }
})


app.directive('painelCPF', function() {
  return {
    restrict: 'E',
    replace: true,
    templateUrl: 'App/View/Empresa/templates/painel-c-p-f.html'
  }
})

app.directive('painelCNPJ', function() {
  return {
    restrict: 'E',
    replace: true,
    templateUrl: 'App/View/Empresa/templates/painel-c-n-p-j.html'
  }
})

// Painel tipo pessoa no cadastro de pessoa
app.directive('painelTP',
  function($rootScope, $compile) {
    return {
      template: '<div ng-include="templateDin" style="height:100%"></div>',
      restrict: 'E',
      replace: true,
      controller: function($scope) {
        $scope.templateDin = 'App/View/Empresa/templates/painel-c-p-f.html';
        $scope.pnl_t_p_cng = function(template) {
          console.log(template);
          $scope.templateDin = template;
        }
      }
    }
  }
);