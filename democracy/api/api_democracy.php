<?php
class api_democracy extends \SYSTEM\API\api_system {    
    public static function call_send_mail($data){
        $to = 'contact@democracy-deutschland.de';
        if(array_key_exists('files', $data)){
            $data['files'] = json_decode($data['files'],true);}
        $data['json'] = str_replace('\/', '/',json_encode($data,JSON_PRETTY_PRINT));
        switch($data['type']){
            case 'bugreport':
                \SAI\saimod_mail::contact($data['email'],$data['name'],null);
                \SAI\saimod_mail::send_mail($to, \SAI\saimod_mail::EMAIL_WEBSITE_BUGREPORT, null,true,$data);
                break;
            case 'volunteer':
                \SAI\saimod_mail::contact($data['email'],$data['name'],null);
                \SAI\saimod_mail::send_mail($to, \SAI\saimod_mail::EMAIL_WEBSITE_VOLUNTEER, null,true,$data);
                break;
            //contact
            default:
                \SAI\saimod_mail::contact($data['email'],$data['vorname'],$data['nachname']);
                \SAI\saimod_mail::send_mail($to, \SAI\saimod_mail::EMAIL_WEBSITE_CONTACT, null,true,$data);
                
        }
        return \SYSTEM\LOG\JsonResult::ok();
    }
    
    public static function call_send_subscribe($data){ 
        \SAI\saimod_mail::contact($data['email']);
        \SAI\saimod_mail::subscribe($data['email'], \SAI\saimod_mail::EMAIL_LIST_NEWSLETTER);
        \SAI\saimod_mail::send_mail($data['email'], \SAI\saimod_mail::EMAIL_NEWSLETTER_SUBSCRIBE, \SAI\saimod_mail::EMAIL_LIST_NEWSLETTER,true);
        return \SYSTEM\LOG\JsonResult::ok();
    }
    
    public static function call_beta($ios,$android,$email,$code,$newsletter){
        \LIB\lib_mail_cannon::php();
        $code_valid = self::validate_code($code);
        
        \SAI\saimod_mail::contact($email);
        \SAI\saimod_mail::subscribe($email, \SAI\saimod_mail::EMAIL_LIST_ALPHA);
        if($newsletter){
            \SAI\saimod_mail::subscribe($email, \SAI\saimod_mail::EMAIL_LIST_NEWSLETTER);
        }
        
        if($code_valid){
            $data = \SQL\BETA_EMAIL_FIND::Q1(array($email));
            if(!$data){
                \SQL\BETA_INSERT::QI(array($code,$email,$android,$ios));
            } else {
                if(!self::validate_code($data['code'])){
                    \SQL\BETA_DELETE::QI(array($email));
                    \SQL\BETA_INSERT::QI(array($code,$email,$android,$ios));
                } else {
                    throw new \SYSTEM\LOG\ERROR('This EMail has already redeemed a Code');}
            }
        } else {
            \SQL\BETA_INSERT::QI(array($code,$email,$android,$ios));
        }
        
        \SAI\saimod_mail::send_mail($email, \SAI\saimod_mail::EMAIL_ALPHA_REGISTER, \SAI\saimod_mail::EMAIL_LIST_ALPHA,true);
        //SendMail
        return \SYSTEM\LOG\JsonResult::ok();
    }
    
    public static function validate_code($code){
        return \SQL\BETA_CODE_VALIDATE::Q1(array($code))['count'] !== 0 ? true: false;
    }
    
    public static function call_upload(){
        $file_name = md5_file($_FILES['datei']['tmp_name']).'_'.basename($_FILES['datei']['name']);
        if(!\SYSTEM\FILES\files::put('upload', $file_name , $_FILES['datei']['tmp_name'])){
            throw new \SYSTEM\LOG\ERROR("Upload Problem");}
        
        return \SYSTEM\LOG\JsonResult::toString(['file_name' => $file_name]);
    }
    
    public static function call_donation_status(){
        $res = \SYSTEM\PAGE\text::tag('donation');
        $res['donation_percentage'] = round($res['donation_value']/$res['donation_value_goal']*100,0);
        $res['donation_value_goal'] = (int)$res['donation_value_goal'];
        $res['donation_paten_goal'] = (int)$res['donation_paten_goal'];
        $res['donation_paten'] = (int)$res['donation_paten'];
        $res['donation_value'] = (int)$res['donation_value'];
        
        $res['donation_data'] = [];
        $r = \SQL\DONATE_SELECT::QQ();
        while($row = $r->next()){
            $row['percentage'] = $row['type'] == default_donate::DONATION_TYPE_DATA ? round($row['value']/$row['max']*100,0) : 0;
            $row['id'] = (int)$row['id'];
            $row['order'] = (int)$row['order'];
            $row['type'] = (int)$row['type'];
            $row['value'] = (int)$row['value'];
            $row['max'] = (int)$row['max'];
            $res['donation_data'][] = $row;
        }
        
        return \SYSTEM\LOG\JsonResult::toString($res);
    }
}