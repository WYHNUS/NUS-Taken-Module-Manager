<?php

require_once 'simple_html_dom.php';


function curl_post($curl, $url, $post_fields, $add_opt=array()) {
    $agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36";
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => $agent,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_SSL_VERIFYPEER => FALSE
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

function login($ch, $matric_num, $pwd, &$cookiefile) {
    $login_url = "https://myisis.nus.edu.sg/psp/cs90prd/EMPLOYEE/HRMS/h/?tab=DEFAULT&cmd=login&languageCd=ENG";
    $login_fields = "userid=".$matric_num."&pwd=".$pwd;
    return curl_post($ch, $login_url, $login_fields, array(CURLOPT_COOKIEJAR => $cookiefile));
}

function logout($ch) {
    $logout_url = "https://myisis.nus.edu.sg/psp/cs90prd/EMPLOYEE/HRMS/?cmd=logout";
    return curl_get($ch, $logout_url, $cookiefile);
}

function parse_module($module_text) {
    $modules_taken = array();
    
    $regex_pattern = "/[A-Z]{2,3}\d{4}[A-Z]{0,1}/";
    $num_modules_taken = preg_match_all($regex_pattern, $module_text, $modules_code);
    $modules_info_array = preg_split($regex_pattern, $module_text);
    
    if ($num_modules_taken) {
        for ($i=0; $i<$num_modules_taken; $i++) {
            $module_info = explode(" ", trim($modules_info_array[$i+1]));
            $info_len = count($module_info);
            
            $module = array(
                "code" => $modules_code[0][$i],
                "name" => implode(" ", array_slice($module_info, 0, -2)),
                "MC" => $module_info[$info_len - 2],
                "grade" => $module_info[$info_len - 1]
            );
            array_push($modules_taken, $module);
        }
    }
    return $modules_taken;
}

// information to login to myisis
$matric_num = "<your matricNo without last alphabet>";
$pwd = "<password>";

// url to retrive all exam result root directory
$all_result_url = "https://myisis.nus.edu.sg/psc/cs90prd/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.N_SSR_SSENRL_GRADE.GBL?FolderPath=PORTAL_ROOT_OBJECT.CO_EMPLOYEE_SELF_SERVICE.HCCC_ENROLLMENT.N_SSR_SSENRL_GRADE_GBL";

// url to retrieve specified semester exam result
$sem_result_url = "https://myisis.nus.edu.sg/psc/cs90prd/EMPLOYEE/HRMS/c/SA_LEARNER_SERVICES.N_SSR_SSENRL_GRADE.GBL";
$sem_result_field = "ICAJAX=1&ICAction=N_DERIVED_EXAM_SSR_PB_GO&SSR_DUMMY_RECV1\$sels\$0=";

$cookiefile = "cookie";
$ch = curl_init();

$login_state = login($ch, $matric_num, $pwd, $cookiefile);

if ($login_state && !strpos(strip_tags($login_state), "Your User ID and/or Password are invalid")) {
    // crawl "View My Exam Results" page from ISIS
    $all_result_page = curl_get($ch, $all_result_url, $cookiefile);
    // parse dom tree
    $all_result_dom = str_get_html($all_result_page);
    // store information for semester
    $sem_index_array = array();
    $sem_name_array = array();
    foreach($all_result_dom->find("input") as $ele){
        // only radio input are selectable semesters
        /*
            Each tr in term table (except th) has the following structure:
                <tr>
                    <td><div><input type="radio" .../></div></td>
                    <td><div><span>*Semester Details*</span></div></td>
                    ...
                </tr>
            
        */
        if ($ele->type == "radio") {
            array_push($sem_index_array, $ele->value);
            array_push($sem_name_array, $ele->parent()->parent()->next_sibling()->children(0)->children(0)->innertext);
        }
    }
    $all_result_dom->clear();
    
    $all_modules_taken = array();
    // crawl each exam result page
    for ($i=0; $i<count($sem_index_array); $i++) {
        $sem_result_page = curl_post($ch, $sem_result_url, $sem_result_field.$sem_index_array[$i], array(CURLOPT_COOKIEFILE => $cookiefile));
        
        // logout then login again to avoid cached content 
        // (there must exist a better way to do this...)
        logout($ch);
        login($ch, $matric_num, $pwd, $cookiefile);
        curl_get($ch, $all_result_url, $cookiefile);

        // use text processing to extract information
        $raw_text = strip_tags($sem_result_page);
        // replace multiple spaces with one
        $trimed_text = preg_replace('!\s+!', ' ', $raw_text);
        $start_str = "Official Grades Module Description MCs/Units Grade";
        $end_str = "Term Statistics";
        $module_text_start_index = strpos($trimed_text, $start_str) + strlen($start_str);
        $module_text_length = strpos($trimed_text, $end_str) - $module_text_start_index;
        $module_text = substr($trimed_text, $module_text_start_index, $module_text_length);

        // process $module_text further based on information given in nus official website: http://www.nus.edu.sg/registrar/edu/modular.html
        $modules_taken = parse_module($module_text);
        array_push($all_modules_taken, array("semester" => $sem_name_array[$i],
                                             "module_info" => $modules_taken));
    }
    print_r($all_modules_taken);
    
    $module_grad_dict = array(
        "A+" => 5.0, "A" => 5.0, "A-" => 4.5,
        "B+" => 4.0, "B" => 3.5, "B-" => 3.0,
        "C+" => 2.5, "C" => 2.0, 
        "D+" => 1.5, "D" => 1.0, "F" => 0,
        "CS" => "NA", "CU" => "NA",
        "EXE" => "NA", "IC" => "NA", "IP" => "NA",
        "S" => "NA", "U" => "NA", "W" => "NA"
    );
    /*
        Completed Satisfactorily/Completed Unsatisfactorily (CS/CU)
        Exempted (EXE) 
        Incomplete (IC)
        In Progress (IP) 
        Satisfactory/Unsatisfactory (S/U)
        Withdrawn (W)
        
        CAP = sum ((module grade points) * 
                   (the number of MCs for the corresponding module))
              / (total number of MCs)
    */
} else {
    echo("failed to login");
}
curl_close($ch);

?>