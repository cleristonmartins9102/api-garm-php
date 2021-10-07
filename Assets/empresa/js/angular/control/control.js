app.controller('appZoho', function($compile, $scope, $http) {
  angular.element() === jQuery() === $();

  $scope.short_mail_list = [];

  $scope.backend = {
    pessoas: {
      get_pessoas: function(callback) {
        url = "http://127.0.0.1/zoho/api/empresa/list";
        $http({
          url: url,
          method: 'POST',
        }).then(function(resp) {
          $scope.pessoas = resp.data;
          // console.log($scope.pessoas);
        })
      }
    }
  };

  $scope.ui = {
    folder_template: 'App/View/Empresa/templates',
    //Selecionar os campos para preenchimento em pessoa juridica
    pnl_t_p_cng: {
      pnlCNPJ: function(template) {
        return `${$scope.ui.folder_template}/painel-c-n-p-j.html`;
      },
      pnlCPF: function(template) {
        return `${$scope.ui.folder_template}/painel-c-p-f.html`;
      }
    }
  }
  $('form').validator()

  $scope.tool = {
    validateEntry: function(e) {
      console.log("dsds");
      console.log(this.value);
    }
  }

  angular.element('div.card').css('background', 'red');

  $scope.backend.pessoas.get_pessoas();
  $scope.tes = function() {
    alert('dsds');
  }
})