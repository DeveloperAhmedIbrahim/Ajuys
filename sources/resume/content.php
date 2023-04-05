<?php
use Dompdf\Dompdf;

if (IS_LOGGED == false) {
    header("Location: " . PT_Link('login'));
    exit();
}

$user_id = $user->id;
$pt->is_admin = PT_IsAdmin();
$pt->is_settings_admin = false;

$pt->settings = PT_UserData($user_id);
$pt->setting_page = 'information';
$pages_array = array(

);

if ($pt->settings->id == $user->id) {
    $pages_array = array(
        'information',
        'experience',
        'education',
        'skills',
        'preview',
        'download',
    );
}
$pt->page_url_ = $pt->config->site_url . '/resume';

if (!empty($_GET['page'])) {
    if (in_array($_GET['page'], $pages_array)) {
        $pt->setting_page = $_GET['page'];
        $pt->page_url_ = $pt->config->site_url . '/resume/' . $pt->setting_page;
    }
}

$pt->resume = $db->where('user_id', $user_id)->getOne(T_RESUME);

$educations = "";
if ($pt->setting_page == 'education') {
    $user_educations = $db->where('user_id', $pt->user->id)->where('type', 'resume')->get(T_EDUCATION);

    foreach ($user_educations as $education) {
        $education->start_date = date_format(date_create($education->start_date), 'Y-m-d');
        $education->end_date = date_format(date_create($education->end_date), 'Y-m-d');

        $educations .= PT_LoadPage("settings/includes/education-list", array(
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

$experinces = "";

if ($pt->setting_page == 'experience') {

    $user_experince = $db->where('user_id', $pt->user->id)->where('type', 'resume')->get(T_EXPERINCE);
    foreach ($user_experince as $experince) {
        $experince->start_date = date_format(date_create($experince->start_date), 'Y-m-d');
        $experince->end_date = date_format(date_create($experince->end_date), 'Y-m-d');

        $experinces .= PT_LoadPage("settings/includes/experience-list", array(
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
    $user_skills = $db->where('user_id', $pt->user->id)->where('type', 'resume')->get(T_SKILLS);
    foreach ($user_skills as $skill) {
        $skills .= PT_LoadPage("settings/includes/skills-list", array(
            'NAME' => $skill->name,
            'ID' => $skill->id,
            "USER_ID" => $pt->user->id,
        ));
    }
}

if ($pt->setting_page == 'preview') {
    $pt->experince = $db->where('user_id', $pt->user->id)->where('type', 'resume')->get(T_EXPERINCE);
    $pt->educations = $db->where('user_id', $pt->user->id)->where('type', 'resume')->get(T_EDUCATION);
    $pt->skills = $db->where('user_id', $pt->user->id)->where('type', 'resume')->get(T_SKILLS);

}

if ($pt->setting_page == 'download') {

    $pt->experince = $db->where('user_id', $pt->user->id)->where('type', 'resume')->get(T_EXPERINCE);
    $pt->educations = $db->where('user_id', $pt->user->id)->where('type', 'resume')->get(T_EDUCATION);
    $pt->skills = $db->where('user_id', $pt->user->id)->where('type', 'resume')->get(T_SKILLS);

    $html = '<table style="font-family:Lato, sans-serif !important; border-collapse: separate;
    border-spacing: 10px 5px;width:100% !important">';
    $html .= '<tr style="text-transform: uppercase;
    font-weight: 700;
    font-size: 40px;">';

    $html .= '<td colspan="4">';
    $html .= $pt->resume->first_name . $pt->resume->last_name;
    $html .= '<td>';

    $html .= '</tr>';


    $html .= '<tbody>';
    $html .= '<tr style="border-top: 1em solid transparent; height:30px"> <td style="    color: #999; font-weight: 300; width:20px">Email:</td>';
    $html .= ' <td style="50px !important">'.$pt->resume->email.'</td>';
    $html .= ' <td style="    color: #999;font-weight: 300; width:20px">Phone:</td>';
    $html .= ' <td style="50px">'.$pt->resume->phone.'</td>';
    $html .= ' </tr> <br><tr><td colspan="4"> <span style="    font-weight: bold; display: inline-block; margin-right: 10px; text-decoration: underline;">';
    $html .=    strtoupper($pt->resume->profession);
    $html .= ' </span><br>';
    $html .=    $pt->resume->description;
    $html .= ' </td> </tr>';
   
    $html .=   '<br>';
    $html .= '<tr>  <td colspan="4" style="letter-spacing: 2px;  color: #54AFE4;  font-weight: bold; margin-bottom: 10px;  text-transform: uppercase;"> Experience  </td></tr>';
    foreach($pt->experince as $item){
        $html .= ' <br><tr> ';
        $html.='<td colspan="2"><div style="font-weight:bold">';
        $html .= strtoupper( $item->company_name);
        $html .= '</div><div>';
        $html .= $item->location;
        $html .= '</div> <div>';
        $start_date = date_format ( date_create($item->start_date) , 'M Y');
        $end_date = $item->currently_working_here ? "Present" :  date_format ( date_create($item->end_date) , 'M Y');

        $html .= $start_date .' - '.$end_date;
        $html .= '</div></td>';

        $html.='<td colspan="2"><div style="font-weight:bold">';
        $html .= strtoupper($item->title) ;
        $html .= '</div><div>';
        $html .= $item->employment_type;
        $html .= '</div></td>';
        $html .= '</tr>';
    }

    $html .=   '<br>';
    $html .= '<tr>  <td colspan="4" style="letter-spacing: 2px;  color: #54AFE4;  font-weight: bold; margin-bottom: 10px;  text-transform: uppercase;"> EDUCATION  </td></tr>';
    foreach($pt->educations as $item){
        $html .= ' <br><tr> ';
        $html.='<td colspan="2"><div style="font-weight:bold">';
        $html .= strtoupper( $item->institude);
        $html .= '</div><div>';
        $html .= $item->field_of_study;
        $html .= '</div> <div>';
        $start_date = date_format ( date_create($item->start_date) , 'M Y');
        $end_date =   date_format ( date_create($item->end_date) , 'M Y');

        $html .= $start_date .' - '.$end_date;
        $html .= '</div></td>';

        $html.='<td colspan="2"><div style="font-weight:bold">';
        $html .= strtoupper($item->degree) ;
        $html .= '</div><div>';
        $html .= $item->grade;
        $html .= '</div></td>';
        $html .= '</tr>';
    }

    $html .=   '<br>';
    $html .= '<tr>  <td colspan="4" style="letter-spacing: 2px;  color: #54AFE4;  font-weight: bold; margin-bottom: 10px;  text-transform: uppercase;"> SKILLS  </td></tr>';
    foreach($pt->skills as $item){
        $html .= ' <br><tr> ';
        $html.='<td colspan="2"><div style="font-weight:bold">';
        $html .= strtoupper( $item->name);
       
        $html .= '</div></td>';

        
        $html .= '</tr>';
    }

    $html .= '</tbody>';

    $html .= '</table>';

   

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream();
}


$pt->page = 'resume';
$pt->title = $lang->settings . ' | ' . $pt->config->title;
$pt->description = $pt->config->description;
$pt->keyword = $pt->config->keyword;
$pt->content = PT_LoadPage("resume/content", array(
    'SETTINGSPAGE' => PT_LoadPage("resume/$pt->setting_page", array(
        "Education_List" => $educations,
        "Experince_List" => $experinces,
        'SKILLS_LIST' => $skills,
    )),
));
