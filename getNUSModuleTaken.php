<?php

require_once 'simple_html_dom.php';

function curl_post($curl, $url, $post_fields, $add_opt=array()) {
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36",
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_SSL_VERIFYPEER => false
    );
    curl_setopt_array($curl, $options+$add_opt);
    return curl_exec ($curl);
}

function curl_get($curl, $url, $cookiefile) {
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEFILE => $cookiefile
    );
    curl_setopt_array($curl, $options);
    return curl_exec ($curl);
}

// information to login to myisis
$matricNo = "<your matricNo without last alphabet>";
$pwd = "<password>";
$login_url = "https://myisis.nus.edu.sg/psp/cs90prd/EMPLOYEE/HRMS/h/?tab=DEFAULT&cmd=login&languageCd=ENG";
$login_fields = "userid=".$matricNo."&pwd=".$pwd;

// url to retrive all exam result root directory
$all_result_url = "https://myisis.nus.edu.sg/psc/cs90prd/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.N_SSR_SSENRL_GRADE.GBL?FolderPath=PORTAL_ROOT_OBJECT.CO_EMPLOYEE_SELF_SERVICE.HCCC_ENROLLMENT.N_SSR_SSENRL_GRADE_GBL";

// url to retrieve specified semester exam result
$sem_result_url = "https://myisis.nus.edu.sg/psc/cs90prd/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.N_SSR_SSENRL_GRADE.GBL";
$sem_result_field = "ICAJAX=1&ICNAVTYPEDROPDOWN=0&ICType=Panel&ICElementNum=0&ICStateNum=13&ICAction=N_DERIVED_EXAM_SSR_PB_GO&ICXPos=0&ICYPos=39&ResponsetoDiffFrame=-1&TargetFrameName=None&FacetPath=None&ICFocus=&ICSaveWarningFilter=0&ICChanged=-1&ICResubmit=0&ICSID=obV2B6B3wRXLZhJ3GP%2B4KPtK1NxsAdKQzosY8l%2B8e3g%3D&ICActionPrompt=false&ICFind=&ICAddCount=&ICAPPCLSDATA=&SSR_DUMMY_RECV1\$sels\$0=";

$cookiefile = "cookie";
$ch = curl_init();
// login
$login_state = curl_post($ch, $login_url, $login_fields, array(CURLOPT_COOKIEJAR => $cookiefile));
if ($login_state) {
    // crawl "View My Exam Results" page from ISIS
    $all_result_page = curl_get($ch, $all_result_url, $cookiefile);
    // parse dom tree
    $all_result_dom = str_get_html($all_result_page);
    // number of semesters
    $num_sem = 0;
    foreach($all_result_dom->find('input') as $ele){
        if ($ele->type == "radio") {
            error_log($ele->value.'<br>');
            $num_sem += 1;
        }
    }

    // crawl each page
    // testing with page with index 1 (not working currently => require to login again even though cookie is enabled => require further investigation)
    $sem_result_page = curl_post($ch, $sem_result_url, $sem_result_field."1", array(CURLOPT_COOKIEFILE => $cookiefile));
    $sem_result_dom = str_get_html($sem_result_page);
    echo($sem_result_dom);

} else {
    echo("failed to login");
}
curl_close($ch);

?>