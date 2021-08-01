<?php namespace TimeTracking;
    require_api( 'csv_api.php' );
    
    $t_headers = array( 'user', 'project', 'issue', 'expenditure date', 'hour','bug note', 'description' );
    $t_time_filter_from =  !empty($_GET["time_filter_from"]) ? htmlspecialchars($_GET["time_filter_from"]) : null;
    $t_time_filter_to = !empty($_GET["time_filter_to"]) ? htmlspecialchars($_GET["time_filter_to"]) : null;
    $t_time_filter_user_id = !empty($_GET["time_filter_user_id"]) ? htmlspecialchars($_GET["time_filter_user_id"]) : null;
    $t_time_filter_category = !empty($_GET["time_filter_category"]) ? htmlspecialchars($_GET["time_filter_category"]) : null;

    $t_query = new \DbQuery();
    $t_sql = 'SELECT username, mantis_project_table.id as project, bug_id as issue, time_exp_date as exp_date, time_count, bugnote_id, info'
    . ' FROM mantis_plugin_timetracking_data_table as timetracking'
    . ' join mantis_bug_table' 
    . ' on mantis_bug_table.id = timetracking.bug_id'
    . ' join mantis_project_table '
    . ' on mantis_project_table.id = mantis_bug_table.project_id'
    . ' join mantis_user_table '
    . ' on timetracking.user_id = mantis_user_table.id';

    $t_where= array();
    
    if(!empty($t_time_filter_from)){
        $t_where[] = 'timetracking.time_exp_date >=' . $t_query->param( (int)$t_time_filter_from);
    }
    if(!empty($t_time_filter_to)){
        $t_where[] = 'timetracking.time_exp_date <=' . $t_query->param( (int)$t_time_filter_to);
    }
    if(!empty($t_time_filter_user_id)){
        $t_where[] = 'timetracking.user_id = ' . $t_query->param( (int)$t_time_filter_user_id);
    }
    if(!empty($t_time_filter_category)){
        $t_where[] = 'timetracking.category = ' . $t_query->param( (int)$t_time_filter_category);
    }
    if(helper_get_current_project() > 0){
        $t_where[] = 'mantis_project_table.id = ' . $t_query->param( (int)helper_get_current_project());
    }
    if(!empty($t_where)){
        $t_sql = $t_sql . ' WHERE ' . implode($t_where, ' AND ');
    }
    
    $t_query->sql( $t_sql );
    $t_result = $t_query->execute();
    
    ob_end_clean();
    $fp = fopen('php://output','w');

    fputcsv($fp, $t_headers);
    
    $t_result_array = array();
    while( $t_row = db_fetch_array( $t_result ) ) {
        $t_result_array[] = $t_row;
    }
    foreach( $t_result_array as $t_row ){
        $data_row = array();
        foreach( $t_row as $t_key => $t_value ) {
            if( 'time_count' == $t_key ) {
                array_push($data_row, seconds_to_hours( $t_value ));
                //array_push($data_row, seconds_to_hms( $t_value ));                
            } else {
                array_push($data_row, Report::format_value( $t_key, $t_value , true)); 
            }
        }
        fputcsv($fp, $data_row);
    }
    

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . csv_get_default_filename());

    exit();

    
?>