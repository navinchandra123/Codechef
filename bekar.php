<?php
// For API call
function make_api_request($oauth_config, $path){
    $headers[] = 'Authorization: Bearer ' . $oauth_config;
    return make_curl_request($path, false, $headers);
}


function make_curl_request($url, $post = FALSE, $headers = array())
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }

    $headers[] = 'content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    return $response;
}
function make_contest_problem_api_request($config,$oauth_details , $problem_code , $contest_code){
    $path = $config."contests/".$contest_code."/problems/".$problem_code;
    $response = make_api_request($oauth_details, $path);
    return $response;
}
$ps =json_decode(file_get_contents('index.json'));
    
    foreach($ps->content as $i){
        $br = '<br>';
        $problem_code = $i->problemCode;
        $contest_code = 'PRACTICE';
        $oauth_details = '3c0ac8d2f8ac11102f43b2995febab2ef54d2b18';
        $config = 'https://api.codechef.com/';
        $codechef_data = json_decode(make_contest_problem_api_request($config , $oauth_details , $problem_code , $contest_code));
        $problemName = json_encode($codechef_data->result->data->content->problemName);
        $difficulty = 'challenge';
        $author = json_encode($codechef_data->result->data->content->author);
        $tags = json_encode($codechef_data->result->data->content->tags);
        $body = json_encode($codechef_data->result->data->content->body);
        require_once('dbconnection.php');
        $query = "INSERT INTO `data` (`problemCode`, `problemName`, `difficulty`, `author`, `tags`, `body`) VALUES ('$problem_code', '$problemName', '$difficulty', '$author', '$tags', '$body')";
        $result = $mysqli->query($query);
        if($result === TRUE){
            echo 'Data inserted successfully...';
            echo $br;
        }
        else{
            echo "Error: " . mysqli_error($mysqli);
            echo $br;
            echo $br;

        }
    }


?>