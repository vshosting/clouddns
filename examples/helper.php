<?php

function handleResponse($request, $message){

    if ($request->getStatusCode() !== 200){
        $body = $request->getBody();
        die($message . "\r\n" . $body . "\r\n");
    }

}


?>