<?php

if($first == "information"){
    $update_data =[
        'user_id' => $_POST['user_id'],
        'first_name' => PT_Secure($_POST['first_name']),
        'last_name' => PT_Secure($_POST['last_name']),
        'profession' => PT_Secure($_POST['profession']),
        'city' => PT_Secure($_POST['city']),
        'country' => PT_Secure($_POST['country']),
        'postal_code' => PT_Secure($_POST['postal_code']),
        'phone' => PT_Secure($_POST['phone']),
        'email' => PT_Secure($_POST['email']),
        'description' => PT_Secure($_POST['description'])
    ];

    $check = $db->where("user_id",$_POST['user_id'])->getOne(T_RESUME);

    if($check){
        $update = $db->where('user_id', PT_Secure($_POST['user_id']))->update(T_RESUME, $update_data);
    }else{
        $update = $db->insert(T_RESUME, $update_data);
    }
    $data = array(
        'status' => 200,
        'message' => $success_icon . $lang->setting_updated,
    );
}
if($first == "pdf"){
   
    

    $file_to_save = '../upload/resume/file.pdf';
//save the pdf file on the server
file_put_contents($file_to_save, $dompdf->output()); 
//print the pdf file to the screen for saving
header('Content-type: application/pdf');
header('Content-Disposition: inline; filename="file.pdf"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($file_to_save));
header('Accept-Ranges: bytes');
readfile($file_to_save);

    $data = array(
        'status' => 201,
        'message' => $success_icon . $lang->setting_updated,
        'html' => $html
    );
}
header("Content-type: application/json");
if (isset($errors)) {
    echo json_encode(array(
        'errors' => $errors
    ));
    exit();
}