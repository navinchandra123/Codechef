<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use Slim\Views\TwigMiddleware;

use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Extra\Markdown\DefaultMarkdown;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;


require_once __DIR__ . '/../vendor/autoload.php';


$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->add(new BasePathMiddleware($app));
$app->addErrorMiddleware(true, true, true);

// Create Twig
$twig = Twig::create(__DIR__ . '/../templates',['cache' => false]);

$twig->addExtension(new MarkdownExtension());
$twig->addRuntimeLoader(new class implements RuntimeLoaderInterface {
    public function load($class) {
        if (MarkdownRuntime::class === $class) {
            return new MarkdownRuntime(new DefaultMarkdown());
        }
    }
});


// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));


$br = '<br><br><br>';
$userEmail = array() ;
// Define app routes
$app->get('/', function (Request $request, Response $response) {
    $view = Twig::fromRequest($request);
    require_once('dbconnection.php');
    $query = "SELECT * FROM `data`";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    // print_r($data);
    return $view->render($response , 'index.html' , ['data'=> $data , 'flag'=>false]);
});
$app->post('/' , function(Request $request , Response $response){
    $view = Twig::fromRequest($request);

    $difficulty = $_POST['difficulty'];
    $tags = $_POST['tags'];
    $author = $_POST['author'];
    // echo $difficulty." ".$tags.' '.$author;
    
    require_once('dbconnection.php');
    $query = "SELECT * FROM `data`";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }

    foreach($data as $datas){
        if($difficulty == $datas['difficulty']){
            $problem[] = $datas;
        }
        if($author == $datas['author']){
            if(!in_array($datas , $problem))
                $problem[] = $datas;
        }
        if($tags != 'select'){
            $tagg = explode(',' , $datas['tags']);
            foreach($tagg as $tag){
                $a = trim($tag , ']');
                $a = trim($a , '[');
                if($a == $tags){
                    if(!in_array($datas , $problem))
                        $problem[] = $datas;
                    break;
                }
            }
        }
    }
    $query = "SELECT * FROM `data`";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }

    // print_r($problem);
    return $view->render($response , 'index.html' , ['problems' => $problem , 'flag'=>true , 'data'=>$data]);
});


$app->get('/login' , function(Request $request , Response $response){
    // $response->getBody()->write("Har Har Mahdev");
    // return $response;
    $view = Twig::fromRequest($request);
    return $view->render($response , 'login.html');
});

$app->get('/signup' , function(Request $request , Response $response){
    $view = Twig::fromRequest($request);
    return $view->render($response , 'signup.html');
});

$app->post('/signup' , function(Request $request , Response $response){
    $view = Twig::fromRequest($request);
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass1 = $_POST['pass'];
    $pass2 = $_POST['pass2'];
    // echo $name.' '.$email.' '.$pass1.' '.$pass2;
    if(!$name || !$email || !$pass1 || !$pass2){
        return $view->render($response , 'signup.html' , ['error_message' => 'Please fill all the fields']);
    }
    if($pass1 != $pass2){
        return $view->render($response , 'signup.html' , ['error_message' => 'Password does not match']);
    }
    if(strlen($pass1)<6){
        return $view->render($response , 'signup.html' , ['error_message' => 'Password should have atleast 6 characters']);
    }
    require_once('dbconnection.php');
    $query = "INSERT INTO `signup`(`name`, `email`, `pass1`, `pass2`) VALUES ('$name', '$email' , '$pass1' , '$pass2')";
    $result = $mysqli->query($query);
    $check = 0 ;
    if($result == TRUE){
        $check=1;
    }
    if($check == 0){
        return $view->render($response , 'signup.html' , ['error_message'=>'Email already exist...']);
    }
    return $response->withHeader('Location', '/Codechef/login')->withStatus(200);
});

$app->post('/login' , function(Request $request , Response $response) use($userEmail , $app) {
    $view = Twig::fromRequest($request);

    $email = $_POST['email'];
    $pass1 = $_POST['pass1'];

    require_once('dbconnection.php');
    $query = "SELECT * FROM `signup` WHERE email = '$email'";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    if(sizeof($data)){
        if($data[0]['pass1'] != $pass1){
           return  $view->render($response , 'login.html' , ['error_message' => 'Email Or Password are Incorrect!']);
        }
    }
    else{
        return  $view->render($response , 'login.html' , ['error_message' => 'Email Or Password are Incorrect!']);
    }
    return $response->withHeader('Location', '/Codechef/afterlogin?id='.$_POST['email']."&wrong=1")->withStatus(200);
});

$app->get('/afterlogin' , function(Request $request , Response $response ) {
    $view = Twig::fromRequest($request);
    require_once('dbconnection.php');
    $query = "SELECT * FROM `data`";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    $email = $_GET['id'];
    $query = "SELECT * FROM `data`";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    return $view->render($response , 'afterlogin.html' , ['data'=>$data , 'flag'=>false , 'email' => $email]);
});

$app->post('/afterlogin' , function(Request $request , Response $response){
    $view = Twig::fromRequest($request);

    $difficulty = $_POST['difficulty'];
    $tags = $_POST['tags'];
    $author = $_POST['author'];
    $email = $_POST['email'];
    // echo $difficulty." ".$tags.' '.$author;
    
    require_once('dbconnection.php');
    $query = "SELECT * FROM `data`";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }

    foreach($data as $datas){
        if($difficulty == $datas['difficulty']){
            $problem[] = $datas;
        }
        if($author == $datas['author']){
            if(!in_array($datas , $problem))
                $problem[] = $datas;
        }
        if($tags != 'select'){
            $tagg = explode(',' , $datas['tags']);
            foreach($tagg as $tag){
                $a = trim($tag , ']');
                $a = trim($a , '[');
                if($a == $tags){
                    if(!in_array($datas , $problem))
                        $problem[] = $datas;
                    break;
                }
            }
        }
    }
    $query = "SELECT * FROM `data`";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }

    // print_r($problem);
    return $view->render($response , 'afterlogin.html' , ['problems' => $problem , 'flag'=>true , 'data'=>$data , 'email'=>$email]);

});

$app->get('/addproblem' , function(Request $request , Response $response){
    $view = Twig::fromRequest($request);
    $problemCode = $_GET['problemCode'];
    $email = $_GET['email'];
    return $view->render($response , 'addproblem.html' , ['problemCode'=>$problemCode , 'email'=>$email]);
});

$app->post('/addProblem' , function(Request $request , Response $response){
    $view = Twig::fromRequest($request);
    $problemCode = $_POST['problemCode'];
    $email = $_POST['email'];
    $tag = $_POST['tag'];
    // echo $problemCode.' '.$email.' '.$tag.'ssdasdsad';
    require_once('dbconnection.php');
    $query = "SELECT * FROM `signup` where email='$email'";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    // print_r($data);
    $tags = $data[0]['tags'];
    $ttags = $tags.$tag.':'.$problemCode.',';
    // echo $ttags;
    $query = "UPDATE signup SET tags = '$ttags' WHERE email = '$email'";
    $result = $mysqli->query($query);
    return $response->withHeader('Location', '/Codechef/afterlogin?id='.$_POST['email']."&wrong=1")->withStatus(200);
});

$app->get('/seeyourtags' , function(Request $request , Response $response){
    $view = Twig::fromRequest($request);

    $email = $_GET['id'];

    require_once('dbconnection.php');
    $query = "SELECT * FROM `signup`  where email='$email'";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    $tags = $data[0]['tags'];
    $tagss = explode(',' , $tags);
    foreach($tagss as $tag){
        $anothertag = explode(':' , $tag);
        if(strlen($anothertag[0])==0)continue;
        if ( ! isset($anothertag[1])) {
            $anothertag[1] = null;
         }
         $problem[$anothertag[0]][] = $anothertag[1];
    }

    // foreach($problem as $key=>$value){
    //     foreach($value as $problemCode){
    //         $query = "SELECT * FROM `data`  where problemCode='$problemCode'";
    //         $result = $mysqli->query($query);
    //         while($row = $result->fetch_assoc()){
    //             $dataa[] = $row;
    //         }
    //         $datas = $dataa[0];
    //         $list[$key][] = $datas;
    //         print_r($list);
    //     }
    // }
    // print_r($list);

    return $view->render($response , 'seeyourtags.html' , ['data' => $problem , 'email'=>$email] );
});

$app->post('/seeyourtags' , function(Request $request , Response $response){
    $view = Twig::fromRequest($request);
    $email = $_POST['email'];
    $usertag = $_POST['tags'];

    require_once('dbconnection.php');
    $query = "SELECT * FROM `signup`  where email='$email'";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    $tags = $data[0]['tags'];
    $tagss = explode(',' , $tags);
    foreach($tagss as $tag){
        $anothertag = explode(':' , $tag);
        if(strlen($anothertag[0])==0)continue;
        if ( ! isset($anothertag[1])) {
            $anothertag[1] = null;
         }
         $problem[$anothertag[0]][] = $anothertag[1];
    }

    foreach($problem[$usertag] as $key=>$value){
        $query = "SELECT * FROM `data`  where problemCode='$value'";
        $result = $mysqli->query($query);
        while($row = $result->fetch_assoc()){
            $dataa[] = $row;
        }
        $datas = $dataa[0];
        $list[] = $datas;
    }
    // print_r($list);

    return $view->render($response , 'seeyourtags.html' , ['tag'=>$usertag, 'flag'=>true, 'data' => $problem , 'list'=>$list , 'email'=>$email] );
});

$app->get('/logout' , function(Request $request , Response $response){
    $view = Twig::fromRequest($request);
    return $response->withHeader('Location', '/Codechef/login')->withStatus(200);
});

$app->run();
?>