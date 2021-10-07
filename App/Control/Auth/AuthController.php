<?php
namespace App\Control\Auth;

use Slim\Http \ {
    Request,
    Response
};
use App\Model\Usuario\Usuario;
use App\Mvc\Controller;
use Firebase\JWT\JWT;
use App\Model\Token\Token;

final class AuthController extends Controller
{
    private $permissoes;

    public function login(Request $request, Response $response, array $args): Response
    {
        self::openTransaction();
        $data = $request->getParsedBody();
        $email = $data['usuario'];
        $senha = $data['senha'];

        $usuario = new Usuario;
        $usuario('email', $email);
        $permissoes = $usuario->permissoes;
        foreach ($permissoes as $key => &$per) {
            $idx = -1;
            $full = [];
            foreach ($per as $k => $perm) {
                if (in_array_r($perm['nome'], $full, true)) {
                    if (isset($perm['sub']) and isset($full[$idx]['sub']))
                        $full[$idx]['sub'] = array_merge($full[$idx]['sub'], $perm['sub']);
                } else {
                    $idx++;
                    $full[] = $perm;
                }
            }
            $permissoes[$key] = $full;
        }
        $this->permissoes = $permissoes;
        // print_r($permissoes);exit();
        // Verificando se o usuário existe
        if (is_null($usuario->id_usuario))
            return $response->withJson(["message" => "invalid user", "status" => 401], 401);

        // Verificando se a senha esta correta
        if (!password_verify($senha, $usuario->senha))
            return $response->withJson(["message" => "invalid password", "status" => 401], 401);

        $token = self::createToken($usuario);
        $response = $response->withJson([
            "ac" => base64_encode(json_encode($this->permissoes)),
            // "gr" => base64_encode(json_encode($usuario->grupo_acesso->grupo)),
            "token" => $token->token,
            "refresh_token" => $token->refresh_token,
        ]);
        self::closeTransaction();

        return $response->withStatus(200);
    }

    public function refreshToken(Request $request, Response $response, array $args): Response
    {
        self::openTransaction();
        $data = $request->getParsedBody();
        $ref_token = $data['refresh_token'];

        $refresh_token = new Token;
        $refresh_token('refresh_token', $ref_token);

        if (!isset($refresh_token->id_token))
            return $response->withJson(['message' => 'invalid token'], 401);

        // Desativando o refresh_token
        $refresh_token->activated = false;
        $refresh_token - store();

        // Criando novo token
        $token = self::createToken($refresh_token->usuario);

        self::closeTransaction();
        $response = $response->withJson([
            "ac" => base64_encode(json_encode($this->permissoes)),
            "token" => $token->token,
            "refresh_token" => $token->refresh_token,
        ]);

        return $response;
    }
    private function createToken(Usuario $usuario)
    {
        $expired_at = (new \DateTime())->modify('+ 1 days')->format('Y-m-d H:i:s');
        $token_payload = [
            'name' => $usuario->nome,
            'sub' => $usuario->id_usuario,
            'gru' => $usuario->grupo_acesso->grupo,
            'email' => $usuario->email,
            'expired_at' => $expired_at
        ];

        $refresh_token_payload = [
            'email' => $usuario->email,
            'ramdom' => uniqid()
        ];

        $jwt = JWT::encode($token_payload, getenv('JWT_SECRET_KEY'));
        $refresh_jwt = JWT::encode($refresh_token_payload, getenv('JWT_SECRET_KEY'));
        $token = new Token;        
        $token->id_usuario = $usuario->id_usuario;
        $token->expired_at = $expired_at;
        $token->token = $jwt;
        $token->refresh_token = $refresh_jwt;
        $token->store();
        return $token;
    }
}
