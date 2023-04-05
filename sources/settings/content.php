<?php
if (IS_LOGGED == false) {
    header("Location: " . PT_Link('login'));
    exit();
}
$user_id               = $user->id;
$pt->is_admin          = PT_IsAdmin();
$pt->is_settings_admin = false;

if (isset($_GET['user']) && !empty($_GET['user']) && ($pt->is_admin === true)) {
    if (empty($db->where('username', PT_Secure($_GET['user']))->getValue(T_USERS, 'count(*)'))) {
        header("Location: " . PT_Link(''));
        exit();
    }
    $user_id               = $db->where('username', PT_Secure($_GET['user']))->getValue(T_USERS, 'id');
    $pt->is_settings_admin = true;
}

$pt->settings     = PT_UserData($user_id);
$pt->setting_page = 'general';
$pages_array      = array(
    'general',
    'profile',
    'password',
    'privacy',
    'change',
    'social',
    'avatar',
    'email',
    'delete',
    'monetization',
    'withdrawals',
    'verification',
    'balance',
    'two_factor',
    'blocked_users',
    'manage_sessions',
    'points',
    'my_info',
    'invitation_links',
    'education',
);

if ($pt->settings->id == $user->id) {
    $pages_array = array(
        'general',
        'profile',
        'password',
        'privacy',
        'change',
        'social',
        'avatar',
        'email',
        'delete',
        'monetization',
        'withdrawals',
        'verification',
        'balance',
        'two_factor',
        'blocked_users',
        'manage_sessions',
        'points',
        'my_info',
        'invitation_links',
        'education',
        'experience',
        'skills',
        'company',
        'job'
    );
}
$pt->page_url_ = $pt->config->site_url.'/settings';
if (!empty($_GET['page']) && $_GET['page'] == 'two_factor' && $pt->config->two_factor_setting != 'on') {
    header("Location: " . PT_Link(''));
    exit();
}
if (!empty($_GET['page'])) {
    if (in_array($_GET['page'], $pages_array)) {
        if ($_GET['page'] != 'balance') {
            $pt->setting_page = $_GET['page'];
            $pt->page_url_ = $pt->config->site_url.'/settings/'.$pt->setting_page;
        }
        else{
            if (($pt->config->usr_v_mon == 'off' && $pt->config->sell_videos_system == 'off')) {
                $pt->setting_page = 'general';
                $pt->page_url_ = $pt->config->site_url.'/settings/'.$pt->setting_page;
            }
            else{
                $pt->setting_page = $_GET['page'];
                $pt->page_url_ = $pt->config->site_url.'/settings/'.$pt->setting_page;
            }
        }
    }
}

$pt->user_setting = '';
if (!empty($_GET['user'])) {
    $pt->user_setting = 'user=' . $_GET['user'] . '&';
    $pt->page_url_ = $pt->config->site_url.'/settings/'.$pt->setting_page.'/'.$_GET['user'];
}
$countries = '';
foreach ($countries_name as $key => $value) {
    $selected = ($key == $pt->settings->country_id) ? 'selected' : '';
    $countries .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
}


$experince_options = '';
$education_options = '';

// Get user custom Fields
if ($pt->setting_page == 'general') {
    $db->where('placement','general');
} 
else if ($pt->setting_page == 'profile') {
    $experinces  = $db->where('user_id',$pt->user->id)->get(T_EXPERINCE);  
    foreach ($experinces as $experince) {
        $selected = "";
        if($experince->id == $pt->user->experience_id){
            $selected = "selected";
        }
        $experince_options .="<option value='".$experince->id."' ".$selected.">". $experince->title ."</option>";
    }

    $educations  = $db->where('user_id',$pt->user->id)->get(T_EDUCATION);  
    
    foreach ($educations as $education) {
        $selected = "";
        if($education->id == $pt->user->education_id){
            $selected = "selected";
        }

        $education_options .="<option value='".$education->id."' ".$selected.">". $education->institude ."</option>";
    }

    $db->where('placement',array('profile','social'),'IN');
}

$pt->profile_fields = null;
$pt->profile_fields = $db->where('active','1')->get(T_FIELDS);
$pt->user->fields   = $db->where('user_id',$user_id)->getOne(T_USR_PROF_FIELDS);
$pt->user->fields   = (is_object($pt->user->fields)) ? get_object_vars($pt->user->fields) : array();
$custom_fields      = "";


foreach ($pt->profile_fields as $field_data) {
    $field_data->fid  = 'fid_' . $field_data->id;
    $field_data->name = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($pt) {
        return (isset($pt->lang->$m[1])) ? $pt->lang->$m[1] : '';
    }, $field_data->name);

    $field_data->description = preg_replace_callback("/{{LANG (.*?)}}/", function($m) use ($pt) {
        return (isset($pt->lang->$m[1])) ? $pt->lang->$m[1] : '';
    }, $field_data->description);

    if ($field_data->type == 'select') {
        $fid       = '';
        $pt->field = $field_data;
        if (!empty($pt->user->fields[$field_data->fid])) {
            $fid   = $pt->user->fields[$field_data->fid];
        } 

        $pt->fid   = $fid;
        $custom_fields .= PT_LoadPage('settings/custom-options',array(
            "FID"  => $fid,
            "NAME" => $field_data->name,
            "DESC" => $field_data->description,
        ));
    }

    else if ($field_data->type == 'textbox' || $field_data->type == 'textarea') {
        $fid       = '';
        $pt->field = $field_data;
        if (!empty($pt->user->fields[$field_data->fid])) {
            $fid   = $pt->user->fields[$field_data->fid];
        } 

        $pt->fid   = $fid;
        $custom_fields .= PT_LoadPage('settings/custom-inputs',array(
            "ID"   =>  $field_data->id,
            "FID"  => $fid,
            "NAME" => $field_data->name,
            "DESC" => $field_data->description,
        ));
    }


}

$withdrawal_history = "";
if ($pt->setting_page == 'withdrawals') {
    $user_withdrawals  = $db->where('user_id',$pt->user->id)->get(T_WITHDRAWAL_REQUESTS);  
    foreach ($user_withdrawals as $withdrawal) {
        $pt->withdrawal_stat = $withdrawal->status;
        $withdrawal_history .= PT_LoadPage("settings/includes/withdrawals-list",array(
            'W_ID' => $withdrawal->id,
            'W_REQUESTED' => date('Y-F-d',$withdrawal->requested),
            'W_AMOUNT' => number_format($withdrawal->amount, 2),
            'W_CURRENCY' => $withdrawal->currency,
        ));
    }
}
$educations ="";
if ($pt->setting_page == 'education') {
    $user_educations  = $db->where('user_id',$pt->user->id)->where('type','profile')->get(T_EDUCATION);  
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
            "USER_ID" => $pt->user->id,
            "Education" => json_encode($education),
        ));
    }
}

$experinces ="";

if ($pt->setting_page == 'experience') {
   
    $user_experince  = $db->where('user_id',$pt->user->id)->where('type','profile')->get(T_EXPERINCE);  
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
}
$skills = "";
if ($pt->setting_page == 'skills') {
    $user_skills = $db->where('user_id', $pt->user->id)->where('type','profile')->get(T_SKILLS);
    foreach ($user_skills as $skill) {
        $skills .= PT_LoadPage("settings/includes/skills-list", array(
            'NAME' => $skill->name,
            'ID' => $skill->id,
            "USER_ID" => $pt->user->id,
        ));
    }
}

$blocked_users = "";
if ($pt->setting_page == 'blocked_users') {
    $b_users = GetBlockedUsers();
    foreach ($b_users as $user) {
        $blocked_users .= PT_LoadPage("settings/includes/blocked_users_list",array(
            'USER_DATA'    => $user,
            'BLOCK_BUTTON'  => PT_GetBlockButton($user->id,'false')
        ));
    }
    if (empty($blocked_users)) {
        $blocked_users = '<p class="empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>'.$lang->no_users_found.'</p>';
    }
}

$sessions = "";
if ($pt->setting_page == 'manage_sessions') {
    $user_sessions = PT_GetUserSessions($pt->settings->id);
    //$user_sessions = $db->where('user_id',$pt->settings->id)->get(T_SESSIONS);
    if (!empty($user_sessions)) {
        foreach ($user_sessions as $key => $pt->session) {
            $sessions .= PT_LoadPage("settings/includes/sessions");
        }
    }
    // $b_users = GetBlockedUsers();
    // foreach ($b_users as $user) {
    //     $blocked_users .= PT_LoadPage("settings/includes/blocked_users_list",array(
    //         'USER_DATA'    => $user,
    //         'BLOCK_BUTTON'  => PT_GetBlockButton($user->id,'false')
    //     ));
    // }
    // if (empty($blocked_users)) {
    //     $blocked_users = '<p class="empty_state"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>'.$lang->no_users_found.'</p>';
    // }
}
if($pt->setting_page == 'company'){
$pt->company = $db->where('user_id', $pt->user->id)->getOne(T_COMPANY);
}
$jobs_list = '';
if($pt->setting_page == 'job'){
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
}
$pt->is_mon = 0;
if ($pt->setting_page == 'monetization') {
    $pt->is_mon = ($db->where('user_id',$pt->settings->id)->getValue(T_MON_REQUESTS,'count(*)'));
}
$pt->available_links = 0;
$pt->generated_links = 0;
$pt->used_links = 0;
$trans_html = '';
$pt->show_trans = false;
if ($pt->setting_page == 'invitation_links') {
    $pt->available_links = GetAvailableLinks($pt->settings->id);
    if ($pt->config->user_links_limit > 0) {
        $pt->generated_links = $pt->config->user_links_limit - $pt->available_links;
    }
    else{
        $pt->generated_links = GetGeneratedLinks($pt->settings->id);
    }
    $pt->used_links = GetUsedLinks($pt->settings->id);

    $trans = GetMyInvitaionCodes($pt->settings->id);
    if (count($trans) > 0){
        $pt->show_trans = true;
        foreach ($trans as $key => $transaction){
            $link = '';
            if (!empty($transaction['user_name'])) {
                $link = '<a href="'.$transaction['user_url'].'">'.$transaction['user_name'].'</a>';
            }
            $trans_html .= '<tr data-ad-id="'.$transaction['id'].'"><td><button type="button" class="btn btn-sm btn-default copy-invitation-url" data-link="'.$pt->config->site_url.'/register?invite='.$transaction['code'].'">'.$lang->copy.'</button></td><td>'.$link.'</td><td>'.TranslateDate(date($pt->config->date_style, $transaction['time'])).'</td></tr>';
        }
    }
}



$pt->page        = 'settings';
$pt->title       = $lang->settings . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword     = $pt->config->keyword;
$pt->content     = PT_LoadPage("settings/content", array(
    'SETTINGSPAGE' => PT_LoadPage("settings/$pt->setting_page", array(
        'USER_DATA' => $pt->settings,
        'COUNTRIES_LAYOUT' => $countries,
        'CUSTOM_FIELDS' => $custom_fields,
        'WITHDRAWAL_HISTORY_LIST' => $withdrawal_history,
        'CUSTOM_DATA' => ((!empty($custom_fields)) ? "1" : "0"),
        'BLOCKED_USERS' => $blocked_users,
        'available_links' => $pt->available_links,
        'generated_links' => $pt->generated_links,
        'used_links' => $pt->used_links,
        'trans_html' => $trans_html,
        'SESSIONS' => $sessions,
        "Education_List" => $educations,
        "Experince_List" => $experinces,
        'SKILLS_LIST' => $skills,
        'JOB_LIST' => $jobs_list,
        "EXPERINCE_OPTIONS" => $experince_options,
        "EDUCATION_OPTIONS" => $education_options,
        'ADMIN_LAYOUT' => PT_LoadPage('settings/admin', array(
        'USER_DATA' => $pt->settings,
        
        ))
    ))
));
