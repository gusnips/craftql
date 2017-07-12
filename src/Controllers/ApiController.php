<?php

namespace markhuot\CraftQL\Controllers;

use Craft;
use craft\web\Controller;
use craft\records\User;
use markhuot\CraftQL\Plugin;
use markhuot\CraftQL\Models\Token;
use yii\web\ForbiddenHttpException;

class ApiController extends Controller
{
    protected $allowAnonymous = ['index'];

    private $graphQl;
    private $request;

    function __construct(
        $id,
        $module, 
        \markhuot\CraftQL\Services\GraphQLService $graphQl,
        \markhuot\CraftQL\Services\RequestService $request,
        $config = []
    ) {
        parent::__construct($id, $module, $config);

        $this->graphQl = $graphQl;
        $this->request = $request;
    }

    function actionGraphiql() {
        $url = Craft::$app->request->getUrl();
        $url = preg_replace('/\?.*$/', '', $url);

        $html = file_get_contents(dirname(__FILE__) . '/../../graphiql/index.html');
        $html = str_replace('{{ url }}', $url, $html);
        return $html;
    }

    function actionIndex()
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            $tokenId = Craft::$app->request->headers->get('X-Token');
            if ($tokenId) {
                $token = Token::find()->where(['token' => $tokenId])->one();
                if ($token) {
                    $user = User::find()->where(['id' => $token->userId])->one();
                }
            }
        }

        // @todo, check user permissions when PRO license

        if (!$user) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            return json_encode([
                'errors' => [
                    ['message' => 'Not authorized']
                ]
            ]);
        }

        $input = $this->request->input();
        $variables = $this->request->variables();

        $this->graphQl->bootstrap();

        try {
            $result = $this->graphQl->execute($input, $variables);
        } catch (\Exception $e) {
            $result = [
                'errors' => [
                    'message' => $e->getMessage()
                ]
            ];
        }

        header('Content-Type: application/json; charset=UTF-8');

        $index = 1;
        foreach ($this->graphQl->getTimers() as $key => $timer) {
            header('X-Timer-'.$index++.'-'.ucfirst($key).': '.$timer);
        }

        return json_encode($result);
    }
}
