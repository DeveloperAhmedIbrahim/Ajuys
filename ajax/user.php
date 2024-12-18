<?php
if($first == 'update_info'){
    PT_RunInBackground(array('status' => 200));
    $is_there_scheduled = $db->where('privacy',0,'!=')->where('publication_date',0,'!=')->where('publication_date',time(),'<=')->update(T_VIDEOS,array('privacy' => 0));
}
if ($first == 'download_user_info' && IS_LOGGED) {
    $data['status'] = 200;
    if(!empty($pt->user->info_file)){
       // Get parameters
       $file = $pt->user->info_file;
       $filepath = $file; // upload/files/2019/20/adsoasdhalsdkjalsdjalksd.html

       // Process download
       if(file_exists($filepath)) {
           header('Content-Description: File Transfer');
           header('Content-Type: application/octet-stream');
           // rename the file to username
           header('Content-Disposition: attachment; filename="'.$pt->user->username.'.html"');
           header('Expires: 0');
           header('Cache-Control: must-revalidate');
           header('Pragma: public');
           header('Content-Length: ' . filesize($filepath));
           flush(); // Flush system output buffer
           readfile($filepath);
           // delete the file
           unlink($filepath);
           // remove user data
          $db->where('id', $pt->user->id)->update(T_USERS, array('info_file' => ''));
          header("Location: " . PT_Link(''));
           exit;
       }
    }
    header("Location: " . PT_Link(''));
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}

if (empty($_POST['user_id']) || !IS_LOGGED) {
    exit("Undefined Dolphin.");
}

$is_owner = false;
if ($_POST['user_id'] == $user->id || PT_IsAdmin()) {
    $is_owner = true;
}
if ($first == 'change_price') {
    if (!empty($_POST['subscriber_price']) && (!is_numeric($_POST['subscriber_price']) || $_POST['subscriber_price'] < 0)) {
        $errors[] = $error_icon . $lang->please_check_details;
    }
    if (empty($errors)) {
        $update_data = array();
        $update_data['subscriber_price'] = 0;
        if ($pt->config->payed_subscribers == 'on' && !empty($_POST['subscriber_price']) && is_numeric($_POST['subscriber_price']) && $_POST['subscriber_price'] > 0) {
            $update_data['subscriber_price'] = PT_Secure($_POST['subscriber_price']);
        }
        if ($is_owner == true) {
            $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
        }
        $data = array(
                    'status' => 200,
                    'message' => $success_icon . $lang->setting_updated
                );
    }
    
}

if ($first == 'save_ads') {
    if (!empty($_POST['total_ads']) && !is_numeric($_POST['total_ads'])) {
        $errors[] = $error_icon . $lang->please_check_details;
    } 
    else{
        $update_data = array();
        $update_data['total_ads'] = 0;
        if (!empty($_POST['total_ads']) && is_numeric($_POST['total_ads']) && $_POST['total_ads'] > 0) {
            $update_data['total_ads'] = PT_Secure($_POST['total_ads']);
        }
        if ($is_owner == true) {
            $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
            if ($update){ 

                $data = array(
                    'status' => 200,
                    'message' => $success_icon . $lang->setting_updated
                );
            }
        }
    }
    
}

if ($first == 'general') {
    if (empty($_POST['username']) OR empty($_POST['email'])) {
        $errors[] = $error_icon . $lang->please_check_details;
    } 

    else {
        $user_data = PT_UserData($_POST['user_id']);
        if (!empty($user_data->id)) {
            if ($_POST['email'] != $user_data->email) {
                if (PT_UserEmailExists($_POST['email'])) {
                    $errors[] = $error_icon . $lang->email_exists;
                }
            }
            if ($_POST['username'] != $user_data->username) {
                $is_exist = PT_UsernameExists($_POST['username']);
                if ($is_exist) {
                    $errors[] = $error_icon . $lang->username_is_taken;
                }
            }
            if (in_array($_POST['username'], $pt->site_pages)) {
                $errors[] = $error_icon . $lang->username_invalid_characters;
            }
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = $error_icon . $lang->email_invalid_characters;
            }
            if (!empty($_POST['donation_paypal_email'])) {
                if (!filter_var($_POST['donation_paypal_email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = $error_icon . $lang->email_invalid_characters;
                }
            }

            if (strlen($_POST['username']) < 4 || strlen($_POST['username']) > 32) {
                $errors[] = $error_icon . $lang->username_characters_length;
            }
            if (!preg_match('/^[\w]+$/', $_POST['username'])) {
                $errors[] = $error_icon . $lang->username_invalid_characters;
            }
            $active = $user_data->active;
            if (!empty($_POST['activation']) && PT_IsAdmin()) {
                if ($_POST['activation'] == '1') {
                    $active = 1;
                } else {
                    $active = 2;
                }
                if ($active == $user_data->active) {
                    $active = $user_data->active;
                }
            }
            $type = $user_data->admin;
            if (!empty($_POST['type']) && PT_IsAdmin()) {
                if ($_POST['type'] == '2') {
                    $type = 1;
                } 

                else if ($_POST['type'] == '1') {
                    $type = 0;
                }
                if ($type == $user_data->admin) {
                    $type = $user_data->admin;
                }
            }

            $is_pro = $user_data->is_pro;
            if (isset($_POST['is_pro']) && PT_IsAdmin()) {
                if ($_POST['is_pro'] == 1) {
                    $is_pro = 1;
                } 

                else if ($_POST['is_pro'] == 0) {
                    $is_pro = 0;
                    if ($user_data->admin != 1) {
                        $db->where('user_id',$user_data->id)->update(T_VIDEOS,array('featured' => 0));
                    }
                }
            }


            
            $gender       = 'male';
            $gender_array = array(
                'male',
                'female'
            );
            if (!empty($_POST['gender'])) {
                if (in_array($_POST['gender'], $gender_array)) {
                    $gender = $_POST['gender'];
                }
            }

            $field_data         = array();
            if (!empty($_POST['cf'])) {
                $fields         = $db->where('placement','general')->get(T_FIELDS);
                foreach ($fields as $key => $field) {
                    $field_id   = $field->id;
                    $field->fid = "fid_$field_id";
                    $name       = $field->fid;
                    if (isset($_POST[$name])) {
                        if (mb_strlen($_POST[$name]) > $field->length) {
                            $errors[] = $error_icon . $field->name . ' field max characters is ' . $field->length;
                        }
                        else{
                            $field_data[] = array(
                                $name => $_POST[$name]
                            );
                        } 
                    }
                }
            }
            $age = $user_data->age;
            $age_changed = $user_data->age_changed;
            if (($_POST['age'] >= 0 && $_POST['age'] < 100) && $age != $_POST['age']) {
                if (!PT_IsAdmin()) {
                    if ($user_data->age_changed > 1) {
                        $errors[] = $error_icon . $lang->not_allowed_change_age;
                    } else {
                        $age = PT_Secure($_POST['age']);
                        $age_changed = $user_data->age_changed + 1;
                    }
                } else {
                    $age = PT_Secure($_POST['age']);
                }
            }
            
            if (empty($errors)) {
                $newsletters = 0;
                if (!empty($_POST['newsletters']) && in_array($_POST['newsletters'], array(1,2))) {
                    if ($_POST['newsletters'] == 1) {
                        $newsletters = 0;
                    }
                    if ($_POST['newsletters'] == 2) {
                        $newsletters = 1;
                    }
                }
                

                $update_data = array(
                    'username' => PT_Secure($_POST['username']),
                    'gender' => PT_Secure($gender),
                    'country_id' => PT_Secure($_POST['country']),
                    'active' => PT_Secure($active),
                    'admin' => PT_Secure($type),
                    'is_pro' => $is_pro,
                    'age' => $age,
                    'age_changed' => $age_changed,
                    'newsletters' => $newsletters,
                    'donation_paypal_email' => PT_Secure($_POST['donation_paypal_email'])
                );
                

                $show_modal = false;

                if ($pt->config->validation == 'on' && !empty($_POST['email']) && $user_data->email != $_POST['email']) {
                    $code = rand(111111, 999999);
                    $hash_code = md5($code);
                    $update_data = array('email_code' => $hash_code);
                    $db->where('id', $user_data->id);
                    $update = $db->update(T_USERS, $update_data);
                    $message = "Your confirmation code is: $code";
                    $send_email_data = array(
                        'from_email' => $pt->config->email,
                        'from_name' => $pt->config->name,
                        'to_email' => $_POST['email'],
                        'to_name' => $user_data->name,
                        'subject' => 'Please verify that it’s you',
                        'charSet' => 'UTF-8',
                        'message_body' => $message,
                        'is_html' => true
                    );
                    $send_message = PT_SendMessage($send_email_data);
                    $send_message = true;
                    if ($send_message) {
                        $show_modal = true;
                        $success = $success_icon . $lang->email_sent;
                        $update_data['new_email'] = PT_Secure($_POST['email']);
                    }
                }
                else{
                    $update_data['email'] = PT_Secure($_POST['email']);
                }
                

                // user max upload 
                $limit_array = array('0','2000000','6000000','12000000','24000000','48000000','96000000','256000000','512000000','1000000000','10000000000','unlimited');
                if (isset($_POST['user_upload_limit']) && PT_IsAdmin()) {
                    if (in_array($_POST['user_upload_limit'], $limit_array)) {
                        $update_data['user_upload_limit'] = PT_Secure($_POST['user_upload_limit']);
                    } 
                }
                
                // user max upload 
                if (PT_IsAdmin()) {
                    if (!empty($_POST['suspend_upload']) && in_array($_POST['suspend_upload'], array('suspend','unsuspend'))) {
                        if ($_POST['suspend_upload'] == 'suspend') {
                            $update_data['suspend_upload'] = 1;
                        }
                        elseif ($_POST['suspend_upload'] == 'unsuspend') {
                            $update_data['suspend_upload'] = 0;
                        }
                    }
                    if (!empty($_POST['suspend_import']) && in_array($_POST['suspend_import'], array('suspend','unsuspend'))) {
                        if ($_POST['suspend_import'] == 'suspend') {
                            $update_data['suspend_import'] = 1;
                        }
                        elseif ($_POST['suspend_import'] == 'unsuspend') {
                            $update_data['suspend_import'] = 0;
                        }
                    }
                }
              
                if (!empty($_POST['verified'])) {
                    if ($_POST['verified'] == 'verified') {
                        $verification = 1;
                    } else {
                        $verification = 0;
                    }
                    if ($verification == $user_data->verified) {
                        $verification = $user_data->verified;
                    }
                    $update_data['verified'] = $verification;
                }
                if ($is_owner == true) {
                    $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
                    if ($update){ 
                        if (!empty($field_data)) {
                            $insert = PT_UpdateUserCustomData($_POST['user_id'], $field_data);
                        }

                        $data = array(
                            'status' => 200,
                            'message' => $success_icon . $lang->setting_updated,
                            'show_modal' => $show_modal
                        );
                    }
                }
            }
        }
    }
}

if ($first == 'profile') {
    $user_data = PT_UserData($_POST['user_id']);
    $field_data         = array();
    if (!empty($_POST['cf'])) {
        $fields         = $db->where('placement',array('profile','social'), 'IN')->get(T_FIELDS);
        foreach ($fields as $key => $field) {
            $field_id   = $field->id;
            $field->fid = "fid_$field_id";
            $name       = $field->fid;
            if (isset($_POST[$name])) {
                if (mb_strlen($_POST[$name]) > $field->length) {
                    $errors[] = $error_icon . $field->name . ' field max characters is ' . $field->length;
                }
                else{
                    $field_data[] = array(
                        $name => $_POST[$name]
                    );
                } 
            }
        }
    }

    if (!empty($user_data->id)) {
        if (empty($errors)) {
            $category = array();
            if (!empty($_POST['fav_category'])) {
                
                foreach ($_POST['fav_category'] as $key => $value) {
                    if (in_array($value, array_keys(get_object_vars($pt->categories)))) {
                        $category[] = PT_Secure($value);
                    }
                } 
            }

            if (!empty($category)) {
                $category = json_encode($category);
            }
            else{
                $category = '';
            }
            $google_tracking_code = '';
            if ($pt->config->pro_google == 'on' && $user_data->is_pro && !empty($_POST['google_tracking_code'])) {
                $_POST['google_tracking_code'] = strip_tags($_POST['google_tracking_code']);
                $_POST['google_tracking_code'] = preg_replace('/on[^<>=]+=[^<>]*/m', '', $_POST['google_tracking_code']);
                $_POST['google_tracking_code'] = htmlspecialchars($_POST['google_tracking_code']);
                $google_tracking_code = PT_Secure($_POST['google_tracking_code']);
            }
            $update_data = array(
                'first_name' => PT_Secure($_POST['first_name']),
                'last_name' => PT_Secure($_POST['last_name']),
                'about' => PT_Secure($_POST['about']),
                'facebook' => PT_Secure($_POST['facebook']),
                'google' => PT_Secure($_POST['google']),
                'twitter' => PT_Secure($_POST['twitter']),
                'instagram' => PT_Secure($_POST['instagram']),
                'fav_category' => $category,
                'google_tracking_code' => $google_tracking_code,
            );

            if(isset($_POST['education_id']) && $_POST['education_id']){
                $update_data['education_id'] = PT_Secure($_POST['education_id']);
            }else{
                $update_data['education_id'] = null;

            }

            if(isset($_POST['experience_id']) && $_POST['experience_id']){
                $update_data['experience_id'] = PT_Secure($_POST['experience_id']);
            }else{
                $update_data['experience_id'] = null;
            }
            if(isset($_POST['show_education_in_info']) && $_POST['show_education_in_info'] == "on"){
                $update_data['show_education'] = true;
            }else{
                $update_data['show_education'] = false;
            }

            if(isset($_POST['show_company_in_info']) && $_POST['show_company_in_info'] == "on"){
                $update_data['show_experience'] = true;
            }else{
                $update_data['show_experience'] = false;
            }
            if ($is_owner == true) {
                $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
                if ($update) {
                    if (!empty($field_data)) {
                        $insert = PT_UpdateUserCustomData($_POST['user_id'], $field_data);
                    }

                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $lang->setting_updated,
                        'data' => $update_data
                    );
                }
            }
        }
    }
}

if ($first == 'company') {
    $user_data = PT_UserData($_POST['user_id']);
    $field_data         = array();
   

    if (!empty($user_data->id)) {
        if (empty($errors)) {
            
           
            $update_data = array(
                'company_name' => PT_Secure($_POST['company_name']),
                'industry' => PT_Secure($_POST['industry']),
                'location' => PT_Secure($_POST['location']),
                'phone_number' => PT_Secure($_POST['phone_number']),
                'number_of_employee' => PT_Secure($_POST['company_size']),
                'website_url' => PT_Secure($_POST['website']),
            );

          
           
            if ($is_owner == true) {
                $check = $db->where('user_id', PT_Secure($_POST['user_id']))->getOne(T_COMPANY);
                if($check){

                    $update = $db->where('user_id', PT_Secure($_POST['user_id']))->update(T_COMPANY, $update_data);
                }else{
                    $update_data['user_id'] = PT_Secure($_POST['user_id']);
                    $update = $db->insert(T_COMPANY, $update_data);
                }
                if ($update) {
                        $data = array(
                        'status' => 200,
                        'message' => $success_icon . $lang->setting_updated,
                    );
                }
            }
        }
    }
}
if ($first == 'job') {
    $user_data = PT_UserData($_POST['user_id']);
    $field_data         = array();
   

    if (!empty($user_data->id)) {
        if (empty($errors)) {
      
            $update_data = array(
                'user_id' => $_POST['user_id'],
                'title' => PT_Secure($_POST['title']),
                'description' => PT_Secure($_POST['description']),
                'location' => PT_Secure($_POST['location']),
                'type' => PT_Secure($_POST['type']),
                'qualifcations' => PT_Secure($_POST['qualifcation']),
                'is_active' => PT_Secure($_POST['is_active'])
            );
            if ($is_owner == true) {
                if($_POST['id']){
                    $update = $db->where('id', PT_Secure($_POST['id']))->update(T_JOB, $update_data);

                }else{
                    $update = $db->insert(T_JOB, $update_data);

                }
                if ($update) {
                    $jobs_list ="";
                    $jobs  = $db->where('user_id',$pt->user->id)->get(T_JOB);  
                    foreach ($jobs as $job) {
                       
                        $jobs_list .= PT_LoadPage("settings/includes/job-list",array(
                            'Title' => $job->title,
                            'DESCRIPTION' => $job->description,
                            'LOCATION' => $job->location,
                            'TYPE' => $job->type,
                            'QUALIFCATION' => $job->qualifcations,
                            'IS_ACTIVE' => $job->is_active ? 'Active' : "Not Active",
                            'ID' => $job->id,
                            "USER_ID" => $pt->user->id,
                            "JOB" => json_encode($job),
                        ));
                    }
                    

                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $lang->setting_updated,
                        'data' => $jobs_list,
                    );
                }
            }
        }
    }
}
if ($first == 'education') {
    $user_data = PT_UserData($_POST['user_id']);
    $field_data         = array();
    if($_POST['start_date'] > $_POST['end_date'] ){
        $errors[] = "Start Date Must be less then End Date.";
    }

    if (!empty($user_data->id)) {
        if (empty($errors)) {
      
            $update_data = array(
                'user_id' => $_POST['user_id'],
                'institude' => PT_Secure($_POST['institude']),
                'degree' => PT_Secure($_POST['degree']),
                'field_of_study' => PT_Secure($_POST['field_of_study']),
                'grade' => PT_Secure($_POST['grade']),
                'activities_and_societies' => PT_Secure($_POST['activities_and_societies']),
                'start_date' => PT_Secure($_POST['start_date']),
                'end_date' => PT_Secure($_POST['end_date']),
                'description' => PT_Secure($_POST['description']),
                'type' => PT_Secure($_POST['type'])
            );
            if ($is_owner == true) {
                if($_POST['id']){
                    $update = $db->where('id', PT_Secure($_POST['id']))->update(T_EDUCATION, $update_data);

                }else{
                    $update = $db->insert(T_EDUCATION, $update_data);

                }
                if ($update) {
                    $educations ="";
                        $user_educations  = $db->where('user_id',$pt->user->id)->where('type',$_POST['type'])->get(T_EDUCATION);  
                        foreach ($user_educations as $education) {
                            $education->start_date = date_format ( date_create($education->start_date) , 'Y-m-d');
                            $education->end_date = date_format ( date_create($education->end_date) , 'Y-m-d');

                            $educations .= PT_LoadPage("settings/includes/education-list",array(
                                'Institude' => $education->institude,
                                'Degree' => $education->degree,
                                'Field_Of_Study' => $education->field_of_study,
                                'E_Start_Date' => $education->start_date,
                                'End_Date' => $education->end_date,
                                'ID' => $education->id,
                                "Education" => json_encode($education),
                            ));
                        }
                    

                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $lang->setting_updated,
                        'data' => $educations,
                    );
                }
            }
        }
    }
}


if ($first == 'experience') {
    $user_data = PT_UserData($_POST['user_id']);
    $field_data         = array();
    if($_POST['start_date'] > $_POST['end_date'] ){
        $errors[] = "Start Date Must be less then End Date.";
    }

    if (!empty($user_data->id)) {
        if (empty($errors)) {
      
            $update_data = array(
                'user_id' => $_POST['user_id'],
                'title' => PT_Secure($_POST['title']),
                'employment_type' => PT_Secure($_POST['employment_type']),
                'company_name' => PT_Secure($_POST['company_name']),
                'location' => PT_Secure($_POST['location']),
                'location_type' => PT_Secure($_POST['location_type']),
                'currently_working_here' => isset($_POST['currently_working_here']) && PT_Secure($_POST['currently_working_here']) == "on" ? true : false,
                'start_date' => PT_Secure($_POST['start_date']),
                'end_date' => PT_Secure($_POST['end_date']),
                'industry' => PT_Secure($_POST['industry']),
                'description' => PT_Secure($_POST['description']),
                'type' => PT_Secure($_POST['type'])
            );
            if ($is_owner == true) {
                if($_POST['id']){
                    $update = $db->where('id', PT_Secure($_POST['id']))->update(T_EXPERINCE, $update_data);

                }else{
                    $update = $db->insert(T_EXPERINCE, $update_data);

                }
                if ($update) {
                    $experinces ="";
                        $user_experince = $db->where('user_id',$pt->user->id)->where('type',$_POST['type'])->get(T_EXPERINCE);  
                        foreach ($user_experince as $experince) {
                            $experince->start_date = date_format ( date_create($experince->start_date) , 'Y-m-d');
                            $experince->end_date = date_format ( date_create($experince->end_date) , 'Y-m-d');

                            $experinces .= PT_LoadPage("settings/includes/experience-list",array(
                                'Title' => $experince->title,
                                'Company_Name' => $experince->company_name,
                                'Employment_Type' => $experince->employment_type,
                                'Start_Date' => $experince->start_date,
                                'End_Date' => $experince->end_date,
                                'ID' => $experince->id,
                                "USER_ID" => $pt->user->id,
                                "Experince" => json_encode($experince),
                            ));
                        }
                    

                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $lang->setting_updated,
                        'data' => $experinces,
                    );
                }
            }
        }
    }
}

if ($first == 'experince_delete') {
    $user_data = PT_UserData($_POST['user_id']);
    if(!isset($_POST['delete_id'])){
        $errors[] = "Some Thing Went Wrong.";
    }
    if (!empty($user_data->id)) {
        if (empty($errors)) {
            $user_experince = $db->where('id',$_POST['delete_id'])->delete(T_EXPERINCE);  
            if($user_experince){
                $experinces ="";
                        $user_experince = $db->where('user_id',$pt->user->id)->get(T_EXPERINCE);  
                        foreach ($user_experince as $experince) {
                            $experince->start_date = date_format ( date_create($experince->start_date) , 'Y-m-d');
                            $experince->end_date = date_format ( date_create($experince->end_date) , 'Y-m-d');

                            $experinces .= PT_LoadPage("settings/includes/experience-list",array(
                                'Title' => $experince->title,
                                'Company_Name' => $experince->company_name,
                                'Employment_Type' => $experince->employment_type,
                                'Start_Date' => $experince->start_date,
                                'End_Date' => $experince->end_date,
                                'ID' => $experince->id,
                                "USER_ID" => $pt->user->id,
                                "Experince" => json_encode($experince),
                            ));
                        }
                $data = array(
                    'status' => 200,
                    'message' => $success_icon . $lang->setting_updated,
                    'data' => $experinces
                );
            }else{
                $errors[] = "Data not found.";
            }
        }
    }
}

if ($first == 'education_delete') {
    $user_data = PT_UserData($_POST['user_id']);
    if(!isset($_POST['delete_id'])){
        $errors[] = "Some Thing Went Wrong.";
    }
    if (!empty($user_data->id)) {
        if (empty($errors)) {
            $user_experince = $db->where('id',$_POST['delete_id'])->delete(T_EDUCATION);  
            if($user_experince){
                $experinces ="";
                        $user_experince = $db->where('user_id',$pt->user->id)->get(T_EDUCATION);  
                        foreach ($user_experince as $experince) {
                            $experince->start_date = date_format ( date_create($experince->start_date) , 'Y-m-d');
                            $experince->end_date = date_format ( date_create($experince->end_date) , 'Y-m-d');

                            $experinces .= PT_LoadPage("settings/includes/experience-list",array(
                                'Title' => $experince->title,
                                'Company_Name' => $experince->company_name,
                                'Employment_Type' => $experince->employment_type,
                                'Start_Date' => $experince->start_date,
                                'End_Date' => $experince->end_date,
                                'ID' => $experince->id,
                                "USER_ID" => $pt->user->id,
                                "Experince" => json_encode($experince),
                            ));
                        }
                $data = array(
                    'status' => 200,
                    'message' => $success_icon . $lang->setting_updated,
                    'data' => $experinces
                );
            }else{
                $errors[] = "Data not found.";
            }
        }
    }
}

if ($first == 'skills_delete') {
    $user_data = PT_UserData($_POST['user_id']);
    if(!isset($_POST['delete_id'])){
        $errors[] = "Some Thing Went Wrong.";
    }
    if (!empty($user_data->id)) {
        if (empty($errors)) {
            $user_experince = $db->where('id',$_POST['delete_id'])->delete(T_SKILLS);  
            if($user_experince){
                $data = array(
                    'status' => 200,
                    'message' => $success_icon . $lang->setting_updated,
                );
            }else{
                $errors[] = "Data not found.";
            }
        }
    }
}
if($first == "skills"){
    $user_data = PT_UserData($_POST['user_id']);
    $field_data         = array();
   

    if (!empty($user_data->id)) {
        if (empty($errors)) {
      
            $update_data = array(
                'user_id' => $_POST['user_id'],
                'name' => PT_Secure($_POST['name']),
                'type' => PT_Secure($_POST['type'])
            );
            if ($is_owner == true) {
                if($_POST['id']){
                    $update = $db->where('id', PT_Secure($_POST['id']))->update(T_SKILLS, $update_data);

                }else{
                    $update = $db->insert(T_SKILLS, $update_data);

                }
                if ($update) {
                    $skills ="";
                        $user_skills = $db->where('user_id',$pt->user->id)->where('type',$_POST['type'])->get(T_SKILLS);  
                        foreach ($user_skills as $skill) {
                            $skills .= PT_LoadPage("settings/includes/skills-list",array(
                                'NAME' => $skill->name,
                                'ID' => $skill->id,
                                "USER_ID" => $pt->user->id,
                            ));
                        }
                    

                    $data = array(
                        'status' => 200,
                        'message' => $success_icon . $lang->setting_updated,
                        'data' => $skills,
                    );
                }
            }
        }
    }
}
if ($first == 'change-pass') {
    $user_data = PT_UserData($_POST['user_id']);
    if (!empty($user_data->id)) {
        if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_new_password'])) {
            $errors[] = $error_icon . $lang->please_check_details;
        } else {
            $password = $_POST['current_password'];
            $hash                = 'sha1';
            if (strlen($user_data->password) == 60) {
                $hash = 'password_hash';
            }
            $logged = false;
            if ($hash == 'password_hash') {
                if (password_verify($password, $user_data->password)) {
                    $logged = true;
                }
                else{
                    $errors[] = $error_icon . $lang->current_password_dont_match;
                }
            } else {
                $login_password = $hash(PT_Secure($password));
                if ($user_data->password != $login_password) {
                    $errors[] = $error_icon . $lang->current_password_dont_match;
                }
            }


                
            if (strlen($_POST['new_password']) < 4) {
                $errors[] = $error_icon . $lang->password_is_short;
            }
            if ($_POST['new_password'] != $_POST['confirm_new_password']) {
                $errors[] = $error_icon . $lang->new_password_dont_match;
            }
            if (empty($errors)) {
                $update_data = array(
                    'password' => password_hash($_POST['new_password'], PASSWORD_DEFAULT)
                );
                if ($is_owner == true) {
                    $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
                    if ($update) {
                       $data = array(
                            'status' => 200,
                            'message' => $success_icon . $lang->setting_updated
                        );
                    }
                }
            }
        }
    }
}

if ($first == 'avatar') {
    $user_data = PT_UserData($_POST['user_id']);
    $update_data = array();
    if (!empty($user_data->id)) {
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $file_info = array(
                'file' => $_FILES['avatar']['tmp_name'],
                'size' => $_FILES['avatar']['size'],
                'name' => $_FILES['avatar']['name'],
                'type' => $_FILES['avatar']['type'],
                'crop' => array('width' => 400, 'height' => 400)
            );
            $file_upload = PT_ShareFile($file_info);
            if (!empty($file_upload['filename'])) {
                $update_data['avatar'] = $file_upload['filename'];
            }
            if (!empty($user_data->ex_avatar) && $user_data->ex_avatar != 'upload/photos/d-avatar.jpg' && $user_data->ex_avatar != 'upload/photos/f-avatar.png') {
                PT_DeleteFromToS3($user_data->ex_avatar);
            }
        }
        if (!empty($_FILES['cover']['tmp_name'])) {
            $file_info = array(
                'file' => $_FILES['cover']['tmp_name'],
                'size' => $_FILES['cover']['size'],
                'name' => $_FILES['cover']['name'],
                'type' => $_FILES['cover']['type'],
                'crop' => array('width' => 1200, 'height' => 200)
            );
            $file_upload = PT_ShareFile($file_info,2);
            if (!empty($file_upload['filename'])) {
                $update_data['cover'] = $file_upload['filename'];
            }
        }
    }
    if ($is_owner == true) {
        $update = $db->where('id', PT_Secure($_POST['user_id']))->update(T_USERS, $update_data);
        if ($update) {
           $data = array(
                'status' => 200,
                'message' => $success_icon . $lang->setting_updated
            );
        }
    }
}

if ($first == 'delete' && $pt->config->delete_account == 'on') {
    $user_data = PT_UserData($_POST['user_id']);
    if (!empty($user_data->id)) {
        if ($user_data->password != sha1($_POST['current_password'])) {
            $errors[] = $error_icon . $lang->current_password_dont_match;
        }
        if (empty($errors) && $is_owner == true) {
            $delete = PT_DeleteUser($user_data->id);
            if ($delete) {
                $data = array(
                    'status' => 200,
                    'message' => $success_icon . $lang->your_account_was_deleted,
                    'url' => PT_Link('')
                );
            }
        }
    }
}

if ($first == 'video-monetization' && (($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'off') || ($pt->config->usr_v_mon == 'on' && $pt->config->user_mon_approve == 'on' && $pt->user->monetization == '1'))) {
    
    $user_id        = $user->id;
    $video_mon      = ($user->video_mon == 1) ? 0 : 1;
    $update_data    = array(
        'video_mon' => $video_mon
    );

    $db->where('id',$user_id)->update(T_USERS,$update_data);
    $data['status'] = 200;
}

if ($first == 'request-withdrawal') {

    $error    = none;
    $balance  = $user->balance;
    $user_id  = $user->id;
    $currency = $pt->config->payment_currency;

    // Check is unprocessed requests exits
    $db->where('user_id',$user_id);
    $db->where('status',0);
    $requests = $db->getValue(T_WITHDRAWAL_REQUESTS, 'count(*)');

    if (!empty($requests)) {
        $error = $lang->cant_request_withdrawal;
    }

    else if ($user->balance_or < $_POST['amount']) {
        $error = $lang->please_check_details;;
    }

    else{

        if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $error = $lang->please_check_details;
        }

        else if(empty($_POST['amount']) || !is_numeric($_POST['amount'])){
            $error = $lang->please_check_details;
        }

        else if($_POST['amount'] < $pt->config->m_withdrawal){
            $error = $lang->invalid_amount_value_withdrawal . " $currency" . $pt->config->m_withdrawal;
        }
    }

    if (empty($error)) {
        $insert_data    = array(
            'user_id'   => $user_id,
            'amount'    => PT_Secure($_POST['amount']),
            'email'     => PT_Secure($_POST['email']),
            'requested' => time(),
            'currency' => $currency,
        );

        $insert  = $db->insert(T_WITHDRAWAL_REQUESTS,$insert_data);
        if (!empty($insert)) {
            $notif_data = array(
                'recipient_id' => 0,
                'type' => 'with',
                'admin' => 1,
                'time' => time()
            );
            
            pt_notify($notif_data);
            $data['status']  = 200;
            $data['message'] = $lang->withdrawal_request_sent;
        }
    }

    else{
        $data['status']  = 400;
        $data['message'] = $error;
    }
}
if ($first == 'get_more_subscribers_' && !empty($_POST['id']) && is_numeric($_POST['id'])) {
    $id = PT_Secure($_POST['id']);
    $subscribers_ = $db->rawQuery("SELECT * FROM ".T_SUBSCRIPTIONS." WHERE subscriber_id = '".$user->id."' AND id < '".$id."' ORDER BY id DESC LIMIT 6");
    $html = '';
    if (!empty($subscribers_)) {
        foreach ($subscribers_ as $key => $user_) {
            $user_subscribers_ = PT_UserData($user_->user_id);
            if (!empty($user_subscribers_)) {
                $html .= '<li data_subscriber_id="'.$user_->id.'" class="subscribers_"><a  href="'.$user_subscribers_->url.'" data-load="?link1=timeline&id='.$user_subscribers_->username.'"><img class="header-image subscribers_img_" src="'.$user_subscribers_->avatar.'" alt="'.$user_subscribers_->name.' avatar" />'.substr($user_subscribers_->name, 0,20)."..".'</a></li>';
            }
        }
    }
    $data['status'] = 200;
    $data['html'] = $html;
}

if ($first == 'two_factor' && in_array($_POST['two_factor'],array('0','1'))) {
    $user_id = $user->id;
    if (!empty($_POST['user_id']) && is_numeric($_POST['user_id']) && $_POST['user_id'] > 0) {
        if (PT_IsAdmin()) {
            $user_id = PT_Secure($_POST['user_id']);
        }
    }
    $update = $db->where('id', $user_id)->update(T_USERS, array('two_factor' => PT_Secure($_POST['two_factor'])));
    $data['status'] = 200;
    $data['message'] = $success_icon . $lang->setting_updated;
}

if ($first == 'block') {
    if (empty($_POST['user_id']) || !is_numeric($_POST['user_id']) || $_POST['user_id'] < 1 || empty(PT_UserData($_POST['user_id']))) {
        $errors[] = $error_icon . $lang->please_check_details;
    } 
    else {
        $user_id = PT_Secure($_POST['user_id']);
        $check_if_admin = $db->where('id', $user_id)->where('admin', 0,'>')->getValue(T_USERS, 'count(*)');
        if ($check_if_admin == 0) {
            $check_if_block = $db->where('user_id', $pt->user->id)->where('blocked_id', $user_id)->getValue(T_BLOCK, 'count(*)');
            if ($check_if_block > 0) {
                $db->where('user_id', $pt->user->id)->where('blocked_id', $user_id)->delete(T_BLOCK);
                $data['message'] = $lang->block;
            }
            else{
                $db->insert(T_BLOCK,array('user_id' => $pt->user->id,
                                      'blocked_id' => $user_id,
                                      'time' => time()));
                $data['message'] = $lang->unblock;
            }
            $data['status'] = 200;
        }
        else{
            $data['status'] = 400;
        }
    }
}

if ($first == 'remove_session') {
    if (!empty($_POST['id'])) {
        $id = PT_Secure($_POST['id']);
    }
    $check_session = $db->where('id', $id)->getOne(T_SESSIONS);
    if (!empty($check_session)) {
        $data['reload'] = false;
        if (($check_session->user_id == $pt->user->id) || PT_IsAdmin()) {
            if ((!empty($_SESSION['user_id']) && $_SESSION['user_id'] == $check_session->session_id) || (!empty($_COOKIE['user_id']) && $_COOKIE['user_id'] == $check_session->session_id)) {
                session_destroy();
                unset($_COOKIE['user_id']);
                setcookie('user_id', null, -1,'/');
                $_SESSION = array();
                unset($_SESSION);
                $data['reload'] = true;
            }
            $delete_session = $db->where('id', $id)->delete(T_SESSIONS);
            if ($delete_session) {
                $data['status'] = 200;
            }
        }
    }
}

if ($first == 'verify_email') {
    $data['status'] = 400;
    if (!empty($_POST['code'])) {
        $code = md5(PT_Secure($_POST['code']));
        $db->where('email_code', $code);
        $user_data = $db->getOne(T_USERS);
        if (!empty($user_data->id) && $user_data->id == $pt->user->id) {
            $update = $db->where('id', $user_data->id)->update(T_USERS, array('email' => $user_data->new_email,
                                                                              'new_email' => ''));
            $data['status'] = 200;
        }
        else{
            $data['message'] = $lang->wrong_code;
        }
    }
    else{
        $data['message'] =  $lang->please_check_details;
    }
}
if ($first == 'info') {
    $data['status'] = 400;
    if (!empty($_POST['my_information']) || !empty($_POST['videos']) || !empty($_POST['subscribe']) || !empty($_POST['posts']) || !empty($_POST['history'])) {
        $pt->user_info = new stdClass();
        if (!empty($_POST['my_information'])) {
            $pt->user_info->setting = $pt->user;
            $pt->user_info->setting->session = PT_GetUserSessions($pt->user->id);
            $pt->user_info->setting->block = GetBlockedUsers();
            $pt->user_info->setting->trans        = $db->where('user_id',$pt->user->id)->where('type','ad','!=')->orderBy('id','DESC')->get(T_VIDEOS_TRSNS);
        }
        if (!empty($_POST['videos'])) {
            $pt->user_info->videos        = $db->where('user_id',$pt->user->id)->orderBy('id','DESC')->get(T_VIDEOS);
        }
        if (!empty($_POST['subscribe'])) {
            $pt->user_info->subscribe = $db->where('subscriber_id', $pt->user->id)->where('id',$pt->blocked_array , 'NOT IN')->get(T_SUBSCRIPTIONS);
        }
        if (!empty($_POST['posts'])) {
            $pt->user_info->posts = $db->where('active', '1')->where('user_id',$pt->blocked_array , 'NOT IN')->orderBy('id', 'DESC')->get(T_POSTS);
        }
        if (!empty($_POST['history'])) {
            $blocked_videos = $db->where('user_id',$pt->blocked_array , 'IN')->get(T_VIDEOS,null,'id');
            $blocked_videos_array = array(0);
            foreach ($blocked_videos as $key => $value) {
                $blocked_videos_array[] = $value->id;
            }
            $pt->user_info->history = $db->where('user_id', $pt->user->id)->where('video_id',$blocked_videos_array , 'NOT IN')->orderby('id', 'DESC')->get(T_HISTORY);
        }
        $lang_array['copyright'] = str_replace('{{DATE}}', date('Y'), $lang_array['copyright']);
        $html = PT_LoadPage('settings/includes/user_info');
        if (!file_exists('upload/files/' . date('Y'))) {
            @mkdir('upload/files/' . date('Y'), 0777, true);
        }
        if (!file_exists('upload/files/' . date('Y') . '/' . date('m'))) {
            @mkdir('upload/files/' . date('Y') . '/' . date('m'), 0777, true);
        }

        $folder   = 'files';
        $fileType = 'file';
        $dir         = "upload/files/" . date('Y') . '/' . date('m');
        $hash    = $dir . '/' . PT_GenerateKey() . '_' . date('d') . '_' . md5(time()) . "_file.html";
        $file = fopen($hash, 'w');
        fwrite($file, $html);
        fclose($file);
        $update = $db->where('id', $pt->user->id)->update(T_USERS, array('info_file' => $hash));
        $data['status'] = 200;
        $data['message'] = $lang->file_ready;

    }
    else{
        $errors[] =  $lang->please_check_details;
    }
}

if ($first == 're_cover') {
    $data['status'] = 400;
    if ($is_owner) {
        $user_data = PT_UserData($_POST['user_id']);
        $from_top             = abs($_POST['pos']);
        $cover_image          = $user_data->ex_cover;
        $full_url_image       = $user_data->cover;
        $default_image        = explode('.', $user_data->ex_cover);
        $default_image        = $default_image[0] . '_full.' . $default_image[1];
        $get_default_image    = file_put_contents($default_image, file_get_contents($user_data->full_cover));
        $default_cover_width  = 1200;
        $default_cover_height = 200;
        require_once("assets/libs/thumbncrop.inc.php");
        $tb = new ThumbAndCrop();
        $tb->openImg($default_image);
        $newHeight = $tb->getRightHeight($default_cover_width);
        $tb->creaThumb($default_cover_width, $newHeight);
        $tb->setThumbAsOriginal();
        $tb->cropThumb($default_cover_width, 200, 0, $from_top);
        $tb->saveThumb($cover_image);
        $tb->resetOriginal();
        $tb->closeImg();
        $upload_s3        = PT_UploadToS3($cover_image);
        $data = array(
           'status' => 200,
           'url' => $full_url_image . '?timestamp=' . md5(time())
        );
        $update_data = $db->where('id', $user_data->id)->update(T_USERS, ['last_active' => time()]);
    }
}

header("Content-type: application/json");
if (isset($errors)) {
    echo json_encode(array(
        'errors' => $errors
    ));
    exit();
}
